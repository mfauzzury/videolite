<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Video;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Package $package, Request $request)
    {
        // Verify enrollment
        $enrollment = $package->enrollments()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->firstOrFail();

        // Update last accessed
        $enrollment->update(['last_accessed_at' => now()]);

        // Get current video
        $videoId = $request->query('video');
        $currentVideo = $videoId
            ? Video::findOrFail($videoId)
            : $package->videos()->where('status', 'published')->orderByPivot('order_column')->first();

        // Get all package videos
        $videos = $package->videos()->where('status', 'published')->orderByPivot('order_column')->get();

        return view('player.show', compact('package', 'enrollment', 'currentVideo', 'videos'));
    }
}
