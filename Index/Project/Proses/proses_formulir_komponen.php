<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli('localhost', 'root', '', 'sinergi_a');
    if ($conn->connect_error) {
        die(json_encode(['status' => 'error', 'message' => 'Koneksi database gagal: ' . $conn->connect_error]));
    }

    $uploadDir = 'upload/foto_bukti/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $data = $_POST['data'] ?? [];
    if (empty($data)) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada data yang diterima.']);
        exit;
    }

    foreach ($data as $index => $row) {
        $id_komponen = $row['komponen'] ?? null;
        $id_temuan = $row['temuan'] ?? null;
        $id_solusi = $row['solusi'] ?? null;
        $prioritas = $row['prioritas'] ?? null;
        $keterangan = $row['keterangan'] ?? null;
        $fotoPath = null;

        // Cek apakah file foto dikirim untuk item ini
        if (isset($_FILES['data']['name'][$index]['foto']) && $_FILES['data']['error'][$index]['foto'] === UPLOAD_ERR_OK) {
            $fotoName = basename($_FILES['data']['name'][$index]['foto']);
            $fotoPath = $uploadDir . uniqid() . '_' . $fotoName;

            if (!move_uploaded_file($_FILES['data']['tmp_name'][$index]['foto'], $fotoPath)) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah foto untuk item ke-' . $index]);
                exit;
            }
        }

        // Simpan data ke database
        $query = $conn->prepare(
            "INSERT INTO audit_komponen (id_komponen, id_temuan, id_solusi, prioritas, keterangan, foto_bukti) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $query->bind_param("iiiiss", $id_komponen, $id_temuan, $id_solusi, $prioritas, $keterangan, $fotoPath);

        if (!$query->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $query->error]);
            exit;
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Data berhasil disimpan.']);
}