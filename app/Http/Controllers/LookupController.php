<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MusicType;
use App\Models\MusicKey;
use App\Models\MusicGenre;
use App\Models\EditType;
use App\Models\FootageType;
use App\Models\ReleaseTiming;
use App\Models\Department;
use App\Models\DepartmentStatus;
use App\Models\Deliverable;
use App\Models\DeliverableStatus;
use App\Models\Client;

class LookupController extends Controller
{
    public function musicTypes()
    {
        return response()->json(MusicType::orderBy('name')->get());
    }

    public function musicKeys()
    {
        return response()->json(MusicKey::all());
    }

    public function musicGenres()
    {
        return response()->json(MusicGenre::all());
    }

    public function editTypes()
    {
        return response()->json(EditType::all());
    }

    public function footageTypes()
    {
        return response()->json(FootageType::all());
    }

    public function releaseTimings()
    {
        return response()->json(ReleaseTiming::all());
    }

    public function departments()
    {
        return response()->json(Department::all());
    }

    public function departmentStatuses($departmentId)
    {
        return response()->json(DepartmentStatus::forDepartment($departmentId)->get());
    }

    /**
     * Get deliverable statuses grouped by type.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deliverableStatuses()
    {
        $statuses = DeliverableStatus::active()
            ->ordered()
            ->get()
            ->groupBy('type');

        return response()->json($statuses);
    }

    public function deliverables(Request $request)
    {
        $departmentId = $request->get('department_id');
        $query = Deliverable::query();

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
            ->get(['id', 'name', 'slug']);

        return response()->json($childDepartments);
    }

    public function getInitialData()
    {
        $departments = Department::all();

        if (auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin')) {
            $departmentList = Department::whereIn('id', [1,2])->get();
        } else {
            $departmentList = auth()->user()->departments()->whereIn('departments.id', [1,2])
            ->orderBy('departments.id')
            ->get();
        }

        return response()->json([
            'departments' => $departmentList,
            'clients' => Client::orderBy('name')->get(),
            'lookup_data' => [
                'music_types' => MusicType::orderBy('name')->get(),
                'music_keys' => MusicKey::all(),
                'music_genres' => MusicGenre::all(),
                'edit_types' => EditType::all(),
                'footage_types' => FootageType::all(),
                'release_timings' => ReleaseTiming::all(),
            ],
            'department_ids' => [
                'musicCreationId' => optional($departments->where('slug', 'music-creation')->first())->id,
                'musicMasteringId' => optional($departments->where('slug', 'music-mastering')->first())->id,
                'graphicDesignId' => optional($departments->where('slug', 'graphic-design')->first())->id,
                'videoFilmingId' => optional($departments->where('slug', 'video-filming')->first())->id,
                'videoEditingId' => optional($departments->where('slug', 'video-editing')->first())->id,
                'distributionVideoId' => optional($departments->where('slug', 'distribution-video')->first())->id,
                'distributionGraphicId' => optional($departments->where('slug', 'distribution-graphic')->first())->id,
                'distributionMusicId' => optional($departments->where('slug', 'distribution-music')->first())->id,
                'marketingId' => optional($departments->where('slug', 'marketing')->first())->id,
            ]
        ]);
    }
}
