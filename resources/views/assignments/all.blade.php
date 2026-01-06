@extends('layouts.backend')
@section('page-name', 'All Assignments')
@section('css_after')
    <link href="{{ mix('/css/vuetify.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div id="vue-app">
        <div class="content">
            <assignment-list :department-id="{{ $departmentId ?? 'null' }}" scope="all" />
        </div>
    </div>
@endsection

@section('js_after')
@endsection
