<?php

use Illuminate\Database\Seeder;
use League\Csv\Reader;

class csv extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (! ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $csv = Reader::createFromPath(__DIR__ . '/yrcs.csv');
        $csv->setOffset(1); //because we don't want to insert the header

        $d = $csv->fetchAll();

        foreach ($d as $i)
        {
            print_r($i);
        }

        $nbInsert = $csv->each(function ($row) {
            print_r($row);
//            Do not forget to validate your data before inserting it in your database
//            $sth->bindValue(':firstname', $row[0], PDO::PARAM_STR);
//            $sth->bindValue(':lastname', $row[1], PDO::PARAM_STR);
//            $sth->bindValue(':email', $row[2], PDO::PARAM_STR);
//
//            return $sth->execute(); //if the function return false then the iteration will stop
        });

    }
}
