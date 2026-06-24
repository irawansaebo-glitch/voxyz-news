<?php
// Koneksi Database
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'nokosku_db';

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) die("Koneksi gagal: " . mysqli_connect_error());

session_start();
$pesan = '';

// Proses Daftar Akun
if (isset($_POST['daftar'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $sandi = password_hash($_POST['sandi'], PASSWORD_DEFAULT);
    $cek = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($cek) > 0) {
        $pesan = "Email sudah terdaftar!";
    } else {
        mysqli_query($conn, "INSERT INTO users (nama, email, sandi) VALUES ('$nama', '$email', '$sandi')");
        $pesan = "Daftar berhasil! Silakan masuk.";
    }
}

// Proses Masuk
if (isset($_POST['masuk'])) {
    $email = $_POST['email'];
    $sandi = $_POST['sandi'];
    $ambil = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE email='$email'"));
    if ($ambil && password_verify($sandi, $ambil['sandi'])) {
        $_SESSION['user'] = $ambil;
    } else {
        $pesan = "Email atau sandi salah!";
    }
}

// Proses Keluar
if (isset($_GET['keluar'])) {
    session_destroy();
    header("Location: nokosku.php");
    exit;
}

// Proses Pesan Layanan
if (isset($_POST['pesan'])) {
    if (!isset($_SESSION['user'])) {
        $pesan = "Silakan masuk terlebih dahulu!";
    } else {
        $id_layanan = $_POST['id_layanan'];
        $user = $_SESSION['user'];
        $layanan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM layanan WHERE id='$id_layanan'"));
        if ($user['saldo'] >= $layanan['harga']) {
            // Kurangi saldo
            $baru = $user['saldo'] - $layanan['harga'];
            mysqli_query($conn, "UPDATE users SET saldo='$baru' WHERE id='{$user['id']}'");
            $_SESSION['user']['saldo'] = $baru;
            // Simpan pesanan
            mysqli_query($conn, "INSERT INTO pesanan (id_pengguna, id_layanan) VALUES ('{$user['id']}', '$id_layanan')");
            $pesan = "Pesanan berhasil! Silakan cek di riwayat.";
        } else {
            $pesan = "Saldo tidak cukup!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id-ID">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NokosKu - Nomor Virtual & Jasa OTP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#2563eb', dark: '#0f172a' } } } }
    </script>
</head>
<body class="bg-gray-50 text-gray-800">

<!-- Navigasi -->
<header class="bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
        <a href="nokosku.php" class="text-xl font-bold text-primary">NokosKu</a>
        <nav class="flex gap-4 items-center">
            <a href="#layanan" class="hover:text-primary">Layanan</a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="?hal=dashboard" class="bg-primary text-white px-3 py-1 rounded">Dashboard</a>
                <a href="?keluar=1" class="text-red-500">Keluar</a>
            <?php else: ?>
                <a href="?hal=masuk" class="hover:text-primary">Masuk</a>
                <a href="?hal=daftar" class="bg-primary text-white px-3 py-1 rounded">Daftar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<div class="container mx-auto px-4 py-6">
    <?php if ($pesan): ?>
        <div class="mb-4 p-3 rounded <?= strpos($pesan, 'berhasil') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= $pesan ?>
        </div>
    <?php endif; ?>

    <?php
    $hal = $_GET['hal'] ?? 'beranda';
    if ($hal == 'masuk'):
    ?>
        <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
            <h2 class="text-xl font-bold mb-4">Masuk Akun</h2>
            <form method="post">
                <input type="email" name="email" placeholder="Email" class="w-full border p-2 rounded mb-3" required>
                <input type="password" name="sandi" placeholder="Kata Sandi" class="w-full border p-2 rounded mb-4" required>
                <button type="submit" name="masuk" class="w-full bg-primary text-white py-2 rounded">Masuk</button>
            </form>
        </div>

    <?php elseif ($hal == 'daftar'): ?>
        <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
            <h2 class="text-xl font-bold mb-4">Daftar Akun Baru</h2>
            <form method="post">
                <input type="text" name="nama" placeholder="Nama Lengkap" class="w-full border p-2 rounded mb-3" required>
                <input type="email" name="email" placeholder="Email" class="w-full border p-2 rounded mb-3" required>
                <input type="password" name="sandi" placeholder="Kata Sandi" class="w-full border p-2 rounded mb-4" required>
                <button type="submit" name="daftar" class="w-full bg-primary text-white py-2 rounded">Daftar Sekarang</button>
            </form>
        </div>

    <?php elseif ($hal == 'dashboard' && isset($_SESSION['user'])): ?>
        <?php $u = $_SESSION['user']; ?>
        <div class="bg-white p-5 rounded shadow mb-6">
            <h3 class="text-lg font-semibold">Selamat datang, <?= $u['nama'] ?></h3>
            <p class="text-xl mt-2">Saldo Anda: <span class="text-green-600 font-bold">Rp <?= number_format($u['saldo'],0,',','.') ?></span></p>
            <button class="mt-3 bg-primary text-white px-4 py-2 rounded">Isi Saldo</button>
        </div>

        <?php if ($u['peran'] == 'admin'): ?>
            <div class="bg-white p-5 rounded shadow mb-6">
                <h3 class="text-lg font-semibold mb-3">📊 Panel Admin</h3>
                <p>Jumlah Pengguna: <?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE peran='pengguna'")) ?></p>
                <p>Jumlah Pesanan: <?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM pesanan")) ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-white p-5 rounded shadow">
            <h3 class="text-lg font-semibold mb-3">📋 Riwayat Pesanan</h3>
            <?php
            $riwayat = mysqli_query($conn, "SELECT p.*, l.nama FROM pesanan p JOIN layanan l ON p.id_layanan=l.id WHERE p.id_pengguna='{$u['id']}' ORDER BY p.id DESC");
            if (mysqli_num_rows($riwayat) > 0):
            ?>
                <table class="w-full text-sm">
                    <tr class="border-b">
                        <th class="text-left p-2">Layanan</th>
                        <th class="text-left p-2">Status</th>
                        <th class="text-left p-2">Tanggal</th>
                    </tr>
                    <?php while($r = mysqli_fetch_assoc($riwayat)): ?>
                    <tr class="border-b">
                        <td class="p-2"><?= $r['nama'] ?></td>
                        <td class="p-2"><?= $r['status'] ?></td>
                        <td class="p-2"><?= date('d/m/Y H:i', strtotime($r['dibuat_pada'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p class="text-gray-500">Belum ada pesanan</p>
            <?php endif; ?>
        </div>

    <?php else: // Halaman Beranda ?>
        <section class="bg-gradient-to-r from-blue-600 to-blue-700 text-white py-10 text-center rounded-lg mb-8">
            <h1 class="text-3xl font-bold mb-3">Layanan Nokos & Nomor Virtual</h1>
            <p class="max-w-2xl mx-auto">Solusi verifikasi akun digital dengan nomor virtual, jasa OTP, dan OTP WA yang cepat, aman, dan terpercaya.</p>
        </section>

        <section id="layanan">
            <h2 class="text-2xl font-bold mb-6">Pilih Layanan Kami</h2>
            <div class="grid md:grid-cols-3 gap-5">
                <?php
                $layanan = mysqli_query($conn, "SELECT * FROM layanan WHERE aktif=1");
                while($l = mysqli_fetch_assoc($layanan)):
                ?>
                <div class="bg-white p-5 rounded shadow hover:shadow-md transition">
                    <h3 class="text-lg font-semibold"><?= $l['nama'] ?></h3>
                    <p class="text-gray-600 mt-2"><?= $l['keterangan'] ?></p>
                    <p class="text-xl font-bold text-primary mt-3">Rp <?= number_format($l['harga'],0,',','.') ?></p>
                    <form method="post" class="mt-4">
                        <input type="hidden" name="id_layanan" value="<?= $l['id'] ?>">
                        <button type="submit" name="pesan" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90">Pesan Sekarang</button>
                    </form>
                </div>
                <?php endwhile; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<footer class="bg-dark text-white py-4 text-center mt-8">
    <p>&copy; <?= date('Y') ?> NokosKu. Gunakan layanan sesuai hukum dan ketentuan yang berlaku.</p>
</footer>

</body>
</html>
