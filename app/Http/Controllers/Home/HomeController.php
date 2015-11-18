<?php

namespace App\Http\Controllers\Home;

use App\Family;
use App\Http\Controllers\Controller;
use App\TimeLog;
use App\YRCSFamilies;
use App\YRCSGuardians;
use App\YRCSStudents;
use App\YubaRiver;
use Ascension\SurveyMonkey;
use DB;
use Exception;
use Fhaculty\Graph\Graph;
use Gbrock\Table\Table;
use Graphp\GraphViz\GraphViz;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use League\Csv\Reader;
use League\Period\Period;
use Monolog\ErrorHandler;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use ref;
use Stash\Driver\FileSystem;
use Stash\Pool;
use Stiphle\Throttle\LeakyBucket;
use Symfony\Component\Debug\Exception\FatalErrorException;

class HomeController extends Controller
{
    private $volunteerHourSurveyId = '';
    private $volunteerHourSurveyId2 = '';
    private $allSurveyIDs = [];
    private $currentSurveyId = '';

    private $qLookup, $aLookup, $rLookup = [];

    private $SM, $pool;

    private $stats;

    private $throttle, $throttle_id;

    public function __construct()
    {
        ref::config('expLvl', -1);
        ref::config('maxDepth', 0);

        $this->volunteerHourSurveyId = env('SURVEY_MONKEY_SURVEY_ID_1');
        $this->volunteerHourSurveyId2 = env('SURVEY_MONKEY_SURVEY_ID_2');

        $this->allSurveyIDs = [
            $this->volunteerHourSurveyId,
            $this->volunteerHourSurveyId2
        ];

        $this->throttle = new LeakyBucket;
        $this->throttle_id = 'sm';


        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $this->SM = new SurveyMonkey(env('SURVEY_MONKEY_API_KEY'), env('SURVEY_MONKEY_ACCESS_TOKEN'));
        $driver = new FileSystem();
        $this->pool = new Pool($driver);
        $logger = new Logger('log');
        $logger->pushHandler((new ErrorLogHandler())->setFormatter(new LineFormatter()));
        $this->pool->setLogger($logger);
        ErrorHandler::register($logger);
//        $logger->log('info', 'test');
//        $this->pool->flush();
    }

    public function index()
    {
        $students = YRCSStudents::sorted()->paginate(10, ['*'], 'students');

        $stu_table = (new Table)->create($students, ['first']);
        $stu_table->setView('tablecondensed');
        $stu_table->addColumn('last', 'Last', function ($model) {
            return "<a href='/family/{$model->family_id}'>{$model->last}</a>";
        });

        $guardians = YRCSGuardians::sorted()->paginate(10, ['*'], 'guardians');
        $guardian_table = (new Table)->create($guardians, ['relationship', 'first']);
        $guardian_table->setView('tablecondensed');
        $guardian_table->addColumn('last', 'Last', function ($model) {
            return "<a href='/family/{$model->family_id}'>{$model->last}</a>";
        });

        $families = YRCSFamilies::sorted()->paginate(10, ['*'], 'families');
        $fam_table = (new Table)->create($families, ['id']);
        $fam_table->setView('tablecondensed');
        $fam_table->addColumn('family_id', 'Family ID', function ($model) {
            return "<a href='/family/{$model->family_id}'>{$model->family_id}</a>";
        });
        $fam_table->addColumn('hours', 'Hours', function ($model) {
            $s = TimeLog::whereFamilyId($model->family_id)->get();
            return $s->sum('hours');
        });

        $hours = TimeLog::sorted()->paginate(10, ['*'], 'hours');
        $hours_table = (new Table)->create($hours, ['date', 'hours']);
        $hours_table->setView('tablecondensed');
        $hours_table->addColumn('family_id', 'Family ID', function ($model) {
            return "<a href='/family/{$model->family_id}'>{$model->family_id}</a>";
        });

        $s_count = YRCSStudents::all()->count();
        $g_count = YRCSGuardians::all()->count();
        $f_count = YRCSFamilies::all()->count();

        $total_hours = TimeLog::all()->sum('hours');

        $expected_monthly_hours = 5 * $f_count;

        $total_expected_hours = $this->calc_expected_hours($expected_monthly_hours);

        return view('index', [
            'fam_table' => $fam_table,
            'guardians_table' => $guardian_table,
            'students_table' => $stu_table,
            'hours_table' => $hours_table,
            'student_count' => $s_count,
            'guardian_count' => $g_count,
            'family_count' => $f_count,
            'total_hours' => $total_hours,
            'total_expected_hours' => $total_expected_hours,
            'ratio' => round($total_hours / $total_expected_hours, 4) * 100,
            'mom_count' => YRCSGuardians::whereRelationship('mother')->count(),
            'dad_count' => YRCSGuardians::whereRelationship('father')->count(),
            'stepdad_count' => YRCSGuardians::whereRelationship('stepfather')->count(),
            'stepmom_count' => YRCSGuardians::whereRelationship('stepmother')->count(),
            'grandmom_count' => YRCSGuardians::whereRelationship('grandmother')->count(),
            'granddad_count' => YRCSGuardians::whereRelationship('grandfather')->count(),
            'emergency_count' => YRCSGuardians::whereRelationship('emergency contact')->count(),
            'other_count' => YRCSGuardians::whereRelationship('other relationship')->count(),
        ]);
    }

