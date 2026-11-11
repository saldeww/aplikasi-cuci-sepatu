
<?php
require_once 'config/database.php';

$akun = [
    [
        'nama' => 'Administrator',
        'email' => 'admin@cucidisini.com',
        'password' => 'Admin123!',
        'no_wa' => '081234567890',
        'role' => 'Admin'
    ],
    [
        'nama' => 'Pelanggan Demo',
        'email' => 'customer@cucidisini.com',
        'password' => 'Customer123!',
        'no_wa' => '081234567891',
        'role' => 'Customer'
    ]
];

$stmt = $conn->prepare("
    INSERT INTO users (nama, email, password, no_wa, role)
    VALUES (?, ?, ?, ?, ?)
");

foreach ($akun as $data) {

    $hash = password_hash(
        $data['password'],
        PASSWORD_DEFAULT
    );

    $stmt->bind_param(
        "sssss",
        $data['nama'],
        $data['email'],
        $hash,
        $data['no_wa'],
        $data['role']
    );

    if ($stmt->execute()) {
        echo "Berhasil membuat akun: "
            . htmlspecialchars($data['email']) . "<br>";
    } else {
        echo "Gagal: "
            . htmlspecialchars($data['email'])
            . " - " . htmlspecialchars($stmt->error)
            . "<br>";
    }
}

$stmt->close();
$conn->close();
?>
