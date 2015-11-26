<?php

namespace App\Http\Controllers\Home;

use App\DebugDumper;
use App\Family;
use App\Http\Controllers\Controller;
use App\ThrottledSurveyMonkey;
use App\TimeLog;
use App\YRCSGuardians;
use App\YRCSStudents;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use ref;

/**
 * Class SyncController
 * @package App\Http\Controllers\Home
 */
class SyncController extends Controller
{
    use DebugDumper;
    /**
     * @var string First survey to process
     */
    private $volunteerHourSurveyId = '';
    /**
     * @var string Second survey to process
     */
    private $volunteerHourSurveyId2 = '';
    /**
     * @var array Container to hold all survey IDs
     */
    private $allSurveyIDs = [];
    /**
     * @var string The currently-processed survey ID
     */
    private $currentSurveyId = '';
    /**
     * @var array Lookup tables
     */
    private $qLookup, $aLookup, $rLookup = [];
    /**
     * @var ThrottledSurveyMonkey
     */
    private $SM;
    /**
     * @var array Storage for sync statistics
     */
    private $stats;
    /**
     * @var int Total hours seen
     */
    private $totalHours = 0;
    /**
     * @var int Total hours logged
     */
    private $totalLogsDone = 0;
    /**
     * @var int Total hours not logged
     */
    private $totalLogsMissed = 0;
    /**
     * @var int Total count of respondents
     */
    private $totalRespondents = 0;

    /**
     * SyncController constructor.
     */
    public function __construct()
    {
        $this->initConfig();
        $this->initErrorHandler();
        $this->configSurveyIDs();
        $this->SM = new ThrottledSurveyMonkey();
    }

    /**
     * Sets up some output configs
     * @throws \Exception
     */
    private function initConfig()
    {
        try {
            ref::config('expLvl', -1);
            ref::config('maxDepth', 0);
            if (!ini_get('auto_detect_line_endings')) {
                ini_set('auto_detect_line_endings', '1');
            }
        } catch (\Exception $e) {

        }
    }

    /**
     * Sets up error handler
     */
    private function initErrorHandler()
    {
        $logger = new Logger('log');
        $logger->pushHandler((new ErrorLogHandler())->setFormatter(new LineFormatter()));
        ErrorHandler::register($logger);
    }

    /**
     * Sets up the survey IDs
     */
    private function configSurveyIDs()
    {
        $this->volunteerHourSurveyId = env('SURVEY_MONKEY_SURVEY_ID_1');
        $this->volunteerHourSurveyId2 = env('SURVEY_MONKEY_SURVEY_ID_2');

        $this->allSurveyIDs = [
            $this->volunteerHourSurveyId,
            $this->volunteerHourSurveyId2
        ];
    }

    /**
     * Loop through each Survey ID,
     * generating questions, answers, and respondents lookup tables,
     * match that to existing families, and log matches to database.
     */
    public function syncSurveyMonkey()
    {
        foreach ($this->allSurveyIDs as $surveyID) {
            $this->currentSurveyId = $surveyID;
            $this->generateQuestionsLookupTable($surveyID);
            $responses = $this->generateResponsesLookupTable($surveyID);
            $log = $this->parseHoursToFamilyId($responses);
            $this->saveHoursToFamilyId($log);
        }
        dd($this->totalHours, $this->totalLogsDone, $this->totalLogsMissed, $this->totalRespondents, $this->stats);
    }

    /**
     * @param $surveyID
     * @return array
     */
    private function generateQuestionsLookupTable($surveyID)
    {
        $surveyDetails = $this->SM->surveyMonkeyGetSurveyDetails($surveyID);
        $questions = $this->extractQuestions($surveyDetails);
        $questions = $this->processQuestions($questions);
        return $questions;
    }