    /**
     * @param $expected_monthly_hours
     * @return mixed
     */
    private function calc_expected_hours($expected_monthly_hours)
    {
        $months_elapsed = $this->months_elapsed();

        $total_expected_hours = $expected_monthly_hours * $months_elapsed;
        return $total_expected_hours;
    }

    /**
     * @return int
     */
    private function months_elapsed()
    {
        $period = new Period('2015-08-19', '2016-06-03');
        $now = date('F, Y');
        $months_elapsed = 0;
        foreach ($period->getDatePeriod('1 MONTH') as $datetime) {
            $months_elapsed++;
            if ($now === $datetime->format('F, Y')) {
                break;
            }
        }
        return $months_elapsed;
    }

    public function dev()
    {
        foreach ($this->SM->getSurveyList()['data']['surveys'] as $list) {
            $id = $list['survey_id'];
            r($this->surveyMonkeyGetSurveyDetails($id));
        }
    }

    private function surveyMonkeyGetSurveyDetails($surveyID)
    {
        $this->throttleSM();
        return $this->SM->getSurveyDetails($surveyID)['data'];
    }

    private function throttleSM()
    {
        $this->throttle->throttle($this->throttle_id, 2, 2000);;
    }

    public function csv()
    {
        $csv = Reader::createFromPath(__DIR__ . '/../../../../database/seeds/yrcs.csv');
        $csv->setOffset(1);
        $d = $csv->fetchAll();

        foreach ($d as $i) {
            try {
                $y = YubaRiver::updateOrCreate(
                    [
                        'student_last' => $i[0],
                        'student_first' => $i[1],
                        'parent_last' => $i[5],
                        'parent_first' => $i[4],
                    ],
                    [
                        'student_last' => $i[0],
                        'student_first' => $i[1],
                        'parent_last' => $i[5],
                        'parent_first' => $i[4],
                        'grade' => $i[2],
                        'relationship' => $i[3],
                        'email' => $i[7],
                        'phone' => $i[8],
                        'child_lives_with' => $i[9],
                        'city' => $i[10],
                        'state' => $i[11],
                        'address' => $i[12],
                        'zip' => $i[13],
                    ]);
                $y->save();
            } catch (QueryException $e) {

            }

        }
    }

