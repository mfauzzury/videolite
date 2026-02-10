<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoController extends Controller
{
    /**
     * Stream video with HTTP Range support for seeking/resuming
     */
    public function stream(Lesson $lesson)
    {
        $user = Auth::user();

        // Check if lesson has video
        if (!$lesson->hasVideo()) {
            return response()->json([
                'error' => 'No video available for this lesson',
            ], 404);
        }

        // CRITICAL: Verify enrollment
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return response()->json([
                'error' => 'You must be enrolled in this course to watch videos',
            ], 403);
        }

        // Update last accessed time
        $enrollment->update(['last_accessed_at' => now()]);

        // Get video file path
        $videoPath = $lesson->video_path;
        $disk = Storage::disk('videos');

        if (!$disk->exists($videoPath)) {
            return response()->json([
                'error' => 'Video file not found',
            ], 404);
        }

        $fullPath = $disk->path($videoPath);
        $fileSize = filesize($fullPath);
        $mimeType = $this->getMimeType($videoPath);

        // Handle HTTP Range requests (for seeking)
        $range = request()->header('Range');

        if ($range) {
            return $this->streamPartialContent($fullPath, $fileSize, $mimeType, $range);
        }

        // Stream full video
        return $this->streamFullContent($fullPath, $fileSize, $mimeType);
    }

    /**
     * Stream full video content
     */
    protected function streamFullContent(string $path, int $size, string $mimeType): StreamedResponse
    {
        return response()->stream(function () use ($path) {
            $stream = fopen($path, 'rb');
            while (!feof($stream)) {
                echo fread($stream, 256 * 1024); // 256KB chunks
                flush();
            }
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => $size,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * Stream partial content (HTTP 206) for seeking/resuming
     */
    protected function streamPartialContent(string $path, int $size, string $mimeType, string $range): StreamedResponse
    {
        // Parse range header (e.g., "bytes=0-1024")
        preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
        $start = intval($matches[1]);
        $end = !empty($matches[2]) ? intval($matches[2]) : $size - 1;
        $length = $end - $start + 1;

        return response()->stream(function () use ($path, $start, $length) {
            $stream = fopen($path, 'rb');
            fseek($stream, $start);
            $bytesRead = 0;
            while (!feof($stream) && $bytesRead < $length) {
                $chunkSize = min(256 * 1024, $length - $bytesRead);
                echo fread($stream, $chunkSize);
                $bytesRead += $chunkSize;
                flush();
            }
            fclose($stream);
        }, 206, [
            'Content-Type' => $mimeType,
            'Content-Length' => $length,
            'Content-Range' => "bytes {$start}-{$end}/{$size}",
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * Download PDF reference material with enrollment verification
     */
    public function downloadPdf(Lesson $lesson)
    {
        $user = Auth::user();

        // Check if lesson has PDF
        if (!$lesson->hasPdf()) {
            return response()->json([
                'error' => 'No PDF available for this lesson',
            ], 404);
        }

        // CRITICAL: Verify enrollment
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return response()->json([
                'error' => 'You must be enrolled in this course to download PDFs',
            ], 403);
        }

        // Update last accessed time
        $enrollment->update(['last_accessed_at' => now()]);

        // Get PDF file path
        $pdfPath = $lesson->pdf_path;
        $disk = Storage::disk('lesson-pdfs');

        if (!$disk->exists($pdfPath)) {
            return response()->json([
                'error' => 'PDF file not found',
            ], 404);
        }

        // Stream PDF for download
        return $disk->download($pdfPath, $lesson->pdf_filename ?? 'lesson-reference.pdf');
    }

    /**
     * Get MIME type for video file
     */
    protected function getMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $mimeTypes = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'flv' => 'video/x-flv',
            'wmv' => 'video/x-ms-wmv',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}
