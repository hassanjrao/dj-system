@extends('layouts.backend')
@section('page-name', 'Assignments')
@section('css_after')
    <link href="{{ mix('/css/vuetify.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div id="vue-app">
        <div class="content">
            <assignment-list :department-id="{{ $departmentId ?? 'null' }}" />
        </div>
    </div>
@endsection

@section('js_after')
@endsection
