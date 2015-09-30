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
use Gbrock\Table\Table;
use League\Csv\Reader;
use League\Period\Period;
use Monolog\ErrorHandler;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use ref;
use Stash\Driver\FileSystem;
use Stash\Pool;

class HomeController extends Controller
{
    private $volunteerHourSurveyId = '66119268';

    private $qLookup, $aLookup, $rLookup = [];

    private $SM, $pool;

    public function __construct()
    {
        ref::config('expLvl', -1);
        ref::config('maxDepth', 0);

        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $this->SM = new SurveyMonkey(env('SURVEY_MONKEY_API_KEY'), env('SURVEY_MONKEY_ACCESS_TOKEN'));
        $driver = new FileSystem();
        $this->pool = new Pool($driver);
        $logger = new Logger('log');
        $logger->pushHandler((new ErrorLogHandler())->setFormatter(new HtmlFormatter()));
        $this->pool->setLogger($logger);
        ErrorHandler::register($logger);
        $logger->log('info', 'test');
//        $this->pool->flush();
    }

    public function index()
    {
        $students = YRCSStudents::sorted()->paginate(10, ['*'], 'students');

        $stu_table = (new Table)->create($students, ['first']);
        $stu_table->setView('tablecondensed');
        $stu_table->addColumn('last', 'Last', function ($model) {
            return "<a href='/family/{$model->family_hash_id}'>{$model->last}</a>";
        });

        $guardians = YRCSGuardians::sorted()->paginate(10, ['*'], 'guardians');
        $guardian_table = (new Table)->create($guardians, ['relationship', 'first']);
        $guardian_table->setView('tablecondensed');
        $guardian_table->addColumn('last', 'Last', function ($model) {
            return "<a href='/family/{$model->family_hash_id}'>{$model->last}</a>";
        });

        $families = YRCSFamilies::sorted()->paginate(10, ['*'], 'families');
        $fam_table = (new Table)->create($families, ['id']);
        $fam_table->setView('tablecondensed');
        $fam_table->addColumn('family_hash_id', 'Family Hash', function ($model) {
            return "<a href='/family/{$model->family_hash_id}'>{$model->family_hash_id}</a>";
        });
        $fam_table->addColumn('hours', 'Hours', function ($model) {
            $s = TimeLog::whereFamilyHashId($model->family_hash_id)->get();
            return $s->sum('hours');
        });

        $hours = TimeLog::sorted()->paginate(10, ['*'], 'hours');
        $hours_table = (new Table)->create($hours, ['date', 'hours']);
        $hours_table->setView('tablecondensed');
        $hours_table->addColumn('family_hash_id', 'Family Hash Id', function ($model) {
            return "<a href='/family/{$model->family_hash_id}'>{$model->family_hash_id}</a>";
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
//        $r = $this->processResponses([]);
    }

    public function csv()
    {
        $csv = Reader::createFromPath(__DIR__ . '/../../../../database/seeds/yrcs.csv');
        $csv->setOffset(1);
        $d = $csv->fetchAll();

        foreach ($d as $i) {
            ~r($i);
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
        }
    }

    public function generateFamilyHashes()
    {
        $this->generateStudentHashes();
        $this->generateGuardianHashes();
    }

    public function generateStudentHashes()
    {
        $users = DB::table('yuba_river')
//            ->select(DB::raw('group_concat(distinct SUBSTRING(parent_last, 3), SUBSTRING(parent_first, 3) SEPARATOR "") as family_hash'))
            ->select(DB::raw("student_first, student_last, parent_first, parent_last, group_concat(distinct SUBSTRING(parent_last, 1, 3), SUBSTRING(parent_first, 1, 3) SEPARATOR '') as family_hash, group_concat(distinct parent_last, parent_first SEPARATOR '') as family_hash_2"))
            ->groupBy('student_first', 'student_last')
            ->get();

//        dd($users);

        foreach ($users as $u) {
            $lower = strtolower($u->family_hash);
            $replace = str_replace([',', ' ', '.', "'"], '', $lower);
            $split = str_split($replace);
            $h = array_unique($split);
            sort($h);
            $u->family_hash_3 = trim(implode('', $h));
//            r($u);
            $this->saveStudent($u);
            $this->saveFamilyHash($u);
        }
    }

    /**
     * @param $u
     */
    private function saveStudent($u)
    {
        $s = YRCSStudents::updateOrCreate(
            [
                'first' => $u->student_first, 'last' => $u->student_last, 'family_hash_id' => $u->family_hash_3
            ],
            [
                'first' => $u->student_first, 'last' => $u->student_last, 'family_hash_id' => $u->family_hash_3
            ]
        );
        try {
            $s->save();
        } catch (\Exception $e) {
            r($e);
        }
    }

    /**
     * @param $u
     */
    private function saveFamilyHash($u)
    {
        $s = YRCSFamilies::updateOrCreate(
            [
                'family_hash_id' => $u->family_hash_3
            ],
            [
                'family_hash_id' => $u->family_hash_3
            ]
        );
        try {
            $s->save();
        } catch (\Exception $e) {
            r($e);
        }
    }

    public function generateGuardianHashes()
    {
        $users = DB::table('yuba_river')
            ->select(DB::raw("student_first, student_last, parent_first, parent_last, relationship"))
            ->get();
        foreach ($users as $u) {
            /** @var YRCSStudents $s */
            $s = YRCSStudents::whereFirst($u->student_first)->whereLast($u->student_last)->first()->family_hash_id;
            $p = YRCSGuardians::updateOrCreate(
                [
                    'first' => $u->parent_first, 'last' => $u->parent_last
                ],
                [
                    'first' => $u->parent_first, 'last' => $u->parent_last, 'family_hash_id' => $s, 'relationship' => $u->relationship
                ]
            );
            try {
                $p->save();
            } catch (Exception $e) {

            }

        }
    }

    public function syncSurveyMonkey()
    {
        $questions = $this->getQuestions();
        $responses = $this->getResponses();
        $log = $this->getLog($responses);
//        ~r($log);

        foreach ($log as $item) {
            $fam = Family::firstOrCreate(['surname' => $item['family']]);
            if (isset($fam->id)) {
                echo '<br>' . $fam->id;
                $attributes = [
//                    'family_id' => $fam->id,
                    'hours' => $item['hours'],
                    'date' => $item['date'],
                    'family_hash_id' => $item['family_hash_id'],
                ];
                r($attributes);
                $log = TimeLog::create($attributes);
                try {
                    $log->save();
                } catch (Exception $e) {

                }

            } else {
//                rt($fam->attributesToArray());
            }
        }
//        r($log);
//        r($this->aLookup, $this->qLookup, $this->rLookup, $questions, $responses);
    }

    /**
     * @return array|mixed|null
     */
    private function getQuestions()
    {
        $item = $this->pool->getItem('/surveyDetails');
        $surveyDetails = $item->get();
        r($item->isMiss());
        if ($item->isMiss()) {
            echo '<br>regenerating questions';
            $item->lock();
            $surveyDetails = $this->SM->getSurveyDetails($this->volunteerHourSurveyId)['data'];
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
    private function getResponses()
    {
        $item = $this->pool->getItem('/reslist');
        $list = [];
        $reslist = $item->get();
        r($item->isMiss());
        if ($item->isMiss()) {
            echo '<br>regenerating responses';
            $item->lock();
            $reslist = $this->SM->getRespondentList($this->volunteerHourSurveyId, ['fields' => ['date_modified']]);
            $item->set($reslist);
        }
        foreach ($reslist['data']['respondents'] as $r) {
            $this->rLookup[$r['respondent_id']] = $r['date_modified'];
            $list[] = $r['respondent_id'];
        }
        $responses = $this->SM->getResponses($this->volunteerHourSurveyId, $list)['data'];
        $responses = $this->processResponses($responses);
        return $responses;
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
                        $resp[$this->t($rid, 'r')][$this->t($qid, 'q')][$this->t($ans['row'], 'a')] = $ans['text'];
                    } elseif (isset($ans['row'])) {
                        $resp[$this->t($rid, 'r')][$this->t($qid, 'q')][] = $this->t($ans['row'], 'a');
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
            $return = $this->aLookup[$id];
            $return = preg_replace('/[()]/', '', $return);
            return $return;
        }
        if ($type === 'q') {
            return $this->qLookup[$id];
        }
        if ($type === 'r') {
            return $this->rLookup[$id];
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
//            r($date, $r);
            if (!empty($r['How many hours did you volunteer?'][0])) {
                $hours = $r['How many hours did you volunteer?'][0];
            } elseif (!empty($r['How many hours did you volunteer?']['Other please specify'])) {
                $hours = $r['How many hours did you volunteer?']['Other please specify'];
            }

            $familyLastName = '';
            if (!empty($r['Tell us about yourself']['Family Last Name'])) {
                $familyLastName = $r['Tell us about yourself']['Family Last Name'];
            } elseif (!empty($r['Tell us about yourself']['Your Last Name'])) {
                $familyLastName = $r['Tell us about yourself']['Your Last Name'];
            }

            $firstName = false;
            if (!empty($r['Tell us about yourself']['Your First Name'])) {
                $firstName = $r['Tell us about yourself']['Your First Name'];
            }
            $lastName = false;
            if (!empty($r['Tell us about yourself']['Your Last Name'])) {
                $lastName = $r['Tell us about yourself']['Your Last Name'];
            }
            $f = $this->detectFamily($familyLastName, $firstName, $lastName);
            $class = $r['What class/program is one of your children in?']['0'];
            $log[] = [
                'date' => $date,
                'family' => $familyLastName,
                'class' => $class,
                'hours' => $hours,
                'family_hash_id' => $f
            ];
        }
        return $log;
    }

    private function detectFamily($familyName, $first, $last)
    {
        $names = $this->detectDelimiter($familyName);
        $fnames = $this->detectDelimiter($first);
        $lnames = $this->detectDelimiter($last);

        foreach ($names as $familyName) {
            r("Looking for " . $familyName);
            $f = YRCSGuardians::whereLast($familyName)->count();
            if ($f === 0) {
                r("continuing, nothing found for " . $familyName, $first, $last);
                continue;
            }
            if ($f === 1) {
                r("found one, returning");
                return YRCSGuardians::whereLast($familyName)->first()->family_hash_id;
            }
            if ($f > 1) {
                r(["found more than one famname:", $f]);
                $all = YRCSGuardians::whereLast($familyName)->get();
                $found = [];
                foreach ($all as $a) {
                    $found[$a->family_hash_id] = '';
                }
                if (count($found) > 1) {
                    r(["found more than one again:", $found]);
                    foreach ($fnames as $first) {
                        r(["checking firsts ", $first]);
                        $c = YRCSGuardians::whereFirst($first)->whereLast($familyName)->count();
                        r([$first, $familyName, $c]);
                        if ($c === 1) {
                            $a = YRCSGuardians::whereFirst($first)->whereLast($familyName)->first();
                            return $a->family_hash_id;
                        }
                    }

                    foreach ($lnames as $last) {
                        r(["checking lasts ", $last]);
                        $c = YRCSGuardians::whereFirst($first)->whereLast($last)->count();
                        r([$first, $last, $c]);
                        if ($c === 1) {
                            $a = YRCSGuardians::whereFirst($first)->whereLast($last)->first();
                            return $a->family_hash_id;
                        }
                    }
                    r('more than one family matches ' . $familyName . ': ' . print_r($found, true));
//                    return 'more than one family matches ' . $familyName . ': ' . print_r($found, true);
                }
                r(["found, returning:", $found]);
                return $a->family_hash_id;
            }
        }
        r('not found');
        return 'not found';

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
}
