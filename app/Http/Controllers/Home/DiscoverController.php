<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\YRCSFamilies;
use App\YRCSGuardians;
use App\YRCSStudents;
use Fhaculty\Graph\Graph;
use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Stash\Driver\FileSystem;

class DiscoverController extends Controller
{
    public function __construct()
    {
        $logger = new Logger('log');
        $logger->pushHandler((new ErrorLogHandler())->setFormatter(new LineFormatter()));
        ErrorHandler::register($logger);
    }

    public function discoverFamilyUnits()
    {
        $school['students'] = [];
        $school['guardians'] = [];
        $graph = new Graph();
        $guardians = YRCSGuardians::all();
//        $a = $guardians->random(20);
        foreach ($guardians as $guardian) {
            $family_unit = $this->initFamilyUnit($guardian);
            list($guardian_name, $school) = $this->addInitialGuardian($guardian, $graph, $school);
            foreach ($guardian->students()->get() as $student) {
                list($family_unit, $school) = $this->findThisGuardiansStudents($student, $family_unit, $graph, $school, $guardian);
                list($family_unit, $school) = $this->findOtherGuardiansAndTheirStudents($student, $guardian_name, $family_unit, $graph, $school);
            }

            $this->saveFamilyUnit($family_unit);
        }

//        $graphviz = new GraphViz();
//        $data = $graphviz->createImageData($graph);
//        $image = \imagecreatefromstring($data);
//        header('Content-Type: image/png');
//        \imagepng($image);
//        \imagedestroy($image);
//        exit;

    }

    /**
     * @param $guardian
     * @return array
     */
    private function initFamilyUnit($guardian)
    {
        $family_unit['guardians'] = [$guardian->id];
        $family_unit['students'] = [];
        return $family_unit;
    }

    /**
     * @param $guardian YRCSGuardians
     * @param $graph Graph
     * @param $school
     * @return array
     */
    private function addInitialGuardian($guardian, $graph, $school)
    {
        $guardian_name = $guardian->first . ' ' . $guardian->last;
        $school['guardians'][$guardian->id] = $graph->createVertex($guardian_name, true);
        $school['guardians'][$guardian->id]->setAttribute('graphviz.color', 'red');
        return array($guardian_name, $school);
    }

    /**
     * @param $student YRCSStudents
     * @param $family_unit
     * @param $graph Graph
     * @param $school array
     * @param $guardian YRCSGuardians
     * @return array
     */
    private function findThisGuardiansStudents($student, $family_unit, $graph, $school, $guardian)
    {
        if (!in_array($student->id, $family_unit['students'])) {
            $family_unit['students'][] = $student->id;
        }
        $student_name = $student->first . ' ' . $student->last;
        $school['students'][$student->id] = $graph->createVertex($student_name, true);
        $school['students'][$student->id]->setAttribute('graphviz.color', 'green');
        $edge = $school['guardians'][$guardian->id]->createEdgeTo($school['students'][$student->id]);
        $this->setRelationshipEdgeColor($guardian, $edge);
        return array($family_unit, $school);
    }

    /**
     * @param $guardian YRCSGuardians
     * @param $edge
     */
    private function setRelationshipEdgeColor($guardian, $edge)
    {
        $rel = $guardian->relationship;
        switch ($rel) {
            case 'Father':
                $color = 'blue';
                break;
            case 'Stepfather':
                $color = 'orange';
                break;
            case 'Grandfather':
                $color = 'brown';
                break;
            case 'Mother':
                $color = 'purple';
                break;
            case 'Stepmother':
                $color = 'yellow';
                break;
            case 'Grandmother':
                $color = 'black';
                break;
            default:
                $color = 'gray';
                break;
        }
        $edge->setAttribute('graphviz.color', $color);
    }

    /**
     * @param $student
     * @param $guardian_name
     * @param $family_unit
     * @param $graph
     * @param $school
     * @return array
     */
    private function findOtherGuardiansAndTheirStudents(YRCSStudents $student, $guardian_name, $family_unit, $graph, $school)
    {
        $other_guardians = $student->guardians()->get();
        foreach ($other_guardians as $other_guardian) {
            $gname = $other_guardian->first . ' ' . $other_guardian->last;
            if ($gname === $guardian_name) {
                continue;
            }
            if (!in_array($other_guardian->id, $family_unit['guardians'])) {
                $family_unit['guardians'][] = $other_guardian->id;
            }
            $school['guardians'][$other_guardian->id] = $graph->createVertex($gname, true);
            $school['guardians'][$other_guardian->id]->setAttribute('graphviz.color', 'red');
            $edge = $school['guardians'][$other_guardian->id]->createEdgeTo($school['students'][$student->id]);
            $this->setRelationshipEdgeColor($other_guardian, $edge);
        }
        return array($family_unit, $school);
    }

    private function saveFamilyUnit($family_unit)
    {
        $fam = false;
        foreach ($family_unit['guardians'] as $g) {
            /** @var YRCSGuardians $guardian */
            $guardian = YRCSGuardians::find($g);
            if (!isset($guardian->family()->getResults()->id)) {
                continue;
            } else {
                $fam = $guardian->family()->getResults()->id;
            }
        }

        if (!$fam) {
            $fam = YRCSFamilies::create();
            $fam->save();
        }

        foreach ($family_unit['guardians'] as $g) {
            /** @var YRCSGuardians $guardian */
            $guardian = YRCSGuardians::find($g);
            if (!isset($guardian->family()->getResults()->id)) {
                $guardian->family()->associate($fam);
                $guardian->save();
            }
        }
        foreach ($family_unit['students'] as $s) {
            /** @var YRCSStudents $student */
            $student = YRCSStudents::find($s);
            if (!isset($student->family()->getResults()->id)) {
                $student->family()->associate($fam);
                $student->save();
            }
        }
        r($family_unit);
    }
}
