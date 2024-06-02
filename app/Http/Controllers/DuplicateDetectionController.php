<?php

namespace App\Http\Controllers;

use App\Services\DuplicateDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;

class DuplicateDetectionController extends Controller
{
    public function detectDuplicates(): JsonResponse
    {
        try {
            $duplicateDetectionService = new DuplicateDetectionService([]);
            $potentialDuplicates = $duplicateDetectionService->processDuplicates();
            return response()->json(['duplicates' => $potentialDuplicates], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function index(): View
    {
        return view('pages.duplicates.index', [
            'title' => __('hiko.duplicates'),
        ]);
    }
}