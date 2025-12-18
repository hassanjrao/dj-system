<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssignmentRelationship;
use App\Models\Assignment;

class AssignmentRelationshipController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_assignment_id' => 'required|exists:assignments,id',
            'child_assignment_id' => 'required|exists:assignments,id',
        ]);

        // Prevent circular relationships
        if ($validated['parent_assignment_id'] == $validated['child_assignment_id']) {
            return response()->json(['error' => 'Assignment cannot be its own parent'], 400);
        }

        $relationship = AssignmentRelationship::firstOrCreate($validated);
        return response()->json($relationship->load(['parentAssignment', 'childAssignment']), 201);
    }

    public function destroy($id)
    {
        $relationship = AssignmentRelationship::findOrFail($id);
        $relationship->delete();
        return response()->json(['message' => 'Relationship deleted successfully']);
    }
}
