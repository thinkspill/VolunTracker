<?php

namespace App\Http\Controllers\Home;

use App;
use App\ElapsedMonths;
use App\TimeLog;
use App\YRCSFamilies;
use DB;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Http\Requests;
use App\Http\Controllers\Controller;
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
        $familyCount = YRCSFamilies::all()->count();
//        $totalHours = TimeLog::all()->sum('hours');
        $totalHours = (int) Db::table('time_logs')->whereBetween('date',['2015-08-01', '2015-12-01'])->sum('hours');
        $expectedMonthlyHours = 5 * $familyCount;
        $totalExpectedHours = $this->calcExpectedHours($expectedMonthlyHours);

        list($none, $under, $meets, $exceeds) = $this->generateReports();
        return view('printable', [
            'exceeds' => $exceeds,
            'meets' => $meets,
            'under' => $under,
            'none' => $none,
            'improveMessage' => $this->howToImproveMessage(),
            'total_hours' => $totalHours,
            'total_expected_hours' => $totalExpectedHours,
            'ratio' => round($totalHours / $totalExpectedHours, 4) * 100,
        ]);
    }

    private function calcExpectedHours($expectedMonthlyHours)
    {
        $monthsElapsed = $this->monthsElapsed();
        $totalExpectedHours = $expectedMonthlyHours * $monthsElapsed;
        return $totalExpectedHours;
    }

    private function howToImproveMessage()
    {
        ob_start();
        ?>
        <p style="font-weight: bold;">Here are some ways to improve your family's volunteerism report:</p>
        <ol>
            <li>
                You may need to &nbsp;<b>volunteer more often</b>. If you need volunteer hours, please ask your class teacher, class parent or PC rep how you can help out. Volunteer needs are also posted in the <i>Current</i>. Each family is asked to volunteer 5 hours per month.
            </li>
            <li>
                Many people volunteer but forget to &nbsp;<b>log volunteer hours</b>. Please be sure to log your hours on the school website: <b>yubariverschool.org/volunteers</b>
            </li>
            <li>
                This is a new system and &nbsp;<b>we may have overlooked your hours.</b> &nbsp;Please email <b>yrcs.volunteer@gmail.com</b> if this is the case.
            </li>
        </ol>
        <?php

        return ob_get_clean();

    }

    public function test(PDF $pdf)
    {
        return PDF::loadFile('http://vol.dev/report')->stream('yrcs-report.pdf');
    }

    public function pdf()
    {
        return PDF::loadFile('http://vol.dev/report')->stream('yrcs-report.pdf');
    }

    /**
     * @return int
     */
    private function monthsElapsed()
    {
        $m = new ElapsedMonths();
        return $m->elapsed();
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
        $expected_hours = 5 * $this->monthsElapsed();

        $all = YRCSFamilies::all();

        $exceeds = $meets = $under = $none = [];

        $c = 0;
        foreach ($all as $f) {
//            if ($c > 10) {
//                break;
//            }
            /** @var YRCSFamilies $f */
            /** @var HasMany $hours */
            $hours = $f->hours();
            $hoursLoaded = $hours->getEager();
            if (!count($hoursLoaded)) {
                $d = [
                    'family_id' => $f->family_id,
                    'guardians' => $f->guardians()->get(['first', 'last', 'relationship'])->toArray(),
                    'students' => $f->students()->get(['first', 'last'])->toArray(),
                    'mailing_address' => $this->lookupMailingAddress($f->students()->get(['first', 'last'])->first()),
                    'hours' => 0,
                    'expected' => $expected_hours,
                    'ratio' => '0',
                    'count' => $c
                ];

                $d = $this->generateGreeting($d);

                $none[] = $d;
            } else {
                $fam_sum = 0;
                $log = [];
                foreach ($hoursLoaded as $hour) {
                    if ($hour->date < '2015-08-01' || $hour->date > '2015-12-01') {
//                        echo $hour->date . ' skipping';
//                        exit;
                        continue;
                    }
                    /** @var TimeLog $hour */
                    $fam_sum += $hour->hours;
                    $log[] = ['date' => $hour->date, 'hours' => $hour->hours];

                }

                if ($fam_sum > $expected_hours) {
                    $exceeds[] = $this->buildFamArray($f, $fam_sum, $expected_hours, $log);
                }
                if ($fam_sum === $expected_hours) {
                    $meets[] = $this->buildFamArray($f, $fam_sum, $expected_hours, $log);
                }
                if ($fam_sum < $expected_hours) {
                    $under[] = $this->buildFamArray($f, $fam_sum, $expected_hours, $log);
                }
                $c++;
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

        if (count($d['guardians']) === 0) {
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
    private function buildFamArray($f, $fam_sum, $expected_hours, $log)
    {
        $d = [
            'family_id' => $f->family_id,
            'guardians' => $f->guardians()->get(['first', 'last', 'relationship'])->toArray(),
            'students' => $f->students()->get(['first', 'last'])->toArray(),
            'hours' => $fam_sum,
            'expected' => $expected_hours,
            'mailing_address' => $this->lookupMailingAddress($f->students()->get(['first', 'last'])->first()),
            'ratio' => round(($fam_sum / $expected_hours), 2) * 100,
            'log' => $log
        ];

        $d = $this->generateGreeting($d);
        return $d;
    }

    private function lookupMailingAddress(App\YRCSStudents $student)
    {
        $first = $student->first;
        $last = $student->last;

        $addr = DB::table('yuba_river')
            ->select('parent_first', 'parent_last', 'address', 'city', 'state', 'zip')
            ->where('student_first', $first)
            ->where('student_last', $last)
            ->where('child_lives_with', 't')
            ->first();

        if (isset($addr->parent_first, $addr->parent_last, $addr->address, $addr->city, $addr->state, $addr->zip)) {
            return "{$addr->parent_first} {$addr->parent_last}<br>{$addr->address}<br>{$addr->city} {$addr->state} {$addr->zip}";
        } elseif (isset($addr->parent_first, $addr->parent_last)) {
            return "{$addr->parent_first} {$addr->parent_last}<br><br>";
        } else return '';
    }
}
