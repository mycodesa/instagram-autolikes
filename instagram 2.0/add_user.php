<?php
$servername = "your_server_name";
$username = "your_database_username";
$password = "your_database_password";
$dbname = "your_database_name";


// Veritabanı bağlantısı
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

// Kullanıcı ekleme (bir kereliğine çalıştırın)
$hashed_password = password_hash('38621989', PASSWORD_BCRYPT);
$sql = "INSERT INTO admin_users (username, password) VALUES ('admin', '$hashed_password')";
if ($conn->query($sql) === TRUE) {
    echo "Yeni kullanıcı başarıyla eklendi.";
} else {
    echo "Hata: " . $sql . "<br>" . $conn->error;
}
$conn->close();
?>
