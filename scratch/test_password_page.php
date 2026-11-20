<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UpdatePasswordRequest;

echo "=== KIỂM THỬ MÀN HÌNH ĐỔI MẬT KHẨU (FIGMA #569:44472) ===\n\n";

// 1. User test
$user = User::where('role', 'student')->first() ?? User::first();
if (!$user) {
    echo "❌ Không tìm thấy user nào để test!\n";
    exit(1);
}
echo "1. User test: ID {$user->id} - {$user->name} ({$user->username})\n";

// 2. Controller password() view
$controller = app(\App\Http\Controllers\ProfileController::class);
auth()->login($user);
$response = $controller->password();
echo "2. Controller password() trả về View thành công.\n";

$html = $response->render();

$mustHave = [
    'Đổi mật khẩu',
    'sn-profile-card',
    'sn-security-container',
    'sn-password-form-card',
    'Thay đổi mật khẩu',
    'Mật khẩu hiện tại',
    'Mật khẩu mới',
    'Xác nhận mật khẩu mới',
    'passwordRules',
    'Ít nhất 8 ký tự',
    'Có chữ hoa (A–Z)',
    'Có chữ thường (a–z)',
    'Có chữ số (0–9)',
    'sn-btn-cancel',
    'Hủy',
    'sn-btn-submit-password',
    'Cập nhật mật khẩu',
    'sn-security-guide',
    'Không dùng ngày sinh hoặc số điện thoại làm mật khẩu.',
];

$mustNotHave = [
    'x-page-title',
    'pf-nav',
    'pf-tips',
    'pf-strength',
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

// 3. Kiểm tra Toast khi có session success chuẩn Figma
session()->flash('success', 'Cập nhật mật khẩu thành công. Hãy sử dụng mật khẩu mới trong lần đăng nhập tiếp theo.');
$htmlWithToast = View::make('profile.password', [
    'user' => $user,
])->render();

if (str_contains($htmlWithToast, 'sn-profile-toast') && str_contains($htmlWithToast, 'Cập nhật mật khẩu thành công. Hãy sử dụng mật khẩu mới trong lần đăng nhập tiếp theo.')) {
    echo "\n  [PASS] Toast thành công hiển thị chuẩn Figma.\n";
} else {
    echo "\n  [FAIL] Toast thành công không hiển thị đúng.\n";
    $allMustPass = false;
}

// 4. Kiểm tra Validation rules (Mật khẩu min:8)
$request = new UpdatePasswordRequest();
$rules = $request->rules();
$passwordMinRule = null;
foreach ($rules['password'] as $rule) {
    if (is_string($rule) && str_starts_with($rule, 'min:')) {
        $passwordMinRule = $rule;
    }
}

echo "\n--- Kiểm tra Validation Rules ---\n";
if ($passwordMinRule === 'min:8') {
    echo "  [PASS] Password min rule = 8 (Chuẩn Figma ít nhất 8 ký tự).\n";
} else {
    echo "  [FAIL] Password min rule là {$passwordMinRule}, kỳ vọng min:8.\n";
    $allMustPass = false;
}

$messages = $request->messages();
if (($messages['password.min'] ?? '') === 'Mật khẩu mới phải có ít nhất 8 ký tự.') {
    echo "  [PASS] Message password.min chính xác: 'Mật khẩu mới phải có ít nhất 8 ký tự.'.\n";
} else {
    echo "  [FAIL] Message password.min chưa đúng.\n";
    $allMustPass = false;
}

if (($messages['password.confirmed'] ?? '') === 'Mật khẩu xác nhận không khớp với mật khẩu mới.') {
    echo "  [PASS] Message password.confirmed chính xác: 'Mật khẩu xác nhận không khớp với mật khẩu mới.'.\n";
} else {
    echo "  [FAIL] Message password.confirmed chưa đúng.\n";
    $allMustPass = false;
}

echo "\n============================================\n";
if ($allMustPass && $allMustNotPass) {
    echo "🎉 TẤT CẢ KIỂM THỬ ĐÃ THÀNH CÔNG 100%!\n";
} else {
    echo "⚠️ CÓ LỖI CẦN XỬ LÝ.\n";
    exit(1);
}


// 5. Kiểm tra Flow Submit Đổi mật khẩu
echo "\n--- Kiểm tra flow submit Update Password ---\n";

// Set mật khẩu tạm thời cho user để test
$currentPass = 'MatKhauCu123!';
$newPass = 'MatKhauMoi456@';
$user->password = Hash::make($currentPass);
$user->save();

$fakeRequest = \Illuminate\Http\Request::create(route('profile.password.update'), 'POST', [
    'current_password' => $currentPass,
    'password' => $newPass,
    'password_confirmation' => $newPass,
]);
$fakeRequest->setUserResolver(fn () => $user);

$updatePasswordRequest = UpdatePasswordRequest::createFrom($fakeRequest);
$updatePasswordRequest->setContainer($app);
$updatePasswordRequest->validateResolved();

$redirectResponse = $controller->updatePassword($updatePasswordRequest);

if ($redirectResponse->isRedirect()) {
    echo "  [PASS] Update Password trả về Redirect thành công.\n";
    $session = session()->all();
    $expectedMsg = 'Cập nhật mật khẩu thành công. Hãy sử dụng mật khẩu mới trong lần đăng nhập tiếp theo.';
    if (($session['success'] ?? '') === $expectedMsg) {
        echo "  [PASS] Flash message đúng chuẩn Figma: '{$expectedMsg}'\n";
    } else {
        echo "  [FAIL] Flash message: " . ($session['success'] ?? 'null') . "\n";
    }
} else {
    echo "  [FAIL] Không redirect.\n";
}

// Kiểm tra mật khẩu mới đã được băm và lưu vào DB
$user->refresh();
if (Hash::check($newPass, $user->password)) {
    echo "  [PASS] Mật khẩu mới đã được cập nhật chính xác trong Database.\n";
} else {
    echo "  [FAIL] Mật khẩu mới chưa được lưu vào Database.\n";
}
