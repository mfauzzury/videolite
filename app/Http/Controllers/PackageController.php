<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Subject;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Display package store/catalog
     */
    public function index(Request $request)
    {
        // Get all active subjects with their packages
        $subjects = Subject::active()
            ->with(['packages' => function($query) {
                $query->where('status', 'active')->orderBy('order_column');
            }])
            ->ordered()
            ->get();

        return view('packages.index', compact('subjects'));
    }

    /**
     * Display single package details
     */
    public function show(Package $package)
    {
        if ($package->status !== 'active') {
            abort(404);
        }

        $package->load(['videos' => function($query) {
            $query->where('status', 'published')->orderByPivot('order_column');
        }]);

        // Check if user is enrolled
        $isEnrolled = false;
        if (auth()->check()) {
            $isEnrolled = $package->enrollments()
                ->where('user_id', auth()->id())
                ->where('status', 'active')
                ->exists();
        }

        return view('packages.show', compact('package', 'isEnrolled'));
    }
}
