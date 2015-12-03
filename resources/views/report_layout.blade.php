@extends('layout')
@section('content')
    <style>
        .container {
            max-width: 100%;
        }

        .page-break {
            page-break-after: always;
        }

        p, td, th, li {
            font-size: 14px;
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

        fieldset {
            border-radius: 50px;
            padding: 2em;
            padding-bottom: 1em;
            border: 1px solid #700;
            margin-bottom: 1em;
        }

        fieldset legend {
            width: 340px;
            margin-left: 1em;
            padding-left: 1em;
            color: #700;
            background-color: white;
            margin-bottom: -1em;
        }

        fieldset table.table {
            margin-bottom: 0;
        }

        fieldset table.table ul {
            margin-bottom: 0;
        }

        .return_address, .mailing_title {
            font-family: "Antropos";
        }

        .mailing_address
        {
            max-width: 300px; margin: 0 auto; padding-left: 2em; padding-top: 1em;
        }

        .mailing_title {
            font-size: 20px;
        }
    </style>
    <?php $c = 0;
    $a = array_merge($exceeds, $meets, $under, $none);
    ?>
    @foreach ($a as $f)
        <?php $c++; ?>
        <div class="row">
            <div class="col-sm-12">
                <img src="http://vol.dev/images/yrcs.png" alt="Yuba River Logo" style="width: 80px; float: right;">

                <div>
                    <h1 style="margin-top: .3em;">YRCS Family Volunteerism Report
                        <br><span style="font-size: 15px;">Report Period: 08/01/15 - 11/30/15</span>
                    </h1>
                </div>
                <p>This report gives you the background on your family's volunteer hours.<br>All families have committed to five volunteer hours each month per the school policy.</p>

                <fieldset>
                    <legend>School-wide performance</legend>
                    <div style="margin: 0 auto; width: 100%;">

                        <table class="table table-condensed">
                            <tr>
                                <th>Expected Hours</th>
                                <th>Volunteered Hours</th>
                                <th>% of Expected</th>
                            </tr>
                            <tr>
                                <td>
                                    <p class="card-text">{{$total_expected_hours}}</p>
                                </td>
                                <td>
                                    <p class="card-text">{{$total_hours}}</p>
                                </td>
                                <td>
                                    <p class="card-text">{{$ratio}}%</p>
                                </td>
                            </tr>
                        </table>

                    </div>
                </fieldset>

                <fieldset>
                    <legend>Your family's performance</legend>
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
                        <p>It would appear that your family has either not recorded hours, or has not volunteered this year.</p>
                    @elseif ($f['ratio'] < 50)
                        <p>Thanks for your support! You've not quite reached your volunteer goal for this time period.</p>
                    @elseif ($f['ratio'] < 100)
                        <p>Thanks for your support! You've almost reached your volunteer goal for this time period.</p>
                    @elseif ($f['ratio'] == 100)
                        <p>You've reached your family's volunteer hours for this time period! Thank you so much for supporting your school.</p>
                        <p></p>
                    @elseif ($f['ratio'] > 100)
                        <p>Wow! You've exceeded your family's volunteer hours for this time period! Thank you so much for supporting your school.</p>
                    @endif
                </fieldset>

                <fieldset>
                @if ($f['ratio'] == 0)
                    {!! $improveMessage !!}
                @elseif ($f['ratio'] < 50)
                    {!! $improveMessage !!}
                @elseif ($f['ratio'] < 100)
                    {!! $improveMessage !!}
                    @elseif ($f['ratio'] >= 100)
                        <p>
                            Above is an illustration of your family’s volunteerism level with Yuba River Charter
                            School. This report is intended to help families keep track of their volunteer
                            commitment and see how we are doing as a school.
                        </p>
                        <p>
                            This is a new system and we may have somehow overlooked your hours. Please email
                            yrcs.volunteer@gmail.com if this is the case.
                        </p>
                        <p>
                            Based on our information you are meeting/exceeding your family’s volunteer
                            commitment. Thank you for being an inspiration to other families. You are truly what
                            makes our school a success. Support like yours makes so much possible!
                        </p>
                </fieldset>
                @endif
            </div>
        </div>
        <div class="page-break"></div>
        <div style="height: 9in; width: 100%; padding: 0; margin: 0;">
            <div style="height: 45%;">
                <div class="return_address">
                    Yuba River Charter School
                    <br>505 Main Street
                    <br>Nevada City, CA 95959
                </div>
                <div class="mailing_address">
                    {!! $f['mailing_address'] !!}
                </div>
            </div>
            <div class="mailing_title text-center">
                <span style="font-size: 150%;">Volunteer Hours</span>
                <br>August - November
            </div>
        </div>
        <div class="page-break"></div>
    @endforeach
@endsection
