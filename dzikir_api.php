<?php
// File: dzikir_api.php

// Koneksi ke database (ganti sesuai dengan konfigurasi database Anda)
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'dzikirpop';

$connection = mysqli_connect($host, $username, $password, $dbname);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header("Access-Control-Allow-Headers: Content-Type");

// Fungsi untuk mengirimkan dzikir dan mengupdate leaderboard
function postDzikir($province) {
    global $connection;

    // Cek apakah sudah ada data timestamp terakhir dalam session
    session_start();
    $last_request_time = isset($_SESSION['last_request_time']) ? $_SESSION['last_request_time'] : null;
    
    // Cek jika sudah lewat 30 detik dari request sebelumnya
    if ($last_request_time !== null && time() - $last_request_time < 30) {
        http_response_code(429); // Too Many Requests
        echo json_encode(array("message" => "Only one request allowed per 30 seconds."));
        return;
    }

    // Cek apakah data provinsi sudah ada di leaderboard
    $query = "SELECT * FROM leaderboard WHERE province = '$province'";
    $result = mysqli_query($connection, $query);

    if (mysqli_num_rows($result) > 0) {
        // Jika provinsi sudah ada, update count dan updated_at
        $query = "UPDATE leaderboard SET count = count + 1, updated_at = NOW() WHERE province = '$province'";
        mysqli_query($connection, $query);
    } else {
        // Jika provinsi belum ada, tambahkan data baru ke leaderboard
        $query = "INSERT INTO leaderboard (province, count) VALUES ('$province', 1)";
        mysqli_query($connection, $query);
    }

    // Simpan waktu timestamp terakhir dalam session
    $_SESSION['last_request_time'] = time();
}

// Endpoint untuk menerima POST dzikir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Periksa data yang dikirim
    $province = isset($_POST['province']) ? $_POST['province'] : '';
    if (!empty($province)) {
        // Panggil fungsi untuk memproses dzikir
        postDzikir($province);
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(array("message" => "Province field is required."));
    }
}

// Endpoint untuk mengambil leaderboard
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if(isset($_GET["id"])){
        $id = $_GET["id"];
        $sql = "SELECT * FROM leaderboard WHERE id = $id";
    }
    else{
        $sql = "SELECT * FROM leaderboard ORDER BY count DESC";
    }

    $result = $connection->query($sql);

    $data = array();

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
}
