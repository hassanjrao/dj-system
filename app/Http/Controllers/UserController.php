<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function getByDepartment($departmentId)
    {
        $users = User::whereHas('departments', function($query) use ($departmentId) {
            $query->where('departments.id', $departmentId);
        })->get(['id', 'name', 'email']);
        
        return response()->json($users);
    }

    public function getAvailableForAssignment(Request $request)
    {
        $departmentId = $request->get('department_id');
        
        if ($departmentId) {
            $users = User::whereHas('departments', function($query) use ($departmentId) {
                $query->where('departments.id', $departmentId);
            })->get(['id', 'name', 'email']);
        } else {
            // Get all users if no department specified
            $users = User::all(['id', 'name', 'email']);
        }
        
        return response()->json($users);
    }
}
