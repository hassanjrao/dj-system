<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\Department;
use App\Models\MusicTypeCompletionDay;
use App\Models\Deliverable;
use App\Models\AssignmentArtist;
use App\Models\AssignmentRelationship;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Assignment::with([
            'client', 'department', 'assignedTo', 'album',
            'musicType', 'musicKey', 'musicGenre', 'musicCreationStatus',
            'editType', 'footageType', 'deliverables', 'artists'
        ]);

        // Permission-based filtering
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            // Full access
        } elseif ($user->hasRole('view-all-edit-assigned') || $user->hasRole('view-all-update-assigned')) {
            // Hide Music Creation department
            $musicCreationDept = Department::where('slug', 'music-creation')->first();
            if ($musicCreationDept) {
                $query->where('department_id', '!=', $musicCreationDept->id);
            }
        } else {
            // Only user's department
            if ($user->department_id) {
                $query->where('department_id', $user->department_id);
            } else {
                $assignments = collect([]);
                return view('assignments.index', compact('assignments'));
            }
        }

        // Filter by department
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Show unfinished deliverables
        if ($request->has('unfinished') && $request->unfinished) {
            $query->whereHas('deliverables', function($q) {
                $q->where('assignment_deliverables.status', '!=', 'completed');
            });
        }

        // Order by completion date
        $query->orderBy('completion_date', 'asc');

        $assignments = $query->get();
        
        // Add days remaining
        $assignments->each(function($assignment) {
            if ($assignment->completion_date) {
                $assignment->days_remaining = Carbon::parse($assignment->completion_date)->diffInDays(Carbon::now(), false);
            }
        });

        // Return JSON for API requests, view for web requests
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json($assignments);
        }

        return view('assignments.index', compact('assignments'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->get();
        return view('assignments.create', compact('departments'));
    }

    public function edit($id)
    {
        $user = Auth::user();
        $assignment = Assignment::findOrFail($id);
        
        if (!$this->canEditAssignment($user, $assignment)) {
            abort(403);
        }
        
        $departments = Department::where('is_active', true)->get();
        return view('assignments.edit', compact('assignment', 'departments'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Check permissions
        if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
            if ($user->hasRole('view-all-edit-assigned')) {
                // Check if user can edit this department
                $deptId = $request->department_id;
                if ($user->department_id != $deptId) {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }
            } else {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        // Base validation
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'department_id' => 'required|exists:departments,id',
            'assigned_to_id' => 'nullable|exists:users,id',
            'assignment_name' => 'nullable|string|max:255',
            'completion_date' => 'nullable|date',
            'release_date' => 'nullable|date',
            'release_timing' => 'nullable|in:pre-release,post-release,other',
            'notes_for_team' => 'nullable|string',
            'reference_links' => 'nullable|string',
            'notes_for_admin' => 'nullable|string',
            'status' => 'nullable|in:pending,in-progress,completed,on-hold',
            'parent_assignment_id' => 'nullable|exists:assignments,id',
            'linked_song_assignment_id' => 'nullable|exists:assignments,id',
            'child_departments' => 'nullable|array', // For creating child assignments
        ]);

        // Department-specific validation
        $department = Department::findOrFail($request->department_id);
        
        if ($department->slug === 'music-creation') {
            $validated = array_merge($validated, $request->validate([
                'music_type_id' => 'required|exists:music_types,id',
                'song_name' => 'required|string|max:255',
                'version_name' => 'nullable|string|max:255',
                'album_id' => 'nullable|exists:albums,id',
                'bpm' => 'nullable|integer|min:1|max:999',
                'music_key_id' => 'nullable|exists:music_keys,id',
                'music_genre_id' => 'nullable|exists:music_genres,id',
                'music_creation_status_id' => 'nullable|exists:music_creation_statuses,id',
                'release_date' => 'required|date',
                'artists' => 'nullable|array',
                'artists.*' => 'string|max:255',
            ]));
        } elseif ($department->slug === 'music-mastering') {
            $validated = array_merge($validated, $request->validate([
                'linked_song_assignment_id' => 'required|exists:assignments,id',
                'deliverables' => 'nullable|array',
                'deliverables.*' => 'exists:deliverables,id',
            ]));
            
            // Auto-populate from linked song
            $linkedSong = Assignment::findOrFail($request->linked_song_assignment_id);
            $validated['song_name'] = $linkedSong->song_name;
            $validated['release_date'] = $linkedSong->release_date;
            $validated['music_type_id'] = $linkedSong->music_type_id;
            
            // Calculate completion date
            if (!$request->completion_date) {
                $validated['completion_date'] = $this->calculateCompletionDate(
                    $linkedSong->music_type_id,
                    $request->department_id,
                    $linkedSong->release_date
                );
            }
        } elseif ($department->slug === 'video-editing') {
            $validated = array_merge($validated, $request->validate([
                'edit_type_id' => 'nullable|exists:edit_types,id',
                'footage_type_id' => 'nullable|exists:footage_types,id',
                'deliverables' => 'nullable|array',
                'deliverables.*' => 'exists:deliverables,id',
            ]));
        }

        // Handle parent assignment
        if ($request->parent_assignment_id) {
            $validated['parent_assignment_id'] = $request->parent_assignment_id;
            // Auto-populate from parent if needed
            $parent = Assignment::findOrFail($request->parent_assignment_id);
            if (!$request->song_name && $parent->song_name) {
                $validated['song_name'] = $parent->song_name;
            }
            if (!$request->release_date && $parent->release_date) {
                $validated['release_date'] = $parent->release_date;
            }
            if (!$request->music_type_id && $parent->music_type_id) {
                $validated['music_type_id'] = $parent->music_type_id;
            }
        }

        // Create assignment
        $assignment = Assignment::create($validated);

        // Handle artists
        if ($request->has('artists') && is_array($request->artists)) {
            foreach ($request->artists as $artistName) {
                if (!empty(trim($artistName))) {
                    AssignmentArtist::firstOrCreate([
                        'assignment_id' => $assignment->id,
                        'artist_name' => trim($artistName),
                    ]);
                }
            }
        }

        // Handle deliverables
        if ($request->has('deliverables') && is_array($request->deliverables)) {
            $deliverableIds = $this->preSelectDeliverables(
                $request->music_type_id ?? $assignment->music_type_id,
                $request->department_id,
                $request->deliverables
            );
            $assignment->deliverables()->sync($deliverableIds);
        }

        // Create child assignments if specified
        if ($request->has('child_departments') && is_array($request->child_departments)) {
            foreach ($request->child_departments as $childDeptId) {
                $this->populateChildAssignment($assignment, $childDeptId);
            }
        }

        return response()->json($assignment->load([
            'client', 'department', 'assignedTo', 'album',
            'musicType', 'musicKey', 'musicGenre', 'musicCreationStatus',
            'editType', 'footageType', 'deliverables', 'artists'
        ]), 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        $assignment = Assignment::with([
            'client', 'department', 'assignedTo', 'album',
            'musicType', 'musicKey', 'musicGenre', 'musicCreationStatus',
            'editType', 'footageType', 'deliverables', 'artists',
            'parentAssignment', 'childAssignments', 'linkedSongAssignment'
        ])->findOrFail($id);

        // Check permissions
        if (!$this->canViewAssignment($user, $assignment)) {
            if (request()->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        if (request()->wantsJson()) {
            return response()->json($assignment);
        }

        return view('assignments.show', compact('assignment'));
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $assignment = Assignment::findOrFail($id);

        // Check permissions
        if (!$this->canEditAssignment($user, $assignment)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Base validation
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'assigned_to_id' => 'nullable|exists:users,id',
            'assignment_name' => 'nullable|string|max:255',
            'completion_date' => 'nullable|date',
            'release_date' => 'nullable|date',
            'release_timing' => 'nullable|in:pre-release,post-release,other',
            'notes_for_team' => 'nullable|string',
            'reference_links' => 'nullable|string',
            'notes_for_admin' => 'nullable|string',
            'status' => 'nullable|in:pending,in-progress,completed,on-hold',
        ]);

        // Department-specific validation
        $department = $assignment->department;
        
        if ($department->slug === 'music-creation') {
            $validated = array_merge($validated, $request->validate([
                'music_type_id' => 'nullable|exists:music_types,id',
                'song_name' => 'nullable|string|max:255',
                'version_name' => 'nullable|string|max:255',
                'album_id' => 'nullable|exists:albums,id',
                'bpm' => 'nullable|integer|min:1|max:999',
                'music_key_id' => 'nullable|exists:music_keys,id',
                'music_genre_id' => 'nullable|exists:music_genres,id',
                'music_creation_status_id' => 'nullable|exists:music_creation_statuses,id',
                'artists' => 'nullable|array',
                'artists.*' => 'string|max:255',
            ]));
        } elseif ($department->slug === 'video-editing') {
            $validated = array_merge($validated, $request->validate([
                'edit_type_id' => 'nullable|exists:edit_types,id',
                'footage_type_id' => 'nullable|exists:footage_types,id',
            ]));
        }

        $assignment->update($validated);

        // Handle artists
        if ($request->has('artists')) {
            $assignment->artists()->delete();
            if (is_array($request->artists)) {
                foreach ($request->artists as $artistName) {
                    if (!empty(trim($artistName))) {
                        AssignmentArtist::create([
                            'assignment_id' => $assignment->id,
                            'artist_name' => trim($artistName),
                        ]);
                    }
                }
            }
        }

        // Handle deliverables
        if ($request->has('deliverables')) {
            $assignment->deliverables()->sync($request->deliverables);
        }

        return response()->json($assignment->load([
            'client', 'department', 'assignedTo', 'album',
            'musicType', 'musicKey', 'musicGenre', 'musicCreationStatus',
            'editType', 'footageType', 'deliverables', 'artists'
        ]));
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $assignment = Assignment::findOrFail($id);

        // Check permissions
        if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $assignment->delete();
        return response()->json(['message' => 'Assignment deleted successfully']);
    }

    // Helper Methods

    private function calculateCompletionDate($musicTypeId, $departmentId, $releaseDate)
    {
        if (!$musicTypeId || !$departmentId || !$releaseDate) {
            return null;
        }

        $completionDay = MusicTypeCompletionDay::where('music_type_id', $musicTypeId)
            ->where('department_id', $departmentId)
            ->first();

        if ($completionDay) {
            return Carbon::parse($releaseDate)->subDays($completionDay->days_before_release)->format('Y-m-d');
        }

        // Default to 7 days before release
        return Carbon::parse($releaseDate)->subDays(7)->format('Y-m-d');
    }

    private function preSelectDeliverables($musicTypeId, $departmentId, $requestedDeliverables = [])
    {
        // If deliverables are already provided, use them
        if (!empty($requestedDeliverables)) {
            return $requestedDeliverables;
        }

        // Otherwise, get all active deliverables for the department
        $deliverables = Deliverable::where('department_id', $departmentId)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        return $deliverables;
    }

    private function populateChildAssignment($parentAssignment, $childDepartmentId)
    {
        $childDept = Department::findOrFail($childDepartmentId);
        
        $childData = [
            'client_id' => $parentAssignment->client_id,
            'department_id' => $childDepartmentId,
            'parent_assignment_id' => $parentAssignment->id,
            'status' => 'pending',
        ];

        // Auto-populate based on parent
        if ($parentAssignment->song_name) {
            $childData['song_name'] = $parentAssignment->song_name;
        }
        if ($parentAssignment->release_date) {
            $childData['release_date'] = $parentAssignment->release_date;
        }
        if ($parentAssignment->music_type_id) {
            $childData['music_type_id'] = $parentAssignment->music_type_id;
            
            // Calculate completion date
            $childData['completion_date'] = $this->calculateCompletionDate(
                $parentAssignment->music_type_id,
                $childDepartmentId,
                $parentAssignment->release_date
            );
        }

        $childAssignment = Assignment::create($childData);

        // Pre-select deliverables
        $deliverableIds = $this->preSelectDeliverables(
            $parentAssignment->music_type_id,
            $childDepartmentId
        );
        $childAssignment->deliverables()->sync($deliverableIds);

        return $childAssignment;
    }

    private function canViewAssignment($user, $assignment)
    {
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('view-all-edit-assigned') || $user->hasRole('view-all-update-assigned')) {
            // Hide Music Creation
            if ($assignment->department->slug === 'music-creation') {
                return false;
            }
            return true;
        }

        // User's department only
        return $user->department_id == $assignment->department_id;
    }

    private function canEditAssignment($user, $assignment)
    {
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('view-all-edit-assigned')) {
            // Can only edit assigned departments
            return $user->department_id == $assignment->department_id;
        }

        if ($user->hasRole('view-all-update-assigned')) {
            // Can only update assigned departments
            return $user->department_id == $assignment->department_id;
        }

        return false;
    }
}