    public function discoverFamilyUnits()
    {
        $school['students'] = [];
        $school['guardians'] = [];
        $graph = new Graph();
        $guardians = YRCSGuardians::all();
//        $a = $guardians->random(20);
        foreach ($guardians as $guardian) {
            $family_unit = $this->initFamilyUnit($guardian);
            list($guardian_name, $school) = $this->addInitialGuardian($guardian, $graph, $school);
            foreach ($guardian->students()->get() as $student) {
                list($family_unit, $school) = $this->findThisGuardiansStudents($student, $family_unit, $graph, $school, $guardian);
                list($family_unit, $school) = $this->findOtherGuardiansAndTheirStudents($student, $guardian_name, $family_unit, $graph, $school);
            }

            $this->saveFamilyUnit($family_unit);
        }

//        $graphviz = new GraphViz();
//        $data = $graphviz->createImageData($graph);
//        $image = \imagecreatefromstring($data);
//        header('Content-Type: image/png');
//        \imagepng($image);
//        \imagedestroy($image);
//        exit;

    }

    /**
     * @param $guardian
     * @return array
     */
    private function initFamilyUnit($guardian)
    {
        $family_unit['guardians'] = [$guardian->id];
        $family_unit['students'] = [];
        return $family_unit;
    }

    /**
     * @param $guardian YRCSGuardians
     * @param $graph Graph
     * @param $school
     * @return array
     */
    private function addInitialGuardian($guardian, $graph, $school)
    {
        $guardian_name = $guardian->first . ' ' . $guardian->last;
        $school['guardians'][$guardian->id] = $graph->createVertex($guardian_name, true);
        $school['guardians'][$guardian->id]->setAttribute('graphviz.color', 'red');
        return array($guardian_name, $school);
    }

    /**
     * @param $student YRCSStudents
     * @param $family_unit
     * @param $graph Graph
     * @param $school array
     * @param $guardian YRCSGuardians
     * @return array
     */
    private function findThisGuardiansStudents($student, $family_unit, $graph, $school, $guardian)
    {
        if (!in_array($student->id, $family_unit['students'])) {
            $family_unit['students'][] = $student->id;
        }
        $student_name = $student->first . ' ' . $student->last;
        $school['students'][$student->id] = $graph->createVertex($student_name, true);
        $school['students'][$student->id]->setAttribute('graphviz.color', 'green');
        $edge = $school['guardians'][$guardian->id]->createEdgeTo($school['students'][$student->id]);
        $this->setRelationshipEdgeColor($guardian, $edge);
        return array($family_unit, $school);
    }

    /**
     * @param $guardian YRCSGuardians
     * @param $edge
     */
    private function setRelationshipEdgeColor($guardian, $edge)
    {
        $rel = $guardian->relationship;
        switch ($rel) {
            case 'Father':
                $color = 'blue';
                break;
            case 'Stepfather':
                $color = 'orange';
                break;
            case 'Grandfather':
                $color = 'brown';
                break;
            case 'Mother':
                $color = 'purple';
                break;
            case 'Stepmother':
                $color = 'yellow';
                break;
            case 'Grandmother':
                $color = 'black';
                break;
            default:
                $color = 'gray';
                break;
        }
        $edge->setAttribute('graphviz.color', $color);
    }

    /**
     * @param $student
     * @param $guardian_name
     * @param $family_unit
     * @param $graph
     * @param $school
     * @return array
     */
    private function findOtherGuardiansAndTheirStudents($student, $guardian_name, $family_unit, $graph, $school)
    {
        $other_guardians = $student->guardians()->get();
        foreach ($other_guardians as $other_guardian) {
            $gname = $other_guardian->first . ' ' . $other_guardian->last;
            if ($gname === $guardian_name) {
                continue;
            }
            if (!in_array($other_guardian->id, $family_unit['guardians'])) {
                $family_unit['guardians'][] = $other_guardian->id;
            }
            $school['guardians'][$other_guardian->id] = $graph->createVertex($gname, true);
            $school['guardians'][$other_guardian->id]->setAttribute('graphviz.color', 'red');
            $edge = $school['guardians'][$other_guardian->id]->createEdgeTo($school['students'][$student->id]);
            $this->setRelationshipEdgeColor($other_guardian, $edge);
        }
        return array($family_unit, $school);
    }

    private function saveFamilyUnit($family_unit)
    {
        $fam = false;
        foreach ($family_unit['guardians'] as $g) {
            /** @var YRCSGuardians $guardian */
            $guardian = YRCSGuardians::find($g);
            if (!isset($guardian->family()->getResults()->id)) {
                continue;
            } else {
                $fam = $guardian->family()->getResults()->id;
            }
        }

        if (!$fam) {
            $fam = YRCSFamilies::create();
            $fam->save();
        }

        foreach ($family_unit['guardians'] as $g) {
            /** @var YRCSGuardians $guardian */
            $guardian = YRCSGuardians::find($g);
            if (!isset($guardian->family()->getResults()->id)) {
                $guardian->family()->associate($fam);
                $guardian->save();
            }
        }
        foreach ($family_unit['students'] as $s) {
            /** @var YRCSStudents $guardian */
            $student = YRCSStudents::find($s);
            if (!isset($student->family()->getResults()->id)) {
                $student->family()->associate($fam);
                $student->save();
            }
        }
        r($family_unit);
    }

    public function generateFamilies()
    {
        $this->generateStudents();
        $this->generateGuardians();
        $this->generateStudentsToGuardiansTable();
    }

    public function generateStudents()
    {
        $users = DB::table('yuba_river')
//            ->select(DB::raw('group_concat(distinct SUBSTRING(parent_last, 3), SUBSTRING(parent_first, 3) SEPARATOR "") as family_hash'))
            ->select(DB::raw('student_first, student_last, parent_first, parent_last'))
            ->groupBy('student_first', 'student_last')
            ->get();

        foreach ($users as $u) {
            $this->saveStudent($u);
        }
    }

    /**
     * @param $u
     */
    private function saveStudent($u)
    {
        try {
            $s = YRCSStudents::updateOrCreate(
                [
                    'first' => $u->student_first, 'last' => $u->student_last
                ],
                [
                    'first' => $u->student_first, 'last' => $u->student_last
                ]
            );
            $s->save();
        } catch (\Exception $e) {
//            r($e);
        }
    }

    public function generateGuardians()
    {
        $users = DB::table('yuba_river')
            ->select(DB::raw('student_first, student_last, parent_first, parent_last, relationship'))
            ->get();
        foreach ($users as $u) {
            /** @var YubaRiver $u */
            try {

                $p = YRCSGuardians::updateOrCreate(
                    [
                        'first' => $u->parent_first, 'last' => $u->parent_last
                    ],
                    [
                        'first' => $u->parent_first, 'last' => $u->parent_last, 'relationship' => $u->relationship
                    ]
                );
                $p->save();
            } catch (Exception $e) {

            }

        }
    }

    private function generateStudentsToGuardiansTable()
    {
        /** @var YRCSStudents $students */
        $students = YRCSStudents::all();

        foreach ($students as $student) {
            /** @var YubaRiver $families */
            $families = YubaRiver::whereStudentFirst($student->first)->whereStudentLast($student->last)->get(['parent_first', 'parent_last']);
            foreach ($families as $family) {
                /** @var YubaRiver $family */
                $guardian = YRCSGuardians::whereFirst($family->parent_first)->whereLast($family->parent_last)->get(['id'])->first();
//                dd($guardian->id);
                try {
                    $student->guardians()->attach($guardian->id);
                } catch (\Exception $e) {
                    r($e->getMessage());
                }
            }
        }
    }

    public function syncSurveyMonkey()
    {
        foreach ($this->allSurveyIDs as $surveyID) {
            $this->currentSurveyId = $surveyID;
            $questions = $this->getQuestions($surveyID);
            $responses = $this->getResponses($surveyID);
            $log = $this->getLog($responses);
            $this->processLog($log);
        }
        dd($this->stats);

    }

