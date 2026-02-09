<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            [
                'name' => 'Mathematics',
                'slug' => 'mathematics',
                'description' => 'Complete mathematics curriculum from Form 1 to Form 6, covering all essential topics including Algebra, Geometry, Calculus, Statistics, and Trigonometry.',
                'icon' => 'heroicon-o-calculator',
                'color' => '#3B82F6',
                'status' => 'active',
                'order_column' => 1,
                'meta_title' => 'Mathematics Courses | VideoLite',
                'meta_description' => 'Learn mathematics from Form 1 to Form 6 with comprehensive video lessons and reference materials.',
            ],
            [
                'name' => 'Science',
                'slug' => 'science',
                'description' => 'Comprehensive science education covering Physics, Chemistry, and Biology for all school forms.',
                'icon' => 'heroicon-o-beaker',
                'color' => '#10B981',
                'status' => 'active',
                'order_column' => 2,
                'meta_title' => 'Science Courses | VideoLite',
                'meta_description' => 'Explore science topics from Form 1 to Form 6 with engaging video content and study materials.',
            ],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }
    }
}
