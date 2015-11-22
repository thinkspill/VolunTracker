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
        $students = YRCSStudents::sorted()->orderBy('family_id')->get();
        $families = YRCSFamilies::sorted()->orderBy('id')->get();
        $guardians = YRCSGuardians::get();
        $studentCount = YRCSStudents::all()->count();
        $guardianCount = YRCSGuardians::all()->count();
        $familyCount = YRCSFamilies::all()->count();

        $totalHours = TimeLog::all()->sum('hours');
        $expectedMonthlyHours = 5 * $familyCount;
        $totalExpectedHours = $this->calcExpectedHours($expectedMonthlyHours);

        return view('tables', [
            'families' => $families,
            'guardians' => $guardians,
            'students' => $students,
//            'hours_table' => $hours_table,
            'student_count' => $studentCount,
            'guardian_count' => $guardianCount,
            'family_count' => $familyCount,
            'total_hours' => $totalHours,
            'total_expected_hours' => $totalExpectedHours,
            'ratio' => round($totalHours / $totalExpectedHours, 4) * 100,
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

    private function calcExpectedHours($expectedMonthlyHours)
    {
        $monthsElapsed = $this->monthsElapsed();
        $totalExpectedHours = $expectedMonthlyHours * $monthsElapsed;
        return $totalExpectedHours;
    }

    private function monthsElapsed()
    {
        $period = new Period('2015-08-19', '2016-06-03');
        $now = date('F, Y');
        $monthsElapsed = 0;
        foreach ($period->getDatePeriod('1 MONTH') as $datetime) {
            $monthsElapsed++;
            if ($now === $datetime->format('F, Y')) {
                break;
            }
        }
        return $monthsElapsed;
    }
}
