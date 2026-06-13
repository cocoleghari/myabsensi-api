<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - KaryaOne</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 40px 20px; color: #333; }
        h1 { color: #0A3170; border-bottom: 3px solid #FF6B00; padding-bottom: 10px; }
        h2 { color: #0A3170; margin-top: 30px; }
        .header { text-align: center; margin-bottom: 40px; }
        .logo { font-size: 28px; font-weight: bold; }
        .logo span { color: #FF6B00; }
        .date { color: #666; font-size: 14px; }
        p { line-height: 1.8; }
        ul { line-height: 2; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">Karya<span>One</span></div>
        <p class="date">Terakhir diperbarui: {{ date('d F Y') }}</p>
    </div>

    <h1>Kebijakan Privasi</h1>

    <p>KaryaOne ("kami") berkomitmen untuk melindungi privasi pengguna aplikasi HRIS KaryaOne. 
    Kebijakan privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, dan melindungi data Anda.</p>

    <h2>1. Data yang Dikumpulkan</h2>
    <p>Kami mengumpulkan data berikut untuk keperluan operasional sistem HRIS:</p>
    <ul>
        <li>Nama lengkap dan informasi identitas karyawan</li>
        <li>Foto wajah untuk keperluan verifikasi identitas (face recognition)</li>
        <li>Data lokasi GPS saat melakukan absensi</li>
        <li>Data kehadiran dan catatan absensi</li>
        <li>Data pengajuan dan persetujuan cuti</li>
        <li>Alamat email dan nomor telepon</li>
    </ul>

    <h2>2. Tujuan Penggunaan Data</h2>
    <p>Data yang dikumpulkan digunakan untuk:</p>
    <ul>
        <li>Verifikasi identitas karyawan saat absensi menggunakan face recognition</li>
        <li>Validasi lokasi absensi sesuai ketentuan perusahaan</li>
        <li>Pencatatan dan pelaporan kehadiran karyawan</li>
        <li>Pengelolaan pengajuan dan persetujuan cuti</li>
        <li>Komunikasi internal terkait informasi kepegawaian</li>
    </ul>

    <h2>3. Penyimpanan Data</h2>
    <p>Semua data disimpan di server aman milik perusahaan yang berlokasi di Indonesia. 
    Data dienkripsi menggunakan protokol HTTPS dan tidak dibagikan kepada pihak ketiga 
    tanpa persetujuan dari perusahaan dan karyawan yang bersangkutan.</p>

    <h2>4. Keamanan Data</h2>
    <p>Kami menerapkan langkah-langkah keamanan berikut:</p>
    <ul>
        <li>Enkripsi data menggunakan SSL/TLS</li>
        <li>Autentikasi dengan token yang terenkripsi</li>
        <li>Akses data dibatasi hanya untuk personel yang berwenang</li>
        <li>Server dilindungi firewall dan monitoring keamanan</li>
    </ul>

    <h2>5. Hak Pengguna</h2>
    <p>Sebagai pengguna, Anda berhak untuk:</p>
    <ul>
        <li>Mengakses data pribadi yang kami simpan</li>
        <li>Meminta koreksi data yang tidak akurat</li>
        <li>Meminta penghapusan data setelah tidak lagi menjadi karyawan</li>
    </ul>

    <h2>6. Kontak</h2>
    <p>Jika Anda memiliki pertanyaan terkait kebijakan privasi ini, hubungi kami:</p>
    <ul>
        <li>Email: admin@karyaone.tech</li>
        <li>Website: https://karyaone.tech</li>
    </ul>

    <p style="margin-top:40px; color:#666; font-size:13px; text-align:center;">
        &copy; {{ date('Y') }} KaryaOne. Hak cipta dilindungi.
    </p>
</body>
</html>
