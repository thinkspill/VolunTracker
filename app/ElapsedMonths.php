<?php

namespace App;

use League\Period\Period;

class ElapsedMonths
{
    public function elapsed()
    {
        //        $period = new Period('2015-08-01', '2016-11-30');
//        $now = date('F, Y');
//        $months_elapsed = 0;
//        $c = 0;
//        foreach ($period->getDatePeriod('1 MONTH') as $datetime) {
//            $c++;
//            echo "<br>$c";
//            $months_elapsed++;
//            if ($now === $datetime->format('F, Y')) {
//                break;
//            }
//        }
//        return $months_elapsed;

        return 4; // at this stage we are reporting on
        // specifically 4 months, so we dont need a dynamic calculation for now
    }
}
