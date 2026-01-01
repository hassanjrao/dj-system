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
use App\Models\Client;

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

    public function childDepartments(Request $request)
    {
        $departmentId = $request->get('department_id');
        $departmentSlug = $request->get('department_slug');

        if (!$departmentId && !$departmentSlug) {
            return response()->json([]);
        }

        $department = null;
        if ($departmentId) {
            $department = Department::find($departmentId);
        } elseif ($departmentSlug) {
            $department = Department::where('slug', $departmentSlug)->first();
        }

        if (!$department) {
            return response()->json([]);
        }

        // Define which departments can be children of which parent departments
        // This logic can be moved to database if needed in the future
        $childDepartmentSlugs = [];

        if ($department->slug === 'music-creation') {
            $childDepartmentSlugs = [
                'music-mastering',
                // 'graphic-design',
                // 'video-filming',
                // 'video-editing',
                // 'distribution-video',
                // 'distribution-graphic',
                // 'distribution-music',
                // 'marketing',
            ];
        } elseif ($department->slug === 'music-mastering') {
            $childDepartmentSlugs = [
                // 'graphic-design',
                // 'video-filming',
                // 'video-editing',
                // 'distribution-video',
                // 'distribution-graphic',
                // 'distribution-music',
                // 'marketing',
            ];
        } elseif ($department->slug === 'graphic-design') {
            $childDepartmentSlugs = [
                // 'distribution-graphic',
                // 'marketing',
            ];
        } elseif ($department->slug === 'video-filming') {
            $childDepartmentSlugs = [
                // 'video-editing',
                // 'distribution-video',
                // 'marketing',
            ];
        } elseif ($department->slug === 'video-editing') {
            $childDepartmentSlugs = [
                'distribution-video',
                'marketing',
            ];
        } elseif (in_array($department->slug, ['distribution-video', 'distribution-graphic', 'distribution-music'])) {
            $childDepartmentSlugs = [
                'marketing',
            ];
        }

        // Get the actual department records
        $childDepartments = Department::whereIn('slug', $childDepartmentSlugs)
            ->where('is_active', true)
            ->get(['id', 'name', 'slug']);

        return response()->json($childDepartments);
    }

    public function getInitialData()
    {
        $departments = Department::all();
        return response()->json([
            'departments' => Department::whereIn('id', [1,2])->get(),
            'clients' => Client::orderBy('name')->get(),
            'lookup_data' => [
                'music_types' => MusicType::where('is_active', true)->get(),
                'music_keys' => MusicKey::where('is_active', true)->get(),
                'music_genres' => MusicGenre::where('is_active', true)->get(),
                'music_creation_statuses' => MusicCreationStatus::where('is_active', true)->get(),
                'edit_types' => EditType::where('is_active', true)->get(),
                'footage_types' => FootageType::where('is_active', true)->get(),
                'release_timings' => ReleaseTiming::where('is_active', true)->get(),
            ],
            'department_ids' => [
                'musicCreationId' => $departments->where('slug', 'music-creation')->first()->id,
                'musicMasteringId' => $departments->where('slug', 'music-mastering')->first()->id,
                'graphicDesignId' => $departments->where('slug', 'graphic-design')->first()->id,
                'videoFilmingId' => $departments->where('slug', 'video-filming')->first()->id,
                'videoEditingId' => $departments->where('slug', 'video-editing')->first()->id,
                'distributionVideoId' => $departments->where('slug', 'distribution-video')->first()->id,
                'distributionGraphicId' => $departments->where('slug', 'distribution-graphic')->first()->id,
                'distributionMusicId' => $departments->where('slug', 'distribution-music')->first()->id,
                'marketingId' => $departments->where('slug', 'marketing')->first()->id,
            ]
        ]);
    }
}