    /**
     * @param $surveyDetails
     * @return array
     */
    private function extractQuestions($surveyDetails)
    {
        $questions = [];
        foreach ($surveyDetails['data']['pages'][0]['questions'] as $question) {
            $questions[] = $question;
        }
        return $questions;
    }

    /**
     * @param $v
     * @return array
     */
    private function processQuestions($v)
    {
        $questions = [];
        foreach ($v as $key => $val) {
            $this->qLookup[$val['question_id']] = $val['heading'];
            $questions[$val['heading']] = [
//            $questions[$val['question_id']] = [
                'question' => $val['heading'],
                'answers' => $this->extractAnswers($val['answers'])
            ];
        }
        return $questions;
    }

    /**
     * @param $answers
     * @return array
     */
    private function extractAnswers($answers)
    {
        $r = [];
        foreach ($answers as $a) {
            if (
                isset($a['visible'], $a['text'], $a['answer_id'])
                && $a['visible'] === true
            ) {
                $r[$a['answer_id']] = $a['text'];
                $this->aLookup[$a['answer_id']] = $a['text'];
//                $r[$this->t($a['answer_id'])] = $a['text'];
            }
        }
        return $r;
    }

    /**
     * @param $surveyID
     * @return array
     */
    private function generateResponsesLookupTable($surveyID)
    {
        $respondentList = $this->SM->surveyMonkeyGetRespondentList($surveyID, ['fields' => ['date_modified']]);

        $this->totalRespondents += count($respondentList['data']['respondents']);

        $respondentsToRequest = [];
        foreach ($respondentList['data']['respondents'] as $r) {
            $this->rLookup[$r['respondent_id']] = $r['date_modified'];
            $respondentsToRequest[] = $r['respondent_id'];
        }
        $responses = $this->SM->surveyMonkeyGetResponses($surveyID, $respondentsToRequest);
//        $responses = $this->getFakeResponses();
        $responses = $this->processResponses($responses['data']);
        return $responses;
    }

    /**
     * @param $responses
     * @return array
     */
    private function processResponses($responses)
    {
        $resp = [];
        foreach ($responses as $r) {
            $rid = $r['respondent_id'];
            $q = $r['questions'];
            foreach ($q as $question) {
                $qid = $question['question_id'];
                foreach ($question['answers'] as $ans) {
                    if (isset($ans['text'])) {
                        $respondentID = $this->t($rid, 'r');
                        $questionID = $this->t($qid, 'q');
                        $answerID = $this->t($ans['row'], 'a');
                        $resp[$respondentID][$questionID][$answerID] = $ans['text'];
                    } elseif (isset($ans['row'])) {
                        $respondentID = $this->t($rid, 'r');
                        $questionID = $this->t($qid, 'q');
                        $answerID = $this->t($ans['row'], 'a');
                        $resp[$respondentID][$questionID][] = $answerID;
                    }
                }
            }
        }
        return $resp;
    }

    /**
     * @param $id
     * @param $type
     * @return mixed|string
     */
    private function t($id, $type)
    {
        return $this->translate($id, $type);
    }

    /**
     * @param $id
     * @param $type
     * @return mixed|string
     */
    private function translate($id, $type)
    {
        if ($type === 'a') {
            if (isset($this->aLookup[$id])) {
                $return = $this->aLookup[$id];
                $return = preg_replace('/[()]/', '', $return);
                return $return;
            }
        }
        if ($type === 'q') {
            return isset($this->qLookup[$id]) ? $this->qLookup[$id] : '';
        }
        if ($type === 'r') {
            return isset($this->rLookup[$id]) ? $this->rLookup[$id] : '';
        }
        return '';
    }

