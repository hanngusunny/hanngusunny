<?php

namespace App\Services;

use App\Models\Voucher;
use Carbon\Carbon;

class VoucherService
{
    public function __construct(private VoucherImageService $imageService)
    {
    }

    /**
     * Create a voucher and generate its rendered image.
     *
     * @param  array{student_name:string,phone:string,dob:string,note?:string|null}  $validated
     */
    public function create(array $validated, ?int $createdBy = null): Voucher
    {
        $voucher = new Voucher();
        $voucher->code = Voucher::generateUniqueCode('SN-');
        $voucher->student_name = trim((string) $validated['student_name']);
        $voucher->phone = trim((string) $validated['phone']);
        $voucher->dob = ! empty($validated['dob']) ? Carbon::parse((string) $validated['dob']) : null;
        $voucher->note = $validated['note'] ?? null;
        $voucher->status = Voucher::STATUS_UNUSED;
        $voucher->created_by = $createdBy;
        $voucher->save();

        try {
            $this->imageService->generate($voucher);
        } catch (\Throwable $e) {
            report($e);
        }

        return $voucher;
    }

    /**
     * Format voucher payload for JSON responses.
     *
     * @return array<string, mixed>
     */
    public function toPayload(Voucher $voucher): array
    {
        return [
            'id' => $voucher->id,
            'code' => $voucher->code,
            'student_name' => $voucher->student_name,
            'phone' => $voucher->phone,
            'dob' => $voucher->dob ? $voucher->dob->format('d/m/Y') : '',
            'status' => $voucher->status,
            'status_text' => $voucher->statusText(),
            'image_url' => $voucher->imageUrl(),
        ];
    }
}
