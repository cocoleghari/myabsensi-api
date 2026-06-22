<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - KaryaOne Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --navy: #1565C0;
            --navy-dark: #0D47A1;
            --orange: #F57C00;
            --orange-light: #FFF3E0;
            --sidebar-w: 220px;
            --header-h: 56px;
            --line: #EEF0F4;
            --ink-soft: #5B6472;
            --ink-faint: #9AA1AC;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            font-size: 13px;
            background: #F5F6FA;
            color: #1a1a2e;
            margin: 0;
        }

        /* ── Scrollbar (auto-hide: visible only while actively scrolling) ── */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.08);
            border-radius: 99px;
            transition: background .25s ease;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        html {
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.08) transparent;
        }

        /* ── Backdrop (mobile only) ── */
        #sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .35);
            z-index: 40;
        }

        #sidebar-backdrop.show {
            display: block;
        }

        /* ── Sidebar ──
           Desktop: fixed, open by default, collapses to width 0 (slides out) when toggled off.
           Mobile: overlay, closed by default, slides in over content with backdrop.
        */
        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-w);
            background: #fff;
            border-right: 1px solid var(--line);
            display: flex;
            flex-direction: column;
            z-index: 50;
            overflow: hidden;
            transition: width .22s cubic-bezier(.4, 0, .2, 1), transform .22s cubic-bezier(.4, 0, .2, 1);
        }

        /* Mobile default: hidden off-canvas */
        #sidebar {
            transform: translateX(calc(-1 * var(--sidebar-w)));
        }

        #sidebar.open {
            transform: translateX(0);
        }

        @media (min-width: 768px) {

            /* Desktop default: visible, no transform offset */
            #sidebar {
                transform: translateX(0);
                width: var(--sidebar-w);
            }

            /* Desktop collapsed: width shrinks to 0 */
            #sidebar.desktop-collapsed {
                width: 0;
                border-right-color: transparent;
            }

            #main-content {
                margin-left: var(--sidebar-w);
                transition: margin-left .22s cubic-bezier(.4, 0, .2, 1);
            }

            #main-content.sidebar-collapsed {
                margin-left: 0;
            }

            /* No backdrop needed on desktop */
            #sidebar-backdrop {
                display: none !important;
            }
        }

        /* ── Sidebar Header ── */
        .sb-head {
            height: var(--header-h);
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 0 14px;
            border-bottom: 1px solid var(--line);
            flex-shrink: 0;
            white-space: nowrap;
        }

        .sb-logo {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            overflow: hidden;
            flex-shrink: 0;
            background: #F4F6FA;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sb-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .sb-brand-text {
            flex: 1;
            min-width: 0;
            display: flex;
            align-items: center;
        }

        .sb-brand-name {
            font-size: 20px;
            font-weight: 700;
            color: var(--navy);
            letter-spacing: -.4px;
            line-height: 1.2;
        }

        .sb-brand-name span {
            color: var(--orange);
        }

        /* (hamburger moved to #main-header as #main-hamburger-btn) */

        /* ── Sidebar Nav ── */
        #sidebar-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 2px 10px 14px;
            width: var(--sidebar-w);
        }

        .nav-section-label {
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--navy-dark);
            background: #EEF4FC;
            border-radius: 7px;
            padding: 10px 10px;
            margin: 4px 0 5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
        }

        .nav-section-label:hover {
            background: #E4EDFB;
        }

        .nav-section-label:first-child {
            margin-top: 10px;
        }

        .nav-section-label svg {
            width: 14px;
            height: 14px;
            transition: transform .18s ease;
            color: var(--navy);
            opacity: .55;
        }

        .nav-section-label.collapsed svg {
            transform: rotate(-90deg);
        }

        .nav-group-items {
            overflow: hidden;
            transition: max-height .25s cubic-bezier(.4, 0, .2, 1);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 10px 8px 11px;
            margin: 2px 0;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--ink-soft);
            text-decoration: none;
            transition: background .12s, color .12s;
            position: relative;
            white-space: nowrap;
        }

        .nav-link:hover {
            background: #F5F6F9;
            color: #1a1a2e;
        }

        .nav-link.active {
            background: #E4EDFB;
            color: var(--navy-dark);
            font-weight: 600;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 12%;
            bottom: 12%;
            width: 4px;
            background: var(--orange);
            border-radius: 0 3px 3px 0;
        }

        .nav-link svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
            opacity: .65;
            stroke-width: 1.75;
        }

        .nav-link.active svg {
            opacity: 1;
            stroke-width: 2;
        }

        .nav-badge {
            margin-left: auto;
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 99px;
            background: var(--orange);
            color: #fff;
        }

        /* ── Sidebar Footer ── */
        .sb-footer {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 10px 12px;
            border-top: 1px solid var(--line);
            flex-shrink: 0;
            width: var(--sidebar-w);
        }

        .sb-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10.5px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .sb-user-name {
            font-size: 12.5px;
            font-weight: 600;
            color: #1a1a2e;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        .sb-user-role {
            font-size: 10.5px;
            font-weight: 500;
            color: var(--ink-faint);
        }

        .sb-logout-btn {
            background: none;
            border: none;
            padding: 8px;
            border-radius: 7px;
            cursor: pointer;
            color: var(--ink-faint);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .12s, color .12s;
        }

        .sb-logout-btn:hover {
            background: #FEF2F2;
            color: #E53E3E;
        }

        .sb-logout-btn svg {
            width: 18px;
            height: 18px;
        }

        /* ── Header ── */
        #main-header {
            position: sticky;
            top: 0;
            z-index: 30;
            height: var(--header-h);
            background: #fff;
            border-bottom: 1px solid #E8EAF0;
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 12px;
        }

        /* Main hamburger: lives in the main header (top-left of content area), toggles
           the desktop fixed sidebar (collapse to width 0) or the mobile overlay sidebar. */
        #main-hamburger-btn {
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 8px;
            background: #F4F6FA;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            flex-shrink: 0;
            transition: background .12s;
            position: relative;
            z-index: 1;
        }

        #main-hamburger-btn:hover {
            background: #EBEEF3;
        }

        #main-hamburger-btn span {
            display: block;
            width: 16px;
            height: 1.75px;
            background: var(--ink-soft);
            border-radius: 2px;
            transition: transform .2s, opacity .2s, width .2s;
        }

        #main-hamburger-btn.active span:nth-child(1) {
            transform: translateY(6.25px) rotate(45deg);
        }

        #main-hamburger-btn.active span:nth-child(2) {
            opacity: 0;
            width: 0;
        }

        #main-hamburger-btn.active span:nth-child(3) {
            transform: translateY(-6.25px) rotate(-45deg);
        }

        .header-title {
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }

        .header-title p {
            font-size: 11.5px;
            font-weight: 600;
            color: #6B7280;
            margin: 0;
        }

        #header-search-wrap {
            display: flex;
            align-items: center;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }

        .header-icon-btn {
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 8px;
            background: #F4F6FA;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ink-soft);
            transition: background .12s, color .12s;
            position: relative;
            flex-shrink: 0;
        }

        .header-icon-btn:hover {
            background: #EBEEF3;
            color: #1a1a2e;
        }

        .header-icon-btn svg {
            width: 15px;
            height: 15px;
        }

        #header-search-wrap.active .header-icon-btn {
            background: var(--navy);
            color: #fff;
        }

        #header-search-input {
            width: 0;
            opacity: 0;
            margin-left: 0;
            padding: 0;
            border: none;
            border-radius: 8px;
            background: #F4F6FA;
            font-family: inherit;
            font-size: 13.5px;
            color: #1a1a2e;
            transition: width .22s cubic-bezier(.4, 0, .2, 1), opacity .18s, margin-left .22s, padding .22s;
            pointer-events: none;
        }

        #header-search-input::placeholder {
            color: var(--ink-faint);
        }

        #header-search-wrap.active #header-search-input {
            width: 220px;
            opacity: 1;
            margin-left: 8px;
            padding: 0 12px;
            height: 38px;
            pointer-events: auto;
        }

        @media (min-width: 640px) {
            #header-search-wrap.active #header-search-input {
                width: 280px;
            }
        }

        /* ── Main content ── */
        #main-content {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        #main-body {
            flex: 1;
            padding: 18px 16px;
        }

        @media (min-width: 640px) {
            #main-body {
                padding: 20px 22px;
            }
        }

        /* ── Page loading bar ── */
        #page-loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 2px;
            z-index: 100;
            width: 0;
            opacity: 0;
            background: var(--navy);
            transition: width 4s cubic-bezier(.1, .6, .3, 1);
        }
    </style>
