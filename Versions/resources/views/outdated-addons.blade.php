@extends('layout')

@section('content')
    <table class="table">
        <tr>
            <th>Addon</th>
            <th>Your Version</th>
            <th>Current Version</th>
            <th>Link to repo</th>
        </tr>
    @foreach ($addons as $addon)
        <tr>
            <td>{{ $addon->get('name') }}</td>
            <td>{{ $addon->get('version') }}</td>
            <td>{{ $addon->get('latest_version') }}</a></td>
            <td><a href="{{ $addon->get('url') }}">{{ $addon->get('url') }}</a>
        </tr>
    @endforeach
    </table>
@endsection