    /**
     * @param $responses
     * @return array
     */
    private function parseHoursToFamilyId($responses)
    {
        $log = [];
        $this->dl('Found ' . count($responses) . ' raw responses');
        foreach ($responses as $date => $r) {

            $childFirst = $childLast = '';
            $childNameField = 'Child\'s First and Last Name (this is how we identify your family, only one child\'s name is needed)';
            $childFirstNameField = 'Child\'s First Name';
            $childLastNameField = 'Child\'s Last Name';
            if (isset($r[$childNameField], $r[$childNameField][$childFirstNameField], $r[$childNameField][$childLastNameField])) {
                $childFirst = $r[$childNameField][$childFirstNameField];
                $childLast = $r[$childNameField][$childLastNameField];

                if ($childFirst === 'ChildFirstName') {
                    continue;
                }
            }

            $howManyHoursField = $this->getHowManyHoursField();
//            r($howManyHoursField, $r[$howManyHoursField]);

            if (isset($r[$howManyHoursField][0])) {
                $hours = $r[$howManyHoursField][0];
            } elseif (isset($r[$howManyHoursField][""])) {
                $hours = $r[$howManyHoursField][""];
            } elseif (isset($r[$howManyHoursField]['Other please specify'])) {
                $hours = $r[$howManyHoursField]['Other please specify'];
            } else {
                $this->dl('No hours field found !!!!!!!!!!!!!!!!!!!!');
                $this->dl([$r[$howManyHoursField]]);
                if (isset($r[$howManyHoursField][0])) {
                    $this->dl('Dumping blah blah');
                    $this->dl([$r[$howManyHoursField][0]]);
                }
                $this->totalLogsMissed += 1;
                continue;
            }

            $this->totalHours += $hours;
            $this->totalLogsDone += 1;


            $familyLastName = '';
            if (!empty($this->getFamilyLastNameField($r))) {
                $familyLastName = $this->getFamilyLastNameField($r);
            } elseif (!empty($this->getYourLastNameField($r))) {
                $familyLastName = $this->getYourLastNameField($r);
            }

            $firstName = false;
            if (!empty($this->getYourFirstNameField($r))) {
                $firstName = $this->getYourFirstNameField($r);
            }
            $lastName = false;
            if (!empty($this->getYourLastNameField($r))) {
                $lastName = $this->getYourLastNameField($r);
            }
            $f = $this->detectFamily($familyLastName, $firstName, $lastName, $childFirst, $childLast);
            if ($f) {
                $this->dl("<== Found ID $f");
            } else {
                $this->dl("Family ID not found");
            }
            $classGradeField = $this->getClassGradeField();
            $class = $r[$classGradeField]['0'];
            $log[] = [
                'date' => $date,
                'family' => $familyLastName,
                'class' => $class,
                'hours' => $hours,
                'family_id' => $f
            ];
        }
        return $log;
    }

    /**
     * @return string
     */
    private function getHowManyHoursField()
    {
        switch ($this->currentSurveyId) {
            case $this->volunteerHourSurveyId:
                return 'How many hours did you volunteer?';
            case $this->volunteerHourSurveyId2:
                return 'How many hours did you volunteer? Please only use numerical values and round your total up or down to enter a whole number.';
        }

        return false;
    }

    /**
     * @param $r
     * @return mixed
     */
    private function getFamilyLastNameField($r)
    {
        switch ($this->currentSurveyId) {
            case $this->volunteerHourSurveyId:
                if (isset($r['Tell us about yourself']['Family Last Name'])) {
                    return $r['Tell us about yourself']['Family Last Name'];
                } elseif (isset($r['Tell us about yourself']['Your Last Name'])) {
                    return $r['Tell us about yourself']['Your Last Name'];
                } else return false;
            case $this->volunteerHourSurveyId2:
                if (isset($r['Tell us about yourself']['Family Last Name'])) {
                    return $r['Tell us about yourself']['Family Last Name'];
                } elseif (isset($r['Tell us about yourself']['Your Last Name'])) {
                    return $r['Tell us about yourself']['Your Last Name'];
                } else return false;
        }
    }

    /**
     * @param $r
     * @return mixed
     */
    private function getYourLastNameField($r)
    {
        return $r['Tell us about yourself']['Your Last Name'];
    }

