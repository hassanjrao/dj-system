@extends('layouts.backend')
@section('page-name', 'Create Assignment')
@section('css_after')
    <link href="{{ mix('/css/vuetify.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div id="vue-app">

        <div class="content">
            <assignment-form :is-edit="false">
            </assignment-form>
        </div>
    </div>
@endsection

@section('js_after')
@endsection