    /**
     * @return array|mixed|null
     */
    private function getQuestions($surveyID)
    {
        $item = $this->pool->getItem('/surveyDetails/' . $surveyID);
        $surveyDetails = $item->get();
        r($item->isMiss());
        if ($item->isMiss()) {
            echo '<br>regenerating questions';
            $item->lock();

            $surveyDetails = $this->surveyMonkeyGetSurveyDetails($surveyID);
            $item->set($surveyDetails);
        }
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
        foreach ($surveyDetails['pages'][0]['questions'] as $question) {
            $questions[] = $question;
        }
        return $questions;
    }

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
     * @return array
     */
    private function getResponses($surveyID)
    {
        $item = $this->pool->getItem('/reslist/' . $surveyID);
        $respondentsToRequest = [];
        $respondentList = $item->get();
        r($item->isMiss());
        if ($item->isMiss()) {
            echo '<br>regenerating responses';
            $item->lock();
            $respondentList = $this->surveyMonkeyGetRespondentList($surveyID, ['fields' => ['date_modified']]);
            $item->set($respondentList);
        }

        foreach ($respondentList['data']['respondents'] as $r) {
            $this->rLookup[$r['respondent_id']] = $r['date_modified'];
            $respondentsToRequest[] = $r['respondent_id'];
        }
        $responses = $this->surveyMonkeyGetResponses($surveyID, $respondentsToRequest);
        $responses = $this->processResponses($responses['data']);
        return $responses;
    }

    private function surveyMonkeyGetRespondentList($surveyID, $array)
    {
        $this->throttleSM();
        return $this->SM->getRespondentList($surveyID, $array);
    }

    private function surveyMonkeyGetResponses($surveyID, $respondentsToRequest)
    {
        $this->throttleSM();
        return $this->SM->getResponses($surveyID, $respondentsToRequest);
    }

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

    private function t($id, $type)
    {
        return $this->translate($id, $type);
    }

