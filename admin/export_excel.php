<?php
session_start();

include "../config/db.php";
require "../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/*
|--------------------------------------------------------------------------
| Proteksi admin
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Akses ditolak!");
}

/*
|--------------------------------------------------------------------------
| Ambil filter tanggal
|--------------------------------------------------------------------------
*/
$date_from = $_GET['from'] ?? date("Y-m-d");
$date_to   = $_GET['to']   ?? date("Y-m-d");

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $date_from = date("Y-m-d");
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $date_to = date("Y-m-d");
}

// Tukar jika tanggal terbalik
if (strtotime($date_to) < strtotime($date_from)) {
    $temp = $date_from;
    $date_from = $date_to;
    $date_to = $temp;
}

/*
|--------------------------------------------------------------------------
| Query data absensi
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        a.tanggal,
        u.username AS tukang,
        s.username AS spv,
        p.nama_proyek AS proyek,
        a.jam_masuk,
        a.jam_pulang,
        a.lembur_menit,
        a.note_user,
        a.foto_masuk,
        a.foto_pulang
    FROM absensi a
    LEFT JOIN users u ON u.id = a.tukang_id
    LEFT JOIN users s ON s.id = a.spv_id
    LEFT JOIN proyek p ON p.id = a.proyek_id
    WHERE a.tanggal BETWEEN '$date_from' AND '$date_to'
    ORDER BY a.tanggal DESC, a.jam_masuk DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("DB error: " . mysqli_error($conn));
}

/*
|--------------------------------------------------------------------------
| Fungsi bantu
|--------------------------------------------------------------------------
*/
function formatLembur($menit)
{
    $menit = (int)$menit;

    if ($menit <= 0) {
        return "-";
    }

    $jam = floor($menit / 60);
    $sisaMenit = $menit % 60;

    return $jam . "j " . $sisaMenit . "m";
}

function getStatusTelat($jamMasuk)
{
    if (empty($jamMasuk)) {
        return "-";
    }

    $time = strtotime($jamMasuk);

    if ($time >= strtotime("01:00:00") && $time <= strtotime("08:00:00")) {
        return "Good";
    }

    return "Telat";
}

function getStatusAbsensi($jamMasuk, $jamPulang)
{
    $statusTelat = getStatusTelat($jamMasuk);

    if (empty($jamMasuk)) {
        return "BELUM MASUK";
    }

    if (empty($jamPulang)) {
        return "BELUM PULANG ($statusTelat)";
    }

    return "LENGKAP ($statusTelat)";
}

