<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Database",
    description: "Current tenant database information"
)]
class DatabaseTenantController extends Controller
{
    #[OA\Get(
        path: "/database",
        summary: "Get current tenant database information",
        tags: ["Database"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Current tenant database information",
                content: new OA\JsonContent(
                    example: [
                        "data" => [
                            "name_cs" => "Korespondence Jana Patočky",
                            "name_en" => "Correspondence of Jan Patočka",
                            "main_character" => 3,
                            "created_at" => "2023-09-19T06:47:12.000000Z",
                            "updated_at" => "2026-06-17T14:07:08.000000Z",
                        ],
                    ],
                    properties: [
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "name_cs", type: "string"),
                                new OA\Property(property: "name_en", type: "string"),
                                new OA\Property(property: "main_character", type: "integer", nullable: true),
                                new OA\Property(property: "created_at", type: "string", format: "date-time", nullable: true),
                                new OA\Property(property: "updated_at", type: "string", format: "date-time", nullable: true),
                            ],
                            type: "object"
                        ),
                    ]
                )
            )
        ]
    )]
    public function __invoke(): JsonResponse
    {
        $tenant = tenancy()->tenant;

        return response()->json([
            'data' => [
                'name_cs' => $tenant->displayName('cs'),
                'name_en' => $tenant->displayName('en'),
                'main_character' => $tenant->main_character === null
                    ? null
                    : (int) $tenant->main_character,
                'created_at' => $tenant->created_at?->toISOString(),
                'updated_at' => $tenant->updated_at?->toISOString(),
            ],
        ]);
    }
}
