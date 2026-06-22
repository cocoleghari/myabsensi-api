@extends('layouts.admin')

@section('title', 'Foto Dasar Kehadiran')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    @if (session('success'))
        <div id="toast-success"
            class="fixed top-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-white border border-emerald-100 shadow-xl rounded-xl px-4 py-2.5 text-[12.5px] font-medium text-emerald-700"
            style="animation: slideDown 0.3s ease-out;">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div id="toast-error"
            class="fixed top-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-white border border-rose-100 shadow-xl rounded-xl px-4 py-2.5 text-[12.5px] font-medium text-rose-700"
            style="animation: slideDown 0.3s ease-out;">
            <svg class="w-4 h-4 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <div id="photo-preview" class="fixed z-50 hidden pointer-events-none">
        <img id="photo-preview-img" src="" alt=""
            class="mirrored w-48 h-48 object-cover rounded-xl shadow-2xl border-4 border-white">
    </div>

    <div class="flex items-center justify-between mb-6 gap-3">
        <div class="min-w-0">
            <h3 class="text-sm font-bold text-gray-800">Foto Dasar Kehadiran</h3>
            <p class="text-[11.5px] text-gray-400 mt-1 font-medium">Kelola foto referensi wajah untuk verifikasi absensi ·
                Total {{ $karyawan->total() }} karyawan</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white border border-gray-100 rounded-2xl p-4 mb-4 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <form method="GET" action="{{ route('admin.foto-wajah.index') }}" class="flex flex-wrap items-center gap-2.5">
            <div class="relative flex-1 min-w-[180px]">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama atau kode karyawan..."
                    class="w-full pl-10 pr-4 py-2 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-50 transition-all">
            </div>

            <select name="department_id" class="filter-select text-[12.5px] border border-gray-200 rounded-xl px-3 py-2">
                <option value="">Semua Departemen</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="filter-select text-[12.5px] border border-gray-200 rounded-xl px-3 py-2">
                <option value="">Semua Status</option>
                <option value="terdaftar" {{ request('status') == 'terdaftar' ? 'selected' : '' }}>Terdaftar</option>
                <option value="belum" {{ request('status') == 'belum' ? 'selected' : '' }}>Belum Terdaftar</option>
            </select>

            <button type="submit"
                class="text-[12.5px] font-semibold px-4 py-2 rounded-xl text-white hover:opacity-90 transition-opacity shadow-sm"
                style="background:#0f2d6b">
                Filter
            </button>

            @if (request()->anyFilled(['search', 'department_id', 'status']))
                <a href="{{ route('admin.foto-wajah.index') }}"
                    class="text-[12px] text-gray-400 hover:text-gray-600 transition-colors font-medium">Reset Filter</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70">
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Foto
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            Karyawan</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            Departemen</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status
                        </th>
                        <th class="text-right px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($karyawan as $item)
                        <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-3">
                                @if ($item->foto_wajah_url)
                                    <img src="{{ $item->foto_wajah_url }}" alt="Foto Wajah {{ $item->full_name }}"
                                        data-full="{{ $item->foto_wajah_url }}"
                                        onclick="openLightbox('{{ $item->foto_wajah_url }}')"
                                        class="wajah-photo-thumb mirrored cursor-zoom-in w-11 h-11 rounded-full object-cover border border-gray-100">
                                @else
                                    <div
                                        class="w-11 h-11 rounded-full bg-gray-100 flex items-center justify-center text-gray-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-[13px] font-semibold text-gray-800">{{ $item->full_name }}</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">
                                    {{ $item->employee_code ?? ($item->nik ?? '-') }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-[13px] text-gray-600 font-medium">
                                {{ $item->department->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($item->wajah_terdaftar)
                                    <span
                                        class="text-[11px] px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold">Terdaftar</span>
                                @else
                                    <span
                                        class="text-[11px] px-2.5 py-1 rounded-full bg-gray-100 text-gray-500 font-semibold">Belum
                                        Terdaftar</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button"
                                        onclick="openUploadModal('{{ route('admin.foto-wajah.upload', $item->id) }}', '{{ $item->full_name }}')"
                                        class="px-3.5 py-2 rounded-lg text-[12px] font-semibold text-white hover:opacity-90 transition-opacity"
                                        style="background:#f97316">
                                        Upload
                                    </button>
                                    @if ($item->wajah_terdaftar)
                                        <button type="button"
                                            onclick="openResetModal('{{ route('admin.foto-wajah.reset', $item->id) }}', '{{ $item->full_name }}')"
                                            class="px-3.5 py-2 rounded-lg text-[12px] font-semibold text-rose-600 border border-rose-200 hover:bg-rose-50 transition-colors">
                                            Reset
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-[13px] text-gray-400">Tidak ada data
                                karyawan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($karyawan->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $karyawan->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Upload --}}
    <div id="upload-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full mx-4 p-6" style="animation: popIn 0.2s ease-out;">
            <h3 class="text-[15px] font-bold text-gray-800 mb-1">Upload Foto Wajah</h3>
            <p id="upload-modal-name" class="text-[13px] text-gray-400 mb-4"></p>

            <form id="upload-form" method="POST" action="" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <img id="upload-preview-img" src="" alt=""
                        class="hidden w-32 h-32 mx-auto rounded-xl object-cover border border-gray-100 mb-3">
                    <input type="file" name="foto_wajah" id="upload-file-input"
                        accept="image/png, image/jpeg, image/jpg" required onchange="previewUploadFile(this)"
                        class="w-full text-[12.5px] border border-gray-200 rounded-xl px-3 py-2.5 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-orange-50 file:text-orange-700 file:text-[12px] file:font-semibold">
                    <p class="text-[11px] text-gray-400 mt-1.5">Format JPG/PNG, maksimal 5MB.</p>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeUploadModal()"
                        class="flex-1 py-2 rounded-xl text-[12.5px] font-semibold text-gray-500 border border-gray-200 hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="flex-1 py-2 rounded-xl text-[12.5px] font-semibold text-white transition-opacity hover:opacity-90"
                        style="background:#f97316">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Reset --}}
    <div id="reset-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full mx-4 p-5 text-center"
            style="animation: popIn 0.2s ease-out;">
            <div class="w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-[13.5px] font-bold text-gray-800 mb-1">Reset Foto Wajah?</h3>
            <p id="reset-modal-name" class="text-[12px] text-gray-400 mb-4"></p>

            <form id="reset-form" method="POST" action="">
                @csrf
                <div class="flex gap-3">
                    <button type="button" onclick="closeResetModal()"
                        class="flex-1 py-2 rounded-xl text-[12.5px] font-semibold text-gray-500 border border-gray-200 hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="flex-1 py-2 rounded-xl text-[12.5px] font-semibold text-white transition-opacity hover:opacity-90"
                        style="background:#e11d48">
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Lightbox --}}
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

    <style>
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translate(-50%, -15px);
            }

            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
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

        .mirrored {
            transform: scaleX(-1);
        }
    </style>

    <script>
        ['toast-success', 'toast-error'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                setTimeout(function() {
                    el.style.transition = 'opacity 0.3s, transform 0.3s';
                    el.style.opacity = '0';
                    el.style.transform = 'translate(-50%, -15px)';
                    setTimeout(function() {
                        el.remove();
                    }, 300);
                }, 3000);
            }
        });

        document.querySelectorAll('.filter-select').forEach(function(el) {
            var choice = new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
            });
            choice.containerOuter.element.classList.add('filter-select-wrapper');
        });

        function openUploadModal(action, name) {
            document.getElementById('upload-form').action = action;
            document.getElementById('upload-modal-name').textContent = name;
            document.getElementById('upload-file-input').value = '';
            document.getElementById('upload-preview-img').classList.add('hidden');
            var modal = document.getElementById('upload-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeUploadModal() {
            var modal = document.getElementById('upload-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function previewUploadFile(input) {
            var preview = document.getElementById('upload-preview-img');
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function openResetModal(action, name) {
            document.getElementById('reset-form').action = action;
            document.getElementById('reset-modal-name').textContent = 'Foto wajah "' + name +
                '" akan dihapus dan status verifikasi dikembalikan ke belum terdaftar.';
            var modal = document.getElementById('reset-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeResetModal() {
            var modal = document.getElementById('reset-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

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

        (function() {
            var preview = document.getElementById('photo-preview');
            var previewImg = document.getElementById('photo-preview-img');

            document.querySelectorAll('.wajah-photo-thumb').forEach(function(thumb) {
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
