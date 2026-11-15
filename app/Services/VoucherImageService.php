<?php

namespace App\Services;

use App\Models\Voucher;
use Exception;
use Illuminate\Support\Facades\File;

class VoucherImageService
{
    protected string $defaultTemplatePath;
    protected string $customTemplatePath;
    protected string $outputDir;
    protected string $fontBold;
    protected string $fontRegular;
    protected string $settingsFile;

    public function __construct()
    {
        $this->defaultTemplatePath = public_path('assets/images/voucher-template.png');
        $this->customTemplatePath = public_path('uploads/vouchers/template.png');
        $this->outputDir = public_path('uploads/vouchers');
        $this->fontBold = resource_path('fonts/arialbd.ttf');
        $this->fontRegular = resource_path('fonts/arial.ttf');
        $this->settingsFile = storage_path('app/voucher_settings.json');

        if (!File::isDirectory($this->outputDir)) {
            File::makeDirectory($this->outputDir, 0777, true, true);
        }
    }

    /**
     * Get active template file path
     */
    public function getActiveTemplatePath(): string
    {
        if (File::exists($this->customTemplatePath)) {
            return $this->customTemplatePath;
        }

        if (!File::exists($this->defaultTemplatePath)) {
            $this->createDefaultTemplate();
        }

        return $this->defaultTemplatePath;
    }

    /**
     * Check if a custom template is currently in use
     */
    public function hasCustomTemplate(): bool
    {
        return File::exists($this->customTemplatePath);
    }

    /**
     * Save uploaded template
     */
    public function saveCustomTemplate($uploadedFile): bool
    {
        if (!File::isDirectory($this->outputDir)) {
            File::makeDirectory($this->outputDir, 0777, true, true);
        }

        $imageContent = file_get_contents($uploadedFile->getRealPath());
        $im = @imagecreatefromstring($imageContent);
        if (!$im) {
            return false;
        }

        // Convert and save as PNG for crisp quality
        imagepng($im, $this->customTemplatePath);
        imagedestroy($im);

        return true;
    }

    /**
     * Delete custom template and revert to default
     */
    public function revertToDefaultTemplate(): bool
    {
        if (File::exists($this->customTemplatePath)) {
            return File::delete($this->customTemplatePath);
        }
        return true;
    }

    /**
     * Load settings (coordinates & colors)
     */
    public function getSettings(): array
    {
        $defaults = [
            'mode' => 'auto', // 'default' or 'custom'
            'code_x' => 100,
            'code_y' => 265,
            'code_size' => 36,
            'code_color' => '#f26b21',
            'name_x' => 260,
            'name_y' => 365,
            'name_size' => 17,
            'name_color' => '#ffffff',
            'phone_x' => 260,
            'phone_y' => 425,
            'phone_size' => 17,
            'phone_color' => '#ffffff',
            'dob_x' => 260,
            'dob_y' => 485,
            'dob_size' => 17,
            'dob_color' => '#fde68a',
            'show_dob' => true,
        ];

        if (File::exists($this->settingsFile)) {
            try {
                $saved = json_decode(File::get($this->settingsFile), true);
                if (is_array($saved)) {
                    return array_merge($defaults, $saved);
                }
            } catch (Exception $e) {}
        }

        return $defaults;
    }

