<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaqCategory;
use App\Models\SiteBlock;
use App\Models\SliderCategory;
use Illuminate\Http\JsonResponse;

class CmsEmbedController extends Controller
{
    public function blocks(): JsonResponse
    {
        $items = SiteBlock::query()->where('is_active', 1)->orderBy('name')->get(['id', 'name', 'slug'])
            ->map(fn (SiteBlock $row) => [
                'id' => $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
            ]);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function faqs(): JsonResponse
    {
        $items = FaqCategory::query()->where('is_active', 1)->orderBy('name')->get(['id', 'name'])
            ->map(fn (FaqCategory $row) => [
                'id' => $row->id,
                'name' => $row->name,
            ]);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function sliders(): JsonResponse
    {
        $items = SliderCategory::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (SliderCategory $row) => [
                'id' => $row->id,
                'name' => $row->name,
            ]);

        return response()->json(['ok' => true, 'items' => $items]);
    }
}
