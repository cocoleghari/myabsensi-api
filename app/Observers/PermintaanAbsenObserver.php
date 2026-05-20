<?php

namespace App\Observers;

use App\Models\PermintaanAbsen;

class PermintaanAbsenObserver
{
    public function created(PermintaanAbsen $permintaan): void
    {
        // Karena notifikasi sudah dihandle di Controller (termasuk
        // logika approver vs fallback admin), Observer ini dikosongkan
        // untuk menghindari notifikasi duplikat.
    }

    public function updated(PermintaanAbsen $permintaan): void
    {
        // Juga sudah dihandle di PermintaanAbsenController::proses()
    }

    public function deleted(PermintaanAbsen $permintaanAbsen): void {}

    public function restored(PermintaanAbsen $permintaanAbsen): void {}

    public function forceDeleted(PermintaanAbsen $permintaanAbsen): void {}
}
