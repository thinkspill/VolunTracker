@extends('layout')
@section('content')
    <div class="row">
        <div class="col-sm-4">
            <h3>Meets/exceeds</h3>
            <table class="table table-condensed" id="meets">
                @foreach ($exceeds + $meets as $f)
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
                                        <b>Student</b>: {{$g['first']}} {{$g['last']}}
                                    </li>
                                @endforeach

                                <li><b>Hours</b>: {{$f['hours']}} ( {{$f['ratio']}} )</li>

                                {{--[ {{$f['family_hash']}} ]--}}
                            </ul>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
        <div class="col-sm-4">
            <h3>Under</h3>
            <table class="table table-condensed" id="under">
                @foreach ($under as $f)
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
                                        <b>Student</b>: {{$g['first']}} {{$g['last']}}
                                    </li>
                                @endforeach

                                <li><b>Hours</b>: {{$f['hours']}} ( {{$f['ratio']}} )</li>

                                {{--[ {{$f['family_hash']}} ]--}}
                            </ul>
                        </td>
                    </tr>
                @endforeach
            </table>

        </div>

        <div class="col-sm-4">
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

                                <li><b>Hours</b>: {{$f['hours']}} ( {{$f['ratio']}} )</li>

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
    <script>
        $('#meets').DataTable();
    </script>
@endsection
