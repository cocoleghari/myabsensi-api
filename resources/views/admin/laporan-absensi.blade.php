@extends('layouts.admin')

@section('title', 'Laporan Absensi')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <div id="photo-preview" class="fixed z-50 hidden pointer-events-none">
        <img id="photo-preview-img" src="" alt=""
            class="mirrored w-48 h-48 object-cover rounded-xl shadow-2xl border-4 border-white">
    </div>

    <div class="flex items-center justify-between mb-6 gap-3">
        <div class="min-w-0">
            <h3 class="text-sm font-bold text-gray-800">Laporan Absensi</h3>
            <p class="text-[11.5px] text-gray-400 mt-1 font-medium">Total {{ $absensi->total() }} record absensi</p>
        </div>
        <div class="flex gap-2 flex-shrink-0">
            <a href="{{ route('admin.laporan-absensi.export', array_merge(request()->query(), ['tanggal_mulai' => $tanggalMulai, 'tanggal_selesai' => $tanggalSelesai, 'format' => 'detail'])) }}"
                class="flex items-center gap-2 px-4 py-3 rounded-xl text-[13px] font-semibold text-white shadow-sm hover:opacity-90 transition-opacity"
                style="background:#0f2d6b">
                Export Detail
            </a>
            <a href="{{ route('admin.laporan-absensi.export', array_merge(request()->query(), ['tanggal_mulai' => $tanggalMulai, 'tanggal_selesai' => $tanggalSelesai, 'format' => 'rekap'])) }}"
                class="flex items-center gap-2 px-4 py-3 rounded-xl text-[13px] font-semibold text-white shadow-sm hover:opacity-90 transition-opacity"
                style="background:#f97316">
                Export Rekap
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white border border-gray-100 rounded-2xl p-4 mb-4 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <form method="GET" action="{{ route('admin.laporan-absensi') }}">
            {{-- Baris 1: Search + Tanggal + Button --}}
            <div class="flex items-center gap-2.5 mb-2.5">
                <div class="relative flex-1">
                    <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau NIK..."
                        class="w-full pl-10 pr-4 py-2 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-50 transition-all">
                </div>

                <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}"
                    class="text-[12.5px] border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:border-orange-400 transition-all flex-shrink-0">
                <span class="text-gray-300 text-[12px] flex-shrink-0">–</span>
                <input type="date" name="tanggal_selesai" value="{{ $tanggalSelesai }}"
                    class="text-[12.5px] border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:border-orange-400 transition-all flex-shrink-0">

                <button type="submit"
                    class="text-[12.5px] font-semibold px-4 py-2 rounded-xl text-white hover:opacity-90 transition-opacity shadow-sm flex-shrink-0"
                    style="background:#0f2d6b">
                    Filter
                </button>

                @if (request()->anyFilled(['search', 'department_id', 'pusat_lokasi_id', 'status', 'tipe_absen']))
                    <a href="{{ route('admin.laporan-absensi') }}"
                        class="text-[12px] text-gray-400 hover:text-gray-600 transition-colors font-medium flex-shrink-0">Reset</a>
                @endif
            </div>

            {{-- Baris 2: Select filters --}}
            <div class="flex items-center gap-2.5">
                <select name="department_id"
                    class="filter-select text-[12.5px] border border-gray-200 rounded-xl px-3 py-2 flex-1">
                    <option value="">Semua Departemen</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>

                <select name="pusat_lokasi_id"
                    class="filter-select text-[12.5px] border border-gray-200 rounded-xl px-3 py-2 flex-1">
                    <option value="">Semua Lokasi</option>
                    @foreach ($pusatLokasis as $lokasi)
                        <option value="{{ $lokasi->id }}"
                            {{ request('pusat_lokasi_id') == $lokasi->id ? 'selected' : '' }}>
                            {{ $lokasi->nama_lokasi }}
                        </option>
                    @endforeach
                </select>

                <select name="status"
                    class="filter-select text-[12.5px] border border-gray-200 rounded-xl px-3 py-2 flex-1">
                    <option value="">Semua Status</option>
                    @foreach (['tepat_waktu' => 'Tepat Waktu', 'terlambat' => 'Terlambat', 'diluar_lokasi' => 'Di Luar Lokasi', 'lembur' => 'Lembur'] as $v => $l)
                        <option value="{{ $v }}" {{ request('status') == $v ? 'selected' : '' }}>
                            {{ $l }}</option>
                    @endforeach
                </select>

                <select name="tipe_absen"
                    class="filter-select text-[12.5px] border border-gray-200 rounded-xl px-3 py-2 flex-1">
                    <option value="">Semua Tipe</option>
                    <option value="masuk" {{ request('tipe_absen') == 'masuk' ? 'selected' : '' }}>Masuk</option>
                    <option value="pulang" {{ request('tipe_absen') == 'pulang' ? 'selected' : '' }}>Pulang</option>
                </select>
            </div>
        </form>
    </div>

    @php
        $statusColors = [
            'tepat_waktu' => 'bg-emerald-50 text-emerald-700',
            'terlambat' => 'bg-rose-50 text-rose-700',
            'diluar_lokasi' => 'bg-orange-50 text-orange-700',
            'lembur' => 'bg-violet-50 text-violet-700',
        ];
        $statusLabels = [
            'tepat_waktu' => 'Tepat Waktu',
            'terlambat' => 'Terlambat',
            'diluar_lokasi' => 'Di Luar Lokasi',
            'lembur' => 'Lembur',
        ];
    @endphp

    {{-- Table --}}
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70">
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            Karyawan</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tanggal
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Waktu
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tipe
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Lokasi
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            Koordinat</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Foto
                            Absen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($absensi as $item)
                        <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-3">
                                <p class="text-[13px] font-semibold text-gray-800">
                                    {{ $item->employee?->full_name ?? '-' }}</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">
                                    {{ $item->employee?->department?->name ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-[13px] text-gray-600 font-medium">
                                {{ \Carbon\Carbon::parse($item->tanggal_absen)->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-[13px] text-gray-600 font-medium">
                                {{ \Carbon\Carbon::parse($item->waktu_absen)->format('H:i:s') }}</td>
                            <td class="px-4 py-3 text-[13px] text-gray-600 font-medium">{{ ucfirst($item->tipe_absen) }}
                            </td>
                            <td class="px-4 py-3 text-[13px] text-gray-600 font-medium">
                                {{ $item->pusatLokasi?->nama_lokasi ?? '-' }}</td>
                            <td class="px-4 py-4 text-[13px]">
                                @if ($item->latitude && $item->longitude)
                                    <a href="https://www.google.com/maps?q={{ $item->latitude }},{{ $item->longitude }}"
                                        target="_blank" class="text-blue-600 hover:underline">
                                        {{ number_format($item->latitude, 6) }}, {{ number_format($item->longitude, 6) }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="text-[11.5px] px-3 py-1.5 rounded-full font-semibold {{ $statusColors[$item->status] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ $statusLabels[$item->status] ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($item->foto_absen_url)
                                    <img src="{{ $item->foto_absen_url }}" alt="Foto Absen"
                                        data-full="{{ $item->foto_absen_url }}"
                                        onclick="openLightbox('{{ $item->foto_absen_url }}')"
                                        class="absen-photo-thumb mirrored cursor-zoom-in w-10 h-10 rounded-lg object-cover border border-gray-100">
                                @else
                                    <span class="text-gray-400 text-[12px]">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-[13px] text-gray-400">Tidak ada data
                                absensi pada periode ini</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($absensi->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $absensi->links() }}
            </div>
        @endif
    </div>

    <div id="photo-lightbox" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm"
        onclick="closeLightbox()">
        <img id="photo-lightbox-img" src="" alt=""
            class="mirrored max-w-[90vw] max-h-[90vh] rounded-2xl shadow-2xl" onclick="event.stopPropagation()">
        <button onclick="closeLightbox()"
            class="absolute top-6 right-6 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <script>
        function openLightbox(url) {
            document.getElementById('photo-lightbox-img').src = url;
            var lightbox = document.getElementById('photo-lightbox');
            lightbox.classList.remove('hidden');
            lightbox.classList.add('flex');
        }

        function closeLightbox() {
            var lightbox = document.getElementById('photo-lightbox');
            lightbox.classList.add('hidden');
            lightbox.classList.remove('flex');
        }
    </script>

    <style>
        .filter-select-wrapper {
            margin-bottom: 0 !important;
            width: auto;
            flex-shrink: 1;
        }

        .filter-select-wrapper .choices__inner {
            border: 1px solid #E5E7EB !important;
            border-radius: 12px !important;
            padding: 7px 12px !important;
            font-size: 12.5px !important;
            background: #fff !important;
            min-height: 38px;
            margin: 0 !important;
        }

        .filter-select-wrapper.is-focused .choices__inner {
            border-color: #fb923c !important;
        }

        .filter-select-wrapper .choices__list--dropdown {
            border-radius: 12px !important;
            font-size: 12.5px !important;
        }

        .filter-select-wrapper .choices__list--dropdown .choices__item--selectable.is-highlighted {
            background-color: #fff7ed !important;
            color: #ea580c !important;
        }

        .mirrored {
            transform: scaleX(-1);
        }
    </style>

    <script>
        document.querySelectorAll('.filter-select').forEach(function(el) {
            var choice = new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
            });
            choice.containerOuter.element.classList.add('filter-select-wrapper');
        });
    </script>
    <script>
        (function() {
            var preview = document.getElementById('photo-preview');
            var previewImg = document.getElementById('photo-preview-img');

            document.querySelectorAll('.absen-photo-thumb').forEach(function(thumb) {
                thumb.addEventListener('mouseenter', function(e) {
                    previewImg.src = thumb.dataset.full;
                    preview.classList.remove('hidden');
                    positionPreview(e);
                });
                thumb.addEventListener('mousemove', positionPreview);
                thumb.addEventListener('mouseleave', function() {
                    preview.classList.add('hidden');
                });
            });

            function positionPreview(e) {
                var offset = 16;
                var x = e.clientX + offset;
                var y = e.clientY + offset;
                if (x + 200 > window.innerWidth) x = e.clientX - 200 - offset;
                if (y + 200 > window.innerHeight) y = e.clientY - 200 - offset;
                preview.style.left = x + 'px';
                preview.style.top = y + 'px';
            }
        })();
    </script>
@endsection
