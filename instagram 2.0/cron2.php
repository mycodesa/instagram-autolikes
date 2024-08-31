<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Api.php'; // SMM API sınıfını dahil et

$api_key = '49dc3507e7f165416d32cfe68194391e'; // Instagram API anahtarınız

// Veritabanı bağlantısı
$servername = "your_server_name";
$username = "your_database_username";
$password = "your_database_password";
$dbname = "your_database_name";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

// Log dosyasını aç
$logfile = fopen('logdetail.txt', 'a');
if (!$logfile) {
    die('Log dosyası açılamadı.');
}

// Log dosyasına tarih yaz
fwrite($logfile, "Cron Çalıştırma Tarihi: " . date('Y-m-d H:i:s') . "\n\n");

// send_reels_only = 1 olan tüm kullanıcıları al
$sql_users = "SELECT id, username, instagram_user_id, added_at, send_reels_only FROM users WHERE send_reels_only = 1";
$result_users = $conn->query($sql_users);

if ($result_users === FALSE) {
    fwrite($logfile, "Sorgu hatası: " . $conn->error . "\n");
    fclose($logfile);
    $conn->close();
    exit;
}

while ($row = $result_users->fetch_assoc()) {
    $user_id = $row['id'];
    $username = $row['username'];
    $instagram_user_id = trim($row['instagram_user_id']); // Boşlukları temizle
    $added_at = $row['added_at'];
    $send_reels_only = $row['send_reels_only'];

    // Kullanıcı kimliini kontrol et
    if (empty($instagram_user_id) || !ctype_digit($instagram_user_id)) {
        fwrite($logfile, "Geçersiz kullanıcı ID: $username (Instagram ID: $instagram_user_id)\n");
        continue; // Bir sonraki kullanıcıya geç
    }

    // cron_log tablosuna kullanıcı eklenmiş mi kontrol et
    $sql_check = "SELECT last_checked FROM cron_log WHERE username='$username' AND send_reels_only=1";
    $result_check = $conn->query($sql_check);

    if ($result_check->num_rows == 0) {
        // cron_log tablosuna yeni kullanıcı ekle
        $current_time = (new DateTime('now', new DateTimeZone('Europe/Istanbul')))->format('Y-m-d H:i:s');
        $sql_insert_log = "INSERT INTO cron_log (username, instagram_user_id, send_reels_only, last_checked) VALUES ('$username', '$instagram_user_id', 1, '$current_time')";
        if ($conn->query($sql_insert_log) === FALSE) {
            fwrite($logfile, "cron_log tablosuna ekleme hatası: " . $conn->error . "\n");
            continue;
        }
        $last_checked = $added_at;
    } else {
        $row_check = $result_check->fetch_assoc();
        $last_checked = $row_check['last_checked'];
    }

    // Kullanıcı gnderilerini almak için API çağrsı
    $posts_api_url = "https://api.instagapi.com/userreels/{$instagram_user_id}/10/";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $posts_api_url,
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
        fwrite($logfile, "cURL Hatası #: " . $err . "\n");
        continue; // Bir sonraki kullanıcıya geç
    }

    $posts_data = json_decode($response, true);

    // API yantı hatalı mı kontrol et
    if (!isset($posts_data['data']) || $posts_data['status'] !== 'ok') {
        fwrite($logfile, "API Hatası: Kullanıcı: $username (Instagram ID: $instagram_user_id)\nAPI Yanıtı: " . print_r($posts_data, true) . "\n\n");
        continue; // Bir sonraki kullancıya geç
    }

    fwrite($logfile, "Kullancı: $username (Instagram ID: $instagram_user_id)\nAPI Yanıtı: " . print_r($posts_data, true) . "\n\n");

    if (empty($posts_data['data']['items'])) {
        fwrite($logfile, "Gönderiler bulunamadı: $username (Instagram ID: $instagram_user_id)\n\n");
        continue; // Bir sonraki kullanıcıya geç
    }

    // Son 10 gönderiyi al
    fwrite($logfile, "Kullanıc: $username\nSon 10 Gönderi:\n");
    foreach ($posts_data['data']['items'] as $index => $post) {
        if ($index >= 10) break;
        $post_date = new DateTime('@' . $post['media']['taken_at']);
        $post_date->setTimezone(new DateTimeZone('Europe/Istanbul'));
        $formatted_date = $post_date->format('Y-m-d H:i:s');
        fwrite($logfile, "$formatted_date - https://www.instagram.com/p/" . $post['media']['code'] . "/\n");
    }

    // Gönderileri veritabanına ekle ve SMM API'ye gönder
    foreach ($posts_data['data']['items'] as $post) {
        $post_date = new DateTime('@' . $post['media']['taken_at']);
        $post_date->setTimezone(new DateTimeZone('Europe/Istanbul'));
        $formatted_date = $post_date->format('Y-m-d H:i:s');

        if ($formatted_date > $last_checked) {
            $post_link = "https://www.instagram.com/p/" . $post['media']['code'] . "/";
            $added_at = (new DateTime('now', new DateTimeZone('Europe/Istanbul')))->format('Y-m-d H:i:s');
            $sql_insert_post = "INSERT INTO posts (username, post_link, post_date, added_at) VALUES ('$username', '$post_link', '$formatted_date', '$added_at')";
            if ($conn->query($sql_insert_post) === TRUE) {
                fwrite($logfile, "Yeni gnderi eklendi: $post_link\n");

                // Kullanıcya ait tüm SMM API bilgilerini al
                $sql_user_smm_apis = "SELECT smm_api_settings.api_url, smm_api_settings.api_key, smm_api_settings.service_id, user_smm_apis.quantity_min, user_smm_apis.quantity_max 
                                      FROM user_smm_apis 
                                      JOIN smm_api_settings ON user_smm_apis.smm_api_id = smm_api_settings.id 
                                      WHERE user_smm_apis.user_id = $user_id";
                $result_user_smm_apis = $conn->query($sql_user_smm_apis);
                if ($result_user_smm_apis->num_rows > 0) {
                    while ($smm_api = $result_user_smm_apis->fetch_assoc()) {
                        $smm_api_url = $smm_api['api_url'];
                        $smm_api_key = $smm_api['api_key'];
                        $service_id = $smm_api['service_id'];
                        $quantity_min = $smm_api['quantity_min'];
                        $quantity_max = $smm_api['quantity_max'];

                        // SMM API'ye gönder
                        $api = new Api($smm_api_key, $smm_api_url);
                        $quantity = rand($quantity_min, $quantity_max);
                        $response = $api->order([
                            'service' => $service_id,
                            'link' => $post_link,
                            'quantity' => $quantity
                        ]);

                        // Yantı kontrol et ve logla
                        if (isset($response->order)) {
                            fwrite($logfile, "SMM API'ye gönderildi: $post_link\n");
                            fwrite($logfile, "SMM API Bilgileri: Service: $service_id, API Key: $smm_api_key, API URL: $smm_api_url, Quantity: $quantity\n");
                        } else {
                            $error_message = isset($response->error) ? $response->error : 'Bilinmeyen hata';
                            fwrite($logfile, "SMM API hatası: " . $error_message . "\n");
                        }
                    }
                }
            } else {
                fwrite($logfile, "Gönderi ekleme hatası: " . $conn->error . "\n");
            }
        }
    }

    // Son kontrol tarihini güncelle
    $current_time = (new DateTime('now', new DateTimeZone('Europe/Istanbul')))->format('Y-m-d H:i:s');
    $sql_update = "UPDATE cron_log SET last_checked='$current_time' WHERE username='$username' AND send_reels_only=1";
    if ($conn->query($sql_update) === TRUE) {
        fwrite($logfile, "Son kontrol tarihi güncellendi: $username\n\n");
    } else {
        fwrite($logfile, "Son kontrol tarihi güncelleme hatası: " . $conn->error . "\n");
    }
}

fclose($logfile);
$conn->close();
?>
