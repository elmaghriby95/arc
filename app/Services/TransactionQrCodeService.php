<?php

namespace App\Services;

use App\Models\Transaction;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TransactionQrCodeService
{
    public function payload(Transaction $transaction): string
    {
        $transaction->loadMissing('folder');
        $folder = $transaction->folder;

        return implode('|', [
            $transaction->archival_reference,
            $folder?->cabinet_number ?? '',
            $folder?->row_number ?? '',
            $folder?->box_number ?? '',
        ]);
    }

    public function svg(Transaction $transaction, int $size = 200): string
    {
        return (string) QrCode::size($size)
            ->margin(1)
            ->encoding('UTF-8')
            ->generate($this->payload($transaction));
    }
}
