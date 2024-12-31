<?php
require '../vendor/autoload.php';
include '../../SINERGI/Koneksi/Koneksi.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;


if (isset($_GET['id_gedung'])) {
  // Query untuk mengambil data tower berdasarkan id_gedung dan paging
  // Ambil ID Gedung dari tombol export (misalnya via POST atau GET)

  $id_gedung = (int) $_GET['id_gedung'];
  // Query dengan JOIN
  $sql = "
    SELECT
        gedung.id_gedung,
        gedung.nama_gedung,
        gedung.project_code,
        gedung.address,
        gedung.created_at AS gedung_created_at,

        audit_tower.id_tower,
        audit_tower.nama_tower,
        audit_tower.pic,
        audit_tower.jumlah_lantai,
        audit_tower.created_at AS tower_created_at,

        audit_lift.id_lift,
        audit_lift.lift_no,
        audit_lift.lift_brand,
        audit_lift.lift_type,

        audit_komponen.id AS audit_komponen_id,
        audit_komponen.keterangan AS audit_komponen_keterangan,
        audit_komponen.foto_bukti AS audit_komponen_foto_bukti,
        audit_komponen.prioritas AS audit_komponen_prioritas,
        temuan_komponen.nama_temuan AS audit_komponen_temuan,
        solusi_komponen.nama_solusi AS audit_komponen_solusi,

        instalations.id_instalasi,
        instalations.foto_instalasi,
        instalations.nama_instalasi,
        instalations.deskripsi AS instalasi_deskripsi,

        komponen.id_komponen,
        komponen.code_komponen,
        komponen.nama_komponen,
        komponen.keterangan AS komponen_keterangan

    FROM audit_komponen

    -- Join ke table tower
    LEFT JOIN gedung ON gedung.id_gedung = audit_komponen.id_gedung


    -- Join ke table audit_komponen
    LEFT JOIN audit_tower ON audit_komponen.id_tower = audit_tower.id_tower

    -- Join ke table lift
    LEFT JOIN audit_lift ON audit_komponen.id_lift = audit_lift.id_lift

    -- Join ke table temuan_komponen
    LEFT JOIN temuan_komponen ON audit_komponen.id_temuan = temuan_komponen.id_temuan

    -- Join ke table solusi_komponen
    LEFT JOIN solusi_komponen ON audit_komponen.id_solusi = solusi_komponen.id_solusi

    -- Join ke table instalasi
    LEFT JOIN instalations ON audit_lift.id_lift = instalations.id_lift

    -- Join ke table komponen
    LEFT JOIN komponen ON audit_komponen.id_komponen = komponen.id_komponen

    WHERE audit_komponen.id_gedung = ?
";
  // Eksekusi query
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $id_gedung);
  $stmt->execute();
  $results = $stmt->get_result();
  // Ambil data
  if ($results->num_rows > 0) {
    // Proses hasil query menjadi array
    $data = [];
    $lift_komp = [];
    $lift = [];
    $komp_keterangan = [];

    while ($row = $results->fetch_assoc()) {
      // Menambahkan semua data ke array $data
      $data[] = $row;


      // Ambil keterangan dan no_lift
      $keterangan = $row['komponen_keterangan'];
      $no_lift = $row['lift_no'];

      $komp_keterangan[$keterangan][] = $row;
      $lift[$no_lift][] = $row;
      // Jika sudah ada lift dengan nomor yang sama, tambahkan entri keterangan baru
      if (!isset($lift_komp[$no_lift])) {
        $lift_komp[$no_lift] = [];
      }

      // Menambahkan data ke keterangan yang sesuai
      if (!isset($lift_komp[$no_lift][$keterangan])) {
        $lift_komp[$no_lift][$keterangan] = [];
      }
      // Tambahkan row ke dalam keterangan
      $lift_komp[$no_lift][$keterangan][] = $row;
    }

    // Tampilkan hasil data
    // echo '<pre>';
    // print_r($komp_keterangan);
    // echo '</pre>';
    // die();
  } else {
    echo "Data tidak ditemukan.";
  }

  // Cek hasil

}
try {
  // 1. Buat spreadsheet baru

  $templatePath = './templates/audit.xlsx';
  $spreadsheet = IOFactory::load($templatePath);
  $sheetUtama = $spreadsheet->getSheet(0);
  $sheetUtama->setTitle('Defect Keseluruhan');

  $nama_gedung = $data[0]['nama_gedung'];
  $address = $data[0]['address'];
  $gedung_created_at = $data[0]['gedung_created_at'];
  $lift_no = $data[0]['lift_no'];

  $sheetUtama->setCellValue('C3', ' : ' . $nama_gedung);
  $sheetUtama->setCellValue('C4', ' : ' . $address);
  $sheetUtama->setCellValue('F3', ' : ALL UNIT');
  $sheetUtama->setCellValue('F4', ' : ' . $gedung_created_at);
  // Baris yang ingin Anda salin
  $sourceCell = 'G9';
  $startColumn = 'H';

  // Sheet Utama
  $lift_number = 11;
  $index = 0;

  foreach ($lift as $nama_lift => $dataLift) {
    // sheetUtama Defet Keseluruhan
    // urutkan huruf h,i,j,k dst.. sesuai data lift
    $sheetUtama->setCellValue($sourceCell . $lift_number, $nama_lift);
    $sheetUtama->getStyle($sourceCell . $lift_number)->getFont()->setBold(true);

    $targetColumn = chr(ord($startColumn) + $index++);
    $targetCell = "{$targetColumn}9";
    $sourceValues = $sheetUtama->rangeToArray($sourceCell, null, true, true, true);
    $sheetUtama->fromArray($sourceValues, null, "{$targetColumn}9");
    // Salin style

    $sheetUtama->duplicateStyle(
      $sheetUtama->getStyle($sourceCell),
      $targetCell
    );
    //  Merge cell untuk kolom tujuan
    $sheetUtama->setCellValue('G9', $nama_lift);
    // Ganti teks dengan nomor lift
    $sheetUtama->setCellValue("{$targetColumn}9", $nama_lift);
    foreach ($dataLift as $komponen) {
      if ($komponen['audit_komponen_keterangan'] == '') {
        $sheet->setCellValue('G' . $row, 'V');
      } else {
        $sheet->setCellValue('G' . $row, $komponen['audit_komponen_keterangan']);
      }
      $lift_number++;
    }
  }

  $komp_number = 11;
  foreach ($komp_keterangan as $i => $dataKeterangan) {

    // var_dump($row_number);
    $sheetUtama->setCellValue('B' . $komp_number, $i);
    $sheetUtama->getStyle('B' . $komp_number)->getFont()->setBold(true);
    $komp_number++;

    foreach ($dataKeterangan as $komponen) {
      $sheetUtama->setCellValue('B' . $komp_number, $komponen['nama_komponen']);
      $sheetUtama->setCellValue('C' . $komp_number, $komponen['audit_komponen_prioritas']);
      $sheetUtama->setCellValue('D' . $komp_number, $komponen['audit_komponen_temuan']);
      $sheetUtama->setCellValue('E' . $komp_number, $komponen['audit_komponen_solusi']);
      $sheetUtama->setCellValue('F' . $komp_number, $komponen['audit_komponen_foto_bukti']);
      $komp_number++;
    }
  }


  $templateSheet = $spreadsheet->getSheetByName('example');
  $templateSheet->setCellValue('C3', ' : ' . $nama_gedung);
  $templateSheet->setCellValue('C4', ' : ' . $address);
  $templateSheet->setCellValue('F3', ' : ' . $lift_no);
  $templateSheet->setCellValue('F4', ' : ' . $gedung_created_at);

  foreach ($lift_komp as $i => $dataLift) {

    $sheet = clone $templateSheet;
    $sheet->setTitle($i);

    // Tambahkan sheet ke spreadsheet
    $spreadsheet->addSheet($sheet);

    $row = 11;
    foreach ($dataLift as $keterangan => $dataKomponen) {


      // sheet by lift
      $sheet->setCellValue('B10', 'MESIN ROOM');
      $sheet->getStyle('B10')->getFont()->setBold(true);
      $sheet->setCellValue('B' . $row, $keterangan);
      $sheet->getStyle('B' . $row)->getFont()->setBold(true);
      $row++;

      foreach ($dataKomponen as $komponen) {


        // sheet by lift
        $sheet->setCellValue('B' . $row, $komponen['nama_komponen']);
        $sheet->setCellValue('C' . $row, $komponen['audit_komponen_prioritas']);
        $sheet->setCellValue('D' . $row, $komponen['audit_komponen_temuan']);
        $sheet->setCellValue('E' . $row, $komponen['audit_komponen_solusi']);
        $sheet->setCellValue('F' . $row, $komponen['audit_komponen_foto_bukti']);
        if ($komponen['audit_komponen_keterangan'] == '') {
          $sheet->setCellValue('G' . $row, 'V');
        } else {
          $sheet->setCellValue('G' . $row, $komponen['audit_komponen_keterangan']);
        }
        $row++;
      }
    }
  }

  $fileName = 'audit.xlsx';
  $writer = new Xlsx($spreadsheet);
  $writer->save($fileName);

  echo "File Excel berhasil dibuat: <a href='$fileName' download>Download</a>";
  $spreadsheet->removeSheetByIndex(
    $spreadsheet->getIndex($templateSheet)
  );
  exit();
} catch (Exception $e) {
  echo "Terjadi kesalahan: " . $e->getMessage();
}