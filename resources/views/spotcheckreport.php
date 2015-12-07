@extends('layout')
@section('content')
    <style>
        .container {
            max-width: 100%;
        }
    </style>
    <div class="row">
        <div class="col-sm-12">
            <h3>Meets/exceeds</h3>
            <table class="table table-condensed" id="meets">
                @foreach (array_merge($exceeds, $meets) as $f)
                    <tr>
                        <td>
                            <div class="row">
                                <div class="col-sm-6">
                                    <ul>
                                        <li>
                                            <b>Guardians</b>:
                                            @foreach ($f['guardians'] as $g)
                                                [{{$g['first']}} {{$g['last']}}]
                                            @endforeach
                                        </li>

                                        <li>
                                            <b>Students</b>:
                                            @foreach ($f['students'] as $g)
                                                [{{$g['first']}} {{$g['last']}}]
                                            @endforeach
                                        </li>

                                        <li><b>Hours</b>: {{$f['hours']}} ( {{$f['ratio']}}% )</li>

                                        {{--[ {{$f['family_hash']}} ]--}}
                                    </ul>
                                </div>
                                <div class="col-sm-6">
                                    <ul>
                                        @foreach ($f['log'] as $log)
                                            <li>{{$log['date']}}: <b>{{$log['hours']}}</b></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>


                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <h3>Under</h3>
            <table class="table table-condensed" id="under">
                @foreach ($under as $f)
                    <tr>
                        <td>
                            <div class="row">
                                <div class="col-sm-6">
                                    <ul>
                                        <li>
                                            <b>Guardians</b>:
                                            @foreach ($f['guardians'] as $g)
                                                [{{$g['first']}} {{$g['last']}}]
                                            @endforeach
                                        </li>

                                        <li>
                                            <b>Students</b>:
                                            @foreach ($f['students'] as $g)
                                                [{{$g['first']}} {{$g['last']}}]
                                            @endforeach
                                        </li>

                                        <li><b>Hours</b>: {{$f['hours']}} ( {{$f['ratio']}}% )</li>

                                        {{--[ {{$f['family_hash']}} ]--}}
                                    </ul>
                                </div>
                                <div class="col-sm-6">
                                    <ul>
                                        @foreach ($f['log'] as $log)
                                            <li>{{$log['date']}}: <b>{{$log['hours']}}</b></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>


                        </td>
                    </tr>
                @endforeach
            </table>

        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <h3>None</h3>

            <table class="table table-condensed" id="none">
                @foreach ($none as $f)
                    <tr>
                        <td>
                            <ul>
                                @foreach ($f['guardians'] as $g)
                                    <li>
                                        <b>Guardian</b>: {{$g['first']}} {{$g['last']}}
                                    </li>
                                @endforeach

                                @foreach ($f['students'] as $g)
                                    <li>
                                        Student: {{$g['first']}} {{$g['last']}}
                                    </li>
                                @endforeach

                                <li><b>Hours</b>: {{$f['hours']}} ( {{$f['ratio']}}% )</li>

                                {{--[ {{$f['family_hash']}} ]--}}
                            </ul>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

@endsection

@section('footer_scripts')
@endsection
