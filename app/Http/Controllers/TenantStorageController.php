<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TenantStorageController extends Controller
{
    /**
     * Serve files from tenant-specific storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $path
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $path)
    {
        // Verify signed URL if implemented
        if ($request->hasValidSignature()) {
            // Proceed
        } else {
            abort(401, 'Invalid or expired URL.');
        }

        // Sanitize the path
        $path = ltrim($path, '/');

        // Allowed file extensions
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt'];

        // Check file extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            abort(403, 'Forbidden file type.');
        }

        // Use the 'public' disk
        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            abort(404);
        }

        // Get file content and MIME type
        $file = $disk->get($path);
        $mimeType = $disk->mimeType($path);
        $fileName = basename($path);

        // Return the file as a response with caching headers
        return Response::make($file, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "inline; filename=\"{$fileName}\"",
            'Cache-Control' => 'public, max-age=86400', // 1 day
        ]);
    }
}
