<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * Get user's enrolled courses
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $enrollments = auth()->user()->enrollments()
            ->with(['course:id,title,slug,subtitle,description'])
            ->where('status', 'active')
            ->orderBy('last_accessed_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'enrollments' => $enrollments,
        ]);
    }

    /**
     * Get course curriculum for enrolled user
     *
     * @param  \App\Models\Enrollment  $enrollment
     * @return \Illuminate\Http\JsonResponse
     */
    public function curriculum(Enrollment $enrollment)
    {
        // Verify user owns this enrollment
        if ($enrollment->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this enrollment',
            ], 403);
        }

        // Verify enrollment is active
        if (!$enrollment->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This enrollment is not active',
            ], 403);
        }

        $course = $enrollment->course()->with([
            'sections.lessons',
            'instructor:id,name'
        ])->first();

        return response()->json([
            'success' => true,
            'course' => $course,
            'enrollment' => $enrollment,
        ]);
    }
}
