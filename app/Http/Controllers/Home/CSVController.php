<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\YubaRiver;
use Illuminate\Database\QueryException;
use League\Csv\Reader;
use Stash\Driver\FileSystem;

class CSVController extends Controller
{
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
}