function insertImageToSheet($sheet, $imagePath, $cell, $name = 'Foto')
{
    if (!empty($imagePath) && file_exists($imagePath)) {
        $drawing = new Drawing();
        $drawing->setName($name);
        $drawing->setDescription($name);
        $drawing->setPath($imagePath);
        $drawing->setHeight(80);
        $drawing->setCoordinates($cell);
        $drawing->setOffsetX(8);
        $drawing->setOffsetY(5);
        $drawing->setWorksheet($sheet);
        return true;
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| Buat spreadsheet
|--------------------------------------------------------------------------
*/
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Rekap Absensi');

/*
|--------------------------------------------------------------------------
| Judul laporan
|--------------------------------------------------------------------------
*/
$sheet->mergeCells('A1:K1');
$sheet->setCellValue('A1', 'LAPORAN ABSENSI TUKANG');

$sheet->mergeCells('A2:K2');
$sheet->setCellValue('A2', 'Periode: ' . $date_from . ' s/d ' . $date_to);

/*
|--------------------------------------------------------------------------
| Header tabel
|--------------------------------------------------------------------------
*/
$headers = [
    'A4' => 'Tanggal',
    'B4' => 'Tukang',
    'C4' => 'SPV',
    'D4' => 'Proyek',
    'E4' => 'Jam Masuk',
    'F4' => 'Jam Pulang',
    'G4' => 'Jam Lembur',
    'H4' => 'Status Absensi',
    'I4' => 'Catatan',
    'J4' => 'Foto Masuk',
    'K4' => 'Foto Pulang',
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

/*
|--------------------------------------------------------------------------
| Style judul
|--------------------------------------------------------------------------
*/
$sheet->getStyle('A1:K2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:K2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);

/*
|--------------------------------------------------------------------------
| Style header tabel
|--------------------------------------------------------------------------
*/
$sheet->getStyle('A4:K4')->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '1F4E78'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical'   => Alignment::VERTICAL_CENTER,
        'wrapText'   => true,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
]);

/*
|--------------------------------------------------------------------------
| Isi data
|--------------------------------------------------------------------------
*/
$row = 5;

while ($data = mysqli_fetch_assoc($result)) {
    $tanggal    = $data['tanggal'] ?? '';
    $tukang     = $data['tukang'] ?? '';
    $spv        = $data['spv'] ?? '';
    $proyek     = $data['proyek'] ?? '';
    $jamMasuk   = $data['jam_masuk'] ?? '';
    $jamPulang  = $data['jam_pulang'] ?? '';
    $catatan    = $data['note_user'] ?? '';
    $jamLembur  = formatLembur($data['lembur_menit'] ?? 0);
    $status     = getStatusAbsensi($jamMasuk, $jamPulang);

    // Isi kolom teks
    $sheet->setCellValue("A{$row}", $tanggal);
    $sheet->setCellValue("B{$row}", $tukang);
    $sheet->setCellValue("C{$row}", $spv);
    $sheet->setCellValue("D{$row}", $proyek);
    $sheet->setCellValue("E{$row}", $jamMasuk);
    $sheet->setCellValue("F{$row}", $jamPulang);
    $sheet->setCellValue("G{$row}", $jamLembur);
    $sheet->setCellValue("H{$row}", $status);
    $sheet->setCellValue("I{$row}", $catatan);

    // Tinggi baris supaya foto rapi
    $sheet->getRowDimension($row)->setRowHeight(85);

    /*
    |--------------------------------------------------------------------------
    | PATH FOTO
    |--------------------------------------------------------------------------
    | Versi ini mengasumsikan:
    | - kolom foto_masuk  berisi nama file, contoh: abc.jpg
    | - kolom foto_pulang berisi nama file, contoh: xyz.jpg
    | - file disimpan di folder: ../uploads/
    */
    $fotoMasukPath  = !empty($data['foto_masuk'])  ? realpath(__DIR__ . '/../uploads/' . $data['foto_masuk']) : false;
    $fotoPulangPath = !empty($data['foto_pulang']) ? realpath(__DIR__ . '/../uploads/' . $data['foto_pulang']) : false;

    // Foto masuk
    if (!insertImageToSheet($sheet, $fotoMasukPath, "J{$row}", 'Foto Masuk')) {
        $sheet->setCellValue("J{$row}", '-');
    }

    // Foto pulang
    if (!insertImageToSheet($sheet, $fotoPulangPath, "K{$row}", 'Foto Pulang')) {
        $sheet->setCellValue("K{$row}", '-');
    }

    $row++;
}

/*
|--------------------------------------------------------------------------
| Style isi tabel
|--------------------------------------------------------------------------
*/
$lastRow = $row - 1;

if ($lastRow >= 5) {
    $sheet->getStyle("A4:K{$lastRow}")->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ]);

    $sheet->getStyle("A4:K{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle("A4:K{$lastRow}")->getAlignment()->setWrapText(true);
}

/*
|--------------------------------------------------------------------------
| Lebar kolom
|--------------------------------------------------------------------------
*/
$sheet->getColumnDimension('A')->setWidth(14);
$sheet->getColumnDimension('B')->setWidth(18);
$sheet->getColumnDimension('C')->setWidth(18);
$sheet->getColumnDimension('D')->setWidth(24);
$sheet->getColumnDimension('E')->setWidth(12);
$sheet->getColumnDimension('F')->setWidth(12);
$sheet->getColumnDimension('G')->setWidth(12);
$sheet->getColumnDimension('H')->setWidth(22);
$sheet->getColumnDimension('I')->setWidth(30);
$sheet->getColumnDimension('J')->setWidth(18);
$sheet->getColumnDimension('K')->setWidth(18);

/*
|--------------------------------------------------------------------------
| Freeze pane
|--------------------------------------------------------------------------
*/
$sheet->freezePane('A5');

/*
|--------------------------------------------------------------------------
| Output file
|--------------------------------------------------------------------------
*/
$filename = "laporan_absensi_tukang_{$date_from}_sd_{$date_to}.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;