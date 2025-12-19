@extends('layouts.backend')

@section('css_after')
    <link href="{{ mix('/css/vuetify.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Edit Assignment</h3>
            </div>
            <div class="block-content">
                <div id="assignment-form-app">
                    <assignment-form :model-value="{{ json_encode($assignment) }}"
                        :departments="{{ json_encode($departments) }}" :clients="{{ json_encode($clients ?? []) }}"
                        :available-users="{{ json_encode($users ?? []) }}"
                        :lookup-data="{{ json_encode($lookupData ?? []) }}" :is-edit="true"
                        :is-admin="{{ auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin') ? 'true' : 'false' }}">
                    </assignment-form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js_after')
    <script src="{{ mix('/js/app.js') }}"></script>
    <script>
        new Vue({
            el: '#assignment-form-app',
            vuetify: window.vuetifyInstance
        });
    </script>
@endsection
