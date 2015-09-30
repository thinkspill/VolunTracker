<?php

namespace App\Http\Controllers\Home;

use App\TimeLog;
use App\YRCSFamilies;
use App\YRCSGuardians;
use App\YRCSStudents;
use Gbrock\Table\Table;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class FamilyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $students = YRCSStudents::whereFamilyHashId($id)->sorted()->paginate();

        $stu_table = (new Table)->create($students, ['first', 'last', 'family_hash_id']);
        $stu_table->setView('tablecondensed');

        $guardians = YRCSGuardians::whereFamilyHashId($id)->sorted()->paginate();
        $guardian_table = (new Table)->create($guardians, ['first', 'last', 'relationship', 'family_hash_id']);
        $guardian_table->setView('tablecondensed');

        $families = YRCSFamilies::whereFamilyHashId($id)->sorted()->paginate();
        $fam_table = (new Table)->create($families, ['family_hash_id']);
        $fam_table->setView('tablecondensed');

        $hours = TimeLog::whereFamilyHashId($id)->sorted()->paginate();
        $hours_table = (new Table)->create($hours, ['date', 'hours']);
        $hours_table->setView('tablecondensed');

        return view('family', [
            'fam_table' => $fam_table,
            'guardians_table' => $guardian_table,
            'students_table' => $stu_table,
            'hours_table' => $hours_table,
        ]);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
