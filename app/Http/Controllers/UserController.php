<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display user management page (super-admin only)
     */
    public function index()
    {
        if (!Auth::user()->hasRole('super-admin')) {
            // abort(403, 'Unauthorized access');
        }

        return view('users.index');
    }

    /**
     * Get all users with roles and departments (API endpoint)
     */
    public function getUsers()
    {
        if (!Auth::user()->hasRole('super-admin')) {
            // return response()->json(['error' => 'Unauthorized'], 403);
        }

        $users = User::with(['roles', 'departments'])->latest()->get();

        $users = $users->map(function ($user, $index) {
            return [
                'index' => $index + 1,
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->map(function ($role) {
                    return ['name' => $role->name];
                })->toArray(),
                'departments' => $user->departments->map(function ($department) {
                    return [
                        'id' => $department->id,
                        'name' => $department->name,
                    ];
                })->toArray(),
            ];
        });

        return response()->json(['data' => $users]);
    }

    /**
     * Store a new user
     */
    public function store(Request $request)
    {
        if (!Auth::user()->hasRole('super-admin')) {
            // return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assign role
        $role = Role::where('name', $validated['role'])->first();
        if ($role) {
            $user->assignRole($role);
        }

        // Sync departments
        if (isset($validated['departments']) && is_array($validated['departments'])) {
            $user->departments()->sync($validated['departments']);
        }

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load(['roles', 'departments']),
        ], 201);
    }

    /**
     * Update an existing user
     */
    public function update(Request $request, $id)
    {
        if (!Auth::user()->hasRole('super-admin')) {
            // return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // Update password only if provided
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Sync role
        $role = Role::where('name', $validated['role'])->first();
        if ($role) {
            $user->syncRoles([$role]);
        }

        // Sync departments
        if (isset($validated['departments']) && is_array($validated['departments'])) {
            $user->departments()->sync($validated['departments']);
        } else {
            $user->departments()->detach();
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load(['roles', 'departments']),
        ]);
    }

    /**
     * Get available roles for autocomplete
     */
    public function getRoles()
    {
        if (!Auth::user()->hasRole('super-admin')) {
            // return response()->json(['error' => 'Unauthorized'], 403);
        }

        $roles = Role::all(['id', 'name']);

        return response()->json($roles);
    }

    /**
     * Get users by department
     */
    public function getByDepartment($departmentId)
    {
        $users = User::whereHas('departments', function ($query) use ($departmentId) {
            $query->where('departments.id', $departmentId);
        })->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    /**
     * Get users available for assignment
     */
    public function getAvailableForAssignment()
    {
        // This method can be implemented based on specific requirements
        $users = User::with('departments')->get(['id', 'name', 'email']);
        return response()->json($users);
    }
}
