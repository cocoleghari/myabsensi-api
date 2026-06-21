@php
    $hasChildren = $dept->allChildren && $dept->allChildren->count() > 0;
    $indent = $level * 28;
@endphp

<div>
    <div class="flex items-center gap-3 py-3 px-4 rounded-xl hover:bg-gray-50/70 transition-colors group"
        style="margin-left: {{ $indent }}px">
        @if ($hasChildren)
            <button type="button" onclick="toggleNode({{ $dept->id }})" class="flex-shrink-0">
                <svg id="icon-{{ $dept->id }}" class="w-4 h-4 text-gray-400 transition-transform" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        @else
            <div class="w-4 h-4 flex-shrink-0"></div>
        @endif

        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
            style="background: {{ $level === 0 ? '#0f2d6b' : '#f97316' }}1A; color: {{ $level === 0 ? '#0f2d6b' : '#f97316' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>

        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <p class="text-[13.5px] font-semibold text-gray-800">{{ $dept->name }}</p>
                @if ($dept->code)
                    <span
                        class="text-[11px] font-medium px-2 py-0.5 rounded-md bg-gray-100 text-gray-500">{{ $dept->code }}</span>
                @endif
                @if (!$dept->is_active)
                    <span
                        class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-400">Nonaktif</span>
                @endif
            </div>
            <p class="text-[12px] text-gray-400 mt-0.5">
                {{ $dept->manager?->full_name ?? 'Belum ada manager' }} ·
                {{ $dept->employees_count ?? ($dept->total_employees ?? 0) }} karyawan
            </p>
        </div>

        <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
            <a href="{{ route('admin.department.edit', $dept->id) }}"
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-blue-600 hover:border-blue-200 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </a>
            <form method="POST" action="{{ route('admin.department.destroy', $dept->id) }}"
                class="delete-department-form">
                @csrf @method('DELETE')
                <button type="submit"
                    class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-rose-600 hover:border-rose-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </form>
        </div>
    </div>

    @if ($hasChildren)
        <div id="children-{{ $dept->id }}" class="hidden">
            @foreach ($dept->allChildren as $child)
                @include('admin.partials.department-node', ['dept' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>
