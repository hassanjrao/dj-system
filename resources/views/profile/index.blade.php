@extends('layouts.backend')
@section('page-name', 'Profile')
@section('css_after')
    <link href="{{ mix('/css/vuetify.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div id="vue-app">
        <div class="content">
            <profile-update :user="{{ json_encode($user) }}" />
        </div>
    </div>
@endsection

@section('js_after')
@endsection
