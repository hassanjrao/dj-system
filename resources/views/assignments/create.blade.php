@extends('layouts.backend')

@section('content')
<div class="content">
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Create Assignment</h3>
        </div>
        <div class="block-content">
            <p>Assignment creation form will be implemented with Vue components.</p>
            <a href="{{ route('assignments.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
</div>
@endsection

