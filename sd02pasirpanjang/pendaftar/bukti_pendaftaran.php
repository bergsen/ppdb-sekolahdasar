<?php
require_once '../config/database.php';
redirectIfNotAuthorized(['pendaftar']);

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: dashboard.php"); exit; }

$q = $conn->query("
    SELECT 
        cs.*,
        ta.tahun,
        IFNULL(b.nama_file, '') AS nama_file
    FROM calon_siswa cs
    JOIN tahun_ajaran ta ON cs.tahun_ajaran_id = ta.id
    LEFT JOIN berkas b 
        ON b.calon_siswa_id = cs.id
        AND b.jenis_berkas = 'Foto Siswa'
    WHERE cs.id = $id 
      AND cs.user_id = $userId
    LIMIT 1
");



if ($q->num_rows == 0) { header("Location: dashboard.php"); exit; }

$d = $q->fetch_assoc();
if ($d['status_terima'] !== 'diterima') {
    header("Location: dashboard.php"); exit;
}

/* ===== PATH FOTO (AMAN) ===== */
$fotoFile = $d['nama_file']; // sekarang BENAR-BENAR aman
$fotoPath = '../assets/uploads/foto/' . $fotoFile;
$fotoAda  = $fotoFile !== '' && file_exists($fotoPath);

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Bukti Pendaftaran Siswa SDN Pasirpanjang 02</title>

<link rel="stylesheet" href="../assets/css/style.css">

<style>
@media print {
    .no-print { display:none }
    body { background:#fff }
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    color: #000;
}

.container {
    width: 190mm;
    margin: auto;
    padding: 10mm;
}

/* HEADER */
.header {
    text-align: center;
    border-bottom: 2px solid #000;
    margin-bottom: 8px;
}
.header h1 { font-size: 15px; margin:0 }
.header h2 { font-size: 12px; margin:2px 0 }

/* BOX */
.box {
    border: 1px solid #000;
    padding: 6px;
    margin-bottom: 6px;
}
.box-title {
    font-weight: bold;
    margin-bottom: 4px;
}

/* ITEM */
.item {
    display: flex;
    justify-content: space-between;
    border-bottom: 1px dotted #000;
    padding: 2px 0;
}
.item:last-child { border-bottom: none; }

/* GRID */
.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
}

/* FOTO */
.foto {
    width: 3cm;
    height: 4cm;
    border: 1px solid #000;
    object-fit: cover;
    display: block;
}

/* QR */
.qr {
    text-align: center;
    margin: 8px 0;
}

/* CATATAN */
.catatan {
    font-size: 9px;
    margin-top: 4px;
    line-height: 1.4;
}

/* TTD */
.ttd {
    display: flex;
    justify-content: space-between;
    margin-top: 15mm;
}
.ttd div {
    width: 45%;
    text-align: center;
}
.stempel {
    width: 3cm;
    opacity: 0.8;
}
</style>
</head>

<body>

<div class="no-print" style="text-align:center;margin-bottom:6px">
    <button onclick="window.print()" class="btn-secondary" >Cetak <i class="fas fa-print"></i></button>
    <button onclick="downloadPDF()" class="btn-danger">Download PDF<i class="fas fa-pdf"></i></button>
    <a href="dashboard.php" class="btn-primary">Kembali</a>
</div>

<div class="container" id="buktiContent">

<!-- HEADER -->
<div class="header">
    <h1>SD NEGERI PASIRPANJANG 02</h1>
    <h2>BUKTI PENDAFTARAN PESERTA DIDIK BARU</h2>
</div>

<!-- INFO -->
<div class="box">
<div class="item"><span>No Pendaftaran</span><b><?= $d['nomor_pendaftaran'] ?></b></div>
<div class="item"><span>Tanggal Daftar</span><?= date('d/m/Y',strtotime($d['created_at'])) ?></div>
<div class="item"><span>Tahun Ajaran</span><?= $d['tahun'] ?></div>
<div class="item"><span>Status</span><b>DITERIMA</b></div>
</div>

<!-- DATA -->
<div class="grid">

<div class="box">
<div class="box-title">Data Calon Siswa</div>

<div style="display:flex;gap:6px">
<?php if ($fotoAda): ?>
    <img src="<?= $fotoPath ?>" class="foto">
<?php else: ?>
    <div class="foto" style="display:flex;align-items:center;justify-content:center;font-size:9px">
        FOTO<br>TIDAK ADA
    </div>
<?php endif; ?>

<div style="flex:1">
<div class="item"><span>Nama</span><?= htmlspecialchars($d['nama_siswa']) ?></div>
<div class="item"><span>JK</span><?= $d['jenis_kelamin']=='L'?'Laki-laki':'Perempuan' ?></div>
<div class="item"><span>TTL</span><?= htmlspecialchars($d['tempat_lahir']) ?>, <?= date('d/m/Y',strtotime($d['tanggal_lahir'])) ?></div>
<div class="item"><span>Agama</span><?= htmlspecialchars($d['agama']) ?></div>
</div>
</div>
</div>

<div class="box">
<div class="box-title">Data Orang Tua</div>
<div class="item"><span>Ayah</span><?= htmlspecialchars($d['nama_ayah']) ?></div>
<div class="item"><span>Pekerjaan</span><?= htmlspecialchars($d['pekerjaan_ayah']) ?></div>
<div class="item"><span>Ibu</span><?= htmlspecialchars($d['nama_ibu']) ?></div>
<div class="item"><span>Pekerjaan</span><?= htmlspecialchars($d['pekerjaan_ibu']) ?></div>
</div>

</div>

<!-- QR -->
<div class="qr">
<div id="qrcode"></div>
</div>

<!-- CATATAN -->
<div class="catatan">
<b>Catatan:</b><br>
Dokumen ini merupakan bukti terima sah pendaftaran Siswa SD Negeri Pasirpanjang 02 .<br>
Wajib dibawa saat daftar ulang dan pengambilan seragam.<br>
</div>

<!-- TTD -->
<div class="ttd">
<div>
<?= date('d F Y') ?><br>
Orang Tua / Wali<br><br><br>
( __________________ )
</div>

<div>
Panitia PPDB<br>
<img src="../assets/stempelfix.png" class="stempel"><br>
( __________________ )
</div>
</div>

</div>

<!-- SCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.0/build/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
QRCode.toCanvas(
    document.createElement('canvas'),
    `PPDB SD
No: <?= $d['nomor_pendaftaran'] ?>
Nama: <?= $d['nama_siswa'] ?>
Ayah: <?= $d['nama_ayah'] ?>
Ibu: <?= $d['nama_ibu'] ?>
Status: DITERIMA`,
    { width: 90 },
    (err, canvas) => document.getElementById('qrcode').appendChild(canvas)
);

function downloadPDF() {
    const { jsPDF } = window.jspdf;
    html2canvas(document.getElementById('buktiContent'), {
        scale: 2,
        useCORS: true
    }).then(canvas => {
        const pdf = new jsPDF('p','mm','a4');
        const w = 210;
        const h = canvas.height * w / canvas.width;
        const scale = h > 297 ? 297 / h : 1;
        pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, w*scale, h*scale);
        pdf.save('Bukti_Pendaftaran_<?= $d['nomor_pendaftaran'] ?>.pdf');
    });
    html2canvas(document.getElementById('buktiContent'), {
    scale: 2,
    useCORS: true,
    allowTaint: true
})

}
</script>

</body>
</html>
