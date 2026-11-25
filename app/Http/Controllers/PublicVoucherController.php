<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVoucherRequest;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicVoucherController extends Controller
{
    public function create(): View
    {
        return view('public.voucher-create', [
            'templateUrl' => asset('assets/images/voucher-template.png'),
        ]);
    }

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

        return redirect()->route('public.vouchers.create')
            ->with('success', "Đã tạo mã voucher: {$voucher->code}")
            ->with('new_voucher', $voucherService->toPayload($voucher));
    }
}