    private function translate($id, $type)
    {
        if ($type === 'a') {
            if (isset($this->aLookup[$id])) {
                $return = $this->aLookup[$id];
                $return = preg_replace('/[()]/', '', $return);
                return $return;
            } else {
//                r($id, $type);
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
    private function getLog($responses)
    {
        $log = [];
        $hours = 0;
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
                $this->dl("No hours field found");
                r([$r[$howManyHoursField]]);
                if (isset($r[$howManyHoursField][0])) {
                    $this->dl("Dumping blah blah");
                    r([$r[$howManyHoursField][0]]);
                }
                continue;
            }

            $familyLastName = '';
            if (!empty($this->getFamilyLastNameField($r))) {
                $familyLastName = $this->getFamilyLastNameField($r);
            } elseif (!empty($this->getYourLastNameField($r))) {
                $familyLastName = $this->getYourLastNameField($r);
            }

            $firstName = false;
            if (!empty($this->getYourFirstNameFIeld($r))) {
                $firstName = $this->getYourFirstNameFIeld($r);
            }
            $lastName = false;
            if (!empty($this->getYourLastNameField($r))) {
                $lastName = $this->getYourLastNameField($r);
            }
            $f = $this->detectFamily($familyLastName, $firstName, $lastName, $childFirst, $childLast);
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
    }

    private function dl($debugdata, $file = __FILE__, $line = __LINE__)
    {
//        r(['DebugLog: ' . basename($file) . ':' . $line => $debugdata]);
        r(['DebugLog' => $debugdata]);
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
    private function getYourFirstNameFIeld($r)
    {
        return $r['Tell us about yourself']['Your First Name'];
    }

    private function detectFamily($familyName, $first, $last, $childFirst, $childLast)
    {

        $possiblyFoundID = $this->getStudentsWithExactMatchName($childFirst, $childLast);
        if ($possiblyFoundID) {
            $this->dl("Found ID $possiblyFoundID");
            return $possiblyFoundID;
        }

        $this->dl("No clear match for student name {$childFirst} {$childLast}, checking Guardians...");


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
                    $this->dl("Found ID $possiblyFoundID");
                    return $possiblyFoundID;
                }
                $this->dl("Still haven't found $first $last.");
                $this->dl("Checking for guardian with last name $last and first name containing $first");
                $possiblyFoundID = $this->getGuardiansWithSameLastNameAndFirstNameContaining($first, $last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
                $this->dl("Still haven't found $first $last.");
                $this->dl("Checking for other guardians with last name $last");
                $possiblyFoundID = $this->getGuardiansWithSameLastName($last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
                $this->dl("Still haven't found $first $last.");
                $this->dl("Checking for Guardians with last name $last and very similar first name to $first");
                $possiblyFoundID = $this->getSimilarFirstNamesByLastName($first, $last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
                $this->dl("Still haven't found $first $last.");
                $this->dl("Checking for Guardians with similar last name to $last");
                $possiblyFoundID = $this->getSimilarFirstAndLastName($first, $last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
                $this->dl("Still haven't found $first $last.");
                $this->dl("Checking if last name $last is unique to a family or individual in the school");
                $possiblyFoundID = $this->checkIfLastNameIsUnique($first, $last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }
                $this->dl("Still haven't found $first $last.");
                $this->dl("Checking for Guardians with the first and last name swapped, $last $first");
                $possiblyFoundID = $this->getGuardiansWithSwappedFirstAndLast($first, $last);
                if ($possiblyFoundID) {
                    return $possiblyFoundID;
                }

                $this->dl("Couldn't find $first $last at all, giving up");

            }
        }


//        return $this->detectionOriginalMethod($first, $last, $names, $fnames, $lnames);
    }

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

    private function logEffectiveness($__FUNCTION__, $int)
    {
        if (!isset($this->stats[$__FUNCTION__]['success'])) {
            $this->stats[$__FUNCTION__]['success'] = 0;
        }
        if (!isset($this->stats[$__FUNCTION__]['fail'])) {
            $this->stats[$__FUNCTION__]['fail'] = 0;
        }
        if ($int === 1) {
            $this->stats[$__FUNCTION__]['success']++;
        }
        if ($int === -1) {
            $this->stats[$__FUNCTION__]['fail']++;
        }
    }

    /**
     * @param $maybe_delimited_string
     * @return array
     */
    private function detectDelimiter($maybe_delimited_string)
    {
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

    private function getSimilarFirstNamesByLastName($first, $last)
    {
        $guardians = YRCSGuardians::whereLast($last)->get();
        foreach ($guardians as $guardian) {
            $name = $first . ' ' . $last;
            $check_name = $guardian->first . ' ' . $guardian->last;
            $distance = levenshtein(strtolower($name), strtolower($check_name));
            $this->dl("$check_name is $distance edits away from $name");
            if ($distance <= 3) {
                $this->dl("$check_name is close enough to $name");
                $this->logEffectiveness(__FUNCTION__, 1);
                return $guardian->family_id;
            }
        }
        $this->logEffectiveness(__FUNCTION__, -1);
        return false;
    }

    private function getSimilarFirstAndLastName($first, $last)
    {
        $guardians = YRCSGuardians::all();
        $maybe = [];
        foreach ($guardians as $guardian) {
            $distance_last = levenshtein(strtolower($last), strtolower($guardian->last));
            $this->dl("{$guardian->last} is $distance_last edits away from $last");
            if ($distance_last <= 1) {
                $maybe[] = $guardian;
                $this->dl("{$guardian->last} is close at $distance_last edits, adding to maybe");
            }
        }

        if (count($maybe) === 1) {
            $this->dl("Only one very close match, returning {$maybe[0]->family_id} for {$maybe[0]->first} {$maybe[0]->last} as a close-ish match for $first $last");
            $this->logEffectiveness(__FUNCTION__, 1);
            return $maybe[0]->family_id;
        }

        foreach ($maybe as $guardian) {
            $distance_first = levenshtein(strtolower($first), strtolower($guardian->first));
            $this->dl("{$guardian->first} is $distance_first edits away from $first");
            if ($distance_first <= 2) {
                $this->dl("{$guardian->first} is close enough to $first, returning {$guardian->family_id}");
                $this->logEffectiveness(__FUNCTION__, 1);
                return $guardian->family_id;
            }
        }

        $this->logEffectiveness(__FUNCTION__, -1);
        return false;
    }

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
            $this->dl("$check_name is $distance edits away from $name");
            if ($distance <= 3) {
                $this->dl("$check_name is close enough to $name");
                $this->logEffectiveness(__FUNCTION__, 1);
                return $guardian->family_id;
            }
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
    private function processLog($log)
    {
        foreach ($log as $item) {
            try {
                $fam = Family::firstOrCreate(['id' => $item['family_id']]);
                if (isset($fam->id)) {
                    $attributes = [
                        'hours' => $item['hours'],
                        'date' => $item['date'],
                        'family_id' => $item['family_id'],
                    ];
                    $log = TimeLog::create($attributes);
                    $log->save();

                }
            } catch (Exception $e) {

            }

        }
    }

    private function surveyMonkeyRequester()
    {

    }

    private function sortByDistance($a, $b)
    {
        if ($a['distance'] > $b['distance']) return 1;
        if ($a['distance'] < $b['distance']) return -1;
        if ($a['distance'] === $b['distance']) return 0;
    }

    /**
     * @param $SM
     * @param $ids
     */
    private function getSurveyList($SM, $ids)
    {
        foreach ($SM->getSurveyList()['data']['surveys'] as $survey) {
            sleep(1);
            $id = $survey['survey_id'];
            $ids[] = $id;
            $surveyDetails = $SM->getSurveyDetails($id);
            if ($surveyDetails['success'] === true) {
                $title = $surveyDetails['data']['title']['text'];
            }
//            ~r($title, $id, $surveyDetails);
            sleep(1);
        }
    }

    /**
     * @param $first
     * @param $last
     * @param $names
     * @param $fnames
     * @param $lnames
     * @return int|string
     */
    private function detectionOriginalMethod($first, $last, $names, $fnames, $lnames)
    {
        foreach ($names as $familyName) {
            r(["Looking for $familyName"]);
            $guardian = YRCSGuardians::whereLast($familyName)->count();
            if ($guardian === 0) {
                r("continuing, no guardians with last name " . $familyName, $first, $last);
                continue;
            }
            if ($guardian === 1) {
                r("found one, returning");
                return YRCSGuardians::whereLast($familyName)->first()->family_id;
            }
            if ($guardian > 1) {
                r(["found $guardian matching guardians"]);
                $all = YRCSGuardians::whereLast($familyName)->get();
                $found = [];
                foreach ($all as $a) {
                    $found[$a->family_id] = '';
                }
                $count = count($found);
                if (count($found) > 1) {
                    r("found $count matching last names, checking against first names");
                    foreach ($fnames as $first) {
                        r("Guardians with first name $first and family name $familyName");
                        $c = YRCSGuardians::whereFirst($first)->whereLast($familyName)->count();
                        r([$first, $familyName, $c]);
                        if ($c === 1) {
                            $a = YRCSGuardians::whereFirst($first)->whereLast($familyName)->first();
                            r("found id {$a->family_id} via first last");
                            return $a->family_id;
                        } else {
                            r('nothing found');
                        }
                    }

                    foreach ($lnames as $last) {
                        r("checking $last");
                        $c = YRCSGuardians::whereFirst($first)->whereLast($last)->count();
                        r([$first, $last, $c]);
                        if ($c === 1) {
                            $a = YRCSGuardians::whereFirst($first)->whereLast($last)->first();
                            r("found id {$a->family_id} via last");
                            return $a->family_id;
                        } else {
                            r('nothing found');
                        }
                    }
                    r('more than one guardian matches ' . $familyName . ': ' . print_r($found, true));
//                    return 'more than one family matches ' . $familyName . ': ' . print_r($found, true);
                } else {
                    r(['found only one guardian, returning:', $found]);
                }

                return $a->family_id;
            }
        }

        $this->getSimilarNames($first, $last);

        return 'not found';
    }

    private function getSimilarNames($first, $last)
    {
        $guardians = YRCSGuardians::all(['first', 'last']);
        foreach ($guardians as $guardian) {
            $name = $first . ' ' . $last;
            $check_name = $guardian->first . ' ' . $guardian->last;
            $missing[] = [
                'name' => $name,
                'vs' => $check_name,
                'distance' => levenshtein($name, $check_name)
            ];
        }

//        r($missing);
        usort($missing, [$this, 'sortByDistance']);
        ~r($missing);
    }
}
