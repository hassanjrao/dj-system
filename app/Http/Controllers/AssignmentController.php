<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\Department;
use App\Models\DepartmentStatus;
use App\Models\MusicTypeCompletionDay;
use App\Models\Deliverable;
use App\Models\Artist;
use App\Models\Song;
use App\Models\AssignmentRelationship;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $departmentId = $request->get('department_id');

        // If department_id is provided, validate it exists
        if ($departmentId) {
            $department = Department::findOrFail($departmentId);
        } else {
            // For "All" option, set departmentId to null
            $departmentId = null;
            $department = null;
        }

        return view('assignments.index', compact('departmentId'));
    }

    public function allAssignments(Request $request)
    {
        $departmentId = $request->get('department_id');

        // If department_id is provided, validate it exists
        if ($departmentId) {
            $department = Department::findOrFail($departmentId);
        } else {
            // For "All" option, set departmentId to null
            $departmentId = null;
            $department = null;
        }

        return view('assignments.all', compact('departmentId'));
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
            'edit_types' => \App\Models\EditType::all(),
            'footage_types' => \App\Models\FootageType::all(),
        ];

        return view('assignments.create', compact('departments', 'clients', 'users', 'lookupData'));
    }

    public function view($id)
    {
        $user = Auth::user();
        $assignment = Assignment::with(['client', 'department', 'assignedTo', 'deliverables', 'song.artists', 'parentAssignment.song.artists', 'childAssignments.assignedTo', 'childAssignments.department', 'createdBy'])->findOrFail($id);

        // Check if user can view the assignment
        if (!$this->canViewAssignment($user, $assignment)) {
            abort(403);
        }

        // Convert song artists to IDs array for frontend (if song exists)
        if ($assignment->song) {
            $assignment->song_artists = $assignment->song->artists->pluck('id')->toArray();
        } else {
            $assignment->song_artists = [];
        }

        // Notes will be fetched separately via NoteController
        $assignment->notes = [];

        // Format childAssignments with completion_date and completion_date_days
        $formattedChildAssignments = [];
        if ($assignment->childAssignments && $assignment->childAssignments->count() > 0) {
            $formattedChildAssignments = $assignment->childAssignments->map(function ($childAssignment) {
                $childArray = $childAssignment->toArray();
                $childArray['completion_date'] = $childAssignment->getFormattedCompletionDate();
                $childArray['completion_date_days'] = $childAssignment->getCompletionDateDays();
                return $childArray;
            })->toArray();
        }
        $assignment->childAssignments = $formattedChildAssignments;

        // Add creation and update information for frontend
        $assignment->created_by_name = $assignment->createdBy ? $assignment->createdBy->name : null;
        $assignment->created_at_formatted = $assignment->created_at ? $assignment->created_at->format('M j, Y, g:i A') : null;
        $assignment->updated_by_name = $assignment->updatedBy ? $assignment->updatedBy->name : null;
        $assignment->updated_at_formatted = $assignment->updated_at ? $assignment->updated_at->format('M j, Y, g:i A') : null;

        // Get available statuses for this department
        $availableStatuses = DepartmentStatus::forDepartment($assignment->department_id)->get();

        // Check if current user can change status
        $canChangeStatus = $this->canChangeStatus($user, $assignment);

        return view('assignments.view', [
            'assignment' => $assignment,
            'availableStatuses' => $availableStatuses,
            'canChangeStatus' => $canChangeStatus,
        ]);
    }

    public function edit($id)
    {
        $user = Auth::user();
        $assignment = Assignment::with(['client', 'department', 'assignedTo', 'deliverables', 'song.artists', 'parentAssignment.song.artists', 'childAssignments.assignedTo', 'childAssignments.department', 'createdBy', 'updatedBy'])->findOrFail($id);

        if (!$this->canEditAssignment($user, $assignment)) {
            abort(403);
        }

        // Convert song artists to IDs array for frontend (if song exists)
        if ($assignment->song) {
            $assignment->song_artists = $assignment->song->artists->pluck('id')->toArray();
        } else {
            $assignment->song_artists = [];
        }

        // Notes will be fetched separately via NoteController
        $assignment->notes = [];

        // Format childAssignments with completion_date and completion_date_days
        $formattedChildAssignments = [];
        if ($assignment->childAssignments && $assignment->childAssignments->count() > 0) {
            $formattedChildAssignments = $assignment->childAssignments->map(function ($childAssignment) {
                $childArray = $childAssignment->toArray();
                $childArray['completion_date'] = $childAssignment->getFormattedCompletionDate();
                $childArray['completion_date_days'] = $childAssignment->getCompletionDateDays();
                return $childArray;
            })->toArray();
        }
        $assignment->childAssignments = $formattedChildAssignments;

        // Add creation and update information for frontend
        $assignment->created_by_name = $assignment->createdBy ? $assignment->createdBy->name : null;
        $assignment->created_at_formatted = $assignment->created_at ? $assignment->created_at->format('M j, Y, g:i A') : null;
        $assignment->updated_by_name = $assignment->updatedBy ? $assignment->updatedBy->name : null;
        $assignment->updated_at_formatted = $assignment->updated_at ? $assignment->updated_at->format('M j, Y, g:i A') : null;

        $departments = Department::all();
        $clients = \App\Models\Client::orderBy('name')->get();
        $users = \App\Models\User::all(['id', 'name', 'email']);

        // Get lookup data
        $lookupData = [
            'music_types' => \App\Models\MusicType::all(),
            'music_keys' => \App\Models\MusicKey::all(),
            'music_genres' => \App\Models\MusicGenre::all(),
            'edit_types' => \App\Models\EditType::all(),
            'footage_types' => \App\Models\FootageType::all(),
        ];

        // Get available statuses for this department
        $availableStatuses = DepartmentStatus::forDepartment($assignment->department_id)->get();

        // Check if current user can change status
        $canChangeStatus = $this->canChangeStatus($user, $assignment);

        // Return JSON if requested via API (e.g., for child assignment loading)
        if (request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'assignment' => $assignment,
                'departments' => $departments,
                'clients' => $clients,
                'users' => $users,
                'lookupData' => $lookupData,
                'available_statuses' => $availableStatuses,
                'can_change_status' => $canChangeStatus,
            ]);
        }

        return view('assignments.edit', compact('assignment', 'departments', 'clients', 'users', 'lookupData', 'availableStatuses', 'canChangeStatus'));
    }

    public function store(Request $request)
    {
        // For Step 1: Create assignment with minimal data (client_id, department_id, created_by)
        // This allows assignment to be created when user selects client and department
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        // Set default assignment_status from department_statuses table
        $defaultStatus = DepartmentStatus::where('department_id', $validated['department_id'])
            ->where('is_default', true)
            ->first();
        $statusCode = $defaultStatus ? $defaultStatus->code : 'pending';

        $assignment = Assignment::create([
            'client_id' => $validated['client_id'] ?? null,
            'department_id' => $validated['department_id'],
            'assignment_status' => $statusCode,
            'created_by' => Auth::id(),
        ]);

        // Return assignment ID immediately for Step 2
        return response()->json([
            'id' => $assignment->id,
            'message' => 'Assignment created successfully'
        ], 201);
    }

    private function handleDeliverables(array $deliverableIds = [], Assignment $assignment, array $deliverableStatuses = [])
    {
        // Build sync data with pivot values
        $syncData = [];

        // Get default pending status IDs
        $pendingCompletionId = \App\Models\DeliverableStatus::where('type', 'completion')
            ->where('code', 'pending')
            ->value('id');
        $pendingWaveUploadId = \App\Models\DeliverableStatus::where('type', 'wave_upload')
            ->where('code', 'pending')
            ->value('id');
        $pendingMp3UploadId = \App\Models\DeliverableStatus::where('type', 'mp3_upload')
            ->where('code', 'pending')
            ->value('id');

        foreach ($deliverableIds as $deliverableId) {
            // Check if we have existing status data for this deliverable
            $existingPivot = $assignment->deliverables()
                ->where('deliverables.id', $deliverableId)
                ->first();

            // Get existing pivot values if they exist
            $existingCompletionStatusId = $existingPivot && $existingPivot->pivot ? $existingPivot->pivot->completion_status_id : null;
            $existingWaveUploadStatusId = $existingPivot && $existingPivot->pivot ? $existingPivot->pivot->wave_upload_status_id : null;
            $existingMp3UploadStatusId = $existingPivot && $existingPivot->pivot ? $existingPivot->pivot->mp3_upload_status_id : null;

            // Use provided statuses, or existing pivot data, or defaults
            $pivotData = [
                'completion_status_id' => isset($deliverableStatuses[$deliverableId]['completion_status_id'])
                    ? $deliverableStatuses[$deliverableId]['completion_status_id']
                    : ($existingCompletionStatusId ?? $pendingCompletionId),
                'wave_upload_status_id' => isset($deliverableStatuses[$deliverableId]['wave_upload_status_id'])
                    ? $deliverableStatuses[$deliverableId]['wave_upload_status_id']
                    : ($existingWaveUploadStatusId ?? $pendingWaveUploadId),
                'mp3_upload_status_id' => isset($deliverableStatuses[$deliverableId]['mp3_upload_status_id'])
                    ? $deliverableStatuses[$deliverableId]['mp3_upload_status_id']
                    : ($existingMp3UploadStatusId ?? $pendingMp3UploadId),
            ];

            $syncData[$deliverableId] = $pivotData;
        }

        $assignment->deliverables()->sync($syncData);

        $assignment->updateStatusFromDeliverables();
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


    private function processMusicCreationData(Request $request, array $validated, Assignment $assignment, bool $isUserRole = false): Assignment
    {
        $validated = array_merge($validated, $request->validate([
            'song_id' => 'nullable|exists:songs,id',
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
                'completion_date' => $request->song_completion_date ?? $song->completion_date,
            ];

            // Users cannot set release_date for MUSIC CREATION assignments
            if (!$isUserRole) {
                $songData['release_date'] = $request->song_release_date ?? $song->release_date;
            }

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
                'completion_date' => $request->song_completion_date,
            ];

            // Users cannot set release_date for MUSIC CREATION assignments
            if (!$isUserRole) {
                $songData['release_date'] = $request->song_release_date;
            }

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

        if (!empty($updateData)) {
            // updated_by will be set automatically by model observer
            $assignment->update($updateData);
        }

        return $assignment;
    }

    private function processMusicMasteringData(Request $request, array $validated, Assignment $assignment, bool $isUpdate = false): Assignment
    {
        $validated = array_merge($validated, $request->validate([
            'song_id' => 'required|exists:songs,id',
            'deliverables' => 'required|array|min:1',
            'deliverable_statuses' => 'nullable|array',
        ]));

        // For child assignments, auto-populate from parent
        if ($request->parent_assignment_id && !$isUpdate) {
            $parent = Assignment::findOrFail($request->parent_assignment_id);
            if ($parent->song_id) {
                $validated['song_id'] = $parent->song_id;
            }
        }

        if (isset($validated['song_id'])) {
            // Check if the song already has a Music Mastering assignment (prevent duplicates)
            $musicMasteringDept = Department::where('slug', 'music-mastering')->first();
            if ($musicMasteringDept) {
                $existingAssignment = Assignment::where('department_id', $musicMasteringDept->id)
                    ->where('song_id', $validated['song_id'])
                    ->where('id', '!=', $assignment->id) // Exclude current assignment when updating
                    ->first();

                if ($existingAssignment) {
                    $song = \App\Models\Song::find($validated['song_id']);
                    $songName = $song ? $song->name : 'Selected song';
                    throw new \Illuminate\Validation\ValidationException(
                        \Illuminate\Support\Facades\Validator::make([], []),
                        response()->json([
                            'message' => "A Music Mastering assignment already exists for \"{$songName}\". Each song can only have one Music Mastering assignment.",
                            'errors' => ['song_id' => ["A Music Mastering assignment already exists for this song."]]
                        ], 422)
                    );
                }
            }

            // updated_by will be set automatically by model observer
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
            'editType', 'footageType', 'deliverables',
            'parentAssignment', 'childAssignments'
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
            'editType',
            'footageType',
            'deliverables',
            'parentAssignment',
            'childAssignments',
        ])->findOrFail($id);

        // Notes will be fetched separately via NoteController
        $assignment->notes = [];

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

        // Prevent updates on completed assignments
        // Exception: Music Mastering assignments can be updated because their status
        // is derived from deliverable statuses and can change dynamically
        $isMusicMastering = $assignment->department && $assignment->department->slug === 'music-mastering';
        if ($assignment->is_completed && !$isMusicMastering) {
            return response()->json([
                'error' => 'This assignment is completed and cannot be modified.'
            ], 422);
        }

        // Base validation
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'assigned_to_id' => 'nullable|exists:users,id',
            'assignment_name' => 'nullable|string|max:255',
            'completion_date' => 'nullable|date',
            'assignment_status' => 'nullable|string',
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
        if (array_key_exists('assignment_status', $validated)) {
            $updateData['assignment_status'] = $validated['assignment_status'];
        }

        if (!empty($updateData)) {
            // updated_by will be set automatically by model observer
            $assignment->update($updateData);
        }

        // Department-specific validation and processing
        $department = $assignment->department;

        // Check if user role is 'user' and department is 'music-creation'
        // Users cannot set release_date or create child assignments for MUSIC CREATION
        $user = Auth::user();
        $isUserRole = $user->hasRole('user');
        $isMusicCreation = $department->slug === 'music-creation';

        if ($isUserRole && $isMusicCreation) {
            // Prevent setting release_date for users
            if ($request->has('song_release_date') && $request->song_release_date) {
                return response()->json([
                    'error' => 'Users cannot set release date for MUSIC CREATION assignments. Release date is required to create child assignments.'
                ], 422);
            }

            // Prevent creating child assignments for users
            if ($request->has('child_departments') && is_array($request->child_departments) && count($request->child_departments) > 0) {
                return response()->json([
                    'error' => 'Users cannot create child assignments for MUSIC CREATION assignments. Release date must be set to create child assignments.'
                ], 422);
            }
        }

        // Additional validation for Music Creation assignments with existing child assignments
        if ($isMusicCreation) {
            // Load existing child assignments
            $existingChildAssignments = $assignment->childAssignments()->get();
            $existingChildDeptIds = $existingChildAssignments->pluck('department_id')->toArray();

            if (count($existingChildDeptIds) > 0) {
                // If child assignments exist, release date cannot be cleared
                if ($request->has('song_release_date') && empty($request->song_release_date)) {
                    return response()->json([
                        'error' => 'Release date cannot be removed when child assignments exist.'
                    ], 422);
                }

                // Prevent removal of existing child departments
                if ($request->has('child_departments')) {
                    $requestedChildDepts = is_array($request->child_departments) ? $request->child_departments : [];
                    $removedDepts = array_diff($existingChildDeptIds, $requestedChildDepts);

                    if (count($removedDepts) > 0) {
                        return response()->json([
                            'error' => 'Cannot remove existing child assignments. Child assignments that have been created cannot be unchecked.'
                        ], 422);
                    }
                }
            }
        }

        // Department-specific processing
        if ($department->slug === 'music-creation') {
            $assignment = $this->processMusicCreationData($request, $validated, $assignment, $isUserRole);
        } elseif ($department->slug === 'music-mastering') {
            $assignment = $this->processMusicMasteringData($request, $validated, $assignment, true);
            // Handle deliverables with their statuses
            if ($request->has('deliverables')) {
                $deliverables = $request->input('deliverables', []);
                // Ensure deliverables is an array of integers
                if (is_array($deliverables) && count($deliverables) > 0) {
                    $deliverableIds = array_map(function ($item) {
                        return is_array($item) ? (int) ($item['id'] ?? $item) : (int) $item;
                    }, $deliverables);
                    $deliverableStatuses = $request->input('deliverable_statuses', []);
                    $this->handleDeliverables($deliverableIds, $assignment, $deliverableStatuses);
                }
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
            'editType',
            'footageType',
            'deliverables',
            'childAssignments',
            'childAssignments.department',
            'childAssignments.deliverables'
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
                'deliverables.*' => 'integer|exists:deliverables,id',
                'deliverable_statuses' => 'nullable|array',
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
        // Get default status for the child department
        $defaultStatus = DepartmentStatus::where('department_id', $childDepartmentId)
            ->where('is_default', true)
            ->first();
        $statusCode = $defaultStatus ? $defaultStatus->code : 'pending';

        $childData = [
            'client_id' => $parentAssignment->client_id,
            'department_id' => $childDepartmentId,
            'parent_assignment_id' => $parentAssignment->id,
            'created_by' => $parentAssignment->created_by,
            'assignment_status' => $statusCode,
        ];

        // Auto-populate song_id from parent
        if ($parentAssignment->song_id) {
            $childData['song_id'] = $parentAssignment->song_id;
        }

        $childAssignment = Assignment::firstOrCreate([
            'department_id' => $childDepartmentId,
            'parent_assignment_id' => $parentAssignment->id,
        ], $childData);


        // $this->handleDeliverables([], $childAssignment);

        // Ensure department relation is available for response payloads
        $childAssignment->loadMissing('department');
        $childAssignment->loadMissing('deliverables');

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
        // No one can edit completed assignments
        if ($assignment->is_completed) {
            // return false;
        }

        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('user') && $assignment->created_by == $user->id) {
            return true;
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

    public function getCompletionDays($musicTypeId, $departmentId)
    {
        $completionDay = MusicTypeCompletionDay::query()
        ->where('music_type_id', $musicTypeId)
        ->where('department_id', $departmentId)
        ->first();

        return response()->json([
            'days_before_release' => $completionDay->days_before_release ?? 7
        ]);
    }

    public function getAssignments(Request $request)
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'required|in:all,active,completed', // This is for filtering, not the assignment_status field
            'scope' => 'nullable|in:my,all', // 'my' shows only user's assignments, 'all' shows everyone's
            'client_id' => 'nullable|exists:clients,id',
            'search' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $departmentId = $request->get('department_id');
        $status = $request->get('status'); // 'active' or 'completed'
        $scope = $request->get('scope', 'my'); // Default to 'my'
        $clientId = $request->get('client_id');
        $search = $request->get('search');

        // Base query for all assignments (for counts)
        $baseQuery = Assignment::query();

        // If scope is 'my', filter by assigned user
        if ($scope === 'my') {
            $baseQuery->where('assigned_to_id', $user->id);
        }

        // If department_id is provided, filter by it
        if ($departmentId) {
            $baseQuery->where('department_id', $departmentId);
        }

        // Apply MUSIC CREATION restriction for users with role 'user'
        $baseQuery->restrictMusicCreationForUsers($user);

        // Calculate counts for all assignments using the new department_statuses system
        $today = Carbon::today();
        $allAssignments = $baseQuery->get();
        $activeCount = 0;
        $overdueCount = 0;
        $completedCount = 0;

        foreach ($allAssignments as $assignment) {
            // Use the is_completed accessor which checks department_statuses table
            if ($assignment->is_completed) {
                $completedCount++;
            } else {
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
            'client', 'department', 'assignedTo', 'song.musicType', 'deliverables', 'createdBy',
            'childAssignments.department' // For Music Creation to show child assignments as deliverables
        ]);

        // If scope is 'my', filter by assigned user
        if ($scope === 'my') {
            $query->where('assigned_to_id', $user->id);
        }

        // If department_id is provided, filter by it
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        // Filter by status using new department_statuses system
        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'completed') {
            $query->completed();
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

        // Apply MUSIC CREATION restriction for users with role 'user'
        $query->restrictMusicCreationForUsers($user);

        // Order by completion date (due date)
        $query->orderBy('completion_date', 'asc');

        // dd($query->toSql());

        $assignments = $query->get();

        // dd($assignments);

        // Get Music Creation department ID for comparison
        $musicCreationDept = Department::where('slug', 'music-creation')->first();
        $musicCreationDeptId = $musicCreationDept ? $musicCreationDept->id : null;

        // Return only needed fields for frontend
        $assignments = $assignments->map(function ($assignment) use ($today, $musicCreationDeptId, $user) {
            // Get formatted completion date and days using model methods
            $completionDateFormatted = $assignment->getFormattedCompletionDate();
            $completionDateDays = $assignment->getCompletionDateDays($today);

            // Format release date
            $releaseDateFormatted = null;
            $releaseDateDays = null;
            if ($assignment->song && $assignment->song->release_date) {
                // Format: "Fri, Jan 28"
                $releaseDateFormatted = $assignment->song->release_date->format('D, M j');

                // Calculate days remaining or overdue
                $daysRemaining = $today->diffInDays($assignment->song->release_date, false);
                if ($daysRemaining < 0) {
                    $releaseDateDays = abs($daysRemaining) . ' days overdue';
                } elseif ($daysRemaining == 0) {
                    $releaseDateDays = 'Today';
                } elseif ($daysRemaining == 1) {
                    $releaseDateDays = '1 day to go';
                } else {
                    $releaseDateDays = $daysRemaining . ' days to go';
                }
            }

            return [
                'id' => $assignment->id,
                'assignment_id' => $assignment->assignment_id, // Accessor from model (requires department)
                'assignment_display_name' => $assignment->song ? $assignment->song->name : $assignment->assignment_name,
                'completion_date' => $completionDateFormatted,
                'completion_date_days' => $completionDateDays,
                'release_date' => $releaseDateFormatted,
                'release_date_days' => $releaseDateDays,
                'assignment_status' => $assignment->assignment_status,
                'department' => $assignment->department ? [
                    'id' => $assignment->department->id,
                    'name' => $assignment->department->name,
                    'slug' => $assignment->department->slug,
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
                'music_type' => $assignment->song && $assignment->song->musicType ? [
                    'id' => $assignment->song->musicType->id,
                    'name' => $assignment->song->musicType->name,
                ] : null,
                // For Music Creation, show child assignments as deliverables; for others, show actual deliverables
                'deliverables' => ($musicCreationDeptId && $assignment->department_id === $musicCreationDeptId)
                    ? $assignment->childAssignments->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->department ? $child->department->name : 'Unknown',
                        ];
                    })->toArray()
                    : $assignment->deliverables->map(function ($deliverable) {
                        return [
                            'id' => $deliverable->id,
                            'name' => $deliverable->name,
                        ];
                    })->toArray(),
                'is_music_creation' => $musicCreationDeptId && $assignment->department_id === $musicCreationDeptId,
                'can_edit' => $this->canEditAssignment(auth()->user(), $assignment),
                'can_change_status' => $this->canChangeStatus($user, $assignment),
                'available_statuses' => DepartmentStatus::forDepartment($assignment->department_id)->get(),
            ];
        });

        return response()->json([
            'data' => $assignments,
            'active_count' => $activeCount,
            'overdue_count' => $overdueCount,
            'completed_count' => $completedCount
        ]);
    }

    /**
     * Update assignment status.
     * Validates user permission and status code for department.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        $assignment = Assignment::findOrFail($id);

        // Backend permission check
        if (!$this->canChangeStatus($user, $assignment)) {
            return response()->json(['message' => 'Unauthorized to change status'], 403);
        }

        // Prevent status changes on completed assignments
        if ($assignment->is_completed) {
            return response()->json([
                'message' => 'This assignment is completed and cannot be modified.'
            ], 422);
        }

        // Validate status code exists for this department
        $request->validate([
            'status' => 'required|string',
        ]);

        // Check if status code is valid for this department
        $validStatus = DepartmentStatus::where('department_id', $assignment->department_id)
            ->where('code', $request->status)
            ->first();

        if (!$validStatus) {
            return response()->json([
                'message' => 'Invalid status code for this department',
                'errors' => ['status' => ['The selected status is invalid for this department.']]
            ], 422);
        }

        $assignment->update(['assignment_status' => $request->status]);

        return response()->json([
            'message' => 'Status updated successfully',
            'assignment_status' => $assignment->assignment_status,
            'status_details' => $validStatus,
        ]);
    }

    /**
     * Check if user can change assignment status.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Assignment $assignment
     * @return bool
     */
    private function canChangeStatus($user, $assignment): bool
    {
        // No one can change status of completed assignments
        if ($assignment->is_completed) {
            return false;
        }

        // Admins can always change
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        // Assigned user can change their own assignment status
        if ($assignment->assigned_to_id === $user->id) {
            return true;
        }

        return false;
    }
}
