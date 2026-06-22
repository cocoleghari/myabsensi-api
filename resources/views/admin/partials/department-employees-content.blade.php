@if ($employees->isEmpty())
    <p class="text-center text-[13px] text-gray-400 py-12">Belum ada karyawan di department ini</p>
@else
    <div class="divide-y divide-gray-50">
        @foreach ($employees as $emp)
            @php
                $initials = strtoupper(substr($emp->full_name ?? '?', 0, 2));
                $colors = [
                    'bg-blue-50 text-blue-700',
                    'bg-green-50 text-green-700',
                    'bg-purple-50 text-purple-700',
                    'bg-pink-50 text-pink-700',
                    'bg-amber-50 text-amber-700',
                ];
                $color = $colors[$loop->index % count($colors)];
            @endphp
            <div class="flex items-center gap-3 py-3">
                <div
                    class="w-9 h-9 rounded-full {{ $color }} flex items-center justify-center text-[12px] font-semibold flex-shrink-0">
                    {{ $initials }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-medium text-gray-800">{{ $emp->full_name }}</p>
                    <p class="text-[12px] text-gray-400">{{ $emp->nik ?? '-' }} · {{ $emp->position?->name ?? '-' }}</p>
                </div>
                <a href="{{ route('admin.karyawan.show', $emp->id) }}"
                    class="text-[12px] font-medium text-orange-600 hover:underline flex-shrink-0">Lihat</a>
            </div>
        @endforeach
    </div>
@endif
