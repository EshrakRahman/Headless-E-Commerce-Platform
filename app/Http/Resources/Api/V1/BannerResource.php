<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Banner
 */
class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'cta_text' => $this->cta_text,
            'cta_url' => $this->cta_url,
            'desktop_image' => $this->desktop_image ? asset('storage/'.$this->desktop_image) : null,
            'mobile_image' => $this->mobile_image ? asset('storage/'.$this->mobile_image) : null,
            'bg_color' => $this->bg_color,
            'text_color' => $this->text_color,
            'sort_order' => $this->sort_order,
        ];
    }
}
