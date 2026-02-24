<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "System",
    description: "System management endpoints"
)]
class DatabaseSyncController extends Controller
{
    /**
     * Trigger the database sync from Production to Test.
     */
    #[OA\Post(
        path: "/system/sync-database",
        summary: "Sync database from production",
        description: "Triggers a database sync from production to the current environment. Only available in non-production environments and for admins.",
        tags: ["System"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Sync completed successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Database sync completed successfully.")
                    ]
                )
            ),
            new OA\Response(response: 403, description: "Forbidden (Production environment or unauthorized)"),
            new OA\Response(response: 500, description: "Sync failed")
        ]
    )]
    public function __invoke(Request $request)
    {
        // 1. Safety: Environment Check
        if (app()->environment('production')) {
            return response()->json([
                'success' => false,
                'message' => 'This action is strictly forbidden in the production environment.'
            ], 403);
        }

        // 2. Security: Authorization Check
        // We only allow users with the 'debug' ability (Developer role)
        if (!$request->user()->hasAbility('manage-users')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can perform this action.'
            ], 403);
        }

        // 3. Configuration for Long Running Process
        // Increase time limit to prevent timeout during sync
        set_time_limit(600); // 10 minutes
        ini_set('memory_limit', '512M');

        try {
            // 4. Run the Artisan Command
            // We pass '--yes' to skip the interactive confirmation
            $exitCode = Artisan::call('db:sync-prod', [
                '--yes' => true,
            ]);

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Database sync completed successfully.',
                    // Optional: Include command output for debugging
                    // 'output' => Artisan::output(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'The sync command finished with an error code.',
                    'output' => Artisan::output(),
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('API Database Sync Failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during synchronization: ' . $e->getMessage(),
            ], 500);
        }
    }
}
