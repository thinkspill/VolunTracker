@extends('layout')
@section('content')


    <div class="row">
        <div class="col-sm-6">
            <h2>Students</h2>
            <table class="table-condensed" id="students">
                <thead>
                <tr>
                    <td>Name</td>
                    <td>Family ID</td>
                </tr>
                </thead>
                <tbody>
                @foreach ($students as $s)
                    <tr>
                        <td>{{$s->first}} {{$s->last}}</td>
                        <td><a href="/family/{{$s->family_id}}">{{$s->family_id}}</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="col-sm-6">
            <h2>Guardians</h2>
            <table class="table-condensed" id="guardians">
                <thead>
                <tr>
                    <td>Name</td>
                    <td>Total Hours</td>
                    <td>Family ID</td>
                </tr>
                </thead>
                <tbody>
                @foreach ($guardians as $g)
                    <tr>
                        <td>{{$g->first}} {{$g->last}}</td>
                        <td>{{$g->family()->first()->hours()->sum('hours')}}</td>
                        <td><a href="/family/{{$g->family_id}}">{{$g->family_id}}</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6">

            <h2>Hours</h2>
            <table class="table-condensed" id="guardians">
                <thead>
                <tr>
                    <td>Total Hours</td>
                    <td>Family ID</td>
                </tr>
                </thead>
                <tbody>
                @foreach ($hours as $h)
                    <tr>
                        <td>{{$h->hours}}</td>
                        <td><a href="/family/{{$h->family_id}}">{{$h->family_id}}</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>

        </div>
    </div>

@endsection
@section('footer_scripts')
    <script>
        $('table').DataTable();
    </script>
@endsection
