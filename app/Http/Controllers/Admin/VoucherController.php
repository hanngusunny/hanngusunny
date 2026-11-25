<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVoucherRequest;
use App\Models\Voucher;
use App\Services\VoucherImageService;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class VoucherController extends Controller
{
    /**
     * Voucher list & creation form
     */
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $query = Voucher::query()->with(['userUsed', 'creator'])->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', "%{$q}%")
                    ->orWhere('student_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        if ($status !== '' && in_array($status, [Voucher::STATUS_UNUSED, Voucher::STATUS_USED], true)) {
            $query->where('status', $status);
        }

        $vouchers = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Voucher::count(),
            'unused' => Voucher::where('status', Voucher::STATUS_UNUSED)->count(),
            'used' => Voucher::where('status', Voucher::STATUS_USED)->count(),
        ];

        $templateUrl = asset('assets/images/voucher-template.png');

        return view('admin.vouchers.index', [
            'vouchers' => $vouchers,
            'stats' => $stats,
            'filters' => [
                'q' => $q,
                'status' => $status,
            ],
            'templateUrl' => $templateUrl,
        ]);
    }

    /**
     * Store new voucher and generate image
     */
    public function store(StoreVoucherRequest $request, VoucherService $voucherService): JsonResponse|RedirectResponse
    {
        $voucher = $voucherService->create($request->validated(), auth()->id());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Tạo mã voucher thành công!',
                'voucher' => $voucherService->toPayload($voucher),
            ]);
        }

        return redirect()->route('admin.vouchers.index')
            ->with('success', "Đã tạo mã voucher: {$voucher->code}")
            ->with('new_voucher_id', $voucher->id);
    }

    /**
     * Check voucher page
     */
    public function checkPage(): View
    {
        return view('admin.vouchers.check');
    }

    /**
     * AJAX endpoint to check voucher code
     */
    public function checkAjax(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('code', ''));

        if ($code === '') {
            return response()->json([
                'found' => false,
                'status' => 'invalid',
                'status_text' => 'ĐÃ SỬ DỤNG / KHÔNG TỒN TẠI',
                'message' => 'Vui lòng nhập mã voucher.',
            ]);
        }

        $voucher = Voucher::where('code', $code)
            ->with(['userUsed', 'creator'])
            ->first();

        if (!$voucher) {
            return response()->json([
                'found' => false,
                'status' => 'not_found',
                'status_text' => 'ĐÃ SỬ DỤNG / KHÔNG TỒN TẠI',
                'message' => 'Mã voucher không tồn tại hoặc đã hết hạn!',
            ]);
        }

        return response()->json([
            'found' => true,
            'status' => $voucher->status,
            'status_text' => $voucher->isUsed() ? 'ĐÃ SỬ DỤNG' : 'CHƯA SỬ DỤNG',
            'is_unused' => $voucher->isUnused(),
            'is_used' => $voucher->isUsed(),
            'voucher' => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'student_name' => $voucher->student_name,
                'phone' => $voucher->phone,
                'dob' => $voucher->dob ? $voucher->dob->format('d/m/Y') : '—',
                'created_at' => $voucher->created_at ? $voucher->created_at->format('d/m/Y H:i') : '—',
                'used_at' => $voucher->used_at ? $voucher->used_at->format('d/m/Y H:i') : null,
                'used_by_name' => $voucher->userUsed ? ($voucher->userUsed->full_name ?: $voucher->userUsed->name) : null,
                'image_url' => $voucher->imageUrl(),
                'note' => $voucher->note,
            ],
        ]);
    }

    /**
     * Apply / Redeem voucher (Locks the code and marks as ĐÃ SỬ DỤNG)
     */
    public function redeem(Request $request): JsonResponse|RedirectResponse
    {
        $code = trim((string) $request->input('code', ''));
        $voucherId = $request->input('voucher_id');

        $query = Voucher::query();
        if ($voucherId) {
            $query->where('id', $voucherId);
        } else {
            $query->where('code', $code);
        }

        $voucher = $query->first();

        if (!$voucher) {
            $msg = 'Mã voucher không tồn tại.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg, 'status_text' => 'ĐÃ SỬ DỤNG / KHÔNG TỒN TẠI'], 404);
            }
            return back()->with('error', $msg);
        }

        if ($voucher->isUsed()) {
            $msg = 'Voucher này đã được sử dụng trước đó!';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg, 'status_text' => 'ĐÃ SỬ DỤNG'], 422);
            }
            return back()->with('error', $msg);
        }

        // Lock voucher immediately and mark as USED
        $voucher->status = Voucher::STATUS_USED;
        $voucher->used_at = now();
        $voucher->used_by = auth()->id();
        $voucher->save();

        $userName = auth()->user() ? (auth()->user()->full_name ?: auth()->user()->name) : 'Quản lý';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Áp dụng Voucher thành công! Mã đã chuyển sang ĐÃ SỬ DỤNG.',
                'status_text' => 'ĐÃ SỬ DỤNG',
                'voucher' => [
                    'id' => $voucher->id,
                    'code' => $voucher->code,
                    'status' => $voucher->status,
                    'status_text' => 'ĐÃ SỬ DỤNG',
                    'used_at' => $voucher->used_at->format('d/m/Y H:i'),
                    'used_by_name' => $userName,
                ],
            ]);
        }

        return back()->with('success', "Đã áp dụng voucher {$voucher->code} thành công!");
    }

    /**
     * Re-render image for an existing voucher
     */
    public function regenerate(Voucher $voucher, VoucherImageService $imageService): RedirectResponse
    {
        try {
            $imageService->generate($voucher);
            return back()->with('success', "Đã xuất lại ảnh cho voucher {$voucher->code}!");
        } catch (\Throwable $e) {
            return back()->with('error', 'Lỗi khi xuất ảnh: ' . $e->getMessage());
        }
    }

    /**
     * Delete voucher
     */
    public function destroy(Voucher $voucher): RedirectResponse
    {
        if ($voucher->image_path && File::exists(public_path($voucher->image_path))) {
            @File::delete(public_path($voucher->image_path));
        }

        $code = $voucher->code;
        $voucher->delete();

        return back()->with('success', "Đã xóa voucher {$code}.");
    }
}
