<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MusicType;
use App\Models\MusicKey;
use App\Models\MusicGenre;
use App\Models\MusicCreationStatus;
use App\Models\EditType;
use App\Models\FootageType;
use App\Models\ReleaseTiming;
use App\Models\Department;
use App\Models\Deliverable;

class LookupController extends Controller
{
    public function musicTypes()
    {
        return response()->json(MusicType::where('is_active', true)->get());
    }

    public function musicKeys()
    {
        return response()->json(MusicKey::where('is_active', true)->get());
    }

    public function musicGenres()
    {
        return response()->json(MusicGenre::where('is_active', true)->get());
    }

    public function musicCreationStatuses()
    {
        return response()->json(MusicCreationStatus::where('is_active', true)->get());
    }

    public function editTypes()
    {
        return response()->json(EditType::where('is_active', true)->get());
    }

    public function footageTypes()
    {
        return response()->json(FootageType::where('is_active', true)->get());
    }

    public function releaseTimings()
    {
        return response()->json(ReleaseTiming::where('is_active', true)->get());
    }

    public function departments()
    {
        return response()->json(Department::where('is_active', true)->get());
    }

    public function deliverables(Request $request)
    {
        $departmentId = $request->get('department_id');
        $query = Deliverable::where('is_active', true);
        
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        return response()->json($query->get());
    }
}
