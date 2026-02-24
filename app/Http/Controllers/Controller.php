<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "2.0.0",
    title: "HIKO API V2",
    description: "API for managing historical correspondence data (Letters, Identities, Places, etc.)"
)]
#[OA\Server(
    url: "http://{tenant}.localhost/api/v2",
    description: "Local Development",
    variables: [
        new OA\ServerVariable(
            serverVariable: "tenant",
            default: "hiko-test",
            description: "The subdomain of the tenant"
        )
    ]
)]
#[OA\Server(
    url: "https://{tenant}.historicka-korespondence.cz/api/v2",
    description: "Testing Server",
    variables: [
        new OA\ServerVariable(
            serverVariable: "tenant",
            default: "hiko-test10",
            description: "The subdomain of the tenant"
        )
    ]
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "Sanctum"
)]
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
