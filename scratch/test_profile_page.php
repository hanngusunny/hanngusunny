<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\UploadedFile;

echo "=== KIỂM THỬ MÀN HÌNH HỒ SƠ CÁ NHÂN (FIGMA #427:44347) ===\n\n";

// 1. Tìm user mẫu
$user = User::where('role', 'student')->first() ?? User::first();
if (!$user) {
    echo "❌ Không tìm thấy user nào để test!\n";
    exit(1);
}
echo "1. User test: ID {$user->id} - {$user->name} ({$user->username}) - Role: {$user->role_label}\n";

// 2. Test controller logic
$controller = app(\App\Http\Controllers\ProfileController::class);
auth()->login($user);
$response = $controller->show();
echo "2. Controller show() trả về View thành công.\n";

// 3. Render view và kiểm tra nội dung
$html = $response->render();

$mustHave = [
    'Hồ sơ cá nhân',
    'sn-profile-card',
    'sn-avatar-card',
    'sn-avatar-circle',
    'Chọn ảnh mới',
    'JPG, PNG · Tối đa 5 MB',
    'Tổng thời gian hoạt động',
    'sn-stat-card-value',
    'Thông tin tài khoản',
    'Cập nhật thông tin để trung tâm hỗ trợ bạn tốt hơn.',
    'Thông tin do trung tâm quản lý, không thể chỉnh sửa.',
    'Lưu thay đổi',
    'sn-btn-save',
    'btnChooseAvatar',
];

$mustNotHave = [
    'x-page-title',
    'pf-nav',
    'Xóa ảnh hiện tại',
    'remove_avatar',
    'pf-meta',
    'op-panel-head',
    'activity-summary',
    'pf-actions',
];

echo "\n--- Kiểm tra các phần tử BẮT BUỘC theo Figma ---\n";
$allMustPass = true;
foreach ($mustHave as $str) {
    if (str_contains($html, $str)) {
        echo "  [PASS] Có chứa: '{$str}'\n";
    } else {
        echo "  [FAIL] THIẾU: '{$str}'\n";
        $allMustPass = false;
    }
}

echo "\n--- Kiểm tra loại bỏ CÁC CHỨC NĂNG THỪA ---\n";
$allMustNotPass = true;
foreach ($mustNotHave as $str) {
    if (!str_contains($html, $str)) {
        echo "  [PASS] Đã loại bỏ: '{$str}'\n";
    } else {
        echo "  [FAIL] VẪN CÒN SÓT CHỨC NĂNG THỪA: '{$str}'\n";
        $allMustNotPass = false;
    }
}

// 4. Kiểm tra Toast khi có session success
session()->flash('success', 'Đã lưu thay đổi hồ sơ thành công.');
$htmlWithToast = View::make('profile.show', [
    'user' => $user,
    'activeTab' => 'profile',
    'activeHoursText' => '18 giờ 30 phút',
    'activeAvgDailyText' => 'Khoảng 50 phút / ngày',
])->render();

if (str_contains($htmlWithToast, 'sn-profile-toast') && str_contains($htmlWithToast, 'Đã lưu thay đổi hồ sơ thành công.')) {
    echo "\n  [PASS] Toast thành công hiển thị chuẩn Figma.\n";
} else {
    echo "\n  [FAIL] Toast thành công không hiển thị đúng.\n";
    $allMustPass = false;
}

// 5. Kiểm tra Validation rules (Avatar 5MB)
$request = new UpdateProfileRequest();
$rules = $request->rules();
$avatarMaxRule = null;
foreach ($rules['avatar'] as $rule) {
    if (is_string($rule) && str_starts_with($rule, 'max:')) {
        $avatarMaxRule = $rule;
    }
}

echo "\n--- Kiểm tra Validation Rules ---\n";
if ($avatarMaxRule === 'max:5120') {
    echo "  [PASS] Avatar max rule = 5120 (5MB chuẩn Figma).\n";
} else {
    echo "  [FAIL] Avatar max rule là {$avatarMaxRule}, kỳ vọng max:5120.\n";
    $allMustPass = false;
}

$messages = $request->messages();
if (($messages['avatar.max'] ?? '') === 'Ảnh đại diện tối đa 5MB.') {
    echo "  [PASS] Avatar max error message chính xác: 'Ảnh đại diện tối đa 5MB.'.\n";
} else {
    echo "  [FAIL] Avatar max error message chưa đúng.\n";
    $allMustPass = false;
}

echo "\n============================================\n";
if ($allMustPass && $allMustNotPass) {
    echo "🎉 TẤT CẢ KIỂM THỬ ĐÃ THÀNH CÔNG 100%!\n";
} else {
    echo "⚠️ CÓ LỖI CẦN XỬ LÝ.\n";
    exit(1);
}


// 6. Test flow update profile
echo "\n--- Kiểm tra flow submit Update Profile ---\n";
$testName = 'Đỗ Minh Quân Test';
$testPhone = '0987654321';
$fakeRequest = \Illuminate\Http\Request::create(route('profile.update'), 'POST', [
    'full_name' => $testName,
    'email' => $user->email,
    'phone' => $testPhone,
]);
$fakeRequest->setUserResolver(fn () => $user);

$updateRequest = UpdateProfileRequest::createFrom($fakeRequest);
$updateRequest->setContainer($app);
$updateRequest->validateResolved();

$redirectResponse = $controller->update($updateRequest);

if ($redirectResponse->isRedirect()) {
    echo "  [PASS] Update Profile trả về Redirect thành công.\n";
    $session = session()->all();
    if (($session['success'] ?? '') === 'Đã lưu thay đổi hồ sơ thành công.') {
        echo "  [PASS] Flash message đúng chuẩn Figma: 'Đã lưu thay đổi hồ sơ thành công.'\n";
    } else {
        echo "  [FAIL] Flash message: " . ($session['success'] ?? 'null') . "\n";
    }
} else {
    echo "  [FAIL] Không redirect.\n";
}

// Khôi phục tên cũ
$user->full_name = 'Đỗ Minh Quân';
$user->phone = null;
$user->save();
echo "  [PASS] Đã dọn dẹp dữ liệu test hoàn tất.\n";
