@extends('layout')
@section('content')

    <div class="row">
        <div class="col-sm-4">
            <h2>Students</h2>
            {!! $students_table->render() !!}
        </div>
        <div class="col-sm-5">
            <h2>Guardians</h2>
            {!! $guardians_table->render() !!}
        </div>
        <div class="col-sm-3">
            <h2>Families</h2>
            {!! $fam_table->render() !!}
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6">
            <h2>Hours Volunteered</h2>

            {!! $hours_table->render() !!}
        </div>
    </div>

@endsection