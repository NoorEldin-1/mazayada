<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Api\RespondsWithEnvelope;

/**
 * Base controller for every versioned mobile API controller. Provides the unified
 * response envelope ($this->ok / created / paginated / fail) and the framework's
 * AuthorizesRequests (inherited from the app Controller).
 */
abstract class ApiController extends Controller
{
    use RespondsWithEnvelope;
}
