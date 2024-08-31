<?php
session_start();

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Veritabanı bağlantısı
    $servername = "localhost";
    $db_username = "afcp_fehmi";
    $db_password = "FEHmi.3862";
    $dbname = "afcp_fehmi";

    $conn = new mysqli($servername, $db_username, $db_password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT password FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();
    $conn->close();

    if ($hashed_password && password_verify($password, $hashed_password)) {
        $_SESSION['loggedin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Geçersiz kullanıcı adı veya şifre';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
    <div class="container">
        <h1>Giriş Yap</h1>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <form action="" method="post">
            <input type="text" name="username" required placeholder="Kullanıcı Adı">
            <input type="password" name="password" required placeholder="Şifre">
            <button type="submit" name="login">Giriş Yap</button>
        </form>
    </div>
</body>
</html>
