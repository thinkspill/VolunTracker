@extends('layout')
@section('content')



    <div class="row">
        <div class="col-sm-12">

            <br>
            <progress class="progress progress progress-danger" value="{{$ratio}}" max="100">{{$ratio}}%</progress>

            <div class="card-group">
                <div class="card">

                    <div class="card-block">
                        <h4 class="card-title">{{$student_count}}</h4>

                        <p class="card-text">Students</p>

                        <p class="card-text">
                            <small class="text-muted">Total students enrolled at YRCS</small>
                        </p>
                    </div>
                </div>
                <div class="card">

                    <div class="card-block">
                        <h4 class="card-title">{{$guardian_count}}</h4>

                        <p class="card-text">Guardians</p>

                        <p class="card-text">
                            <small class="text-muted">{{$mom_count}} Mothers, {{$dad_count}} Fathers, {{$stepmom_count}}
                                Stepmothers, {{$stepdad_count}} Stepfathers, {{$other_count}} Other, {{$grandmom_count}}
                                Grandmothers, {{$granddad_count}} Grandfathers, {{$emergency_count}} Emergency Contact
                            </small>
                        </p>
                    </div>
                </div>
                <div class="card">

                    <div class="card-block">
                        <h4 class="card-title">{{$family_count}}</h4>

                        <p class="card-text">Families</p>

                        <p class="card-text">
                            <small class="text-muted">Total family units</small>
                        </p>
                    </div>
                </div>
                <div class="card">

                    <div class="card-block">
                        <h4 class="card-title">{{$total_hours}}</h4>

                        <p class="card-text">Hours Volunteered</p>

                        <p class="card-text">
                            <small class="text-muted">Sum of all hours volunteered</small>
                        </p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-block">
                        <h4 class="card-title">{{$total_expected_hours}}</h4>

                        <p class="card-text">Expected Hours</p>

                        <p class="card-text">
                            <small class="text-muted">Expected sum of hours by this time in the year</small>
                        </p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-block">
                        <h4 class="card-title">{{$ratio}}%</h4>

                        <p class="card-text">% of Expected</p>

                        <p class="card-text">
                            <small class="text-muted">Percent of expected hours reached</small>
                            <br>
                            <br>
                            <progress class="progress progress progress-danger" value="{{$ratio}}" max="100">{{$ratio}}%</progress>
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <br>
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
            <h2>Families</h2>
            {!! $hours_table->render() !!}
        </div>
    </div>

    {{--<div class="row">--}}
    {{--<div class="col-sm-5">--}}
    {{--{!! $fam_table->render() !!}--}}
    {{--</div>--}}
    {{--<div class="col-sm-5">--}}
    {{--{!! $yrc_table->render() !!}--}}
    {{--</div>--}}
    {{--</div>--}}

@endsection