@if ($departments->isEmpty())
    <p class="text-center text-[13px] text-gray-400 py-12">Belum ada department</p>
@else
    <div class="space-y-1">
        @foreach ($departments as $dept)
            @include('admin.partials.department-node', ['dept' => $dept, 'level' => 0])
        @endforeach
    </div>
@endif
