<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Deliverable;
use App\Models\Assignment;

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

    // Mark deliverable as completed/uploaded for an assignment
    public function updateStatus(Request $request, $assignmentId, $deliverableId)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,completed,uploaded',
            'notes' => 'nullable|string',
        ]);

        $assignment = Assignment::findOrFail($assignmentId);
        
        if (!$assignment->deliverables()->where('deliverables.id', $deliverableId)->exists()) {
            return response()->json(['error' => 'Deliverable not found for this assignment'], 404);
        }

        $assignment->deliverables()->updateExistingPivot($deliverableId, [
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['message' => 'Deliverable status updated successfully']);
    }
}
