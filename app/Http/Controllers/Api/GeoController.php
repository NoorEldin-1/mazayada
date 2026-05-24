<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wilaya;
use Illuminate\Http\JsonResponse;

class GeoController extends Controller
{
    public function wilayas(): JsonResponse
    {
        $wilayas = Wilaya::orderBy('code')->get(['id', 'code', 'name_ar', 'name_fr']);

        return response()->json($wilayas);
    }

    public function communes(Wilaya $wilaya): JsonResponse
    {
        $communes = $wilaya->communes()->get(['id', 'code', 'name_ar', 'name_fr', 'postal_code']);

        return response()->json($communes);
    }
}
