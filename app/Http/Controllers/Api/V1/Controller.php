<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use OpenApi\Attributes as OA;


#[OA\Info(
    version: "1.0.0",
    title: "Laravel ai rag api v1",
    description: "API для ai rag project"
)]
#[OA\Contact(email: "test@example.com")]
#[OA\Server(
    url: "http://localhost:8080",
    description: "Local Development Server"
)]
abstract class Controller extends BaseController
{
    //
}
