<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        $position = $request->query('position', 'hero');

        $banners = Banner::active()
            ->where('position', $position)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => BannerResource::collection($banners),
        ]);
    }
}