    /**
     * @param $r
     * @return mixed
     */
    private function getYourFirstNameField($r)
    {
        return $r['Tell us about yourself']['Your First Name'];
    }

    /**
     * @param $familyName
     * @param $first
     * @param $last
     * @param $childFirst
     * @param $childLast
     * @return bool|int
     */
    private function detectFamily($familyName, $first, $last, $childFirst, $childLast)
    {
        if ($this->currentSurveyId === $this->volunteerHourSurveyId2) {
            $this->dl('SURVEY 2');
        }
        $this->dl("==> Running detection for ['$familyName', '$first', '$last', '$childFirst', '$childLast']");
        $possiblyFoundID = $this->getStudentsWithExactMatchName($childFirst, $childLast);
        if ($possiblyFoundID) {
            return $possiblyFoundID;
        }

        if ($childFirst === '' && $childLast === '') {
            $this->dl("Student name is empty, checking Guardians...");
        } else {
            $this->dl("No clear match for student name {$childFirst} {$childLast}, checking Guardians...");
        }

        $names = $this->detectDelimiter($familyName);
        $fnames = $this->detectDelimiter($first);
        $lnames = $this->detectDelimiter($last);

        if (count($fnames) === 1 && count($lnames) === 1) {
            $this->dl("Looking for Guardian $first $last");
            $guardiansWithThisName = YRCSGuardians::whereFirst($first)->whereLast($last)->count();

            if ($guardiansWithThisName > 1) {
                $this->dl("Impossible, $guardiansWithThisName guardians share the name $first $last");
                trigger_error('Impossibru!', E_USER_NOTICE);
            } elseif ($guardiansWithThisName === 1) {
                return $this->returnGuardianWithExactMatchFirstAndLast($first, $last);
            } elseif ($guardiansWithThisName < 1) {
                $this->logEffectiveness('returnGuardianWithExactMatchFirstAndLast', -1);
                $this->dl("No guardians found with the name $first $last. We must look deeper.");
                $this->dl("Checking for guardian with last name $last and first name starting with $first");
                $possiblyFoundID = $this->getGuardiansWithMatchingLastNameAndFirstNameStartingWith($first, $last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
                $this->dl("Checking for guardians with very close edit distance");
                $possiblyFoundID = $this->getGuardianWithVeryCloseEditDistance($first, $last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
                $this->dl("Checking for guardian with last name $last and first name containing $first");
                $possiblyFoundID = $this->getGuardiansWithSameLastNameAndFirstNameContaining($first, $last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
                $this->dl("Checking for guardian with last name $last and first name containing $first");
                $possiblyFoundID = $this->getGuardiansWithMatchingFirstNameButDifferentLastNameAsOtherFamily($first, $last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
                $this->dl("Checking for guardian with first name $first and last name containing $last");
                $possiblyFoundID = $this->getGuardiansWithExactFirstNameAndLastNameContainingLast($first, $last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
                $this->dl("Checking for other guardians with last name $last");
                $possiblyFoundID = $this->getGuardiansWithSameLastName($last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
                $this->dl("Checking for Guardians with last name $last and very similar first name to $first");
                $possiblyFoundID = $this->getSimilarFirstNamesByLastName($first, $last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
                $this->dl("Checking for Guardians with similar last name to $last");
                $possiblyFoundID = $this->getSimilarFirstAndLastName($first, $last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
                $this->dl("Checking if last name $last is unique to a family or individual in the school");
                $possiblyFoundID = $this->checkIfLastNameIsUnique($first, $last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
                $this->dl("Checking for Guardians with the first and last name swapped, $last $first");
                $possiblyFoundID = $this->getGuardiansWithSwappedFirstAndLast($first, $last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
            }
        } else {
            $this->dl("Checking for guardian with first name starting with $first and last name ending with $last");
            $possiblyFoundID = $this->getGuardianWithFirstStartingWithFirstAndLastEndingWithLast($first, $last);
            if ($possiblyFoundID) {
                return $possiblyFoundID;
            }

        }

        $this->dl("Couldn't find $first $last at all, giving up");
//        return $this->detectionOriginalMethod($first, $last, $names, $fnames, $lnames);
    }

    /**
     * @param $childFirst
     * @param $childLast
     * @return bool
     */
    private function getStudentsWithExactMatchName($childFirst, $childLast)
    {
        /** @var Collection $student */
        $student = YRCSStudents::whereFirst($childFirst)->whereLast($childLast)->get();
        if ($student->count() === 1) {
            $this->dl("Found exact match for student {$childFirst} {$childLast}, returning {$student->first()->family_id}");
            $this->logEffectiveness(__FUNCTION__, 1);
            return $student->first()->family_id;
        } elseif ($student->count() > 1) {
            $this->dl("Found more than one match for student {$childFirst} {$childLast}, confusing....");
            $this->logEffectiveness(__FUNCTION__, -1);
            return false;
        } else {
            $this->logEffectiveness(__FUNCTION__, -1);
            return false;
        }
    }

    /**
     * @param $__FUNCTION__
     * @param $int
     */
    private function logEffectiveness($__FUNCTION__, $int)
    {
        if (!isset($this->stats[$__FUNCTION__]['success'])) {
            $this->stats[$__FUNCTION__]['success'] = 0;
        }
        if (!isset($this->stats[$__FUNCTION__]['fail'])) {
            $this->stats[$__FUNCTION__]['fail'] = 0;
        }
        if (!isset($this->stats['attempts'])) {
            $this->stats['attempts'] = 0;
        }
        if ($int === 1) {
            $this->stats[$__FUNCTION__]['success']++;
        }
        if ($int === -1) {
            $this->stats[$__FUNCTION__]['fail']++;
        }
        $this->stats['attempts']++;
    }

    /**
     * @param $maybe_delimited_string
     * @return array
     */
    private function detectDelimiter($maybe_delimited_string)
    {
        $maybe_delimited_string = trim($maybe_delimited_string);
        $delimiter = false;
        if ($this->hasDelimiter($maybe_delimited_string, '/')) {
            $delimiter = '/';
        } elseif ($this->hasDelimiter($maybe_delimited_string, '-')) {
            $delimiter = '-';
        } elseif ($this->hasDelimiter($maybe_delimited_string, ' ')) {
            $delimiter = ' ';
        }
        if ($delimiter) {
            $names = explode($delimiter, $maybe_delimited_string);
        } else {
            $names[] = $maybe_delimited_string;
        }
        return $names;
    }

    /**
     * @param $maybe_delimited_string
     * @param $delimiter
     * @return bool
     */
    private function hasDelimiter($maybe_delimited_string, $delimiter)
    {
        return strpos($maybe_delimited_string, $delimiter) !== false;
    }

    /**
     * @param $first
     * @param $last
     * @return int
     */
    private function returnGuardianWithExactMatchFirstAndLast($first, $last)
    {
        $this->logEffectiveness(__FUNCTION__, 1);
        $guardian = YRCSGuardians::whereFirst($first)->whereLast($last)->first();
        $this->dl("Found one guardian named $first $last with family_id {$guardian->family_id}");
        return $guardian->family_id;
    }

    /**
     * @param $first
     * @param $last
     * @return bool|int
     */
    private function getGuardiansWithMatchingLastNameAndFirstNameStartingWith($first, $last)
    {
        /** @var Collection $guardians_collection */
        $guardians_collection = YRCSGuardians::whereLast($last)->where('first', 'like', "$first%")->get();
        if ($guardians_collection->count() === 1) {
            $g = $guardians_collection->first();
            $this->dl("Found Guardian {$g->first} {$g->last}");
            $this->logEffectiveness(__FUNCTION__, 1);
            return (int)$g->family_id;
        }
        $this->logEffectiveness(__FUNCTION__, -1);
        return false;
    }

    /**
     * @param $first
     * @param $last
     * @return bool
     */
    private function getGuardianWithVeryCloseEditDistance($first, $last)
    {
        $guardians = YRCSGuardians::all(['first', 'last', 'family_id']);
        foreach ($guardians as $guardian) {
            $name = $first . ' ' . $last;
            $check_name = $guardian->first . ' ' . $guardian->last;
            if (levenshtein($name, $check_name) === 1) {
                $this->dl("$name is one edit away from $check_name, returning {$guardian->family_id}");
                $this->logEffectiveness(__FUNCTION__, 1);
                return $guardian->family_id;
            };
        }
        $this->logEffectiveness(__FUNCTION__, -1);
        return false;
    }

    /**
     * @param $first
     * @param $last
     * @return bool
     */
    private function getGuardiansWithSameLastNameAndFirstNameContaining($first, $last)
    {
        $this->dl("Checking for other Guardians with last name $last and first name containing $first");
        /** @var Collection $guardians_collection */
        $guardians_collection = YRCSGuardians::whereLast($last)->where('first', 'like', "%$first%")->get();
        $this->dl("Found {$guardians_collection->count()} Guardians with last name $last, containing $first in first");
        if ($guardians_collection->count() === 1) {
            $this->logEffectiveness(__FUNCTION__, 1);
            return $guardians_collection->first()->family_id;
        }
        $this->logEffectiveness(__FUNCTION__, -1);
        return false;
    }

    /**
     * @param $first
     * @param $last
     * @return bool
     */
    private function getGuardiansWithMatchingFirstNameButDifferentLastNameAsOtherFamily($first, $last)
    {
        $this->dl("Searching for families with last name $last");
        /** @var Collection $guardians_collection */
        $guardians_collection = YRCSGuardians::whereLast($last)->get();
        if ($guardians_collection->count() > 0) {
            $this->dl("Found {$guardians_collection->count()} Guardians with last name $last, checking if any of those families has someone named $first");
            $maybe = [];
            foreach ($guardians_collection as $guardian) {
                $maybe[] = $guardian->family_id;
            }
            if (count($maybe)) {
                $this->dl("Found " . count($maybe) . " possible familes");
                foreach ($maybe as $family_id) {
                    $this->dl("Checking if family {$guardian->last} has someone named $first");
                    $gs = YRCSGuardians::whereFamilyId($family_id)->whereFirst($first);
                    if ($gs->count() === 1) {
                        $this->dl("Family {$guardian->last} has someone named $first, returning $family_id");
                        $this->logEffectiveness(__FUNCTION__, 1);
                        return $family_id;
                    }
                }
            }
        }
        $this->logEffectiveness(__FUNCTION__, -1);
        return false;
    }

    /**
     * @param $first
     * @param $last
     * @return bool
     */
    private function getGuardiansWithExactFirstNameAndLastNameContainingLast($first, $last)
    {
        $this->dl("Searching for Guardians with first name $first");
        /** @var Collection $guardians_collection */
        $guardians_collection = YRCSGuardians::whereFirst($first)->where('last', 'like', "%$last%")->get();
        if ($guardians_collection->count() === 1) {
            $this->logEffectiveness(__FUNCTION__, 1);
            $this->dl("Found match for {$guardians_collection->first()->first} {$guardians_collection->first()->last}, returning {$guardians_collection->first()->family_id}");
            return $guardians_collection->first()->family_id;
        } else {
            $this->dl("Found " . $guardians_collection->count() . " matches");
        }
        $this->logEffectiveness(__FUNCTION__, -1);
        return false;
    }

    /**
     * @param $last
     * @return bool
     */
    private function getGuardiansWithSameLastName($last)
    {
        /** @var Collection $guardians_collection */
        $guardians_collection = YRCSGuardians::whereLast($last)->get();
        $this->dl("Found {$guardians_collection->count()} Guardians with last name $last");
        if ($guardians_collection->count() === 1) {
            $guardian = $guardians_collection->first();
            $family_id = $guardian->family_id;
            $found_first = $guardian->first;
            $this->dl("Found {$guardians_collection->count()} Guardian named $found_first $last, returning family_id $family_id");
            $this->logEffectiveness(__FUNCTION__, 1);
            return $family_id;
        }
        $this->logEffectiveness(__FUNCTION__, -1);
        return false;
    }

    /**
     * @param $first
     * @param $last
     * @return bool
     */
    private function getSimilarFirstNamesByLastName($first, $last)
    {
        $guardians = YRCSGuardians::whereLast($last)->get();
        foreach ($guardians as $guardian) {
            $name = $first . ' ' . $last;
            $check_name = $guardian->first . ' ' . $guardian->last;
            $distance = levenshtein(strtolower($name), strtolower($check_name));
            if ($distance <= 3) {
                $this->dl("$check_name is $distance edits away from $name");
                $this->dl("$check_name is close enough to $name");
                $this->logEffectiveness(__FUNCTION__, 1);
                return $guardian->family_id;
            }
        }
        $this->logEffectiveness(__FUNCTION__, -1);
        return false;
    }

    /**
     * @param $first
     * @param $last
     * @return bool
     */
    private function getSimilarFirstAndLastName($first, $last)
    {
        $guardians = YRCSGuardians::all();
        $maybe = [];
        foreach ($guardians as $guardian) {
            $distance_last = levenshtein(strtolower($last), strtolower($guardian->last));
            if ($distance_last <= 1) {
//                $this->dl("{$guardian->last} is $distance_last edits away from $last");
                $this->dl("{$guardian->first}'s last name {$guardian->last} is close at $distance_last edits, adding to maybe");
                $maybe[] = $guardian;
            }
        }

        if (count($maybe) === 1) {
            $this->dl("Only one very close match, returning {$maybe[0]->family_id} for {$maybe[0]->first} {$maybe[0]->last} as a close-ish match for $first $last");
            $this->logEffectiveness(__FUNCTION__, 1);
            return $maybe[0]->family_id;
        }

        foreach ($maybe as $guardian) {
            $distance_first = levenshtein(strtolower($first), strtolower($guardian->first));
            if ($distance_first <= 2) {
                $this->dl("{$guardian->first} is $distance_first edits away from $first");
                $this->dl("{$guardian->first} {$guardian->last} is close enough to $first $last at $distance_first away, returning {$guardian->family_id}");
                $this->logEffectiveness(__FUNCTION__, 1);
                return $guardian->family_id;
            }
        }

        $this->logEffectiveness(__FUNCTION__, -1);
        return false;
    }

    /**
     * @param $first
     * @param $last
     * @return mixed
     */
    private function checkIfLastNameIsUnique($first, $last)
    {
//        if ($last === 'Paige' || $last === 'Mark') return false;
        $count = DB::select("select count(distinct family_id) as count from yrcs_guardians where last = '$last'");
        $count = (int)$count[0]->count;
        $this->dl("Found $count families with last name $last");
        if ($count === 1) {
            $family_id = YRCSGuardians::whereLast($last)->get(['family_id'])->first()->toArray();
            $family_id = $family_id['family_id'];
            $this->dl("Unique family name, returning family_id $family_id");
            $this->logEffectiveness(__FUNCTION__, 1);
            return $family_id;
        }
        $this->logEffectiveness(__FUNCTION__, -1);
    }

    /**
     * @param $first
     * @param $last
     * @return bool
     */
    private function getGuardiansWithSwappedFirstAndLast($first, $last)
    {
        /** @var Collection $guardians */
        $guardians = YRCSGuardians::whereLast($first)->whereFirst($last)->get();;
        if ($guardians->count() === 1) {
            $this->logEffectiveness(__FUNCTION__, 1);
            return $guardians->first()->family_id;
        }
        foreach ($guardians as $guardian) {
            $name = $first . ' ' . $last;
            $check_name = $guardian->first . ' ' . $guardian->last;
            $distance = levenshtein(strtolower($name), strtolower($check_name));
            if ($distance <= 3) {
                $this->dl("$check_name is $distance edits away from $name");
                $this->dl("$check_name is close enough to $name");
                $this->logEffectiveness(__FUNCTION__, 1);
                return $guardian->family_id;
            }
        }
        $this->logEffectiveness(__FUNCTION__, -1);
        return false;
    }

    private function getGuardianWithFirstStartingWithFirstAndLastEndingWithLast($first, $last)
    {
        $first = $this->trimAtDelimiter($first, 'first');
        $last = $this->trimAtDelimiter($last, 'last');
        $this->dl("Checking for name parts $first and $last");
        $guardians_collection = YRCSGuardians::where('first', 'like', "$first%")->where('last', 'like', "%$last")->get();
        if ($guardians_collection->count() === 1) {
            $g = $guardians_collection->first();
            $this->dl("Found Guardian {$g->first} {$g->last}");
            $this->logEffectiveness(__FUNCTION__, 1);
            return (int)$g->family_id;
        }
        $this->logEffectiveness(__FUNCTION__, -1);
        return false;
    }

    /**
     * @return string
     */
    private function getClassGradeField()
    {
        return 'What class/program is one of your children in?';
    }

    /**
     * @param $log
     */
    private function saveHoursToFamilyId($log)
    {
        foreach ($log as $item) {
            try {
                if (Family::whereId($item['family_id'])->count()) ;
                $attributes = [
                    'hours' => $item['hours'],
                    'date' => $item['date'],
                    'family_id' => $item['family_id'],
                ];
                $log = TimeLog::create($attributes);
                $log->save();

            } catch (Exception $e) {
                $this->dl("Couldn't save time log because: " . $e->getMessage());
            }

        }
    }

    /**
     *
     */
    private function fakeRespondentList()
    {
        $r = [
            'success' => true, 'data' => [
                'respondents' => [
                    0 => ['date_modified' => '2015-10-28 18:04:15', 'respondent_id' => '4290426334',],
                    1 => ['date_modified' => '2015-10-28 18:01:35', 'respondent_id' => '4290418693',],
                ],
                'page' => 1,
                'page_size' => 1000,
            ],
        ];
    }

    /**
     *
     */
    private function getFakeResponses()
    {
        $r = ['success' => true,
            'data' => [
                0 => [
                    'respondent_id' => '4290426334',
                    'questions' => [
                        0 => [
                            'answers' => [
                                0 => ['text' => 'Laura', 'row' => '9112913868',],
                                1 => ['text' => 'Hazelton', 'row' => '9112913869',],
                                2 => ['text' => 'Hazelton', 'row' => '9112913870',],
                            ],
                            'question_id' => '817218761',
                        ],
                        1 => [
                            'answers' => [
                                0 => ['row' => '9112913879',],
                            ],
                            'question_id' => '817218762',
                        ],
                        2 => [
                            'answers' => [
                                0 => ['row' => '9112913903',],
                            ],
                            'question_id' => '817218764',
                        ],
                        3 => [
                            'answers' => [
                                0 => [
                                    'row' => '9112945810',
                                ],
                            ], 'question_id' => '817222577',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function trimAtDelimiter($name, $firstOrLast)
    {
        $delimiters = [' ', '-'];
        foreach ($delimiters as $d) {
            $parts = explode($d, $name);
            if (count($parts) > 1) {
                if ($firstOrLast === 'first') {
                    return $parts[0];
                } else {
                    return $parts[1];
                }
            }
        }
        return $name;
    }
}
