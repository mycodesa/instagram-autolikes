<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$servername = "localhost"; // Update this with your actual server name if needed
$db_username = "your_db_username"; // Replace with your actual database username
$db_password = "your_db_password"; // Replace with your actual database password
$dbname = "your_db_name"; // Replace with your actual database name

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Hata mesajı için değişken
$error_message = "";

// Yeni SMM API bilgisi ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_smm_api'])) {
    $name = $_POST['name'];
    $api_url = $_POST['api_url'];
    $api_key = $_POST['api_key'];
    $service_id = $_POST['service_id'];

    $stmt = $conn->prepare("INSERT INTO smm_api_settings (name, api_url, api_key, service_id) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        $error_message = "MySQL error: " . $conn->error;
    } else {
        $stmt->bind_param("ssss", $name, $api_url, $api_key, $service_id);
        if (!$stmt->execute()) {
            $error_message = "MySQL error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Kullanıcı ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $api_key = 'YOUR instagapi KEY ';
    $user_api_url = "https://api.instagapi.com/userid/{$username}";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $user_api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "X-InstagAPI-Key: $api_key"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        $error_message = "cURL Error #: " . $err;
    } else {
        $user_data = json_decode($response, true);
        if ($user_data['status'] === 'success') {
            $instagram_user_id = $user_data['data'];
            $added_at = (new DateTime('now', new DateTimeZone('Europe/Istanbul')))->format('Y-m-d H:i:s');

            $send_reels_only = isset($_POST['send_reels_only']) ? 1 : 0;

            // Kullanıcıyı ekle
            $stmt = $conn->prepare("INSERT INTO users (username, instagram_user_id, added_at, send_reels_only) VALUES (?, ?, ?, ?)");
            if ($stmt === false) {
                $error_message = "MySQL error (prepare): " . $conn->error;
            } else {
                $stmt->bind_param("sssi", $username, $instagram_user_id, $added_at, $send_reels_only);
                if (!$stmt->execute()) {
                    $error_message = "MySQL error (execute): " . $stmt->error;
                } else {
                    $user_id = $stmt->insert_id;
                    $stmt->close();

                    // cron_log tablosuna ekle
                    $stmt = $conn->prepare("INSERT INTO cron_log (username, instagram_user_id, send_reels_only, last_checked) VALUES (?, ?, ?, ?)");
                    if ($stmt === false) {
                        $error_message = "MySQL error (prepare cron_log): " . $conn->error;
                    } else {
                        $stmt->bind_param("ssis", $username, $instagram_user_id, $send_reels_only, $added_at);
                        if (!$stmt->execute()) {
                            $error_message = "MySQL error (execute cron_log): " . $stmt->error;
                        }
                        $stmt->close();
                    }

                    // Seçilen SMM API bilgilerini user_smm_apis tablosuna ekle
                    foreach ($_POST['smm_api_id'] as $smm_api_id => $api_details) {
                        if (isset($api_details['selected'])) {
                            $quantity_min = isset($api_details['quantity_min']) ? intval($api_details['quantity_min']) : null;
                            $quantity_max = isset($api_details['quantity_max']) ? intval($api_details['quantity_max']) : null;

                            if ($quantity_min !== null && $quantity_max !== null) {
                                $stmt = $conn->prepare("INSERT INTO user_smm_apis (user_id, smm_api_id, quantity_min, quantity_max) VALUES (?, ?, ?, ?)");
                                if ($stmt === false) {
                                    $error_message = "MySQL error (prepare user_smm_apis): " . $conn->error;
                                    break;
                                }
                                $stmt->bind_param("iiii", $user_id, $smm_api_id, $quantity_min, $quantity_max);
                                if (!$stmt->execute()) {
                                    $error_message = "MySQL error (execute user_smm_apis): " . $stmt->error;
                                    break;
                                }
                                $stmt->close();
                            }
                        }
                    }

                    // Form gönderildikten sonra yönlendirme yap
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                }
            }
        } else {
            $error_message = "Kullanıcı bulunamadı.";
        }
    }
}

