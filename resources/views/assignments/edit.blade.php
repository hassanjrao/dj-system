@extends('layouts.backend')
@section('page-name', 'Edit' . $assignment->name . ' Assignment')
@section('css_after')
    <link href="{{ mix('/css/vuetify.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div id="vue-app">

        <div class="content">
            <assignment-form :model-value="{{ json_encode($assignment) }}" :is-edit="true"
                :assignment-data="{{ json_encode($assignment) }}"
                :is-admin="{{ auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin') ? 'true' : 'false' }}">
            </assignment-form>
        </div>
    </div>
@endsection

@section('js_after')
@endsection