</head>

<body>

    <div id="page-loading-bar"></div>

    {{-- Backdrop (mobile only) --}}
    <div id="sidebar-backdrop" onclick="closeMobileSidebar()"></div>

    {{-- Sidebar --}}
    <aside id="sidebar">
        <div class="sb-head">
            <div class="sb-logo">
                <img src="{{ asset('images/logo_brand.png') }}" alt="KaryaOne">
            </div>
            <div class="sb-brand-text">
                <div class="sb-brand-name">Karya<span>One</span></div>
            </div>
        </div>

        <nav id="sidebar-nav">

            @php
                $navGroups = [
                    'Menu Utama' => [
                        [
                            'route' => 'admin.dashboard',
                            'match' => 'admin.dashboard',
                            'label' => 'Dashboard',
                            'icon' =>
                                'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                        ],
                        [
                            'route' => 'admin.laporan-absensi',
                            'match' => 'admin.laporan-absensi',
                            'label' => 'Absensi',
                            'icon' =>
                                'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                            'badge' => 'pendingAbsen',
                        ],
                        [
                            'route' => 'admin.laporan-aktivitas',
                            'match' => 'admin.laporan-aktivitas',
                            'label' => 'Aktivitas',
                            'icon' =>
                                'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        ],
                        [
                            'route' => 'admin.foto-wajah.index',
                            'match' => 'admin.foto-wajah.*',
                            'label' => 'Foto Dasar Kehadiran',
                            'icon' =>
                                'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z',
                        ],
                    ],
                    'Pengaturan Akun' => [
                        [
                            'route' => 'admin.list-akun.index',
                            'match' => 'admin.list-akun.*',
                            'label' => 'List Akun',
                            'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                        ],
                        [
                            'route' => 'admin.lokasi-user',
                            'match' => 'admin.lokasi-user',
                            'label' => 'Lokasi User',
                            'icon' =>
                                'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',
                        ],
                        [
                            'route' => 'admin.pengaturan-lokasi.index',
                            'match' => 'admin.pengaturan-lokasi.*',
                            'label' => 'Pengaturan Lokasi',
                            'icon' =>
                                'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
                        ],
                    ],
                    'Pengaturan Karyawan' => [
                        [
                            'route' => 'admin.karyawan.index',
                            'match' => 'admin.karyawan.*',
                            'label' => 'Daftar Karyawan',
                            'icon' =>
                                'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                        ],
                        [
                            'route' => 'admin.department.index',
                            'match' => 'admin.department.*',
                            'label' => 'Department',
                            'icon' =>
                                'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                        ],
                        [
                            'route' => 'admin.posisi.index',
                            'match' => 'admin.posisi.*',
                            'label' => 'Posisi / Jabatan',
                            'icon' =>
                                'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                        ],
                        [
                            'route' => 'admin.job-grade.index',
                            'match' => 'admin.job-grade.*',
                            'label' => 'Job Grade',
                            'icon' =>
                                'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
                        ],
                        [
                            'route' => 'admin.job-level.index',
                            'match' => 'admin.job-level.*',
                            'label' => 'Job Level',
                            'icon' => 'M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12',
                        ],
                        [
                            'route' => 'admin.status-karyawan.index',
                            'match' => 'admin.status-karyawan.*',
                            'label' => 'Status Karyawan',
                            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                        ],
                    ],
                    'Pengaturan Shift' => [
                        [
                            'route' => 'admin.master-shift.index',
                            'match' => 'admin.master-shift.*',
                            'label' => 'Master Shift',
                            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                        ],
                        [
                            'route' => 'admin.pola-shift.index',
                            'match' => 'admin.pola-shift.*',
                            'label' => 'Pola Shift Mingguan',
                            'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16',
                        ],
                        [
                            'route' => 'admin.assign-shift.index',
                            'match' => 'admin.assign-shift.*',
                            'label' => 'Assign Shift',
                            'icon' =>
                                'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                        ],
                    ],
                ];
                $pendingAbsen = \App\Models\PermintaanAbsen::where('status', 'pending')->count();
            @endphp

            @foreach ($navGroups as $groupLabel => $items)
                @php
                    $groupKey = \Illuminate\Support\Str::slug($groupLabel);
                    $hasActiveInGroup = collect($items)->contains(fn($item) => request()->routeIs($item['match']));
                @endphp

                <div class="nav-section-label {{ $hasActiveInGroup ? '' : '' }}" data-group="{{ $groupKey }}"
                    onclick="toggleGroup('{{ $groupKey }}')">
                    <span>{{ $groupLabel }}</span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                <div class="nav-group-items" id="group-{{ $groupKey }}">
                    @foreach ($items as $item)
                        @php $active = request()->routeIs($item['match']); @endphp
                        <a href="{{ route($item['route']) }}" class="nav-link {{ $active ? 'active' : '' }}">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="{{ $item['icon'] }}" />
                            </svg>
                            <span>{{ $item['label'] }}</span>
                            @if (!empty($item['badge']) && $pendingAbsen > 0)
                                <span class="nav-badge">{{ $pendingAbsen }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        <div class="sb-footer">
            <div class="sb-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
            <div style="flex:1;min-width:0">
                <div class="sb-user-name">{{ Auth::user()->name }}</div>
                <div class="sb-user-role">Admin</div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="sb-logout-btn" title="Keluar">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div id="main-content">
        <header id="main-header">
            {{-- Single hamburger: collapses fixed sidebar on desktop, opens overlay on mobile --}}
            <button id="main-hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                <span></span>
                <span></span>
                <span></span>
            </button>

            {{-- Search: click to expand an inline search input --}}
            <div id="header-search-wrap">
                <button id="header-search-btn" class="header-icon-btn" title="Cari" onclick="toggleHeaderSearch()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
                <input type="text" id="header-search-input" placeholder="Cari apa saja..." autocomplete="off">
            </div>

            <div class="header-title">
                <p>{{ now()->translatedFormat('l, d F Y') }}</p>
            </div>
        </header>

        <main id="main-body">
            @yield('content')
        </main>
    </div>

    <script>
        var DESKTOP_BREAKPOINT = 768;

        // ── Mobile overlay sidebar ──
        var mobileSidebarOpen = false;

        function openMobileSidebar() {
            document.getElementById('sidebar').classList.add('open');
            document.getElementById('sidebar-backdrop').classList.add('show');
            document.getElementById('main-hamburger-btn').classList.add('active');
            mobileSidebarOpen = true;
        }

        function closeMobileSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebar-backdrop').classList.remove('show');
            document.getElementById('main-hamburger-btn').classList.remove('active');
            mobileSidebarOpen = false;
        }

        function toggleMobileSidebar() {
            mobileSidebarOpen ? closeMobileSidebar() : openMobileSidebar();
        }

        // ── Desktop fixed sidebar collapse ──
        var desktopSidebarCollapsed = localStorage.getItem('desktop_sidebar_collapsed') === '1';

        function applyDesktopSidebarState() {
            var sidebar = document.getElementById('sidebar');
            var mainContent = document.getElementById('main-content');
            var hamburgerBtn = document.getElementById('main-hamburger-btn');

            if (window.innerWidth >= DESKTOP_BREAKPOINT && desktopSidebarCollapsed) {
                sidebar.classList.add('desktop-collapsed');
                mainContent.classList.add('sidebar-collapsed');
                document.body.classList.add('desktop-sidebar-collapsed');
                hamburgerBtn.classList.add('active');
            } else {
                sidebar.classList.remove('desktop-collapsed');
                mainContent.classList.remove('sidebar-collapsed');
                document.body.classList.remove('desktop-sidebar-collapsed');
                hamburgerBtn.classList.remove('active');
            }
        }

        function toggleDesktopSidebar() {
            desktopSidebarCollapsed = !desktopSidebarCollapsed;
            localStorage.setItem('desktop_sidebar_collapsed', desktopSidebarCollapsed ? '1' : '0');
            applyDesktopSidebarState();
        }

        // ── Single entry point used by the main header hamburger ──
        function toggleSidebar() {
            if (window.innerWidth >= DESKTOP_BREAKPOINT) {
                toggleDesktopSidebar();
            } else {
                toggleMobileSidebar();
            }
        }

        // ── Header search (inline expand) ──
        function toggleHeaderSearch() {
            var wrap = document.getElementById('header-search-wrap');
            var input = document.getElementById('header-search-input');
            var isActive = wrap.classList.contains('active');

            if (isActive) {
                wrap.classList.remove('active');
                input.blur();
            } else {
                wrap.classList.add('active');
                setTimeout(function() {
                    input.focus();
                }, 50);
            }
        }

        document.addEventListener('click', function(e) {
            var wrap = document.getElementById('header-search-wrap');
            if (wrap.classList.contains('active') && !wrap.contains(e.target)) {
                wrap.classList.remove('active');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                var wrap = document.getElementById('header-search-wrap');
                if (wrap.classList.contains('active')) {
                    wrap.classList.remove('active');
                    document.getElementById('header-search-input').blur();
                }
            }
        });

        // ── Auto-hide scrollbar: show while scrolling, fade out after idle ──
        (function() {
            var SCROLL_IDLE_DELAY = 900;

            function bindAutoHideScroll(target, scrollClassEl) {
                var hideTimer = null;
                target.addEventListener('scroll', function() {
                    scrollClassEl.classList.add('is-scrolling');
                    clearTimeout(hideTimer);
                    hideTimer = setTimeout(function() {
                        scrollClassEl.classList.remove('is-scrolling');
                    }, SCROLL_IDLE_DELAY);
                }, {
                    passive: true
                });
            }

            // Main page scroll (window/html)
            bindAutoHideScroll(window, document.documentElement);

            // Sidebar nav has its own scroll container
            var sidebarNav = document.getElementById('sidebar-nav');
            if (sidebarNav) {
                bindAutoHideScroll(sidebarNav, sidebarNav);
            }
        })();

        // Apply on load and on resize
        applyDesktopSidebarState();
        window.addEventListener('resize', applyDesktopSidebarState);

        // Close sidebar on Escape (mobile overlay only)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileSidebarOpen) closeMobileSidebar();
        });

        // ── Nav group toggle ──
        function toggleGroup(key) {
            var panel = document.getElementById('group-' + key);
            var label = document.querySelector('[data-group="' + key + '"]');
            var isOpen = panel.style.maxHeight && panel.style.maxHeight !== '0px';
            panel.style.maxHeight = isOpen ? '0px' : panel.scrollHeight + 'px';
            label.classList.toggle('collapsed', isOpen);
            localStorage.setItem('nav_group_' + key, isOpen ? '0' : '1');
        }

        // Init group states
        document.querySelectorAll('.nav-section-label').forEach(function(label) {
            var key = label.dataset.group;
            var panel = document.getElementById('group-' + key);
            var stored = localStorage.getItem('nav_group_' + key);
            var hasActive = panel.querySelector('.nav-link.active') !== null;
            var open = stored !== null ? stored === '1' : hasActive;

            if (open) {
                panel.style.maxHeight = panel.scrollHeight + 'px';
            } else {
                panel.style.maxHeight = '0px';
                label.classList.add('collapsed');
            }
        });
    </script>

    <script>
        // ── Page loading bar ──
        (function() {
            var bar = document.getElementById('page-loading-bar');

            function startLoading() {
                bar.style.transition = 'none';
                bar.style.width = '0%';
                bar.style.opacity = '1';
                requestAnimationFrame(function() {
                    bar.style.transition = 'width 4s cubic-bezier(0.1, 0.6, 0.3, 1)';
                    bar.style.width = '85%';
                });
            }

            document.addEventListener('click', function(e) {
                var link = e.target.closest('a');
                if (!link) return;
                if (link.target === '_blank' || link.hasAttribute('download')) return;
                var href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
                if (e.ctrlKey || e.metaKey || e.shiftKey) return;
                startLoading();
            });

            document.addEventListener('submit', function(e) {
                startLoading();
                var form = e.target;
                var submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.dataset.originalContent = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.65';
                    submitBtn.style.cursor = 'not-allowed';
                    submitBtn.innerHTML =
                        '<svg style="width:14px;height:14px;animation:spin 1s linear infinite;display:inline" fill="none" viewBox="0 0 24 24">' +
                        '<circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
                        '<path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>' +
                        '</svg> Memproses...';
                }
            });
        })();
    </script>

    <style>
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>

    @stack('scripts')
</body>

</html>
