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
        $assignment = Assignment::with(['client', 'department', 'assignedTo', 'deliverables', 'song.artists', 'notes.creator', 'status', 'parentAssignment.song.artists', 'childAssignments'])->findOrFail($id);

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

        // Ensure childAssignments is always an array (even if empty)
        $assignment->childAssignments = $assignment->childAssignments ? $assignment->childAssignments->toArray() : [];

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
            'reference_links' => 'nullable|string',
            'assignment_status' => 'nullable|exists:assignment_statuses,code',
            'parent_assignment_id' => 'nullable|exists:assignments,id',
            'notes' => 'nullable|array',
            'notes.*.note' => 'required|string',
            'notes.*.note_for' => 'required|in:me,team,admin',
            'child_departments' => 'nullable|array',
        ]);

        // Set default assignment_status if not provided
        if (!isset($validated['assignment_status'])) {
            $defaultStatus = AssignmentStatus::where('code', 'pending')->first();
            if ($defaultStatus) {
                $validated['assignment_status'] = $defaultStatus->code;
            }
        }

        $assignment = Assignment::create([
            'client_id' => $validated['client_id'],
            'department_id' => $validated['department_id'],
            'assigned_to_id' => $validated['assigned_to_id'],
            'assignment_name' => $validated['assignment_name'] ?? null,
            'completion_date' => $validated['completion_date'] ?? null,
            'reference_links' => $validated['reference_links'] ?? null,
            'assignment_status' => $validated['assignment_status'],
            'created_by' => Auth::id(),
        ]);


        // Handle notes
        if ($request->has('notes') && is_array($request->notes)) {
            $this->handleNotes($request->notes, $assignment);
        }

        // Department-specific validation and processing
        $department = Department::findOrFail($request->department_id);

        // Department-specific validation
        if ($department->slug === 'music-creation') {
            $assignment = $this->processMusicCreationData($request, $validated, $assignment);
        } elseif ($department->slug === 'music-mastering') {
            $assignment = $this->processMusicMasteringData($request, $validated, $assignment);
        }

        // $validated = $this->processDepartmentSpecificData($request, $department, $validated);


        // Handle deliverables
        if ($request->has('deliverables') && is_array($request->deliverables)) {
            $this->handleDeliverables($request->deliverables, $assignment);
        }

        // Create child assignments if specified
        $childAssignments = [];
        if ($request->has('child_departments') && is_array($request->child_departments)) {
            $childAssignments = $this->handleChildAssignments($request->child_departments, $assignment);
        }

        $response = $assignment->load([
            'client',
            'department',
            'assignedTo',
            'song.artists',
            'musicCreationStatus',
            'editType',
            'footageType',
            'deliverables',
            'notes',
            'status'
        ]);

        // Add child_assignments to response if any were created
        if (!empty($childAssignments)) {
            $response->child_assignments = $childAssignments;
        }

        return response()->json($response, 201);
    }

    private function handleDeliverables(array $deliverableIds = [], Assignment $assignment)
    {
        // $deliverableIds = $this->preSelectDeliverables(
        //     $assignment->department_id,
        //     $deliverableIds
        // );
        $assignment->deliverables()->sync($deliverableIds);
    }

    private function handleChildAssignments(array $childDepartments, Assignment $assignment)
    {
        $childAssignments = [];
        foreach ($childDepartments as $childDeptId) {
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
        return $childAssignments;
    }

    private function handleNotes(array $notes, Assignment $assignment, bool $isUpdate = false)
    {
        if ($isUpdate) {
            // For updates, sync notes: delete removed ones, keep existing ones, create new ones
            // This preserves created_at timestamps for existing notes

            // Get existing notes and create a map by (note + note_for) as key
            $existingNotes = $assignment->notes()->get();
            $existingNotesMap = [];
            foreach ($existingNotes as $existingNote) {
                $key = trim($existingNote->note) . '|' . $existingNote->note_for;
                $existingNotesMap[$key] = $existingNote;
            }

            // Create a map of requested notes
            $requestedNotesMap = [];
            foreach ($notes as $noteData) {
                if (!empty(trim($noteData['note']))) {
                    $key = trim($noteData['note']) . '|' . $noteData['note_for'];
                    $requestedNotesMap[$key] = $noteData;
                }
            }

            // Delete notes that exist in DB but not in request
            foreach ($existingNotesMap as $key => $existingNote) {
                if (!isset($requestedNotesMap[$key])) {
                    $existingNote->delete();
                }
            }

            // Create notes that exist in request but not in DB
            foreach ($requestedNotesMap as $key => $noteData) {
                if (!isset($existingNotesMap[$key])) {
                    Note::create([
                        'assignment_id' => $assignment->id,
                        'note' => trim($noteData['note']),
                        'created_by' => $assignment->created_by,
                        'note_for' => $noteData['note_for'],
                    ]);
                }
                // If note exists in both, we keep it as-is (preserving created_at)
            }
        } else {
            // For creates, just create notes from request
            foreach ($notes as $noteData) {
                if (!empty(trim($noteData['note']))) {
                    Note::create([
                        'assignment_id' => $assignment->id,
                        'note' => trim($noteData['note']),
                        'created_by' => $assignment->created_by,
                        'note_for' => $noteData['note_for'],
                    ]);
                }
            }
        }
    }

    private function processMusicCreationData(Request $request, array $validated, Assignment $assignment): Assignment
    {
        $validated = array_merge($validated, $request->validate([
            'song_id' => 'nullable|exists:songs,id',
            'music_creation_status_id' => 'nullable|exists:music_creation_statuses,id',
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

        // Handle song creation/selection/update for Music Creation
        if ($request->song_id) {
            // Use existing song - update if song data is provided
            $validated['song_id'] = $request->song_id;

            $song = Song::findOrFail($request->song_id);
            $songData = [
                'name' => $request->song_name ?? $song->name,
                'version' => $request->song_version ?? $song->version,
                'album_id' => $request->song_album_id ?? $song->album_id,
                'music_type_id' => $request->song_music_type_id,
                'music_genre_id' => $request->song_music_genre_id ?? $song->music_genre_id,
                'bpm' => $request->song_bpm ?? $song->bpm,
                'music_key_id' => $request->song_music_key_id ?? $song->music_key_id,
                'release_date' => $request->song_release_date ?? $song->release_date,
                'completion_date' => $request->song_completion_date ?? $song->completion_date,
            ];

            $song->update($songData);

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
                'release_date' => $request->song_release_date,
                'completion_date' => $request->song_completion_date,
            ];
            $song = Song::create(array_filter($songData));
            $validated['song_id'] = $song->id;


        }

        // Handle song artists
        if ($request->has('song_artists') && is_array($request->song_artists)) {
            $song->artists()->sync($request->song_artists);
        }

        $updateData = [];
        if (isset($validated['song_id'])) {
            $updateData['song_id'] = $validated['song_id'];
        }
        if (isset($validated['music_creation_status_id'])) {
            $updateData['music_creation_status_id'] = $validated['music_creation_status_id'];
        }

        if (!empty($updateData)) {
            $assignment->update($updateData);
        }

        return $assignment;
    }

    private function processMusicMasteringData(Request $request, array $validated, Assignment $assignment, bool $isUpdate = false): Assignment
    {
        $validated = array_merge($validated, $request->validate([
            'song_id' => 'required|exists:songs,id',
            'deliverables' => 'required|array',
            'deliverables.*' => 'exists:deliverables,id',
        ]));

        // For child assignments, auto-populate from parent
        if ($request->parent_assignment_id && !$isUpdate) {
            $parent = Assignment::findOrFail($request->parent_assignment_id);
            if ($parent->song_id) {
                $validated['song_id'] = $parent->song_id;
            }
        }

        if (isset($validated['song_id'])) {
            $assignment->update([
                'song_id' => $validated['song_id']
            ]);
        }

        return $assignment;

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

    public function getData($id)
    {
        $assignment = Assignment::with([
            'client',
            'department',
            'assignedTo',
            'song.artists',
            'musicCreationStatus',
            'editType',
            'footageType',
            'deliverables',
            'notes',
            'parentAssignment',
            'childAssignments',
            'status',
            'notes.creator'
        ])->findOrFail($id);

        $assignment->notes = $assignment->notes->map(function ($note) {
            return [
                'note' => $note->note,
                'note_for' => $note->note_for,
            ];
        })->toArray();


        return response()->json($assignment);
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
            'reference_links' => 'nullable|string',
            'assignment_status' => 'nullable|exists:assignment_statuses,code',
            'notes' => 'nullable|array',
            'notes.*.note' => 'required|string',
            'notes.*.note_for' => 'required|in:me,team,admin',
        ]);

        // Update assignment with basic fields (only update fields that are provided)
        $updateData = [];
        if (array_key_exists('client_id', $validated)) {
            $updateData['client_id'] = $validated['client_id'];
        }
        if (array_key_exists('assigned_to_id', $validated)) {
            $updateData['assigned_to_id'] = $validated['assigned_to_id'];
        }
        if (array_key_exists('assignment_name', $validated)) {
            $updateData['assignment_name'] = $validated['assignment_name'];
        }
        if (array_key_exists('completion_date', $validated)) {
            $updateData['completion_date'] = $validated['completion_date'];
        }
        if (array_key_exists('reference_links', $validated)) {
            $updateData['reference_links'] = $validated['reference_links'];
        }
        if (array_key_exists('assignment_status', $validated)) {
            $updateData['assignment_status'] = $validated['assignment_status'];
        }


        if (!empty($updateData)) {
            $assignment->update($updateData);
        }


        // Handle notes
        if ($request->has('notes') && is_array($request->notes)) {
            $this->handleNotes($request->notes, $assignment, true); // true indicates update mode
        }

        // Department-specific validation and processing
        $department = $assignment->department;

        // Department-specific processing
        if ($department->slug === 'music-creation') {
            $assignment = $this->processMusicCreationData($request, $validated, $assignment);
        } elseif ($department->slug === 'music-mastering') {
            $assignment = $this->processMusicMasteringData($request, $validated, $assignment, true);
            // Handle deliverables
            if ($request->has('deliverables') && is_array($request->deliverables)) {
                $this->handleDeliverables($request->deliverables, $assignment);
            }

        } elseif ($department->slug === 'video-editing') {
            $validated = array_merge($validated, $request->validate([
                'edit_type_id' => 'nullable|exists:edit_types,id',
                'footage_type_id' => 'nullable|exists:footage_types,id',
            ]));

            $updateData = [];
            if (isset($validated['edit_type_id'])) {
                $updateData['edit_type_id'] = $validated['edit_type_id'];
            }
            if (isset($validated['footage_type_id'])) {
                $updateData['footage_type_id'] = $validated['footage_type_id'];
            }

            if (!empty($updateData)) {
                $assignment->update($updateData);
            }
        }

        // Create child assignments if specified
        $childAssignments = [];
        if ($request->has('child_departments') && is_array($request->child_departments)) {
            $childAssignments = $this->handleChildAssignments($request->child_departments, $assignment);
        }

        $response = $assignment->load([
            'client',
            'department',
            'assignedTo',
            'song.artists',
            'musicCreationStatus',
            'editType',
            'footageType',
            'deliverables',
            'notes.creator',
            'status'
        ]);

        return response()->json($response);
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

    private function processDepartmentSpecificData(Request $request, Department $department, array $validated)
    {
        // Department-specific validation
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

            // Handle song creation/selection for Music Creation
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
                    'release_date' => $request->song_release_date,
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
                    abort(422, 'Song selection is required for standalone Music Mastering assignments');
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

        return $validated;
    }


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

    private function preSelectDeliverables($departmentId, $requestedDeliverables = [])
    {
        // If deliverables are already provided, use them
        if (!empty($requestedDeliverables)) {
            return $requestedDeliverables;
        }

        // Otherwise, get all active deliverables for the department
        // $deliverables = Deliverable::where('department_id', $departmentId)
        //     ->pluck('id')
        //     ->toArray();

        // return $deliverables;
    }

    private function populateChildAssignment($parentAssignment, $childDepartmentId)
    {
        $childData = [
            'client_id' => $parentAssignment->client_id,
            'department_id' => $childDepartmentId,
            'parent_assignment_id' => $parentAssignment->id,
            'created_by' => $parentAssignment->created_by,
            'assignment_status' => AssignmentStatus::where('code', 'pending')->first()->code,
        ];

        // Auto-populate song_id from parent
        if ($parentAssignment->song_id) {
            $childData['song_id'] = $parentAssignment->song_id;
        }

        $childAssignment = Assignment::firstOrCreate([
            'department_id' => $childDepartmentId,
            'parent_assignment_id' => $parentAssignment->id,
        ],$childData);


        $this->handleDeliverables([], $childAssignment);

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
