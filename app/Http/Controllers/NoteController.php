<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;
use App\Models\Assignment;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    /**
     * Get all notes for an assignment
     */
    public function index(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
        ]);

        $assignment = Assignment::findOrFail($request->assignment_id);
        $user = Auth::user();

        // Check if user can view the assignment
        if (!$this->canViewAssignment($user, $assignment)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notes = Note::where('assignment_id', $assignment->id)
            ->with(['creator', 'updatedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedNotes = $notes->map(function ($note) use ($user) {
            $canEdit = $user->can('edit-notes') || $note->created_by == $user->id;
            $canDelete = $user->can('delete-notes') || $note->created_by == $user->id;

            return [
                'id' => $note->id,
                'note' => $note->note,
                'note_for' => $note->note_for,
                'created_by' => $note->creator->name ?? null,
                'created_at' => $note->created_at ? $note->created_at->format('M j, Y, g:i A') : null,
                'updated_by' => $note->updatedBy->name ?? null,
                'updated_at' => $note->updated_at ? $note->updated_at->format('M j, Y, g:i A') : null,
                'canEdit' => $canEdit,
                'canDelete' => $canDelete,
            ];
        });

        return response()->json($formattedNotes);
    }

    /**
     * Store a newly created note
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'note' => 'required|string',
            'note_for' => 'required|in:me,team,admin',
        ]);

        $assignment = Assignment::findOrFail($validated['assignment_id']);
        $user = Auth::user();

        // Check if user can view the assignment
        if (!$this->canViewAssignment($user, $assignment)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $note = Note::create([
            'assignment_id' => $validated['assignment_id'],
            'note' => trim($validated['note']),
            'note_for' => $validated['note_for'],
            'created_by' => $user->id,
        ]);

        $note->load(['creator', 'updatedBy']);

        $canEdit = $user->can('edit-notes') || $note->created_by == $user->id;
        $canDelete = $user->can('delete-notes') || $note->created_by == $user->id;

        return response()->json([
            'id' => $note->id,
            'note' => $note->note,
            'note_for' => $note->note_for,
            'created_by' => $note->creator->name ?? null,
            'created_at' => $note->created_at ? $note->created_at->format('M j, Y, g:i A') : null,
            'updated_by' => $note->updatedBy->name ?? null,
            'updated_at' => $note->updated_at ? $note->updated_at->format('M j, Y, g:i A') : null,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
        ], 201);
    }

    /**
     * Update the specified note
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'note' => 'required|string',
            'note_for' => 'required|in:me,team,admin',
        ]);

        $note = Note::findOrFail($id);
        $user = Auth::user();

        // Check permissions: can edit if user has permission OR is the creator
        $canEdit = $user->can('edit-notes') || $note->created_by == $user->id;
        if (!$canEdit) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if user can view the assignment
        $assignment = $note->assignment;
        if (!$this->canViewAssignment($user, $assignment)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $note->update([
            'note' => trim($validated['note']),
            'note_for' => $validated['note_for'],
            'updated_by' => $user->id,
        ]);

        $note->load(['creator', 'updatedBy']);

        $canDelete = $user->can('delete-notes') || $note->created_by == $user->id;

        return response()->json([
            'id' => $note->id,
            'note' => $note->note,
            'note_for' => $note->note_for,
            'created_by' => $note->creator->name ?? null,
            'created_at' => $note->created_at ? $note->created_at->format('M j, Y, g:i A') : null,
            'updated_by' => $note->updatedBy->name ?? null,
            'updated_at' => $note->updated_at ? $note->updated_at->format('M j, Y, g:i A') : null,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
        ]);
    }

    /**
     * Remove the specified note
     */
    public function destroy($id)
    {
        $note = Note::findOrFail($id);
        $user = Auth::user();

        // Check permissions: can delete if user has permission OR is the creator
        $canDelete = $user->can('delete-notes') || $note->created_by == $user->id;
        if (!$canDelete) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if user can view the assignment
        $assignment = $note->assignment;
        if (!$this->canViewAssignment($user, $assignment)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $note->delete();

        return response()->json(['message' => 'Note deleted successfully']);
    }

    /**
     * Check if user can view the assignment
     */
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
}

