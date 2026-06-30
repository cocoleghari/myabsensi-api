<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Notification;
use App\Models\PermintaanAbsen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PermintaanAbsenController extends Controller
{
    // ── Karyawan: ajukan permintaan ────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'tipe_absen' => 'required|in:masuk,pulang',
            'jenis' => 'required|in:izin_lokasi,koreksi_lupa_masuk',
            'waktu_koreksi' => 'required_if:jenis,koreksi_lupa_masuk|nullable|date',
            'alasan' => 'required|string|max:100',
            'keterangan' => 'nullable|string|max:500',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'jarak_meter' => 'nullable|numeric',
            'pusat_lokasi_id' => 'nullable|exists:pusat_lokasis,id',
            'foto_wajah' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'alamat_pengajuan' => 'nullable|string|max:500',
        ]);

        $employee = $request->user()->employee;
        if (! $employee) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 403);
        }

        $jenis = $request->jenis;

        // Untuk koreksi lupa masuk: pastikan belum ada absen masuk hari ini
        if ($jenis === 'koreksi_lupa_masuk') {
            $shift = $this->getShiftAktif($employee);
            $sekarang = now()->setTimezone('Asia/Jakarta');
            $tanggalLogis = $shift
                ? $shift->tanggalLogisAbsensi($sekarang)
                : $sekarang->copy()->startOfDay();

            $sudahMasuk = Absensi::where('employee_id', $employee->id)
                ->where('tipe_absen', 'masuk')
                ->whereDate('tanggal_absen', $tanggalLogis->toDateString())
                ->exists();

            if ($sudahMasuk) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah memiliki absen masuk hari ini. Koreksi tidak diperlukan.',
                ], 422);
            }
        }

        $employee->load('department.manager', 'department.parent.manager');

        $foto = $request->file('foto_wajah');
        $fotoPath = $foto->storeAs(
            'permintaan_absen',
            'req_'.$employee->id.'_'.time().'.'.$foto->getClientOriginalExtension(),
            'public'
        );

        $approver = PermintaanAbsen::resolveApprover($employee);

        DB::beginTransaction();
        try {
            $permintaan = PermintaanAbsen::create([
                'employee_id' => $employee->id,
                'pusat_lokasi_id' => $request->pusat_lokasi_id,
                'tipe_absen' => $request->tipe_absen,
                'jenis' => $jenis,
                'waktu_pengajuan' => now(),
                'waktu_koreksi' => $jenis === 'koreksi_lupa_masuk'
                                            ? \Carbon\Carbon::parse($request->waktu_koreksi)
                                            : null,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'jarak_meter' => $request->jarak_meter,
                'alasan' => $request->alasan,
                'keterangan' => $request->keterangan,
                'foto_path' => $fotoPath,
                'approver_employee_id' => $approver?->id,
                'alamat_pengajuan' => $request->alamat_pengajuan,
            ]);

            // Notifikasi ke approver
            $judulNotif = $jenis === 'koreksi_lupa_masuk'
                ? 'Koreksi Lupa Absen Masuk'
                : 'Permintaan Izin Lokasi';

            if ($approver?->user_id) {
                Notification::create([
                    'user_id' => $approver->user_id,
                    'type' => 'permintaan_absen',
                    'title' => $judulNotif,
                    'subtitle' => $employee->full_name.' · '.$request->alasan,
                    'category' => 'request',
                    'data' => json_encode(['permintaan_id' => $permintaan->id]),
                ]);
            } else {
                User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'superadmin']))
                    ->each(function ($admin) use ($permintaan, $employee, $request, $judulNotif) {
                        Notification::create([
                            'user_id' => $admin->id,
                            'type' => 'permintaan_absen',
                            'title' => $judulNotif,
                            'subtitle' => $employee->full_name.' · '.$request->alasan,
                            'category' => 'request',
                            'data' => json_encode(['permintaan_id' => $permintaan->id]),
                        ]);
                    });
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permintaan berhasil dikirim'
                    .($approver ? ' ke '.$approver->full_name : ' ke admin'),
                'data' => $permintaan,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Storage::disk('public')->delete($fotoPath);
            Log::error('PermintaanAbsen store error: '.$e->getMessage());

            return response()->json(['message' => 'Gagal menyimpan permintaan.'], 500);
        }
    }

    // ── Manager: lihat permintaan yang perlu di-approve ───────────────

    public function indexUntukManager(Request $request)
    {
        $managerEmployee = $request->user()->employee;
        if (! $managerEmployee) {
            return response()->json(['message' => 'Profil tidak ditemukan.'], 403);
        }

        $query = PermintaanAbsen::where('approver_employee_id', $managerEmployee->id)
            ->with([
                'employee:id,full_name,employee_code,photo_url,department_id',
                'employee.department:id,name',
                'pusatLokasi:id,nama_lokasi',
            ])
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(20),
        ]);
    }

    // ── Manager: approve / reject ──────────────────────────────────────

    public function proses(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'catatan_admin' => 'nullable|string|max:500',
        ]);

        $managerEmployee = $request->user()->employee;

        $permintaan = PermintaanAbsen::with([
            'employee.user',
            'employee.department',
        ])->findOrFail($id);

        // Pastikan hanya approver yang bisa proses
        if ($permintaan->approver_employee_id != $managerEmployee->id) {
            // Bolehkan juga admin/superadmin
            $isAdmin = in_array($request->user()->role, ['admin', 'superadmin']);
            if (! $isAdmin) {
                return response()->json(['message' => 'Tidak diizinkan.'], 403);
            }
        }

        if (! $permintaan->isPending()) {
            return response()->json(['message' => 'Permintaan sudah diproses.'], 422);
        }

        DB::beginTransaction();
        try {
            $permintaan->update([
                'status' => $request->status,
                'diproses_oleh' => $request->user()->id,
                'diproses_pada' => now(),
                'catatan_admin' => $request->catatan_admin,
            ]);

            // Jika approved → buat record absensi otomatis
            if ($request->status === 'approved') {
                $employeeShift = \App\Models\EmployeeShift::aktifPada($permintaan->waktu_pengajuan)
                    ->where('employee_id', $permintaan->employee_id)
                    ->with(['shift', 'pattern.days.shift'])
                    ->first();

                $shift = null;
                if ($employeeShift) {
                    if ($employeeShift->shift_id && $employeeShift->shift) {
                        $shift = $employeeShift->shift;
                    } elseif ($employeeShift->pattern_id && $employeeShift->pattern) {
                        $shift = $employeeShift->pattern->getShiftForDate($permintaan->waktu_pengajuan);
                    }
                }

                // Untuk koreksi: gunakan waktu_koreksi sebagai waktu absen masuk
                $isKoreksi = $permintaan->jenis === 'koreksi_lupa_masuk';
                $waktuAbsen = $isKoreksi && $permintaan->waktu_koreksi
                    ? $permintaan->waktu_koreksi
                    : $permintaan->waktu_pengajuan;

                $menitTerlambat = 0;
                $menitLembur = 0;
                $statusAbsen = 'tepat_waktu';

                if ($shift) {
                    if ($permintaan->tipe_absen === 'masuk') {
                        $menitTerlambat = $shift->hitungMenitTerlambat($waktuAbsen);
                        if ($shift->isFlex()) {
                            $statusAbsen = 'hadir';
                        } else {
                            $statusAbsen = $menitTerlambat > 0 ? 'terlambat' : 'tepat_waktu';
                        }
                    } else {
                        $menitLembur = $shift->hitungMenitLembur($waktuAbsen);
                        if ($shift->isFlex()) {
                            $statusAbsen = 'hadir';
                        } else {
                            $statusAbsen = $menitLembur > 0 ? 'lembur' : 'tepat_waktu';
                        }
                    }
                } elseif (! $shift) {
                    $statusAbsen = 'hadir';
                }

                // Tanggal logis — untuk koreksi pakai waktu_koreksi
                $tanggalAbsen = $shift
                    ? $shift->tanggalLogisAbsensi($waktuAbsen)->toDateString()
                    : $waktuAbsen->toDateString();

                // Jika koreksi: pastikan belum ada absen masuk di tanggal tersebut
                if ($isKoreksi) {
                    $sudahAda = Absensi::where('employee_id', $permintaan->employee_id)
                        ->where('tipe_absen', 'masuk')
                        ->whereDate('tanggal_absen', $tanggalAbsen)
                        ->exists();

                    if ($sudahAda) {
                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' => 'Absen masuk untuk tanggal tersebut sudah ada. Tidak dapat membuat duplikat.',
                        ], 422);
                    }
                }

                $absensi = Absensi::create([
                    'employee_id' => $permintaan->employee_id,
                    'pusat_lokasi_id' => $permintaan->pusat_lokasi_id,
                    'shift_id' => $shift?->id,
                    'tanggal_absen' => $tanggalAbsen,
                    'tipe_absen' => $permintaan->tipe_absen,
                    'waktu_absen' => $waktuAbsen,
                    'latitude' => $permintaan->latitude,
                    'longitude' => $permintaan->longitude,
                    'jarak_meter' => $permintaan->jarak_meter,
                    'foto_absen_path' => $permintaan->foto_path ?? null,
                    'confidence_score' => null,
                    'wajah_cocok' => false,
                    'status' => $statusAbsen,
                    'menit_terlambat' => $menitTerlambat,
                    'menit_lembur' => $menitLembur,
                    'catatan' => $isKoreksi
                        ? 'Koreksi lupa absen masuk, disetujui oleh '.$managerEmployee->full_name
                        : 'Disetujui via permintaan izin lokasi oleh '.$managerEmployee->full_name,
                ]);

                $permintaan->update(['absensi_id' => $absensi->id]);
            }

            // Notifikasi balik ke karyawan
            if ($permintaan->employee->user_id) {
                $labelStatus = $request->status === 'approved' ? 'Disetujui' : 'Ditolak';
                Notification::create([
                    'user_id' => $permintaan->employee->user_id,
                    'type' => $request->status,
                    'title' => "Izin Lokasi {$labelStatus}",
                    'subtitle' => $request->status === 'approved'
                        ? 'Absensi Anda telah dicatat oleh sistem'
                        : 'Alasan: '.($request->catatan_admin ?? '-'),
                    'category' => 'approvals',
                    'data' => json_encode(['permintaan_id' => $permintaan->id]),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permintaan berhasil '
                    .($request->status === 'approved' ? 'disetujui' : 'ditolak'),
                'data' => $permintaan->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PermintaanAbsen proses error: '.$e->getMessage());

            return response()->json(['message' => 'Gagal memproses permintaan.'], 500);
        }
    }

    // ── Karyawan: lihat riwayat permintaan sendiri ─────────────────────

    public function riwayat(Request $request)
    {
        $employee = $request->user()->employee;
        if (! $employee) {
            return response()->json(['message' => 'Profil tidak ditemukan.'], 403);
        }

        $permintaans = PermintaanAbsen::where('employee_id', $employee->id)
            ->with(['approver:id,full_name', 'pusatLokasi:id,nama_lokasi'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $permintaans]);
    }

    public function show(Request $request, int $id)
    {
        $managerEmployee = $request->user()->employee;

        $permintaan = PermintaanAbsen::with([
            'employee:id,full_name,employee_code,photo_url,department_id',
            'employee.department:id,name',
            'pusatLokasi:id,nama_lokasi',
            'approver:id,full_name',
        ])->findOrFail($id);

        // Hanya approver atau admin yang boleh lihat
        $isApprover = $permintaan->approver_employee_id == $managerEmployee?->id;
        $isAdmin = in_array($request->user()->role, ['admin', 'superadmin']);

        if (! $isApprover && ! $isAdmin) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $permintaan,
        ]);
    }

    private function getShiftAktif(Employee $employee): ?\App\Models\Shift
    {
        $employeeShift = \App\Models\EmployeeShift::aktifPada(now())
            ->where('employee_id', $employee->id)
            ->with(['shift', 'pattern.days.shift'])
            ->first();

        if (! $employeeShift) {
            return null;
        }

        if ($employeeShift->shift_id && $employeeShift->shift) {
            return $employeeShift->shift;
        }

        if ($employeeShift->pattern_id && $employeeShift->pattern) {
            return $employeeShift->pattern->getShiftForDate(now());
        }

        return null;
    }
}
