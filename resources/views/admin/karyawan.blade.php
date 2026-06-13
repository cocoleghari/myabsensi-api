@extends('layouts.admin')
@section('title', 'Karyawan')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-base font-semibold text-gray-800 mb-4">Data Karyawan</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 text-gray-500 font-medium">Nama</th>
                <th class="text-left py-3 px-4 text-gray-500 font-medium">Department</th>
                <th class="text-left py-3 px-4 text-gray-500 font-medium">Posisi</th>
                <th class="text-left py-3 px-4 text-gray-500 font-medium">Perusahaan</th>
            </tr></thead>
            <tbody>
            @forelse($karyawan as $k)
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-3 px-4 font-medium text-gray-800">{{ $k->full_name }}</td>
                <td class="py-3 px-4 text-gray-600">{{ $k->department?->name ?? '-' }}</td>
                <td class="py-3 px-4 text-gray-600">{{ $k->position?->name ?? '-' }}</td>
                <td class="py-3 px-4 text-gray-600">{{ $k->company?->name ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="py-8 text-center text-gray-400">Tidak ada data</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $karyawan->links() }}</div>
</div>
@endsection
