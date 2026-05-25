<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wilaya;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class GeoController extends Controller
{
    public function wilayas(): JsonResponse
    {
        $ttl = now()->addMinutes((int) config('mazayada.cache.wilayas_ttl_minutes', 1440));

        $wilayas = Cache::remember('geo:wilayas', $ttl, function () {
            return Wilaya::orderBy('code')->get(['id', 'code', 'name_ar', 'name_fr']);
        });

        return response()->json($wilayas)
            ->setMaxAge(3600)
            ->setSharedMaxAge(3600);
    }

    public function communes(Wilaya $wilaya): JsonResponse
    {
        $ttl = now()->addMinutes((int) config('mazayada.cache.communes_ttl_minutes', 1440));
        $key = "geo:communes:{$wilaya->id}";

        $communes = Cache::remember($key, $ttl, function () use ($wilaya) {
            return $wilaya->communes()->orderBy('code')
                ->get(['id', 'code', 'name_ar', 'name_fr', 'postal_code']);
        });

        return response()->json($communes)
            ->setMaxAge(3600)
            ->setSharedMaxAge(3600);
    }
}
