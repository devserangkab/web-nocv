<?php

namespace App\Livewire\Concerns;

/**
 * Notifikasi toast via event browser — dipakai komponen Livewire
 * untuk umpan balik sukses operasi tulis (create/update/delete).
 */
trait Notifies
{
    public function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', type: $type, message: $message);
    }
}
