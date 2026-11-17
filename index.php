
<?php
session_start();

require_once "koneksi.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// =====================================
// CEK LOGIN DAN SESSION
// =====================================

$isDashboard = isset($_GET["dashboard"]) &&
               $_GET["dashboard"] === "true";

if ($isDashboard && !isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["username"] ?? "Teman HealthyGo";

// =====================================
// PROSES CRUD LATIHAN
// =====================================

if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["aksi_latihan"])) {

    $aksi = $_POST["aksi_latihan"];

    try {

        // =================================
        // TAMBAH LATIHAN
        // =================================

        if ($aksi === "tambah") {

            $nama = trim($_POST["nama_latihan"] ?? "");
            $deskripsi = trim($_POST["deskripsi"] ?? "");
            $kalori = $_POST["kalori_per_menit"] ?? "";

            if (
                $nama === "" ||
                $deskripsi === "" ||
                $kalori === "" ||
                !is_numeric($kalori) ||
                (float)$kalori < 0
            ) {
                throw new Exception("Data latihan belum lengkap atau tidak valid.");
            }

            $kalori = (float)$kalori;

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO latihan
                (nama_latihan, deskripsi, kalori_per_menit)
                VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param($stmt, "ssd", $nama, $deskripsi, $kalori);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) === 1) {
                $_SESSION["pesan_crud"] = "Latihan berhasil ditambahkan!";
            } else {
                throw new Exception("Data latihan belum berhasil disimpan.");
            }

            mysqli_stmt_close($stmt);
        }

        // =================================
        // EDIT LATIHAN
        // =================================

        elseif ($aksi === "edit") {

            $id = (int)($_POST["id_latihan"] ?? 0);
            $nama = trim($_POST["nama_latihan"] ?? "");
            $deskripsi = trim($_POST["deskripsi"] ?? "");
            $kalori = $_POST["kalori_per_menit"] ?? "";

            if (
                $id <= 0 ||
                $nama === "" ||
                $deskripsi === "" ||
                $kalori === "" ||
                !is_numeric($kalori) ||
                (float)$kalori < 0
            ) {
                throw new Exception("Data latihan tidak valid.");
            }

            $kalori = (float)$kalori;

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE latihan
                SET nama_latihan = ?,
                    deskripsi = ?,
                    kalori_per_menit = ?
                WHERE id_latihan = ?"
            );

            mysqli_stmt_bind_param($stmt, "ssdi", $nama, $deskripsi, $kalori, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $_SESSION["pesan_crud"] = "Data latihan berhasil diperbarui!";
        }

        // =================================
        // HAPUS LATIHAN
        // =================================

        elseif ($aksi === "hapus") {

            $id = (int)($_POST["id_latihan"] ?? 0);

            if ($id <= 0) {
                throw new Exception("ID latihan tidak valid.");
            }

            $stmt = mysqli_prepare(
                $conn,
                "DELETE FROM latihan WHERE id_latihan = ?"
            );

            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $_SESSION["pesan_crud"] = "Data latihan berhasil dihapus!";
        }

        else {
            throw new Exception("Aksi latihan tidak dikenal.");
        }

    } catch (mysqli_sql_exception $e) {

        $_SESSION["pesan_crud"] =
            "Kesalahan database: " . $e->getMessage();

    } catch (Exception $e) {

        $_SESSION["pesan_crud"] = $e->getMessage();
    }

    header("Location: index.php?dashboard=true#kelolaLatihan");
    exit;
}

// =====================================
// MENGAMBIL DATA LATIHAN
// =====================================

$resultLatihan = mysqli_query(
    $conn,
    "SELECT * FROM latihan ORDER BY id_latihan ASC"
);

$resultPilihanLatihan = mysqli_query(
    $conn,
    "SELECT * FROM latihan ORDER BY id_latihan ASC"
);

// =====================================
// PESAN NOTIFIKASI CRUD
// =====================================

$pesanCrud = $_SESSION["pesan_crud"] ?? "";
unset($_SESSION["pesan_crud"]);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>HealthyGo - Teman Olahraga Harianmu</title>

    <link rel="stylesheet" href="style_healthygo_lanjutan.css">
</head>

<body>

