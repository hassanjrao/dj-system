@extends('layouts.backend')
@section('page-name', 'Users')
@section('css_after')
    <link href="{{ mix('/css/vuetify.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div id="vue-app">
        <div class="content">
            <user-list />
        </div>
    </div>
@endsection

@section('js_after')
@endsection
