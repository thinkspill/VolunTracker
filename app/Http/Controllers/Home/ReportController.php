<?php

namespace App\Http\Controllers\Home;

use App;
use App\TimeLog;
use App\YRCSFamilies;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use League\Period\Period;
use PDF;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        list($none, $under, $meets, $exceeds) = $this->generateReports();

//        dd($exceeds, $meets, $under, $none);

        return view('report', [
            'exceeds' => $exceeds,
            'meets' => $meets,
            'under' => $under,
            'none' => $none,
        ]);
    }


    public function pdf()
    {
        list($none, $under, $meets, $exceeds) = $this->generateReports();

//        ~r($none, $under, $meets, $exceeds);

//        ~r(count($none), count($under), count($meets), count($exceeds));

        /** @var \DOMPDF $pdf */
        $pdf = PDF::loadView('printable', [
            'exceeds' => $exceeds,
            'meets' => $meets,
            'under' => $under,
            'none' => $none,
        ]);

        ini_set('max_execution_time', 0);
        return $pdf->stream('test.pdf');
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

    private function hasDelimiter($maybe_delimited_string, $delimiter)
    {
        return strpos($maybe_delimited_string, $delimiter) !== false;
    }

    /**
     * @return array
     */
    private function generateReports()
    {
        $expected_hours = 5 * $this->months_elapsed();

        $all = YRCSFamilies::all();

        $exceeds = $meets = $under = $none = [];

        foreach ($all as $f) {
            /** @var HasMany $hours */
            /** @var YRCSFamilies $f */
            $hours = $f->hours();
            $hoursLoaded = $hours->getEager();
            if (!count($hoursLoaded)) {
                $d = [
                    'family_id' => $f->family_id,
                    'guardians' => $f->guardians()->get(['first', 'last', 'relationship'])->toArray(),
                    'students' => $f->students()->get(['first', 'last'])->toArray(),
                    'hours' => 0,
                    'expected' => $expected_hours,
                    'ratio' => '0%'
                ];

                $d = $this->generateGreeting($d);

                $none[] = $d;
            } else {
                $fam_sum = 0;
                foreach ($hoursLoaded as $hour) {
                    /** @var TimeLog $hour */
                    $fam_sum += $hour->hours;
                }
                if ($fam_sum > $expected_hours) {
                    $exceeds[] = $this->buildFamArray($f, $fam_sum, $expected_hours);
                }
                if ($fam_sum === $expected_hours) {
                    $meets[] = $this->buildFamArray($f, $fam_sum, $expected_hours);
                }
                if ($fam_sum < $expected_hours) {
                    $under[] = $this->buildFamArray($f, $fam_sum, $expected_hours);
                }
            }
        }
        return array($none, $under, $meets, $exceeds);
    }

    /**
     * @param $d
     * @return mixed
     */
    private function generateGreeting($d)
    {

        if (count($d['guardians']) === 0)
        {
            $d['guardian_name_greeting'] = 'Parents &amp; Guardians';
            return $d;
        }

        $gnames = [];

        foreach ($d['guardians'] as $g) {
            if ($this->hasDelimiter($g['first'], ' ')) {
                $gnames[] = explode(' ', $g['first'])[0];
            } else {
                $gnames[] = $g['first'];
            }
        }

        $count_names = count($gnames);

        switch ($count_names) {
            case 1:
                $d['guardian_name_greeting'] = $gnames[0];
                break;
            case 2:
                $d['guardian_name_greeting'] = $gnames[0] . " and " . $gnames[1];
                break;
            case 3:
                $d['guardian_name_greeting'] = $gnames[0] . ", " . $gnames[1] . " and " . $gnames[2];
                break;
            case 4:
                $d['guardian_name_greeting'] = $gnames[0] . ", " . $gnames[1] . ", " . $gnames[2] . " and " . $gnames[3];
                break;
        }
        return $d;
    }

    /**
     * @param $f
     * @param $fam_sum
     * @param $expected_hours
     * @return array|mixed
     */
    private function buildFamArray($f, $fam_sum, $expected_hours)
    {
        $d = [
            'family_id' => $f->family_id,
            'guardians' => $f->guardians()->get(['first', 'last', 'relationship'])->toArray(),
            'students' => $f->students()->get(['first', 'last'])->toArray(),
            'hours' => $fam_sum,
            'expected' => $expected_hours,
            'ratio' => round(($fam_sum / $expected_hours), 2) * 100
        ];

        $d = $this->generateGreeting($d);
        return $d;
    }
}
