<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\Department;
use App\Models\MusicTypeCompletionDay;
use App\Models\Deliverable;
use App\Models\Artist;
use App\Models\Song;
use App\Models\AssignmentRelationship;
use App\Models\Note;
use App\Models\AssignmentStatus;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $departmentId = $request->get('department_id');
        $department = Department::findOrFail($departmentId);
        return view('assignments.index', compact('departmentId'));
    }

    public function create()
    {
        $departments = Department::all();
        $clients = \App\Models\Client::orderBy('name')->get();
        $users = \App\Models\User::all(['id', 'name', 'email']);

        // Get lookup data
        $lookupData = [
            'music_types' => \App\Models\MusicType::all(),
            'music_keys' => \App\Models\MusicKey::all(),
            'music_genres' => \App\Models\MusicGenre::all(),
            'music_creation_statuses' => \App\Models\MusicCreationStatus::all(),
            'edit_types' => \App\Models\EditType::all(),
            'footage_types' => \App\Models\FootageType::all(),
        ];

        return view('assignments.create', compact('departments', 'clients', 'users', 'lookupData'));
    }

    public function edit($id)
    {
        $user = Auth::user();
        $assignment = Assignment::with(['client', 'department', 'assignedTo', 'deliverables', 'song.artists', 'notes.creator', 'status', 'parentAssignment.song.artists'])->findOrFail($id);

        if (!$this->canEditAssignment($user, $assignment)) {
            abort(403);
        }

        // Convert song artists to IDs array for frontend (if song exists)
        if ($assignment->song) {
            $assignment->song_artists = $assignment->song->artists->pluck('id')->toArray();
        } else {
            $assignment->song_artists = [];
        }

        // Convert notes to array format for frontend
        $assignment->notes = $assignment->notes->map(function ($note) {
            return [
                'note' => $note->note,
                'note_for' => $note->note_for,
            ];
        })->toArray();

        $departments = Department::all();
        $clients = \App\Models\Client::orderBy('name')->get();
        $users = \App\Models\User::all(['id', 'name', 'email']);

        // Get lookup data
        $lookupData = [
            'music_types' => \App\Models\MusicType::all(),
            'music_keys' => \App\Models\MusicKey::all(),
            'music_genres' => \App\Models\MusicGenre::all(),
            'music_creation_statuses' => \App\Models\MusicCreationStatus::all(),
            'edit_types' => \App\Models\EditType::all(),
            'footage_types' => \App\Models\FootageType::all(),
        ];

        // Return JSON if requested via API (e.g., for child assignment loading)
        if (request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'assignment' => $assignment,
                'departments' => $departments,
                'clients' => $clients,
                'users' => $users,
                'lookupData' => $lookupData,
            ]);
        }

        return view('assignments.edit', compact('assignment', 'departments', 'clients', 'users', 'lookupData'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'department_id' => 'required|exists:departments,id',
            'assigned_to_id' => 'nullable|exists:users,id',
            'assignment_name' => 'nullable|string|max:255',
            'completion_date' => 'nullable|date',
            'release_date' => 'nullable|date',
            'release_timing' => 'nullable|in:pre-release,post-release,other',
            'reference_links' => 'nullable|string',
            'assignment_status' => 'nullable|exists:assignment_statuses,code',
            'parent_assignment_id' => 'nullable|exists:assignments,id',
            'notes' => 'nullable|array',
            'notes.*.note' => 'required|string',
            'notes.*.note_for' => 'required|in:me,team,admin',
            'linked_song_assignment_id' => 'nullable|exists:assignments,id',
            'child_departments' => 'nullable|array', // For creating child assignments
        ]);

        // Department-specific validation
        $department = Department::findOrFail($request->department_id);

        if ($department->slug === 'music-creation') {
            $validated = array_merge($validated, $request->validate([
                'song_id' => 'nullable|exists:songs,id',
                'music_creation_status_id' => 'nullable|exists:music_creation_statuses,id',
                'release_date' => 'nullable|date',
                // Song creation fields (if creating new song)
                'song_name' => 'nullable|string|max:255',
                'song_version' => 'nullable|string|max:255',
                'song_album_id' => 'nullable|exists:albums,id',
                'song_music_type_id' => 'nullable|exists:music_types,id',
                'song_music_genre_id' => 'nullable|exists:music_genres,id',
                'song_bpm' => 'nullable|integer|min:1|max:999',
                'song_music_key_id' => 'nullable|exists:music_keys,id',
                'song_release_date' => 'nullable|date',
                'song_completion_date' => 'nullable|date',
                'song_artists' => 'nullable|array',
                'song_artists.*' => 'exists:artists,id',
            ]));
        } elseif ($department->slug === 'music-mastering') {
            $validated = array_merge($validated, $request->validate([
                'song_id' => 'nullable|exists:songs,id',
                'deliverables' => 'nullable|array',
                'deliverables.*' => 'exists:deliverables,id',
            ]));

            // For child assignments, auto-populate from parent
            if ($request->parent_assignment_id) {
                $parent = Assignment::findOrFail($request->parent_assignment_id);
                if ($parent->song_id) {
                    $validated['song_id'] = $parent->song_id;
                }
                if ($parent->song && !$request->release_date) {
                    $validated['release_date'] = $parent->song->release_date;
                }
            } else {
                // For standalone assignments, require song_id
                if (!$request->song_id) {
                    return response()->json(['error' => 'Song selection is required for standalone Music Mastering assignments'], 422);
                }
            }

            // Calculate completion date if song exists
            if (isset($validated['song_id'])) {
                $song = Song::findOrFail($validated['song_id']);
                if ($song->music_type_id && $song->release_date && !$request->completion_date) {
                    $validated['completion_date'] = $this->calculateCompletionDate(
                        $song->music_type_id,
                        $request->department_id,
                        $song->release_date
                    );
                }
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
            // Auto-populate song_id from parent if needed
            $parent = Assignment::findOrFail($request->parent_assignment_id);
            if (!$request->song_id && $parent->song_id) {
                $validated['song_id'] = $parent->song_id;
            }
            if ($parent->song && !$request->release_date) {
                $validated['release_date'] = $parent->song->release_date;
            }
        }

        // Handle song creation/selection for Music Creation
        if ($department->slug === 'music-creation') {
            if ($request->song_id) {
                // Use existing song
                $validated['song_id'] = $request->song_id;
            } elseif ($request->song_name) {
                // Create new song
                $songData = [
                    'name' => $request->song_name,
                    'version' => $request->song_version,
                    'album_id' => $request->song_album_id,
                    'music_type_id' => $request->song_music_type_id,
                    'music_genre_id' => $request->song_music_genre_id,
                    'bpm' => $request->song_bpm,
                    'music_key_id' => $request->song_music_key_id,
                    'release_date' => $request->song_release_date ?: $request->release_date,
                    'completion_date' => $request->song_completion_date,
                ];
                $song = Song::create(array_filter($songData));
                $validated['song_id'] = $song->id;

                // Handle song artists
                if ($request->has('song_artists') && is_array($request->song_artists)) {
                    foreach ($request->song_artists as $artistId) {
                        if (!empty($artistId)) {
                            $song->artists()->attach($artistId);
                        }
                    }
                }
            }
        }

        // Set default assignment_status if not provided
        if (!isset($validated['assignment_status'])) {
            $defaultStatus = AssignmentStatus::where('code', 'pending')->first();
            if ($defaultStatus) {
                $validated['assignment_status'] = $defaultStatus->code;
            }
        }

        // Set created_by to current user
        $validated['created_by'] = Auth::id();

        // Create assignment
        $assignment = Assignment::create($validated);

        // Handle deliverables
        if ($request->has('deliverables') && is_array($request->deliverables)) {
            $musicTypeId = null;
            if ($assignment->song && $assignment->song->music_type_id) {
                $musicTypeId = $assignment->song->music_type_id;
            }
            $deliverableIds = $this->preSelectDeliverables(
                $musicTypeId,
                $request->department_id,
                $request->deliverables
            );
            $assignment->deliverables()->sync($deliverableIds);
        }

        // Create child assignments if specified
        $childAssignments = [];
        if ($request->has('child_departments') && is_array($request->child_departments)) {
            foreach ($request->child_departments as $childDeptId) {
                $childAssignment = $this->populateChildAssignment($assignment, $childDeptId);
                $childAssignments[] = [
                    'id' => $childAssignment->id,
                    'department_id' => $childAssignment->department_id,
                    'department' => [
                        'id' => $childAssignment->department->id,
                        'name' => $childAssignment->department->name,
                        'slug' => $childAssignment->department->slug,
                    ],
                ];
            }
        }

        // Handle notes
        if ($request->has('notes') && is_array($request->notes)) {
            foreach ($request->notes as $noteData) {
                if (!empty(trim($noteData['note']))) {
                    Note::create([
                        'assignment_id' => $assignment->id,
                        'note' => trim($noteData['note']),
                        'created_by' => Auth::id(),
                        'note_for' => $noteData['note_for'],
                    ]);
                }
            }
        }

        $response = $assignment->load([
            'client', 'department', 'assignedTo', 'song.artists',
            'musicCreationStatus', 'editType', 'footageType', 'deliverables', 'notes', 'status'
        ]);

        // Add child_assignments to response if any were created
        if (!empty($childAssignments)) {
            $response->child_assignments = $childAssignments;
        }

        return response()->json($response, 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        $assignment = Assignment::with([
            'client', 'department', 'assignedTo', 'song.artists',
            'musicCreationStatus', 'editType', 'footageType', 'deliverables',
            'parentAssignment', 'childAssignments', 'status'
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
            'assignment_status' => 'nullable|exists:assignment_statuses,code',
        ]);

        // Department-specific validation
        $department = $assignment->department;

        if ($department->slug === 'music-creation') {
            $validated = array_merge($validated, $request->validate([
                'song_id' => 'nullable|exists:songs,id',
                'music_creation_status_id' => 'nullable|exists:music_creation_statuses,id',
            ]));
        } elseif ($department->slug === 'video-editing') {
            $validated = array_merge($validated, $request->validate([
                'edit_type_id' => 'nullable|exists:edit_types,id',
                'footage_type_id' => 'nullable|exists:footage_types,id',
            ]));
        }

        $assignment->update($validated);

        // Handle deliverables
        if ($request->has('deliverables')) {
            $assignment->deliverables()->sync($request->deliverables);
        }

        // Handle notes
        if ($request->has('notes') && is_array($request->notes)) {
            foreach ($request->notes as $noteData) {
                if (!empty(trim($noteData['note']))) {
                    Note::create([
                        'assignment_id' => $assignment->id,
                        'note' => trim($noteData['note']),
                        'created_by' => Auth::id(),
                        'note_for' => $noteData['note_for'],
                    ]);
                }
            }
        }

        return response()->json($assignment->load([
            'client', 'department', 'assignedTo', 'song.artists',
            'musicCreationStatus', 'editType', 'footageType', 'deliverables', 'notes.creator', 'status'
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
            ->pluck('id')
            ->toArray();

        return $deliverables;
    }

    private function populateChildAssignment($parentAssignment, $childDepartmentId)
    {
        $childData = [
            'client_id' => $parentAssignment->client_id,
            'department_id' => $childDepartmentId,
            'parent_assignment_id' => $parentAssignment->id,
            'created_by' => $parentAssignment->created_by,
            'assignment_status' => 'pending',
        ];

        // Auto-populate song_id from parent
        if ($parentAssignment->song_id) {
            $childData['song_id'] = $parentAssignment->song_id;
        }

        // Auto-populate release_date from parent's song
        if ($parentAssignment->song && $parentAssignment->song->release_date) {
            $childData['release_date'] = $parentAssignment->song->release_date;
        }

        // Calculate completion date if song exists
        if ($parentAssignment->song && $parentAssignment->song->music_type_id && $parentAssignment->song->release_date) {
            $childData['completion_date'] = $this->calculateCompletionDate(
                $parentAssignment->song->music_type_id,
                $childDepartmentId,
                $parentAssignment->song->release_date
            );
        }

        $childAssignment = Assignment::create($childData);

        // Pre-select deliverables
        $musicTypeId = null;
        if ($parentAssignment->song && $parentAssignment->song->music_type_id) {
            $musicTypeId = $parentAssignment->song->music_type_id;
        }
        $deliverableIds = $this->preSelectDeliverables(
            $musicTypeId,
            $childDepartmentId
        );
        $childAssignment->deliverables()->sync($deliverableIds);

        // Ensure department relation is available for response payloads
        $childAssignment->loadMissing('department');

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

        // User's departments only (many-to-many)
        $userDepartmentIds = $user->departments()->pluck('departments.id');
        return $userDepartmentIds->contains($assignment->department_id);
    }

    private function canEditAssignment($user, $assignment)
    {
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('view-all-edit-assigned')) {
            // Can only edit assigned departments (many-to-many)
            $userDepartmentIds = $user->departments()->pluck('departments.id');
            return $userDepartmentIds->contains($assignment->department_id);
        }

        if ($user->hasRole('view-all-update-assigned')) {
            // Can only update assigned departments (many-to-many)
            $userDepartmentIds = $user->departments()->pluck('departments.id');
            return $userDepartmentIds->contains($assignment->department_id);
        }

        return false;
    }

    public function getArtists()
    {
        $artists = Artist::select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($artists);
    }

    public function storeArtist(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:artists,name',
        ]);

        $artist = Artist::create([
            'name' => $validated['name'],
        ]);

        return response()->json($artist, 201);
    }

    public function getAvailableSongs($id)
    {
        // Get all songs from songs table
        $songs = Song::with('musicType')
            ->get(['id', 'name', 'release_date', 'music_type_id']);

        // Map to match expected frontend format
        $songs = $songs->map(function ($song) {
            return [
                'id' => $song->id,
                'song_name' => $song->name,
                'release_date' => $song->release_date,
                'music_type_id' => $song->music_type_id,
            ];
        });

        return response()->json($songs);
    }

    public function getCompletionDays($musicTypeId)
    {
        $completionDay = MusicTypeCompletionDay::where('music_type_id', $musicTypeId)->first();

        if ($completionDay) {
            return response()->json([
                'days_before_release' => $completionDay->days_before_release
            ]);
        }

        return response()->json([
            'days_before_release' => 7 // Default
        ]);
    }

    public function getAssignments(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'status' => 'required|in:all,active,completed', // This is for filtering, not the assignment_status field
            'client_id' => 'nullable|exists:clients,id',
            'search' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $departmentId = $request->get('department_id');
        $status = $request->get('status'); // 'active' or 'completed'
        $clientId = $request->get('client_id');
        $search = $request->get('search');

        // Base query for all assignments in department (for counts)
        $baseQuery = Assignment::where('department_id', $departmentId);

        $activeStatusCodes = AssignmentStatus::whereIn('code', ['pending', 'in-progress', 'on-hold'])->pluck('code')->toArray();

        // Calculate counts for all assignments in department
        $today = Carbon::today();
        $allAssignments = $baseQuery->get();
        $activeCount = 0;
        $overdueCount = 0;
        $completedCount = 0;

        foreach ($allAssignments as $assignment) {
            if ($assignment->assignment_status === 'completed') {
                $completedCount++;
            } elseif (in_array($assignment->assignment_status, $activeStatusCodes)) {
                $activeCount++;
                if ($assignment->completion_date) {
                    $daysRemaining = $today->diffInDays($assignment->completion_date, false);
                    if ($daysRemaining < 0) {
                        $overdueCount++;
                    }
                }
            }
        }

        // Query for filtered assignments
        $query = Assignment::with([
            'client', 'department', 'assignedTo', 'song', 'deliverables', 'status', 'createdBy'
        ])->where('department_id', $departmentId);

        // Filter by status
        if ($status === 'active') {
            $query->whereIn('assignment_status', $activeStatusCodes);
        } elseif ($status === 'completed') {
            $query->where('assignment_status', 'completed');
        }
        // If status is 'all', don't filter by status (show all assignments)

        // Filter by client(s)
        if ($clientId) {
            if (is_array($clientId)) {
                $query->whereIn('client_id', $clientId);
            } else {
                $query->where('client_id', $clientId);
            }
        }

        // Generic search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('assignment_name', 'like', "%{$search}%")
                  ->orWhereHas('song', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('client', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Order by completion date (due date)
        $query->orderBy('completion_date', 'asc');

        $assignments = $query->get();

        // Return only needed fields for frontend
        $assignments = $assignments->map(function ($assignment) {
            return [
                'id' => $assignment->id,
                'assignment_id' => $assignment->assignment_id, // Accessor from model (requires department)
                'assignment_display_name' => $assignment->song ? $assignment->song->name : $assignment->assignment_name,
                'completion_date' => $assignment->completion_date ? $assignment->completion_date->diffForHumans() : null,
                'assignment_status' => $assignment->assignment_status,
                'department' => $assignment->department ? [
                    'id' => $assignment->department->id,
                    'name' => $assignment->department->name,
                ] : null,
                'assigned_to' => $assignment->assignedTo ? [
                    'id' => $assignment->assignedTo->id,
                    'name' => $assignment->assignedTo->name,
                ] : null,
                'created_by' => $assignment->createdBy ? [
                    'id' => $assignment->createdBy->id,
                    'name' => $assignment->createdBy->name,
                ] : null,
                'client' => $assignment->client ? [
                    'id' => $assignment->client->id,
                    'name' => $assignment->client->name,
                ] : null,
                'deliverables' => $assignment->deliverables->map(function ($deliverable) {
                    return [
                        'id' => $deliverable->id,
                        'name' => $deliverable->name,
                    ];
                })->toArray(),
            ];
        });

        return response()->json([
            'data' => $assignments,
            'active_count' => $activeCount,
            'overdue_count' => $overdueCount,
            'completed_count' => $completedCount
        ]);
    }
}
