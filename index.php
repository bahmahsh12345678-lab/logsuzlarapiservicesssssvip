<?php
// ============================================================
// LOGSUZLAR VIP - TEK DOSYA API + ANA SAYFA
// ============================================================
header('Access-Control-Allow-Origin: *');

$BASE = 'https://apiv2.ajaxsystems.fun';
$SITE = 'https://apiv2.ajaxsystems.fun';
$UAS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:133.0) Gecko/20100101 Firefox/133.0',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 18_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Mobile/15E148 Safari/604.1',
];

// ============ ROUTING ============
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

// API endpointleri (eski .php dosya isimleri)
$API_ENDPOINTS = [
    'tc'           => ['path' => 'tc.php',           'name' => 'TC Sorgu'],
    'tcpro'        => ['path' => 'tcpro.php',        'name' => 'TC Pro'],
    'detaylitc'    => ['path' => 'detaylıtc.php',    'name' => 'Detaylı TC'],
    'aile'         => ['path' => 'aile.php',         'name' => 'Aile'],
    'ailepro'      => ['path' => 'ailepro.php',      'name' => 'Aile Pro'],
    'sulale'       => ['path' => 'sulale.php',       'name' => 'Sülale'],
    'soynesil'     => ['path' => 'soynesil.php',     'name' => 'Soy Nesil'],
    'kuzen'        => ['path' => 'kuzen.php',        'name' => 'Kuzen'],
    'cocuk'        => ['path' => 'cocuk.php',        'name' => 'Çocuk'],
    'es'           => ['path' => 'es.php',           'name' => 'Eş'],
    'tcgsm'        => ['path' => 'tcgsm.php',        'name' => 'TC→GSM'],
    'gsmtc'        => ['path' => 'gsmtc.php',        'name' => 'GSM→TC'],
    'gsmsulale'    => ['path' => 'gsmsulale.php',    'name' => 'GSM Sülale'],
    'sulalegsm'    => ['path' => 'sulalegsm.php',    'name' => 'Sülale GSM'],
    'adres'        => ['path' => 'adres.php',        'name' => 'Adres'],
    'detayliadres' => ['path' => 'detaylıadres.php', 'name' => 'Detaylı Adres'],
    'tapu'         => ['path' => 'tapu.php',         'name' => 'Tapu'],
    'adaparsel'    => ['path' => 'adaparsel.php',    'name' => 'Ada Parsel'],
    'sgk'          => ['path' => 'sgk.php',          'name' => 'SGK'],
    'isyeri'       => ['path' => 'isyeri.php',       'name' => 'İş Yeri'],
    'eokul'        => ['path' => 'eokul.php',        'name' => 'E-Okul'],
    'adsoyad'      => ['path' => 'adsoyad.php',      'name' => 'Ad Soyad'],
];

// Eğer ?action=api&type=tc ise JSON dön
if ($action === 'api') {
    header('Content-Type: application/json; charset=utf-8');
    $type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';
    if (!isset($API_ENDPOINTS[$type])) {
        echo json_encode(['ok' => false, 'error' => 'Unknown type']);
        exit;
    }
    $cfg = $API_ENDPOINTS[$type];
    $params = $_GET;
    unset($params['action'], $params['type']);
    $qs = !empty($params) ? ('?' . http_build_query($params)) : '';
    $url = $BASE . '/' . $cfg['path'] . $qs;
    $ua = $UAS[array_rand($UAS)];

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
            'Referer: ' . $SITE . '/',
            'Origin: ' . $SITE,
        ],
        CURLOPT_ENCODING => '',
    ]);
    $body = curl_exec($ch);
    curl_close($ch);

    if (!$body) {
        echo json_encode(['ok' => false, 'error' => 'Bağlantı hatası']);
        exit;
    }
    $body = preg_replace('/"auth"\s*:\s*"@jessy_php"\s*,?\s*/i', '', $body);
    $body = preg_replace('/"auth_alt"\s*:\s*"@jessy_php"\s*,?\s*/i', '', $body);
    $json = json_decode($body, true);
    if (is_array($json)) {
        $json['api_script_sahibi'] = '@fbxnext';
        $json['instagram'] = '@logsuzlarpanel';
        $json['tiktok'] = '@logsuzlar.inc';
        echo json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    } else {
        echo $body;
    }
    exit;
}

