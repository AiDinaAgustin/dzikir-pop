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

const RATE_LIMIT_DURATION = 30;

function getClientIP() {
    // Mendapatkan alamat IP pengguna yang terhubung ke server
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return $ip;
}

function checkRateLimit($ip) {
    if (isset($_COOKIE['rate_limit_' . $ip])) {
        $lastRequestTime = $_COOKIE['rate_limit_' . $ip];
        $currentTime = time();

        if (($currentTime - $lastRequestTime) <= RATE_LIMIT_DURATION) {
            // Too many requests
            http_response_code(429);
            getData();
            //echo json_encode(['error' => 'Too many requests, please try again later.']);
            exit;
        }
    }

    // Update last request time
    setcookie('rate_limit_' . $ip, time(), [
        'expires' => time() + RATE_LIMIT_DURATION,
        'path' => '/',
        'secure' => true,      // for HTTPS
        'httponly' => true,    // to help prevent attacks.
        'samesite' => 'Strict', // can be 'Strict' or 'Lax'
    ]);
}

// Mendapatkan alamat IP pengguna
$ip = getClientIP();

// Melakukan rate limiting berdasarkan alamat IP
checkRateLimit($ip);
// Fungsi untuk mengirimkan dzikir dan mengupdate leaderboard
function postDzikir($province, $countValue) {
    global $connection;

    // Cek apakah data provinsi sudah ada di leaderboard
    $query = "SELECT * FROM leaderboard WHERE province = '$province'";
    $result = mysqli_query($connection, $query);

    if (mysqli_num_rows($result) > 0) {
        // Jika provinsi sudah ada, update count dan updated_at
        $query = "UPDATE leaderboard SET count = count + '$countValue', updated_at = NOW() WHERE province = '$province'";
        mysqli_query($connection, $query);
    } else {
        // Jika provinsi belum ada, tambahkan data baru ke leaderboard
        $query = "INSERT INTO leaderboard (province, count) VALUES ('$province', '$countValue')";
        mysqli_query($connection, $query);
    }
}

// Endpoint untuk menerima POST dzikir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Periksa data yang dikirim
    $province = isset($_POST['province']) ? $_POST['province'] : '';
    $countValue = isset($_POST['count']) ? (int)$_POST['count'] : 1; // Default value is 1 if count is not provided
    if (!empty($province)) {
        // Panggil fungsi untuk memproses dzikir
        postDzikir($province, $countValue);
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(array("message" => "Province field is required."));
    }
}

function getData(){
    global $connection;
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

}