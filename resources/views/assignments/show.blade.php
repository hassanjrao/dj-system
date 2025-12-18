@extends('layouts.backend')

@section('content')
<div class="content">
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Assignment Details #{{ $assignment->id }}</h3>
        </div>
        <div class="block-content">
            <dl class="row">
                <dt class="col-sm-3">Department:</dt>
                <dd class="col-sm-9">{{ $assignment->department->name ?? 'N/A' }}</dd>
                
                <dt class="col-sm-3">Song Name:</dt>
                <dd class="col-sm-9">{{ $assignment->song_name ?? 'N/A' }}</dd>
                
                <dt class="col-sm-3">Status:</dt>
                <dd class="col-sm-9">{{ $assignment->status }}</dd>
                
                <dt class="col-sm-3">Completion Date:</dt>
                <dd class="col-sm-9">{{ $assignment->completion_date ? $assignment->completion_date->format('Y-m-d') : 'N/A' }}</dd>
            </dl>
            <a href="{{ route('assignments.index') }}" class="btn btn-secondary">Back to List</a>
            <a href="{{ route('assignments.edit', $assignment->id) }}" class="btn btn-primary">Edit</a>
        </div>
    </div>
</div>
@endsection

