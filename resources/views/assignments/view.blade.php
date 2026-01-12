@extends('layouts.backend')
@section('page-name', 'View ' . $assignment->assignment_name . ' Assignment')
@section('css_after')
    <link href="{{ mix('/css/vuetify.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div id="vue-app">

        <div class="content pb-5">
            <assignment-form :model-value="{{ json_encode($assignment) }}" :is-edit="true" :is-view-only="true"
                :assignment-data="{{ json_encode($assignment) }}">
            </assignment-form>
        </div>
    </div>
@endsection

@section('js_after')
@endsection
