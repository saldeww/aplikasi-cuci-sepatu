<?php

function hapusPesanan($conn, $pesanan_id)
{
    $pesanan_id = (int) $pesanan_id;

    if ($pesanan_id <= 0) {
        return [
            'success' => false,
            'message' => 'ID pesanan tidak valid.'
        ];
    }

    $conn->begin_transaction();

    try {
        // Pastikan pesanan tersedia
        $stmt = $conn->prepare("
            SELECT id
            FROM pesanan
            WHERE id = ?
            FOR UPDATE
        ");

        $stmt->bind_param("i", $pesanan_id);
        $stmt->execute();

        $pesanan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pesanan) {
            throw new Exception('Pesanan tidak ditemukan.');
        }

        // Hapus detail pesanan terlebih dahulu
        $stmt = $conn->prepare("
            DELETE FROM detail_pesanan
            WHERE pesanan_id = ?
        ");

        $stmt->bind_param("i", $pesanan_id);
        $stmt->execute();
        $stmt->close();

        // Hapus pesanan utama
        $stmt = $conn->prepare("
            DELETE FROM pesanan
            WHERE id = ?
        ");

        $stmt->bind_param("i", $pesanan_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        return [
            'success' => true,
            'message' => 'Pesanan berhasil dihapus.'
        ];
    } catch (Throwable $e) {
        $conn->rollback();

        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
