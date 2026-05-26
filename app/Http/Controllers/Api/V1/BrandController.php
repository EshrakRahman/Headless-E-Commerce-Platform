<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @tags Brands
     *
     * @unauthenticated
     */
    public function index(): AnonymousResourceCollection
    {
        $brands = Brand::where('is_active', true)->get();

        return BrandResource::collection($brands);
    }
}
