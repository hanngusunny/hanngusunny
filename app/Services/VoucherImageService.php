<?php

namespace App\Services;

use App\Models\Voucher;
use Exception;
use Illuminate\Support\Facades\File;

class VoucherImageService
{
    protected string $templatePath;
    protected string $outputDir;
    protected string $fontBold;
    protected string $fontRegular;

    public function __construct()
    {
        $this->templatePath = public_path('assets/images/voucher-template.png');
        $this->outputDir = public_path('uploads/vouchers');
        $this->fontBold = resource_path('fonts/arialbd.ttf');
        $this->fontRegular = resource_path('fonts/arial.ttf');

        if (!File::isDirectory($this->outputDir)) {
            File::makeDirectory($this->outputDir, 0777, true, true);
        }
    }

    /**
     * Get active template file path
     */
    public function getActiveTemplatePath(): string
    {
        return $this->templatePath;
    }

    /**
     * Generate voucher image with overlay text:
     * - Mã voucher
     * - Họ và tên
     * - Số điện thoại
     * - Ngày sinh
     */
    public function generate(Voucher $voucher): string
    {
        $templatePath = $this->getActiveTemplatePath();

        if (!File::exists($templatePath)) {
            throw new Exception("Không tìm thấy file phôi voucher tại {$templatePath}.");
        }

        $imageContent = file_get_contents($templatePath);
        $im = @imagecreatefromstring($imageContent);

        if (!$im) {
            throw new Exception("Không thể đọc định dạng ảnh phôi voucher.");
        }

        $w = imagesx($im);
        $h = imagesy($im);

        // Baseline template is 1531 x 681 (from high-res PDF export)
        $scaleX = $w / 1531.0;
        $scaleY = $h / 681.0;

        // Fonts
        $fontBold = File::exists($this->fontBold) ? $this->fontBold : (File::exists('C:/Windows/Fonts/arialbd.ttf') ? 'C:/Windows/Fonts/arialbd.ttf' : null);
        $fontRegular = File::exists($this->fontRegular) ? $this->fontRegular : (File::exists('C:/Windows/Fonts/arial.ttf') ? 'C:/Windows/Fonts/arial.ttf' : $fontBold);

        // Colors
        $codeColor = imagecolorallocate($im, 242, 107, 33); // Sunny Orange #f26b21
        $textColor = imagecolorallocate($im, 30, 41, 59);   // Slate 800 #1e293b

        // Text data
        $codeText = trim((string) $voucher->code);
        $nameText = mb_strtoupper(trim((string) $voucher->student_name), 'UTF-8');
        $phoneText = trim((string) $voucher->phone);
        $dobText = $voucher->dob ? $voucher->dob->format('d/m/Y') : '';

        // 1. Mã Voucher (Centered in the large box: center X = 526, center Y = 378 on 1531x681)
        $centerCodeX = (int) round(526 * $scaleX);
        $centerCodeY = (int) round(378 * $scaleY);
        $codeFontSize = max(18, (int) round(56 * $scaleY));

        if ($fontBold) {
            $bboxCode = imagettfbbox($codeFontSize, 0, $fontBold, $codeText);
            $codeX = (int) round($centerCodeX - ($bboxCode[2] + $bboxCode[0]) / 2);
            $codeY = (int) round($centerCodeY - ($bboxCode[7] + $bboxCode[1]) / 2);
            imagettftext($im, $codeFontSize, 0, $codeX, $codeY, $codeColor, $fontBold, $codeText);
        } else {
            $codeX = (int) round($centerCodeX - (strlen($codeText) * imagefontwidth(5)) / 2);
            $codeY = (int) round($centerCodeY - imagefontheight(5) / 2);
            imagestring($im, 5, $codeX, $codeY, $codeText, $codeColor);
        }

        // Shared X start position for the 3 input pills: X = 360 on 1531x681
        $inputStartX = (int) round(360 * $scaleX);
        $pillFontSize = max(12, (int) round(24 * $scaleY));
        $maxPillTextWidth = (int) round(520 * $scaleX);

        // 2. Họ và tên (1st pill: center Y = 478 on 1531x681)
        $centerNameY = (int) round(478 * $scaleY);
        if ($fontBold) {
            $nameSize = $pillFontSize;
            // Auto-scale down if student name is very long
            while ($nameSize > 14) {
                $bboxName = imagettfbbox($nameSize, 0, $fontBold, $nameText);
                $textWidth = abs($bboxName[2] - $bboxName[0]);
                if ($textWidth <= $maxPillTextWidth) {
                    break;
                }
                $nameSize--;
            }
            $bboxName = imagettfbbox($nameSize, 0, $fontBold, $nameText);
            $nameY = (int) round($centerNameY - ($bboxName[7] + $bboxName[1]) / 2);
            imagettftext($im, $nameSize, 0, $inputStartX, $nameY, $textColor, $fontBold, $nameText);
        } else {
            $nameY = (int) round($centerNameY - imagefontheight(4) / 2);
            imagestring($im, 4, $inputStartX, $nameY, $nameText, $textColor);
        }

        // 3. Số điện thoại (2nd pill: center Y = 550 on 1531x681)
        $centerPhoneY = (int) round(550 * $scaleY);
        if ($fontBold) {
            $bboxPhone = imagettfbbox($pillFontSize, 0, $fontBold, $phoneText);
            $phoneY = (int) round($centerPhoneY - ($bboxPhone[7] + $bboxPhone[1]) / 2);
            imagettftext($im, $pillFontSize, 0, $inputStartX, $phoneY, $textColor, $fontBold, $phoneText);
        } else {
            $phoneY = (int) round($centerPhoneY - imagefontheight(4) / 2);
            imagestring($im, 4, $inputStartX, $phoneY, $phoneText, $textColor);
        }

        // 4. Ngày sinh (3rd pill: center Y = 625 on 1531x681)
        if ($dobText !== '') {
            $centerDobY = (int) round(625 * $scaleY);
            if ($fontBold) {
                $bboxDob = imagettfbbox($pillFontSize, 0, $fontBold, $dobText);
                $dobY = (int) round($centerDobY - ($bboxDob[7] + $bboxDob[1]) / 2);
                imagettftext($im, $pillFontSize, 0, $inputStartX, $dobY, $textColor, $fontBold, $dobText);
            } else {
                $dobY = (int) round($centerDobY - imagefontheight(4) / 2);
                imagestring($im, 4, $inputStartX, $dobY, $dobText, $textColor);
            }
        }

        // Save output image
        $fileName = 'voucher_' . $voucher->code . '.png';
        $fullPath = $this->outputDir . DIRECTORY_SEPARATOR . $fileName;
        imagepng($im, $fullPath);
        imagedestroy($im);

        $relativePath = 'uploads/vouchers/' . $fileName;
        $voucher->image_path = $relativePath;
        $voucher->save();

        return $relativePath;
    }
}
