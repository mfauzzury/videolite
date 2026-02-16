<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Get all active packages with optional filters
     */
    public function index(Request $request)
    {
        $query = Package::active()->with(['videos']);

        // Filter by featured
        if ($request->has('featured') && $request->featured) {
            $query->where('is_featured', true);
        }

        // Search by name or description
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $packages = $query->orderBy('order_column')->paginate(12);

        return response()->json([
            'success' => true,
            'packages' => $packages,
        ]);
    }

    /**
     * Get single package details
     */
    public function show(Package $package)
    {
        // Only show active packages
        if ($package->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Package not found',
            ], 404);
        }

        $package->load(['videos' => function($query) {
            $query->where('status', 'published')->orderByPivot('order_column');
        }]);

        // Calculate stats
        $totalVideos = $package->getTotalVideosCount();
        $totalDuration = $package->getTotalDuration();

        // Check if user is enrolled
        $isEnrolled = false;
        if (auth()->check()) {
            $isEnrolled = $package->enrollments()
                ->where('user_id', auth()->id())
                ->where('status', 'active')
                ->exists();
        }

        return response()->json([
            'success' => true,
            'package' => $package,
            'stats' => [
                'total_videos' => $totalVideos,
                'total_duration' => $totalDuration,
                'total_duration_formatted' => gmdate('H:i:s', $totalDuration),
            ],
            'is_enrolled' => $isEnrolled,
        ]);
    }
}
