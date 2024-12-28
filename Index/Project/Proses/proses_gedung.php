<?php
include '../../../Koneksi/Koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $action = $_POST['action'] ?? '';
    $idGedung = $_POST['id_gedung'] ?? '';
    $namaGedung = $_POST['nama_gedung'] ?? '';
    $projectCode = $_POST['project_code'] ?? '';
    $address = $_POST['address'] ?? '';

    // Tambah Data
    if ($action === 'add' && $namaGedung && $projectCode && $address) {
        $query = "INSERT INTO gedung (nama_gedung, project_code, address) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $namaGedung, $projectCode, $address);

        if ($stmt->execute()) {
            header("Location: ../Identitas_Gedung.php?message=success");
            exit();
        } else {
            header("Location: ../Identitas_Gedung.php?message=error");
            exit();
        }
    } 
    // Ubah Data
    elseif ($action === 'edit' && $idGedung && $namaGedung && $projectCode && $address) {
        $query = "UPDATE gedung SET nama_gedung = ?, project_code = ?, address = ? WHERE id_gedung = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $namaGedung, $projectCode, $address, $idGedung);

        if ($stmt->execute()) {
            header("Location: ../Identitas_Gedung.php?message=update-success");
            exit();
        } else {
            header("Location: ../Identitas_Gedung.php?message=update-error");
            exit();
        }
    } 
    // Hapus Data
    elseif ($action === 'delete' && $idGedung) {
        $query = "DELETE FROM gedung WHERE id_gedung = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $idGedung);

        if ($stmt->execute()) {
            header("Location: ../Identitas_Gedung.php?message=delete-success");
            exit();
        } else {
            header("Location: ../Identitas_Gedung.php?message=delete-error");
            exit();
        }
    } 
    // Jika data tidak valid
    else {
        header("Location: ../Identitas_Gedung.php?message=invalid");
        exit();
    }
}

?>