// ============================================================
// ANA SAYFA (HTML)
// ============================================================
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logsuzlar VIP - Sorgu Paneli</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#0a0a0f;color:#e4e4e7;min-height:100vh;
background-image:radial-gradient(circle at 20% 0%,rgba(139,92,246,.15) 0%,transparent 50%),radial-gradient(circle at 80% 100%,rgba(59,130,246,.12) 0%,transparent 50%)}
.header{position:sticky;top:0;z-index:100;background:rgba(10,10,15,.85);backdrop-filter:blur(20px);border-bottom:1px solid rgba(139,92,246,.15);padding:14px 20px}
.header-inner{max-width:1400px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.logo{display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit}
.logo-icon{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#8b5cf6,#3b82f6);display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 0 30px rgba(139,92,246,.4)}
.logo-text{font-size:18px;font-weight:800;background:linear-gradient(135deg,#a78bfa,#60a5fa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.logo-sub{font-size:11px;color:#71717a;font-weight:500;margin-top:2px}
.header-links{display:flex;gap:10px;align-items:center}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all .25s;border:1px solid transparent;white-space:nowrap}
.btn-channel{background:rgba(139,92,246,.1);border-color:rgba(139,92,246,.3);color:#a78bfa}
.btn-channel:hover{background:rgba(139,92,246,.2);transform:translateY(-2px)}
.btn-premium{background:linear-gradient(135deg,#f59e0b,#ef4444);color:#fff;font-weight:700;box-shadow:0 4px 20px rgba(245,158,11,.3)}
.btn-premium:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(245,158,11,.5)}
.hero{text-align:center;padding:60px 20px 40px}
.hero h1{font-size:clamp(28px,5vw,52px);font-weight:900;letter-spacing:-1.5px;background:linear-gradient(135deg,#fff 0%,#a78bfa 50%,#60a5fa 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:16px}
.hero p{font-size:clamp(13px,1.5vw,16px);color:#a1a1aa;max-width:600px;margin:0 auto;line-height:1.6}
.hero-badge{display:inline-flex;align-items:center;gap:8px;padding:6px 14px;background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.3);border-radius:100px;font-size:12px;color:#a78bfa;font-weight:600;margin-bottom:20px}
.hero-badge .dot{width:6px;height:6px;background:#10b981;border-radius:50%;box-shadow:0 0 10px #10b981}
.stats{max-width:1400px;margin:0 auto 50px;padding:0 20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}
.stat{background:rgba(24,24,32,.6);border:1px solid rgba(139,92,246,.15);border-radius:14px;padding:18px;text-align:center;backdrop-filter:blur(10px)}
.stat-num{font-size:26px;font-weight:800;background:linear-gradient(135deg,#a78bfa,#60a5fa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.stat-label{font-size:12px;color:#71717a;margin-top:4px;font-weight:500}
.container{max-width:1400px;margin:0 auto;padding:0 20px 60px}
.section{margin-bottom:40px}
.section-head{display:flex;align-items:center;gap:14px;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid rgba(139,92,246,.12)}
.section-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.ic-purple{background:linear-gradient(135deg,#8b5cf6,#6d28d9)}
.ic-pink{background:linear-gradient(135deg,#ec4899,#be185d)}
.ic-blue{background:linear-gradient(135deg,#3b82f6,#1d4ed8)}
.ic-green{background:linear-gradient(135deg,#10b981,#047857)}
.ic-orange{background:linear-gradient(135deg,#f59e0b,#b45309)}
.ic-red{background:linear-gradient(135deg,#ef4444,#b91c1c)}
.ic-cyan{background:linear-gradient(135deg,#06b6d4,#0e7490)}
.ic-yellow{background:linear-gradient(135deg,#eab308,#a16207)}
.ic-indigo{background:linear-gradient(135deg,#6366f1,#4338ca)}
.ic-rose{background:linear-gradient(135deg,#f43f5e,#be123c)}
.ic-teal{background:linear-gradient(135deg,#14b8a6,#0f766e)}
.ic-violet{background:linear-gradient(135deg,#a855f7,#7e22ce)}
.ic-sky{background:linear-gradient(135deg,#0ea5e9,#0369a1)}
.section-title{font-size:19px;font-weight:700;color:#f4f4f5}
.section-count{font-size:12px;color:#71717a;margin-top:2px}
.section-badge{margin-left:auto;padding:4px 12px;background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.25);border-radius:100px;font-size:11px;color:#a78bfa;font-weight:600}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
.card{background:rgba(24,24,32,.7);border:1px solid rgba(139,92,246,.15);border-radius:14px;overflow:hidden;transition:all .3s;backdrop-filter:blur(10px);position:relative}
.card:hover{border-color:rgba(139,92,246,.5);transform:translateY(-4px);box-shadow:0 15px 40px rgba(139,92,246,.2)}
.card-body{padding:16px}
.card-name{font-size:14px;font-weight:700;color:#f4f4f5;margin-bottom:4px}
.card-desc{font-size:11px;color:#71717a;line-height:1.5;margin-bottom:12px;min-height:32px}
.card-btn{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:9px 12px;background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(239,68,68,.15));border:1px solid rgba(245,158,11,.3);border-radius:9px;color:#fbbf24;font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;transition:all .25s;font-family:inherit}
.card-btn:hover{background:linear-gradient(135deg,#f59e0b,#ef4444);color:#fff;border-color:transparent;box-shadow:0 4px 20px rgba(245,158,11,.4)}
.footer{text-align:center;padding:40px 20px;border-top:1px solid rgba(139,92,246,.12);color:#52525b;font-size:12px}
.footer-links{display:flex;justify-content:center;gap:18px;margin-bottom:14px;flex-wrap:wrap}
.footer-links a{color:#a78bfa;text-decoration:none;font-weight:600}
@media (max-width:640px){.grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr))}}
</style>
</head>
<body>
<header class="header">
  <div class="header-inner">
    <a href="/" class="logo">
      <div class="logo-icon">🔍</div>
      <div><div class="logo-text">LOGSUZLAR VIP</div><div class="logo-sub">Sorgu Paneli v2.0</div></div>
    </a>
    <div class="header-links">
      <a href="https://t.me/logsuzlarvip" target="_blank" class="btn btn-channel">📢 Kanala Katıl</a>
      <a href="https://t.me/fbxnext" target="_blank" class="btn btn-premium">💎 Premium Al</a>
    </div>
  </div>
</header>

<section class="hero">
  <div class="hero-badge"><span class="dot"></span> Sistem Aktif</div>
  <h1>Profesyonel Sorgu Paneli</h1>
  <p>Tüm sorgulara tek panelden erişin. Hızlı, güvenli, güncel.</p>
</section>

<div class="stats">
  <div class="stat"><div class="stat-num">120+</div><div class="stat-label">Toplam Sorgu</div></div>
  <div class="stat"><div class="stat-num">15</div><div class="stat-label">Kategori</div></div>
  <div class="stat"><div class="stat-num">7/24</div><div class="stat-label">Kesintisiz</div></div>
  <div class="stat"><div class="stat-num">%99.9</div><div class="stat-label">Uptime</div></div>
</div>

<div class="container">
<?php
$premium = "https://t.me/fbxnext";
$cats = [
    "Instagram Çözümleri" => ["icon"=>"📸","class"=>"ic-pink","items"=>[["Instagram Çalma","Hesap çalma"],["Instagram Sızma","Hesaba sızma"],["Instagram Gizli Hesap Görme","Gizli hesap gör"]]],
    "WhatsApp Çözümleri" => ["icon"=>"💬","class"=>"ic-green","items"=>[["WhatsApp Çalma","Hesap çalma"],["WhatsApp Sızma","Hesaba sızma"],["WhatsApp DM Okuma","Mesaj okuma"]]],
    "TikTok Çözümleri" => ["icon"=>"🎵","class"=>"ic-cyan","items"=>[["TikTok Çalma","Hesap çalma"],["TikTok Sızma","Hesaba sızma"]]],
    "Snapchat Çözümleri" => ["icon"=>"👻","class"=>"ic-yellow","items"=>[["Snapchat Çalma","Hesap çalma"],["Snapchat Sızma","Hesaba sızma"]]],
    "Galeri Çözümleri" => ["icon"=>"🖼️","class"=>"ic-indigo","items"=>[["Galeri Sızma","Galeriye sızma"]]],
    "E-posta Çözümleri" => ["icon"=>"📧","class"=>"ic-sky","items"=>[["E-posta Şifre Kırma","Şifre kırma"],["E-posta Takip ve Gözetim","Takip sistemi"]]],
    "Cihaz Takip" => ["icon"=>"📍","class"=>"ic-rose","items"=>[["Cihaz Takip","Konum takibi"]]],
    "Kimlik Sorguları" => ["icon"=>"🆔","class"=>"ic-purple","items"=>[["TC","TC sorgu"],["TC Ad","TC ile ad"],["TC Pro","Detaylı TC"],["Azeri TC","Azeri TC"],["Vergi TC","Vergi TC"],["Vergi Ad","Vergi ad"],["Vergi Ad Sade","Basit vergi ad"],["Vergi No","Vergi numarası"]]],
    "Aile & Sülale" => ["icon"=>"👨‍👩‍👧","class"=>"ic-orange","items"=>[["Aile","Aile bireyleri"],["Sülale","Sülale"]]],
    "Telefon Sorguları" => ["icon"=>"📱","class"=>"ic-teal","items"=>[["TC GSM","TC→GSM"],["GSM TC","GSM→TC"],["Operator","Operatör"],["Azeri Tel","Azeri telefon"]]],
    "Eğitim Sorguları" => ["icon"=>"🎓","class"=>"ic-blue","items"=>[["Olu","Öğrenci"],["Olu Ad","Öğrenci ad"],["Ogretmen","Öğretmen"],["E-Okul","E-Okul"],["Universite","Üniversite"],["Universite Ad","Üniversite ad"]]],
    "Resmi Kayıtlar" => ["icon"=>"📋","class"=>"ic-violet","items"=>[["SGK","SGK"],["SGK Ad","SGK ad"],["Sicil","Sicil"],["Sicil Ad","Sicil ad"],["Secmen","Seçmen"],["Secmen Ad","Seçmen ad"],["Adres","Adres"],["Tapu","Tapu"],["Vesika","Vesika"],["Serino SKT","Seri no SKT"],["Meslek","Meslek"],["Ada Parsel","Ada parsel"]]],
    "Araç & Plaka" => ["icon"=>"🚗","class"=>"ic-red","items"=>[["Plaka","Plaka sorgu"],["Plaka Ad","Plaka ad"]]],
    "Dijital Platformlar" => ["icon"=>"💻","class"=>"ic-indigo","items"=>[["Discord ID","Discord ID"],["Discord Email","Discord email"]]],
    "İletişim & SMS" => ["icon"=>"✉️","class"=>"ic-sky","items"=>[["SMS","SMS servisi"],["AM","Anonim mesaj"]]],
];
foreach ($cats as $name => $c):
    $count = count($c['items']);
?>
<div class="section">
  <div class="section-head">
    <div class="section-icon <?= $c['class'] ?>"><?= $c['icon'] ?></div>
    <div><div class="section-title"><?= htmlspecialchars($name) ?></div><div class="section-count"><?= $count ?> sorgu</div></div>
    <div class="section-badge"><?= $count ?> Servis</div>
  </div>
  <div class="grid">
    <?php foreach ($c['items'] as $item): ?>
    <div class="card">
      <div class="card-body">
        <div class="card-name"><?= htmlspecialchars($item[0]) ?></div>
        <div class="card-desc"><?= htmlspecialchars($item[1]) ?></div>
        <a href="<?= $premium ?>" target="_blank" class="card-btn">💎 Premium Al</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
</div>

<footer class="footer">
  <div class="footer-links">
    <a href="https://t.me/logsuzlarvip" target="_blank">📢 @logsuzlarvip</a>
    <a href="https://t.me/fbxnext" target="_blank">💎 @fbxnext</a>
  </div>
  <div>© <?= date('Y') ?> Logsuzlar VIP</div>
</footer>
</body>
</html>
