<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Deliverable;
use App\Models\Assignment;
use App\Models\DeliverableStatus;

class DeliverableController extends Controller
{
    public function index(Request $request)
    {
        $query = Deliverable::with('department');

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        return response()->json($query->where('is_active', true)->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $deliverable = Deliverable::create($validated);
        return response()->json($deliverable->load('department'), 201);
    }

    public function update(Request $request, $id)
    {
        $deliverable = Deliverable::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $deliverable->update($validated);
        return response()->json($deliverable->load('department'));
    }

    public function destroy($id)
    {
        $deliverable = Deliverable::findOrFail($id);
        $deliverable->delete();
        return response()->json(['message' => 'Deliverable deleted successfully']);
    }

    /**
     * Update deliverable status for an assignment.
     * Handles the three status columns: completion_status_id, wave_upload_status_id, mp3_upload_status_id
     *
     * @param Request $request
     * @param int $assignmentId
     * @param int $deliverableId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $assignmentId, $deliverableId)
    {
        $validated = $request->validate([
            'completion_status_id' => 'nullable|exists:deliverable_statuses,id',
            'wave_upload_status_id' => 'nullable|exists:deliverable_statuses,id',
            'mp3_upload_status_id' => 'nullable|exists:deliverable_statuses,id',
            'notes' => 'nullable|string',
        ]);

        $assignment = Assignment::findOrFail($assignmentId);

        // Check if user can update status (assigned user, admin, or super-admin)
        $user = auth()->user();
        $canUpdate = $user->hasRole('admin') ||
                     $user->hasRole('super-admin') ||
                     $assignment->assigned_to_id === $user->id;

        if (!$canUpdate) {
            return response()->json(['error' => 'You do not have permission to update this deliverable status'], 403);
        }

        if (!$assignment->deliverables()->where('deliverables.id', $deliverableId)->exists()) {
            return response()->json(['error' => 'Deliverable not found for this assignment'], 404);
        }

        // Build update data - only include fields that are provided
        $updateData = [];

        if (array_key_exists('completion_status_id', $validated)) {
            $updateData['completion_status_id'] = $validated['completion_status_id'];
        }

        if (array_key_exists('wave_upload_status_id', $validated)) {
            // Validate that wave_upload_status can only be changed if completion is 'done'
            $currentPivot = $assignment->deliverables()
                ->where('deliverables.id', $deliverableId)
                ->first()
                ->pivot;

            $completionStatusId = $validated['completion_status_id'] ?? $currentPivot->completion_status_id;
            $doneStatus = DeliverableStatus::where('type', 'completion')
                ->where('code', 'done')
                ->first();

            if ($doneStatus && $completionStatusId != $doneStatus->id) {
                // Reset to pending if completion is not done
                $pendingStatus = DeliverableStatus::where('type', 'wave_upload')
                    ->where('code', 'pending')
                    ->first();
                $updateData['wave_upload_status_id'] = $pendingStatus ? $pendingStatus->id : null;
            } else {
                $updateData['wave_upload_status_id'] = $validated['wave_upload_status_id'];
            }
        }

        if (array_key_exists('mp3_upload_status_id', $validated)) {
            // Validate that mp3_upload_status can only be changed if completion is 'done'
            $currentPivot = $currentPivot ?? $assignment->deliverables()
                ->where('deliverables.id', $deliverableId)
                ->first()
                ->pivot;

            $completionStatusId = $validated['completion_status_id'] ?? $currentPivot->completion_status_id;
            $doneStatus = $doneStatus ?? DeliverableStatus::where('type', 'completion')
                ->where('code', 'done')
                ->first();

            if ($doneStatus && $completionStatusId != $doneStatus->id) {
                // Reset to pending if completion is not done
                $pendingStatus = DeliverableStatus::where('type', 'mp3_upload')
                    ->where('code', 'pending')
                    ->first();
                $updateData['mp3_upload_status_id'] = $pendingStatus ? $pendingStatus->id : null;
            } else {
                $updateData['mp3_upload_status_id'] = $validated['mp3_upload_status_id'];
            }
        }

        if (array_key_exists('notes', $validated)) {
            $updateData['notes'] = $validated['notes'];
        }

        if (!empty($updateData)) {
            $assignment->deliverables()->updateExistingPivot($deliverableId, $updateData);

            // Auto-update assignment status based on deliverable statuses
            // Need to refresh and reload relationships to get updated pivot data
            $assignment = Assignment::with(['department', 'deliverables'])->find($assignment->id);
            $assignment->updateStatusFromDeliverables();
        }

        // Return updated deliverable with status info
        $updatedDeliverable = $assignment->deliverables()
            ->where('deliverables.id', $deliverableId)
            ->first();

        return response()->json([
            'message' => 'Deliverable status updated successfully',
            'deliverable' => [
                'id' => $updatedDeliverable->id,
                'name' => $updatedDeliverable->name,
                'completion_status_id' => $updatedDeliverable->pivot->completion_status_id,
                'wave_upload_status_id' => $updatedDeliverable->pivot->wave_upload_status_id,
                'mp3_upload_status_id' => $updatedDeliverable->pivot->mp3_upload_status_id,
                'notes' => $updatedDeliverable->pivot->notes,
            ],
            'assignment_status' => $assignment->assignment_status,
        ]);
    }
}
