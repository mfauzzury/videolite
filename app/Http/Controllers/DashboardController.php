<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $enrollments = auth()->user()->enrollments()
            ->with(['package.videos'])
            ->where('status', 'active')
            ->orderBy('last_accessed_at', 'desc')
            ->get();

        return view('dashboard.index', compact('enrollments'));
    }
}