// Kullanıcı ve SMM API verilerini çek
$users_smm_apis = [];
$result_users = $conn->query("SELECT * FROM users");
if ($result_users === false) {
    die("MySQL error: " . $conn->error);
}
while ($user = $result_users->fetch_assoc()) {
    $user_id = $user['id'];
    $only_reels_apis = [];
    $non_reels_apis = [];

    // Sadece Reels için olanları al
    $result_smm_apis_reels = $conn->query("SELECT usa.*, sa.name 
                                            FROM user_smm_apis usa 
                                            JOIN smm_api_settings sa ON usa.smm_api_id = sa.id 
                                            WHERE usa.user_id = $user_id AND (SELECT u.send_reels_only FROM users u WHERE u.id = $user_id) = 1");
    if ($result_smm_apis_reels === false) {
        die("MySQL error: " . $conn->error);
    }
    while ($smm_api = $result_smm_apis_reels->fetch_assoc()) {
        $only_reels_apis[] = $smm_api;
    }

    // Diğer SMM API'leri al
    $result_smm_apis_non_reels = $conn->query("SELECT usa.*, sa.name 
                                                FROM user_smm_apis usa 
                                                JOIN smm_api_settings sa ON usa.smm_api_id = sa.id 
                                                WHERE usa.user_id = $user_id AND (SELECT u.send_reels_only FROM users u WHERE u.id = $user_id) = 0");
    if ($result_smm_apis_non_reels === false) {
        die("MySQL error: " . $conn->error);
    }
    while ($smm_api = $result_smm_apis_non_reels->fetch_assoc()) {
        $non_reels_apis[] = $smm_api;
    }

    $users_smm_apis[] = ['user' => $user, 'only_reels_apis' => $only_reels_apis, 'non_reels_apis' => $non_reels_apis];
}

$result_smm_api = $conn->query("SELECT * FROM smm_api_settings");
if ($result_smm_api === false) {
    die("MySQL error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Instagram Kullanıcı Adı Ekle</title>
    <link rel="stylesheet" href="/styles.css">
    <style>
        .accordion {
            cursor: pointer;
            padding: 10px;
            text-align: left;
            border: none;
            outline: none;
            transition: 0.4s;
            background-color: #d56057;
            margin-bottom: 5px;
        }

        .accordion.active, .accordion:hover {
            background-color: #ccc;
        }

        .panel {
            padding: 0 18px;
            display: none;
            background-color: white;
            overflow: hidden;
        }

        .panel input[type="number"] {
            width: 60px;
        }

        .container {
            width: 80%;
            margin: auto;
        }

        h2, h3, h4 {
            margin-top: 20px;
            color: #333;
        }

        form {
            margin-bottom: 20px;
        }

        form input[type="text"],
        form input[type="number"],
        form select {
            padding: 5px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 3px;
            width: 100%;
        }

        form button {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        form button:hover {
            background-color: #218838;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        ul li {
            background-color: #f8f9fa;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        ul li form {
            display: inline-block;
        }

        ul li form button {
            margin-left: 10px;
            background-color: #dc3545;
        }

        ul li form button:hover {
            background-color: #c82333;
        }

        .alert {
            padding: 15px;
            background-color: #4CAF50;
            color: white;
            margin-bottom: 20px;
            border-radius: 5px;
            display: none;
        }

        .alert.error {
            background-color: #f44336;
        }

    </style>
</head>
<body>
    <div class="container">
        <h1>Instagram Kullanıcı Adı Ekle</h1>
        <?php if ($error_message): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="" method="post">
            <input type="text" name="username" required placeholder="Instagram Kullanıcı Adı">
            <h2>SMM API Seçin</h2>
            <?php while ($row = $result_smm_api->fetch_assoc()): ?>
                <div>
                    <label>
                        <input type="checkbox" name="smm_api_id[<?php echo $row['id']; ?>][selected]">
                        <?php echo $row['name']; ?>
                    </label>
                    <input type="number" name="smm_api_id[<?php echo $row['id']; ?>][quantity_min]" placeholder="Min Quantity" step="1" min="0">
                    <input type="number" name="smm_api_id[<?php echo $row['id']; ?>][quantity_max]" placeholder="Max Quantity" step="1" min="0">
                </div>
            <?php endwhile; ?>
            <label>
                <input type="checkbox" name="send_reels_only"> Sadece Reels Videolarını Gönder
            </label>
            <button type="submit" name="add_user">Ekle</button>
        </form>

        <h2>Kullanıcılar</h2>
        <?php foreach ($users_smm_apis as $user_data): ?>
            <h3><?php echo $user_data['user']['username']; ?> - <?php echo $user_data['user']['instagram_user_id']; ?> - <?php echo $user_data['user']['added_at']; ?></h3>

            <button class="accordion">Sadece Reels Videoları İçin SMM API Servisleri</button>
            <div class="panel">
                <?php if (!empty($user_data['only_reels_apis'])): ?>
                    <ul>
                        <?php foreach ($user_data['only_reels_apis'] as $smm_api): ?>
                            <li>
                                <form action="" method="post" onsubmit="return confirm('Bu servisi güncellemek istediğinize emin misiniz?');">
                                    <label><?php echo $smm_api['name']; ?></label>
                                    <input type="number" name="quantity_min" value="<?php echo $smm_api['quantity_min']; ?>" step="1" min="0">
                                    <input type="number" name="quantity_max" value="<?php echo $smm_api['quantity_max']; ?>" step="1" min="0">
                                    <input type="hidden" name="update_smm_api" value="<?php echo $smm_api['id']; ?>">
                                    <button type="submit">Güncelle</button>
                                    <button type="submit" name="delete_smm_api" value="<?php echo $smm_api['id']; ?>" onclick="return confirm('Bu servisi silmek istediğinize emin misiniz?');">Sil</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Bu kullanıcı için sadece Reels videoları SMM API servisi bulunmamaktadır.</p>
                <?php endif; ?>
                <form action="" method="post" onsubmit="return confirm('Yeni servisi eklemek istediğinize emin misiniz?');">
                    <h4>Yeni SMM API Servisi Ekle</h4>
                    <select name="new_smm_api_id">
                        <?php foreach ($result_smm_api as $api): ?>
                            <option value="<?php echo $api['id']; ?>"><?php echo $api['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="new_quantity_min" placeholder="Min Quantity" step="1" min="0">
                    <input type="number" name="new_quantity_max" placeholder="Max Quantity" step="1" min="0">
                    <input type="hidden" name="user_id" value="<?php echo $user_data['user']['id']; ?>">
                    <button type="submit" name="add_user_smm_api">Ekle</button>
                </form>
            </div>

            <button class="accordion">Diğer SMM API Servisleri</button>
            <div class="panel">
                <?php if (!empty($user_data['non_reels_apis'])): ?>
                    <ul>
                        <?php foreach ($user_data['non_reels_apis'] as $smm_api): ?>
                            <li>
                                <form action="" method="post" onsubmit="return confirm('Bu servisi güncellemek istediğinize emin misiniz?');">
                                    <label><?php echo $smm_api['name']; ?></label>
                                    <input type="number" name="quantity_min" value="<?php echo $smm_api['quantity_min']; ?>" step="1" min="0">
                                    <input type="number" name="quantity_max" value="<?php echo $smm_api['quantity_max']; ?>" step="1" min="0">
                                    <input type="hidden" name="update_smm_api" value="<?php echo $smm_api['id']; ?>">
                                    <button type="submit">Güncelle</button>
                                    <button type="submit" name="delete_smm_api" value="<?php echo $smm_api['id']; ?>" onclick="return confirm('Bu servisi silmek istediğinize emin misiniz?');">Sil</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Bu kullanıcı için diğer SMM API servisi bulunmamaktadır.</p>
                <?php endif; ?>
                <form action="" method="post" onsubmit="return confirm('Yeni servisi eklemek istediğinize emin misiniz?');">
                    <h4>Yeni SMM API Servisi Ekle</h4>
                    <select name="new_smm_api_id">
                        <?php foreach ($result_smm_api as $api): ?>
                            <option value="<?php echo $api['id']; ?>"><?php echo $api['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="new_quantity_min" placeholder="Min Quantity" step="1" min="0">
                    <input type="number" name="new_quantity_max" placeholder="Max Quantity" step="1" min="0">
                    <input type="hidden" name="user_id" value="<?php echo $user_data['user']['id']; ?>">
                    <button type="submit" name="add_user_smm_api">Ekle</button>
                </form>
            </div>
        <?php endforeach; ?>

        <h2>Yeni SMM API Bilgisi Ekle</h2>
        <form action="" method="post" onsubmit="return confirm('Yeni SMM API servisini eklemek istediğinize emin misiniz?');">
            <input type="text" name="name" required placeholder="API Adı">
            <input type="text" name="api_url" required placeholder="API URL">
            <input type="text" name="api_key" required placeholder="API Key">
            <input type="text" name="service_id" required placeholder="Service ID">
            <button type="submit" name="add_smm_api">Ekle</button>
        </form>

        <h2>Mevcut SMM API Bilgileri</h2>
        <ul>
            <?php 
            $result_smm_api = $conn->query("SELECT * FROM smm_api_settings");
            while ($row = $result_smm_api->fetch_assoc()): ?>
                <li><?php echo $row['name']; ?> - <?php echo $row['api_url']; ?> - <?php echo $row['api_key']; ?> - <?php echo $row['service_id']; ?></li>
            <?php endwhile; ?>
        </ul>
    </div>

    <script>
        var acc = document.getElementsByClassName("accordion");
        for (var i = 0; i < acc.length; i++) {
            acc[i].addEventListener("click", function() {
                this.classList.toggle("active");
                var panel = this.nextElementSibling;
                if (panel.style.display === "block") {
                    panel.style.display = "none";
                } else {
                    panel.style.display = "block";
                }
            });
        }

        // Başarı veya hata mesajı varsa, ekranda göster
        var successAlert = document.getElementById("success-alert");
        var errorAlert = document.getElementById("error-alert");
        if (successAlert) {
            successAlert.style.display = "block";
            setTimeout(function() { successAlert.style.display = "none"; }, 5000);
        }
        if (errorAlert) {
            errorAlert.style.display = "block";
            setTimeout(function() { errorAlert.style.display = "none"; }, 5000);
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>
