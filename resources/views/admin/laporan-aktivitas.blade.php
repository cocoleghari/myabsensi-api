@extends('layouts.admin')

@section('title', 'Laporan Aktivitas')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <div id="photo-preview" class="fixed z-50 hidden pointer-events-none">
        <img id="photo-preview-img" src="" alt=""
            class="mirrored w-48 h-48 object-cover rounded-xl shadow-2xl border-4 border-white">
    </div>

    <div class="flex items-center justify-between mb-6 gap-3">
        <div class="min-w-0">
            <h3 class="text-sm font-bold text-gray-800">Laporan Aktivitas</h3>
            <p class="text-[11.5px] text-gray-400 mt-1 font-medium">Total {{ $aktivitas->total() }} record aktivitas</p>
        </div>
        <div class="flex gap-2 flex-shrink-0">
            @php
                $exportParams = array_merge(request()->query(), [
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_selesai' => $tanggalSelesai,
                ]);
                if (\App\Helpers\ScopeHelper::isLimitedRole()) {
                    $deptIds = \App\Helpers\ScopeHelper::getDepartmentIds();
                    if (!empty($deptIds)) {
                        $exportParams['department_ids'] = $deptIds; // ← array
                    }
                }
            @endphp

            <div class="flex gap-2 flex-shrink-0">
                <a href="{{ route('admin.laporan-aktivitas.export', array_merge($exportParams, ['format' => 'detail'])) }}"
                    class="flex items-center gap-2 px-4 py-3 rounded-xl text-[13px] font-semibold text-white shadow-sm hover:opacity-90 transition-opacity"
                    style="background:#0f2d6b">
                    Export Detail
                </a>
                <a href="{{ route('admin.laporan-aktivitas.export', array_merge($exportParams, ['format' => 'rekap_karyawan'])) }}"
                    class="flex items-center gap-2 px-4 py-3 rounded-xl text-[13px] font-semibold text-white shadow-sm hover:opacity-90 transition-opacity"
                    style="background:#f97316">
                    Rekap Karyawan
                </a>
                <a href="{{ route('admin.laporan-aktivitas.export', array_merge($exportParams, ['format' => 'rekap_tipe'])) }}"
                    class="flex items-center gap-2 px-4 py-3 rounded-xl text-[13px] font-semibold text-white shadow-sm hover:opacity-90 transition-opacity"
                    style="background:#8B5CF6">
                    Rekap Tipe
                </a>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white border border-gray-100 rounded-2xl p-4 mb-4 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <form method="GET" action="{{ route('admin.laporan-aktivitas') }}" class="flex flex-wrap items-center gap-2.5">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau NIK..."
                    class="w-full pl-10 pr-4 py-2 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-50 transition-all">
            </div>

            <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}"
                class="text-[12.5px] border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:border-orange-400 transition-all">
            <input type="date" name="tanggal_selesai" value="{{ $tanggalSelesai }}"
                class="text-[12.5px] border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:border-orange-400 transition-all">

            <select name="department_id" class="filter-select text-[12.5px] border border-gray-200 rounded-xl px-3 py-2">
                <option value="">Semua Departemen</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>

            <select name="tipe_aktivitas_id"
                class="filter-select text-[12.5px] border border-gray-200 rounded-xl px-3 py-2">
                <option value="">Semua Tipe</option>
                @foreach ($tipeAktivitasList as $tipe)
                    <option value="{{ $tipe->id }}" {{ request('tipe_aktivitas_id') == $tipe->id ? 'selected' : '' }}>
                        {{ $tipe->nama }}
                    </option>
                @endforeach
            </select>

            <button type="submit"
                class="text-[12.5px] font-semibold px-4 py-2 rounded-xl text-white hover:opacity-90 transition-opacity shadow-sm"
                style="background:#0f2d6b">
                Filter
            </button>

            @if (request()->anyFilled(['search', 'department_id', 'tipe_aktivitas_id']))
                <a href="{{ route('admin.laporan-aktivitas') }}"
                    class="text-[12px] text-gray-400 hover:text-gray-600 transition-colors font-medium">Reset</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70">
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            Karyawan</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tipe
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tugas
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tujuan
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Mulai
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            Berakhir</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Durasi
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            Koordinat</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Foto
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($aktivitas as $item)
                        @php
                            $mulai = \Carbon\Carbon::parse($item->mulai);
                            $berakhir = \Carbon\Carbon::parse($item->berakhir);
                            $menit = $mulai->diffInMinutes($berakhir);
                            $jam = intdiv($menit, 60);
                            $sisa = $menit % 60;
                            $durasi = $jam > 0 ? ($sisa > 0 ? "{$jam}j {$sisa}mnt" : "{$jam}j") : "{$sisa}mnt";
                            $firstFoto = $item->fotos->first();
                        @endphp
                        <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-3">
                                <p class="text-[13px] font-semibold text-gray-800">
                                    {{ $item->employee?->full_name ?? '-' }}</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">
                                    {{ $item->employee?->department?->name ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="text-[11.5px] px-3 py-1.5 rounded-full font-semibold bg-violet-50 text-violet-700">
                                    {{ $item->tipeAktivitas?->nama ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-[13px] text-gray-600 max-w-[200px] truncate">
                                {{ $item->tugas ?? '-' }}</td>
                            <td class="px-4 py-4 text-[13px] text-gray-600 max-w-[160px] truncate">
                                {{ $item->tujuan ?? '-' }}</td>
                            <td class="px-4 py-4 text-[13px] text-gray-600 font-medium">{{ $mulai->format('d M Y H:i') }}
                            </td>
                            <td class="px-4 py-4 text-[13px] text-gray-600 font-medium">
                                {{ $berakhir->format('d M Y H:i') }}</td>
                            <td class="px-4 py-4 text-[13px] text-gray-600 font-medium">{{ $durasi }}</td>
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
                                @if ($firstFoto)
                                    <div class="relative inline-block">
                                        <img src="{{ $firstFoto->foto_url }}" alt="Foto Aktivitas"
                                            data-photos="{{ $item->fotos->pluck('foto_url')->toJson() }}"
                                            onclick="openLightboxFromEl(this)"
                                            class="aktivitas-photo-thumb mirrored cursor-zoom-in w-10 h-10 rounded-lg object-cover border border-gray-100">
                                        @if ($item->fotos->count() > 1)
                                            <span
                                                class="absolute -top-1.5 -right-1.5 bg-gray-700 text-white text-[9px] font-bold w-5 h-5 rounded-full flex items-center justify-center">
                                                +{{ $item->fotos->count() - 1 }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 text-[12px]">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-[13px] text-gray-400">Tidak ada data
                                aktivitas pada periode ini</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($aktivitas->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $aktivitas->links() }}
            </div>
        @endif
    </div>

    <div id="photo-lightbox" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm"
        onclick="closeLightbox()">
        <button onclick="event.stopPropagation(); prevPhoto()" id="lightbox-prev-btn"
            class="absolute left-4 sm:left-8 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <img id="photo-lightbox-img" src="" alt=""
            class="mirrored max-w-[80vw] max-h-[85vh] rounded-2xl shadow-2xl" onclick="event.stopPropagation()">

        <button onclick="event.stopPropagation(); nextPhoto()" id="lightbox-next-btn"
            class="absolute right-4 sm:right-8 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <span id="lightbox-counter"
            class="absolute bottom-6 left-1/2 -translate-x-1/2 text-white text-[13px] font-medium bg-black/30 px-3 py-1 rounded-full"></span>

        <button onclick="closeLightbox()"
            class="absolute top-6 right-6 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <style>
        .mirrored {
            transform: scaleX(-1);
        }

        .filter-select-wrapper {
            margin-bottom: 0 !important;
            width: 180px;
            flex-shrink: 0;
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

        let lightboxPhotos = [];
        let lightboxIndex = 0;

        function openLightboxFromEl(el) {
            var photos = JSON.parse(el.dataset.photos);
            openLightbox(photos, 0);
        }

        function openLightbox(photos, index) {
            lightboxPhotos = photos;
            lightboxIndex = index || 0;
            showLightboxPhoto();

            var lightbox = document.getElementById('photo-lightbox');
            lightbox.classList.remove('hidden');
            lightbox.classList.add('flex');
        }

        function showLightboxPhoto() {
            document.getElementById('photo-lightbox-img').src = lightboxPhotos[lightboxIndex];

            var counter = document.getElementById('lightbox-counter');
            var prevBtn = document.getElementById('lightbox-prev-btn');
            var nextBtn = document.getElementById('lightbox-next-btn');

            if (lightboxPhotos.length > 1) {
                counter.textContent = (lightboxIndex + 1) + ' / ' + lightboxPhotos.length;
                counter.classList.remove('hidden');
                prevBtn.classList.remove('hidden');
                nextBtn.classList.remove('hidden');
            } else {
                counter.classList.add('hidden');
                prevBtn.classList.add('hidden');
                nextBtn.classList.add('hidden');
            }
        }

        function nextPhoto() {
            lightboxIndex = (lightboxIndex + 1) % lightboxPhotos.length;
            showLightboxPhoto();
        }

        function prevPhoto() {
            lightboxIndex = (lightboxIndex - 1 + lightboxPhotos.length) % lightboxPhotos.length;
            showLightboxPhoto();
        }

        function closeLightbox() {
            var lightbox = document.getElementById('photo-lightbox');
            lightbox.classList.add('hidden');
            lightbox.classList.remove('flex');
        }

        document.addEventListener('keydown', function(e) {
            var lightbox = document.getElementById('photo-lightbox');
            if (lightbox.classList.contains('hidden')) return;

            if (e.key === 'ArrowRight') nextPhoto();
            if (e.key === 'ArrowLeft') prevPhoto();
            if (e.key === 'Escape') closeLightbox();
        });

        (function() {
            var preview = document.getElementById('photo-preview');
            var previewImg = document.getElementById('photo-preview-img');

            document.querySelectorAll('.aktivitas-photo-thumb').forEach(function(thumb) {
                thumb.addEventListener('mouseenter', function(e) {
                    var photos = JSON.parse(thumb.dataset.photos);
                    previewImg.src = photos[0];
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
