<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\TimeLog;
use App\YRCSFamilies;
use App\YRCSGuardians;
use App\YRCSStudents;
use Gbrock\Table\Table;
use League\Period\Period;
use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use ref;
use Stash\Driver\FileSystem;

class HomeController extends Controller
{
    public function __construct()
    {
        ref::config('expLvl', -1);
        ref::config('maxDepth', 0);

        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $logger = new Logger('log');
        $logger->pushHandler((new ErrorLogHandler())->setFormatter(new LineFormatter()));
        ErrorHandler::register($logger);
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
}
