@extends('layout')

@section('content')
    <table class="table">
        <tr>
            <th>Addon</th>
            <th>Your Version</th>
            <th>Current Version</th>
        </tr>
    @foreach ($addons as $addon)
        <tr>
            <td>{{ $addon->get('name') }}</td>
            <td>{{ $addon->get('version') }}</td>
            <td><a href="{{ $addon->get('url') }}">{{ $addon->get('latest_version') }}</a></td>
        </tr>
    @endforeach
    </table>
@endsection