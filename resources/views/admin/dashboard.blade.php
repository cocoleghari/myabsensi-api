@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @php
        $hadir = \App\Models\Absensi::whereDate('created_at', today())->count();
        $izin = \App\Models\PermintaanAbsen::where('status', 'pending')->count();
        $alpha = max(0, $totalKaryawan - $hadir - $izin);
        $pct = $totalKaryawan > 0 ? round(($hadir / $totalKaryawan) * 100) : 0;
        $c = 2 * pi() * 38;
        $dH = round(($hadir / max($totalKaryawan, 1)) * $c, 1);
        $dI = round(($izin / max($totalKaryawan, 1)) * $c, 1);
        $dA = round(($alpha / max($totalKaryawan, 1)) * $c, 1);
    @endphp

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 mb-6">
        <div
            class="bg-white border border-gray-100 rounded-2xl p-5 sm:p-6 shadow-[0_2px_8px_rgba(16,24,40,0.05)] hover:shadow-[0_8px_24px_rgba(16,24,40,0.08)] hover:-translate-y-0.5 transition-all">
            <div class="flex items-start justify-between mb-4">
                <span class="text-[13px] text-gray-400 font-medium leading-tight">Total karyawan</span>
                <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 tracking-tight">{{ $totalKaryawan }}</p>
            <p class="text-[12.5px] text-emerald-600 mt-1.5 font-semibold">+4 bulan ini</p>
        </div>

        <div
            class="bg-white border border-gray-100 rounded-2xl p-5 sm:p-6 shadow-[0_2px_8px_rgba(16,24,40,0.05)] hover:shadow-[0_8px_24px_rgba(16,24,40,0.08)] hover:-translate-y-0.5 transition-all">
            <div class="flex items-start justify-between mb-4">
                <span class="text-[13px] text-gray-400 font-medium leading-tight">Hadir hari ini</span>
                <div class="w-11 h-11 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 tracking-tight">{{ $absensiHariIni }}</p>
            <p class="text-[12.5px] text-gray-400 mt-1.5 font-medium">{{ $pct }}% kehadiran</p>
        </div>

        <div
            class="bg-white border border-gray-100 rounded-2xl p-5 sm:p-6 shadow-[0_2px_8px_rgba(16,24,40,0.05)] hover:shadow-[0_8px_24px_rgba(16,24,40,0.08)] hover:-translate-y-0.5 transition-all">
            <div class="flex items-start justify-between mb-4">
                <span class="text-[13px] text-gray-400 font-medium leading-tight">Menunggu persetujuan</span>
                <div class="w-11 h-11 bg-amber-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 tracking-tight">{{ $menungguPersetujuan }}</p>
            <p class="text-[12.5px] text-rose-500 mt-1.5 font-semibold">+3 dari kemarin</p>
        </div>

        <div
            class="bg-white border border-gray-100 rounded-2xl p-5 sm:p-6 shadow-[0_2px_8px_rgba(16,24,40,0.05)] hover:shadow-[0_8px_24px_rgba(16,24,40,0.08)] hover:-translate-y-0.5 transition-all">
            <div class="flex items-start justify-between mb-4">
                <span class="text-[13px] text-gray-400 font-medium leading-tight">Total akun user</span>
                <div class="w-11 h-11 bg-violet-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 tracking-tight">{{ $totalUser }}</p>
            <p class="text-[12.5px] text-gray-400 mt-1.5 font-medium">Akun aktif</p>
        </div>
    </div>

    {{-- Bottom Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- Tabel Absensi --}}
        <div class="bg-white border border-gray-100 rounded-2xl p-6 sm:p-7 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-sm font-bold text-gray-800">Absensi terbaru</h3>
                <a href="{{ route('admin.laporan-absensi') }}"
                    class="text-[13px] font-semibold text-orange-500 hover:text-orange-600 transition-colors">Lihat semua
                    →</a>
            </div>
            <div class="overflow-x-auto -mx-1">
                <table class="w-full min-w-[460px]">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left pb-3 px-1 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                Karyawan</th>
                            <th class="text-left pb-3 px-1 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                Waktu</th>
                            <th class="text-left pb-3 px-1 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(\App\Models\Absensi::with(['employee','pusatLokasi'])->whereDate('created_at', today())->latest()->take(8)->get() as $absen)
                            @php
                                $initials = strtoupper(substr($absen->employee?->full_name ?? '?', 0, 2));
                                $colors = [
                                    'bg-blue-50 text-blue-700',
                                    'bg-emerald-50 text-emerald-700',
                                    'bg-violet-50 text-violet-700',
                                    'bg-pink-50 text-pink-700',
                                    'bg-amber-50 text-amber-700',
                                ];
                                $color = $colors[$loop->index % count($colors)];
                            @endphp
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="py-3.5 px-1">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-full {{ $color }} flex items-center justify-center text-[11px] font-bold flex-shrink-0">
                                            {{ $initials }}</div>
                                        <span
                                            class="text-[13px] font-medium text-gray-800 truncate max-w-[140px]">{{ $absen->employee?->full_name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-1 text-[13px] text-gray-400 font-medium">
                                    {{ $absen->waktu_absen ? \Carbon\Carbon::parse($absen->waktu_absen)->format('H:i') : '—' }}
                                </td>
                                <td class="py-3.5 px-1">
                                    @if ($absen->status === 'tepat_waktu')
                                        <span
                                            class="text-[11.5px] px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold">Tepat
                                            waktu</span>
                                    @else
                                        <span
                                            class="text-[11.5px] px-3 py-1 rounded-full bg-rose-50 text-rose-600 font-semibold">Terlambat</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-12 text-center text-[13px] text-gray-400">
                                    Belum ada absensi hari ini
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Donut + Progress Bar --}}
        <div class="bg-white border border-gray-100 rounded-2xl p-6 sm:p-7 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-sm font-bold text-gray-800">Rekap kehadiran</h3>
                <span class="text-[11.5px] text-gray-400 font-semibold bg-gray-50 px-2.5 py-1 rounded-full">Hari ini</span>
            </div>
            <div class="flex items-center gap-7 flex-wrap">
                <svg width="130" height="130" viewBox="0 0 100 100" class="flex-shrink-0">
                    <circle cx="50" cy="50" r="38" fill="none" stroke="#F3F4F6" stroke-width="11" />
                    <circle cx="50" cy="50" r="38" fill="none" stroke="#16a34a" stroke-width="11"
                        stroke-linecap="round" stroke-dasharray="{{ $dH }} {{ $c - $dH }}"
                        stroke-dashoffset="0" transform="rotate(-90 50 50)" />
                    <circle cx="50" cy="50" r="38" fill="none" stroke="#d97706" stroke-width="11"
                        stroke-linecap="round" stroke-dasharray="{{ $dI }} {{ $c - $dI }}"
                        stroke-dashoffset="-{{ $dH }}" transform="rotate(-90 50 50)" />
                    <circle cx="50" cy="50" r="38" fill="none" stroke="#dc2626" stroke-width="11"
                        stroke-linecap="round" stroke-dasharray="{{ $dA }} {{ $c - $dA }}"
                        stroke-dashoffset="-{{ $dH + $dI }}" transform="rotate(-90 50 50)" />
                    <text x="50" y="46" text-anchor="middle" font-size="17" font-weight="800"
                        fill="#111827">{{ $pct }}%</text>
                    <text x="50" y="60" text-anchor="middle" font-size="8" font-weight="600" fill="#9CA3AF">dari
                        {{ $totalKaryawan }}</text>
                </svg>
                <div class="flex-1 min-w-[180px] space-y-4">
                    <div>
                        <div class="flex justify-between text-[13px] mb-2">
                            <span class="text-gray-500 font-medium">Hadir</span>
                            <span class="font-bold text-gray-800">{{ $hadir }}</span>
                        </div>
                        <div class="h-2 bg-emerald-100 rounded-full overflow-hidden">
                            <div class="h-2 bg-emerald-600 rounded-full transition-all"
                                style="width:{{ $pct }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-[13px] mb-2">
                            <span class="text-gray-500 font-medium">Izin / sakit</span>
                            <span class="font-bold text-gray-800">{{ $izin }}</span>
                        </div>
                        <div class="h-2 bg-amber-100 rounded-full overflow-hidden">
                            <div class="h-2 bg-amber-500 rounded-full transition-all"
                                style="width:{{ $totalKaryawan > 0 ? round(($izin / $totalKaryawan) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-[13px] mb-2">
                            <span class="text-gray-500 font-medium">Alpha</span>
                            <span class="font-bold text-gray-800">{{ $alpha }}</span>
                        </div>
                        <div class="h-2 bg-rose-100 rounded-full overflow-hidden">
                            <div class="h-2 bg-rose-500 rounded-full transition-all"
                                style="width:{{ $totalKaryawan > 0 ? round(($alpha / $totalKaryawan) * 100) : 0 }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