    /**
     * Save settings
     */
    public function saveSettings(array $settings): void
    {
        File::put($this->settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Generate voucher image with overlay text
     */
    public function generate(Voucher $voucher): string
    {
        $templatePath = $this->getActiveTemplatePath();
        $imageContent = file_get_contents($templatePath);
        $im = @imagecreatefromstring($imageContent);

        if (!$im) {
            throw new Exception("Không thể đọc file phôi voucher.");
        }

        $w = imagesx($im);
        $h = imagesy($im);

        $settings = $this->getSettings();
        $isDefaultTemplate = !$this->hasCustomTemplate() && $w === 1200 && $h === 600;

        $fontBold = File::exists($this->fontBold) ? $this->fontBold : (File::exists('C:/Windows/Fonts/arialbd.ttf') ? 'C:/Windows/Fonts/arialbd.ttf' : null);
        $fontRegular = File::exists($this->fontRegular) ? $this->fontRegular : (File::exists('C:/Windows/Fonts/arial.ttf') ? 'C:/Windows/Fonts/arial.ttf' : $fontBold);

        $codeText = (string) $voucher->code;
        $nameText = mb_strtoupper((string) $voucher->student_name, 'UTF-8');
        $phoneText = (string) $voucher->phone;
        $dobText = $voucher->dob ? $voucher->dob->format('d/m/Y') : '';

        if ($isDefaultTemplate) {
            // High-precision coordinates for default Sunny template (1200x600)
            $codeColor = imagecolorallocate($im, 242, 107, 33); // Sunny orange
            $white = imagecolorallocate($im, 255, 255, 255);
            $gold = imagecolorallocate($im, 253, 230, 138);

            if ($fontBold) {
                imagettftext($im, 36, 0, 100, 265, $codeColor, $fontBold, $codeText);
                imagettftext($im, 17, 0, 260, 365, $white, $fontBold, $nameText);
                imagettftext($im, 17, 0, 260, 425, $white, $fontBold, $phoneText);
                if ($dobText) {
                    imagettftext($im, 17, 0, 260, 485, $gold, $fontBold, $dobText);
                }
            } else {
                imagestring($im, 5, 100, 240, $codeText, $codeColor);
                imagestring($im, 5, 260, 350, $nameText, $white);
                imagestring($im, 5, 260, 410, $phoneText, $white);
                if ($dobText) {
                    imagestring($im, 5, 260, 470, $dobText, $gold);
                }
            }
        } else {
            // Custom uploaded template: scale proportionally based on width and height
            $scale = $w / 1200;
            $codeColor = $this->allocateHexColor($im, $settings['code_color'] ?? '#f26b21');
            $nameColor = $this->allocateHexColor($im, $settings['name_color'] ?? '#ffffff');
            $phoneColor = $this->allocateHexColor($im, $settings['phone_color'] ?? '#ffffff');
            $dobColor = $this->allocateHexColor($im, $settings['dob_color'] ?? '#fde68a');

            $codeX = (int) round(($settings['code_x'] ?? 100) * $scale);
            $codeY = (int) round(($settings['code_y'] ?? 265) * $scale);
            $codeSize = max(14, (int) round(($settings['code_size'] ?? 36) * $scale));

            $nameX = (int) round(($settings['name_x'] ?? 260) * $scale);
            $nameY = (int) round(($settings['name_y'] ?? 365) * $scale);
            $nameSize = max(11, (int) round(($settings['name_size'] ?? 17) * $scale));

            $phoneX = (int) round(($settings['phone_x'] ?? 260) * $scale);
            $phoneY = (int) round(($settings['phone_y'] ?? 425) * $scale);
            $phoneSize = max(11, (int) round(($settings['phone_size'] ?? 17) * $scale));

            $dobX = (int) round(($settings['dob_x'] ?? 260) * $scale);
            $dobY = (int) round(($settings['dob_y'] ?? 485) * $scale);
            $dobSize = max(11, (int) round(($settings['dob_size'] ?? 17) * $scale));

            if ($fontBold) {
                imagettftext($im, $codeSize, 0, $codeX, $codeY, $codeColor, $fontBold, $codeText);
                imagettftext($im, $nameSize, 0, $nameX, $nameY, $nameColor, $fontBold, $nameText);
                imagettftext($im, $phoneSize, 0, $phoneX, $phoneY, $phoneColor, $fontBold, $phoneText);
                if ($dobText && !empty($settings['show_dob'])) {
                    imagettftext($im, $dobSize, 0, $dobX, $dobY, $dobColor, $fontBold, $dobText);
                }
            } else {
                imagestring($im, 5, $codeX, $codeY, $codeText, $codeColor);
                imagestring($im, 4, $nameX, $nameY, $nameText, $nameColor);
                imagestring($im, 4, $phoneX, $phoneY, $phoneText, $phoneColor);
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

    /**
     * Allocate color from hex string like #f26b21
     */
    protected function allocateHexColor($im, string $hex): int
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return imagecolorallocate($im, $r, $g, $b);
    }

    /**
     * Create default template if missing
     */
    public function createDefaultTemplate(): void
    {
        $dir = dirname($this->defaultTemplatePath);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0777, true, true);
        }

        $w = 1200;
        $h = 600;
        $im = imagecreatetruecolor($w, $h);

        $bgStart = [15, 23, 42];   // Slate 900
        $bgEnd = [30, 41, 59];     // Slate 800
        $primary = imagecolorallocate($im, 242, 107, 33); // #f26b21 Sunny Orange
        $gold = imagecolorallocate($im, 245, 158, 11);    // Amber 500
        $goldLight = imagecolorallocate($im, 253, 230, 138); // Amber 200
        $white = imagecolorallocate($im, 255, 255, 255);
        $slate400 = imagecolorallocate($im, 148, 163, 184);
        $slate700 = imagecolorallocate($im, 51, 65, 85);

        // Vertical gradient
        for ($y = 0; $y < $h; $y++) {
            $r = (int) ($bgStart[0] + ($bgEnd[0] - $bgStart[0]) * ($y / $h));
            $g = (int) ($bgStart[1] + ($bgEnd[1] - $bgStart[1]) * ($y / $h));
            $b = (int) ($bgStart[2] + ($bgEnd[2] - $bgStart[2]) * ($y / $h));
            $color = imagecolorallocate($im, $r, $g, $b);
            imageline($im, 0, $y, $w, $y, $color);
        }

        // Borders
        imagesetthickness($im, 3);
        imagerectangle($im, 24, 24, $w - 24, $h - 24, $gold);
        imagesetthickness($im, 1);
        imagerectangle($im, 30, 30, $w - 30, $h - 30, $goldLight);

        $fontBold = File::exists($this->fontBold) ? $this->fontBold : null;
        $fontRegular = File::exists($this->fontRegular) ? $this->fontRegular : $fontBold;

        if ($fontBold) {
            imagettftext($im, 26, 0, 70, 95, $primary, $fontBold, "SUNNY EDUCATION");
            imagettftext($im, 15, 0, 70, 130, $goldLight, $fontRegular, "PHIẾU QUÀ TẶNG & VOUCHER HỌC VIÊN");

            // Separator dashed
            for ($y = 40; $y < $h - 40; $y += 16) {
                imageline($im, 880, $y, 880, $y + 8, $slate400);
            }

            imagettftext($im, 18, 0, 915, 120, $white, $fontBold, "QUY ĐỊNH");
            imagettftext($im, 11, 0, 915, 170, $slate400, $fontRegular, "- Mỗi mã chỉ áp dụng 01 lần.");
            imagettftext($im, 11, 0, 915, 210, $slate400, $fontRegular, "- Mã gắn liền với thông tin");
            imagettftext($im, 11, 0, 915, 235, $slate400, $fontRegular, "  học sinh đã đăng ký.");
            imagettftext($im, 11, 0, 915, 275, $slate400, $fontRegular, "- Xuất trình ảnh voucher");
            imagettftext($im, 11, 0, 915, 300, $slate400, $fontRegular, "  khi làm thủ tục ghi danh.");
            imagettftext($im, 13, 0, 915, 530, $primary, $fontBold, "duhocsunny.edu.vn");

            // Box for Code
            imagefilledrectangle($im, 70, 170, 830, 290, imagecolorallocate($im, 255, 255, 255));
            imagerectangle($im, 70, 170, 830, 290, $primary);
            imagerectangle($im, 73, 173, 827, 287, $primary);
            imagettftext($im, 12, 0, 100, 205, imagecolorallocate($im, 100, 116, 139), $fontBold, "MÃ VOUCHER ĐẶC BIỆT");

            // Info box
            imagefilledrectangle($im, 70, 320, 830, 520, imagecolorallocatealpha($im, 255, 255, 255, 118));
            imagerectangle($im, 70, 320, 830, 520, $slate700);

            imagettftext($im, 14, 0, 100, 365, $goldLight, $fontBold, "HỌ VÀ TÊN:");
            imagettftext($im, 14, 0, 100, 425, $goldLight, $fontBold, "SỐ ĐIỆN THOẠI:");
            imagettftext($im, 14, 0, 100, 485, $goldLight, $fontBold, "NGÀY SINH:");
        }

        imagepng($im, $this->defaultTemplatePath);
        imagedestroy($im);
    }
}
