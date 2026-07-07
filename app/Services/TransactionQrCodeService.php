<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionQrSetting;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TransactionQrCodeService
{
    public function settings(): TransactionQrSetting
    {
        return TransactionQrSetting::instance();
    }

    public function displaySize(): int
    {
        return $this->settings()->display_size;
    }

    public function printSize(): int
    {
        return $this->settings()->print_size;
    }

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

    public function svg(Transaction $transaction, ?int $size = null): string
    {
        $size ??= $this->displaySize();

        return (string) QrCode::size($size)
            ->margin(1)
            ->encoding('UTF-8')
            ->generate($this->payload($transaction));
    }
}
