<?php

use App\Person;
use Illuminate\Database\Seeder;

class TimeLog extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $total_volunteers = 125;
        while ($total_volunteers > 0) {
            $v = Person::all()->random(1);
            $log = \App\TimeLog::create([
                'person_id' => $v->id,
                'hours' => rand(1, 7),
                'date' => $this->randomDate('2015-06-01', '2016-09-01')
            ]);
            $log->save();
            $total_volunteers--;
        }
    }

    private function randomDate($start_date, $end_date)
    {
        // Convert to timetamps
        $min = strtotime($start_date);
        $max = strtotime($end_date);

        // Generate random number using above bounds
        $val = rand($min, $max);

        // Convert back to desired date format
        return date('Y-m-d H:i:s', $val);

    }
}
