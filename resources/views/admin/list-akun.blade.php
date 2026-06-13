@extends('layouts.admin')
@section('title', 'List Akun')
@section('content')
<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-base font-semibold text-gray-800 mb-4">List Akun</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 text-gray-500 font-medium">Nama</th>
                <th class="text-left py-3 px-4 text-gray-500 font-medium">Email</th>
                <th class="text-left py-3 px-4 text-gray-500 font-medium">Role</th>
                <th class="text-left py-3 px-4 text-gray-500 font-medium">Status</th>
            </tr></thead>
            <tbody>
            @forelse($users as $user)
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-3 px-4 font-medium text-gray-800">{{ $user->name }}</td>
                <td class="py-3 px-4 text-gray-600">{{ $user->email }}</td>
                <td class="py-3 px-4"><span class="px-2 py-1 rounded-full text-xs font-medium {{ $user->role === 'admin' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">{{ $user->role }}</span></td>
                <td class="py-3 px-4"><span class="px-2 py-1 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
            </tr>
            @empty
            <tr><td colspan="4" class="py-8 text-center text-gray-400">Tidak ada data</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</div>
@endsection
