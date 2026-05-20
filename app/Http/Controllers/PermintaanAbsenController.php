<?php

namespace App\Http\Controllers;

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

        // Load department + parent untuk resolveApprover
        $employee->load('department.manager', 'department.parent.manager');

        // Simpan foto
        $foto = $request->file('foto_wajah');
        $fotoPath = $foto->storeAs(
            'permintaan_absen',
            'req_'.$employee->id.'_'.time().'.'.$foto->getClientOriginalExtension(),
            'public'
        );

        // Tentukan approver
        $approver = PermintaanAbsen::resolveApprover($employee);

        DB::beginTransaction();
        try {
            $permintaan = PermintaanAbsen::create([
                'employee_id' => $employee->id,
                'pusat_lokasi_id' => $request->pusat_lokasi_id,
                'tipe_absen' => $request->tipe_absen,
                'waktu_pengajuan' => now(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'jarak_meter' => $request->jarak_meter,
                'alasan' => $request->alasan,
                'keterangan' => $request->keterangan,
                'foto_path' => $fotoPath,
                'approver_employee_id' => $approver?->id,
                'alamat_pengajuan' => $request->alamat_pengajuan,
            ]);

            // Notifikasi ke approver (manager)
            if ($approver?->user_id) {
                Notification::create([
                    'user_id' => $approver->user_id,
                    'type' => 'permintaan_absen',
                    'title' => 'Permintaan Izin Lokasi',
                    'subtitle' => $employee->full_name.' · '.$request->alasan,
                    'category' => 'request',
                    'data' => json_encode(['permintaan_id' => $permintaan->id]),
                ]);
            } else {
                // Fallback: notif ke semua admin/superadmin
                User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'superadmin'])
                )->each(function ($admin) use ($permintaan, $employee, $request) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'permintaan_absen',
                        'title' => 'Permintaan Izin Lokasi',
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
                // Ambil shift aktif karyawan saat waktu pengajuan
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

                // Hitung menit terlambat / lembur
                $menitTerlambat = 0;
                $menitLembur = 0;
                $statusAbsen = 'tepat_waktu';

                if ($shift) {
                    if ($permintaan->tipe_absen === 'masuk') {
                        $menitTerlambat = $shift->hitungMenitTerlambat($permintaan->waktu_pengajuan);
                        $statusAbsen = $menitTerlambat > 0 ? 'terlambat' : 'tepat_waktu';
                    } else {
                        $menitLembur = $shift->hitungMenitLembur($permintaan->waktu_pengajuan);
                        $statusAbsen = $menitLembur > 0 ? 'lembur' : 'tepat_waktu';
                    }
                }

                $absensi = Absensi::create([
                    'employee_id' => $permintaan->employee_id,
                    'pusat_lokasi_id' => $permintaan->pusat_lokasi_id,
                    'shift_id' => $shift?->id,                    // ← dari shift aktif
                    'tanggal_absen' => $shift
                        ? $shift->tanggalLogisAbsensi($permintaan->waktu_pengajuan)->toDateString()
                        : $permintaan->waktu_pengajuan->toDateString(),
                    'tipe_absen' => $permintaan->tipe_absen,
                    'waktu_absen' => $permintaan->waktu_pengajuan,
                    'latitude' => $permintaan->latitude,
                    'longitude' => $permintaan->longitude,
                    'jarak_meter' => $permintaan->jarak_meter,
                    'foto_absen_path' => $permintaan->foto_path ?? null, // ← path lengkap
                    'confidence_score' => null,                           // tidak ada face recognition
                    'wajah_cocok' => false,
                    'status' => $statusAbsen,                   // ← dihitung dari shift
                    'menit_terlambat' => $menitTerlambat,                // ← dihitung dari shift
                    'menit_lembur' => $menitLembur,                   // ← dihitung dari shift
                    'catatan' => 'Disetujui via permintaan izin lokasi oleh '
                                          .$managerEmployee->full_name,
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
}
