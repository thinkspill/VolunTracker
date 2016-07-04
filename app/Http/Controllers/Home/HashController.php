<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\YRCSGuardians;
use App\YRCSStudents;
use App\YubaRiver;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;

class HashController extends Controller
{
    public function __construct()
    {
        $logger = new Logger('log');
        $logger->pushHandler((new ErrorLogHandler())->setFormatter(new LineFormatter()));
        ErrorHandler::register($logger);
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
                    'first' => $u->student_first, 'last' => $u->student_last,
                ],
                [
                    'first' => $u->student_first, 'last' => $u->student_last,
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
            /* @var YubaRiver $u */
            try {
                $p = YRCSGuardians::updateOrCreate(
                    [
                        'first' => $u->parent_first, 'last' => $u->parent_last,
                    ],
                    [
                        'first' => $u->parent_first, 'last' => $u->parent_last, 'relationship' => $u->relationship,
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
            /** @var Collection $families */
            $families = YubaRiver::whereStudentFirst($student->first)->whereStudentLast($student->last)->get(['parent_first', 'parent_last']);
            foreach ($families as $family) {
                /* @var YubaRiver $family */
                /** @var Collection $res */
                /* @var YRCSGuardians $guardian */
                $res = YRCSGuardians::whereFirst($family->parent_first)->whereLast($family->parent_last)->get(['id']);
                $guardian = $res->first();
                try {
                    $student->guardians()->attach($guardian->id);
                } catch (\Exception $e) {
                    r($e->getMessage());
                }
            }
        }
    }
}
