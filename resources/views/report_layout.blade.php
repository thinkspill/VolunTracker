@extends('layout')
@section('content')
    <style>
        .container {
            max-width: 100%;
        }

        .page-break {
            page-break-after: always;
        }

        p, td, th {
            font-size: 15px;
        }

        .desc_para {
            font-size: 10px;
        }

        h1 {
            font-size: 30px;
        }

        h2 {
            font-size: 20px;
        }
    </style>
    <?php $c = 0;
    $a = array_merge($exceeds, $meets, $under, $none);
    ?>
    @foreach ($a as $f)
        <?php $c++; ?>
        <div class="row">
            <div class="col-sm-1">
                <img src="http://vol.dev/images/yrcs.png" alt="Yuba River Logo" style="width: 50px; float: left;">
            </div>
            <div class="col-sm-11">
                <div style="margin-left: 55px; margin-bottom: 2em;">
                    <h1 style="margin-top: .3em;">YRCS Family Volunteerism Report
                        <br><span style="font-size: 15px; font-weight: bold;">August through November, 2015</span>
                    </h1>
                </div>
                <p>Below are your recorded volunteer hours for the months of August through November 2015. As a reminder, all YRCS families agree to volunteer 5 hours per month, for a total of 20 hours at this point in the year. If your recorded hours look incorrect for any reason, please let us know.</p>
                <table class="table table-condensed">
                    <tr>
                        <th>
                            @if (count($f['guardians']) > 1)Guardians
                            @else
                                Guardian
                            @endif
                        </th>
                        <th>
                            @if (count($f['students']) > 1)Students
                            @else
                                Student
                            @endif
                        </th>
                        <th>Hours</th>
                        <th>% of Expected</th>
                    </tr>
                    <tr>
                        <td>
                            <ul style="list-style-type: none; list-style-position: outside; padding: 0;">
                                @foreach ($f['guardians'] as $g)
                                    <li>
                                        {{$g['first']}} {{$g['last']}}
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td>
                            <ul style="list-style-type: none; list-style-position: outside; padding: 0;">
                                @foreach ($f['students'] as $g)
                                    <li>
                                        {{$g['first']}} {{$g['last']}}
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td>
                            <p>{{$f['hours']}}</p>
                        </td>
                        <td><p>{{$f['ratio']}}%</p></td>
                    </tr>
                </table>

                @if ($f['ratio'] == 0)
                    <p>It would appear that your family has either not recorded their hours, or have not volunteered this year.</p>
                    {!! $improveMessage !!}
                @elseif ($f['ratio'] < 50)
                    <p>Thanks for your assistance! You've not quite reached your family's volunteer goal for this time period.</p>
                    {!! $improveMessage !!}
                @elseif ($f['ratio'] < 100)
                    <p>Thanks for your assistance! You've almost reached your family's volunteer goal for this time period.</p>
                    {!! $improveMessage !!}
                @elseif ($f['ratio'] == 100)
                    <p>You've reached your family's volunteer hours for this time period! Thank you so much for supporting your school.</p>
                @elseif ($f['ratio'] > 100)
                    <p>Wow! You've exceeded your family's volunteer hours for this time period! Thank you so much for supporting your school.</p>
                @endif
            </div>
        </div>
        <div class="page-break"></div>
    @endforeach
@endsection
