@extends('layouts.backend')

@section('content')
<div class="content">
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Assignments</h3>
            <div class="block-options">
                <a href="{{ route('assignments.create') }}" class="btn btn-primary">Create Assignment</a>
            </div>
        </div>
        <div class="block-content">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Department</th>
                            <th>Song Name</th>
                            <th>Assigned To</th>
                            <th>Completion Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                            <tr>
                                <td>{{ $assignment->id }}</td>
                                <td>{{ $assignment->department->name ?? 'N/A' }}</td>
                                <td>{{ $assignment->song_name ?? $assignment->assignment_name ?? 'N/A' }}</td>
                                <td>{{ $assignment->assignedTo->name ?? 'N/A' }}</td>
                                <td>{{ $assignment->completion_date ? $assignment->completion_date->format('Y-m-d') : 'N/A' }}</td>
                                <td><span class="badge bg-{{ $assignment->status === 'completed' ? 'success' : ($assignment->status === 'in-progress' ? 'primary' : 'secondary') }}">{{ $assignment->status }}</span></td>
                                <td>
                                    <a href="{{ route('assignments.show', $assignment->id) }}" class="btn btn-sm btn-info">View</a>
                                    <a href="{{ route('assignments.edit', $assignment->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No assignments found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection


