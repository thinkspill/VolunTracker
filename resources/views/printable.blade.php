@extends('layout')
@section('content')
<style>
    .page-break {
        page-break-after: always;
    }
</style>
@foreach ($exceeds + $meets as $f)
    <div class="row">
        <div class="col-sm-12">
            <p>Hello {{$f['guardian_name_greeting']}},</p>

            <p>We're writing to thank you today for meeting or exceeding your volunteer hours to YRCS. Gold star!</p>

            <p>As of this time in the year, each family is expected to have contributed {{$f['expected']}} hours. Your family has contributed {{$f['hours']}} hours, for a ratio of {{$f['ratio']}}%.</p>

            <p>Sincerely,</p>

            <p>YRCS</p>

        </div>
    </div>
    <div class="page-break"></div>
@endforeach

@foreach ($under as $f)
    <div class="row">
        <div class="col-sm-12">
            <p>Hello {{$f['guardian_name_greeting']}},</p>

            <p>We're writing to let you know that you are missing a few volunteer hours. Don't do better, <b>be</b> better.</p>

            <p>As of this time in the year, each family is expected to have contributed {{$f['expected']}} hours. Your family has contributed {{$f['hours']}} hours, for a ratio of {{$f['ratio']}}.</p>

            <p>Sincerely,</p>

            <p>YRCS</p>

        </div>
    </div>
    <div class="page-break"></div>
@endforeach

@foreach ($none as $f)
    <?php if (empty($f['guardian_name_greeting'])) dd($f); ?>
    <div class="row">
        <div class="col-sm-12">
            <p>Hello {{$f['guardian_name_greeting']}},</p>

            <p>We're writing to let you know that we know you haven't contributed any volunteer hours this year. None. Zip. Zilch. Zero.</p>

            <p>As of this time in the year, each family is expected to have contributed {{$f['expected']}} hours. Your family has contributed {{$f['hours']}} hours, for a ratio of {{$f['ratio']}}.</p>

            <p>Sincerely,</p>

            <p>YRCS</p>

        </div>
    </div>
    <div class="page-break"></div>
@endforeach
@endsection