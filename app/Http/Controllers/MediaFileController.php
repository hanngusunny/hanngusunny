<?php

namespace App\Http\Controllers;

use App\Models\SiteMedia;
use App\Models\SliderSlide;
use App\Support\PublicFilePath;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaFileController extends Controller
{
    public function file(int $id, ?string $name = null): BinaryFileResponse
    {
        $row = SiteMedia::query()->findOrFail($id);
        $full = PublicFilePath::resolve((string) $row->path);
        abort_unless($full !== null, 404);

        return Response::file($full);
    }

    public function sliderAsset(int $id, string $variant = 'desktop'): BinaryFileResponse
    {
        $slide = SliderSlide::query()->findOrFail($id);
        $variant = $variant === 'mobile' ? 'mobile' : 'desktop';
        $rel = $variant === 'mobile' ? (string) $slide->image_mobile : (string) $slide->image_desktop;
        $full = PublicFilePath::resolve($rel);
        abort_unless($full !== null, 404);

        return Response::file($full, ['Cache-Control' => 'public, max-age=604800']);
    }
}