<div class="wrap">

    <!-- HALAMAN HOME -->
    <section id="home"
        style="<?= $isDashboard ? 'display:none;' : '' ?>">

        <div class="hero">

            <div class="hero-content">

                <div class="logo">HealthyGo 💛</div>

                <h1 id="judulHome">
                    Teman Olahraga<br>Harianmu
                </h1>

                <p>
                    Atur rutinitas olahraga, tentukan target kebugaran,
                    dan pantau kemajuan kesehatanmu setiap hari
                    bersama HealthyGo.
                </p>

                <div class="dom-buttons">
                    <button type="button" onclick="ubahJudul()">Ubah Judul</button>
                    <button type="button" onclick="ubahWarna()">Ubah Warna Tombol</button>
                    <button type="button" onclick="tampilDeskripsi()">Info Olahraga</button>
                </div>

                <p id="infoOlahraga" hidden>
                    Olahraga rutin membantu menjaga stamina dan kebugaran tubuh.
                </p>

                <button class="start" type="button"
                    onclick="window.location.href='login.php'">
                    Mulai Hidup Sehatmu
                </button>

            </div>

            <img src="image.png" alt="Ilustrasi olahraga">

        </div>
    </section>

    <!-- DASHBOARD -->
    <section id="dashboard"
        style="<?= $isDashboard ? 'display:block;' : 'display:none;' ?>">

        <h2 class="dash-title">
            Halo, <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>! 👋
        </h2>

        <p class="dash-sub">
            Yuk tetap aktif dan capai target olahraga hari ini.
        </p>

        <!-- TARGET HARIAN -->
        <div class="target">
            Target Hari Ini

            <small id="targetText">
                0% target olahraga selesai.
            </small>

            <div class="target-line">
                <i id="targetBar"></i>
            </div>
        </div>

        <!-- STATISTIK -->
        <div class="stats">

            <div class="stat">
                🔥 <strong id="calories">0</strong>
                Kalori Terbakar
            </div>

            <div class="stat">
                ⏱️ <strong id="minutes">0 Menit</strong>
                Total Waktu Olahraga
            </div>

            <div class="stat">
                🏆 <strong id="sessions">0 Latihan</strong>
                Latihan Dipilih
            </div>

        </div>

        <!-- TIMER -->
        <div class="timer-box" style="position: relative; z-index: 9999;">

            <h3>⏱️ Timer Latihan HealthyGo</h3>

            <label for="durasiTimer">Pilih durasi latihan:</label>

            <select id="durasiTimer"
                style="position: relative; z-index: 10000; pointer-events: auto; width: 100%; cursor: pointer;">

                <option value="1">1 menit</option>
                <option value="5">5 menit</option>
                <option value="10">10 menit</option>
                <option value="15">15 menit</option>
                <option value="20" selected>20 menit</option>
                <option value="25">25 menit</option>
                <option value="30">30 menit</option>

            </select>

            <p id="namaLatihanAktif">
                Belum ada latihan dipilih.
            </p>

            <div id="timerDisplay">20:00</div>

            <p id="timerStatus" aria-live="polite">
                Pilih latihan untuk memulai hitung mundur.
            </p>

            <div class="timer-actions">
                <button type="button" id="tombolTimer">▶ Mulai</button>
                <button type="button" id="tombolJedaTimer">⏸ Jeda</button>
                <button type="button" id="tombolResetTimer">↻ Atur Ulang</button>
            </div>

        </div>

        <!-- JADWAL -->
        <h3 class="title">Jadwal Hari Ini</h3>

        <div class="schedule" id="scheduleList">
            <p class="jadwal-kosong">
                Belum ada latihan hari ini.
            </p>
        </div>

        <!-- KEMAJUAN MINGGUAN -->
        <h3 class="title">Kemajuan Harian</h3>

        <div class="week">

            <div class="day">Senin<div class="bar"><i></i></div></div>
            <div class="day">Selasa<div class="bar"><i></i></div></div>
            <div class="day">Rabu<div class="bar"><i></i></div></div>
            <div class="day">Kamis<div class="bar"><i></i></div></div>
            <div class="day">Jumat<div class="bar"><i></i></div></div>
            <div class="day">Sabtu<div class="bar"><i></i></div></div>
            <div class="day">Minggu<div class="bar"><i></i></div></div>

        </div>

        <!-- CATATAN MINGGUAN -->
        <h3 class="title">Catatan Minggu Ini</h3>

        <div class="weekly-notes">

            <label for="catatanMinggu">
                Tulis catatan atau target olahraga:
            </label>

            <textarea id="catatanMinggu" rows="4"
                placeholder="Contoh: olahraga 20 menit setiap hari."></textarea>

            <button type="button" id="simpanCatatanBtn">
                Simpan Catatan
            </button>

            <p id="statusCatatan">
                Catatan tersimpan di browser setelah disimpan.
            </p>

        </div>

        <!-- PILIHAN LATIHAN -->
        <h3 class="title">Pilih Latihan</h3>

        <div class="exercise-grid">

            <?php while ($pilihan = mysqli_fetch_assoc($resultPilihanLatihan)): ?>

                <div class="exercise">

                    <h3>
                        💪 <?= htmlspecialchars($pilihan["nama_latihan"], ENT_QUOTES, 'UTF-8') ?>
                    </h3>

                    <button type="button"
                        onclick="latihan(
                            <?= htmlspecialchars(json_encode($pilihan["nama_latihan"]), ENT_QUOTES, 'UTF-8') ?>,
                            20,
                            <?= (float)$pilihan["kalori_per_menit"] ?>
                        )">
                        Mulai Latihan
                    </button>

                </div>

            <?php endwhile; ?>

        </div>

        <!-- KELOLA DATA LATIHAN CRUD -->
        <div id="kelolaLatihan" class="crud-section">

            <h3 class="title">Kelola Data Latihan</h3>

            <?php if ($pesanCrud !== ""): ?>
                <p class="pesan-crud">
                    <?= htmlspecialchars($pesanCrud, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <!-- FORM TAMBAH -->
            <h4>Tambah Latihan Baru</h4>

            <form method="POST"
                action="index.php?dashboard=true#kelolaLatihan">

                <input type="hidden" name="aksi_latihan" value="tambah">

                <label>Nama Latihan:</label>
                <input type="text" name="nama_latihan" required>

                <label>Deskripsi:</label>
                <textarea name="deskripsi" required></textarea>

                <label>Kalori per Menit:</label>
                <input type="number" name="kalori_per_menit"
                    step="0.01" min="0" required>

                <button type="submit">Simpan Latihan</button>

            </form>

            <hr>

            <!-- DAFTAR DATA LATIHAN -->
            <h4>Daftar Data Latihan</h4>

            <?php while ($latihanData = mysqli_fetch_assoc($resultLatihan)): ?>

                <div class="crud-item">

                    <!-- FORM EDIT -->
                    <form method="POST"
                        action="index.php?dashboard=true#kelolaLatihan">

                        <input type="hidden" name="aksi_latihan" value="edit">

                        <input type="hidden" name="id_latihan"
                            value="<?= (int)$latihanData["id_latihan"] ?>">

                        <label>Nama Latihan:</label>

                        <input type="text" name="nama_latihan"
                            value="<?= htmlspecialchars($latihanData["nama_latihan"], ENT_QUOTES, 'UTF-8') ?>"
                            required>

                        <label>Deskripsi:</label>

                        <input type="hidden" name="deskripsi"
                            value="<?= htmlspecialchars($latihanData["deskripsi"], ENT_QUOTES, 'UTF-8') ?>">

                        <button type="button"
                            onclick="lihatDeskripsi(
                                <?= htmlspecialchars(json_encode($latihanData["nama_latihan"], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>,
                                <?= htmlspecialchars(json_encode($latihanData["deskripsi"], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>
                            )">
                            Lihat Deskripsi
                        </button>

                        <label>Kalori per Menit:</label>

                        <input type="number" name="kalori_per_menit"
                            value="<?= htmlspecialchars($latihanData["kalori_per_menit"], ENT_QUOTES, 'UTF-8') ?>"
                            step="0.01" min="0" required>

                        <button type="submit">
                            Edit / Simpan Perubahan
                        </button>

                    </form>

                    <!-- FORM HAPUS -->
                    <form method="POST"
                        action="index.php?dashboard=true#kelolaLatihan"
                        onsubmit="return confirm('Yakin ingin menghapus latihan ini?')">

                        <input type="hidden" name="aksi_latihan" value="hapus">

                        <input type="hidden" name="id_latihan"
                            value="<?= (int)$latihanData["id_latihan"] ?>">

                        <button type="submit">Hapus</button>

                    </form>

                </div>

            <?php endwhile; ?>

            <!-- POPUP DESKRIPSI -->
            <div id="popupDeskripsi" class="popup-deskripsi">

                <div class="isi-popup">

                    <h3 id="judulDeskripsi">
                        Deskripsi Latihan
                    </h3>

                    <p id="isiDeskripsi"></p>

                    <button type="button" onclick="tutupDeskripsi()">
                        Tutup
                    </button>

                </div>

            </div>

        </div>

        <!-- TIPS -->
        <h3 class="title">Tips Olahraga</h3>

        <button type="button" id="ambilTipsBtn">
            Ambil Tips Olahraga
        </button>

        <p id="hasilTips">
            Klik tombol untuk mendapatkan tips olahraga.
        </p>

        <!-- RESET -->
        <button class="reset" type="button" onclick="resetHari()">
            Reset Hari Ini
        </button>

        <!-- LOGOUT -->
        <button type="button"
            onclick="window.location.href='logout.php'">
            Logout
        </button>

    </section>

</div>

<!-- TOAST -->
<div id="toast" class="toast" role="status" aria-live="polite">
    <span id="toastPesan"></span>
</div>

<!-- JAVASCRIPT HEALTHYGO -->
<script src="script_healthygo_lanjutan.js?v=3"></script>

<script>
// =====================================
// POPUP DESKRIPSI LATIHAN
// =====================================

function lihatDeskripsi(nama, deskripsi) {

    document.getElementById("judulDeskripsi").textContent = nama;

    document.getElementById("isiDeskripsi").textContent = deskripsi;

    document.getElementById("popupDeskripsi").style.display = "flex";
}

function tutupDeskripsi() {

    document.getElementById("popupDeskripsi").style.display = "none";
}

// Menutup popup jika bagian luar kotak diklik
document.getElementById("popupDeskripsi").addEventListener("click", function(event) {

    if (event.target === this) {
        tutupDeskripsi();
    }

});
</script>

</body>
</html>
