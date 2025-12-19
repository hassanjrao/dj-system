@extends('layouts.backend')

@section('css_after')
    <link href="{{ mix('/css/vuetify.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div id="vue-app">

        <div class="content">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Create Assignment</h3>
                </div>
                <div class="block-content">
                    <assignment-form :departments="{{ json_encode($departments) }}"
                        :clients="{{ json_encode($clients ?? []) }}" :available-users="{{ json_encode($users ?? []) }}"
                        :lookup-data="{{ json_encode($lookupData ?? []) }}"
                        :is-admin="{{ auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin') ? 'true' : 'false' }}">
                    </assignment-form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js_after')
@endsection
