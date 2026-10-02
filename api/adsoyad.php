<?php
// ARO API - Otomatik oluşturuldu (Reklam temizleyici + Alt bilgi)
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$TARGET = 'https://solidarksystems.alwaysdata.net/adsoyad.php';

// Parametreleri al
$params = $_GET;
$url = $TARGET;
if (!empty($params)) {
    $url .= '?' . http_build_query($params);
}

// User-Agent havuzu
$UAS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:133.0) Gecko/20100101 Firefox/133.0',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 18_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Mobile/15E148 Safari/604.1',
    'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    'Googlebot/2.1 (+http://www.google.com/bot.html)',
    'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Googlebot/2.1; +http://www.google.com/bot.html) Chrome/131.0.0.0 Safari/537.36'
];
$ua = $UAS[array_rand($UAS)];

// cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_USERAGENT => $ua,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json, text/plain, */*',
        'Accept-Language: tr-TR,tr;q=0.9,en;q=0.8',
        'Referer: https://apiv2.ajaxsystems.fun/',
        'Origin: https://apiv2.ajaxsystems.fun'
    ],
    CURLOPT_ENCODING => ''
]);

$body = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($body === false || $body === '') {
    $ctx = stream_context_create([
        'http' => ['method'=>'GET','header'=>"User-Agent: $ua\r\n",'timeout'=>15,'ignore_errors'=>true],
        'ssl' => ['verify_peer'=>false,'verify_peer_name'=>false]
    ]);
    $body = @file_get_contents($url, false, $ctx);
}

if ($body === false || $body === '') {
    echo json_encode(['ok'=>false,'hata'=>'Bağlantı hatası']);
    exit;
}

// ============================================================
// REKLAM TEMİZLEYİCİ
// ============================================================
// "auth": "@jessy_php", ve "auth_alt": "@jessy_php" satırlarını sil
$body = preg_replace('/"auth"\s*:\s*"@jessy_php"\s*,?\s*/i', '', $body);
$body = preg_replace('/"auth_alt"\s*:\s*"@jessy_php"\s*,?\s*/i', '', $body);

// JSON'u decode et, alt bilgi ekle, tekrar encode et
$json = json_decode($body, true);

if (is_array($json)) {
    // Alt bilgi ekle
    $json['api_script_sahibi'] = '@fbxnext';
    $json['instagram'] = '@logsuzlarpanel';
    $json['tiktok'] = '@logsuzlar.inc';

    echo json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} else {
    // JSON değilse ham veriye alt bilgi ekle
    $body = rtrim($body);
    $body .= "\n\n---\napi script sahibi @izeyder\ninstagram @logsuzlarpanel\ntiktok @logsuzlar.inc";
    echo $body;
}
