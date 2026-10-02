<?php
// ============================================================
// INDEX.PHP - FOX INT SORGU PANELI
// ============================================================
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logsuzlar VIP - Sorgu Paneli</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: #0a0a0f;
    color: #e4e4e7;
    min-height: 100vh;
    overflow-x: hidden;
    background-image:
        radial-gradient(circle at 20% 0%, rgba(139, 92, 246, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 100%, rgba(59, 130, 246, 0.12) 0%, transparent 50%),
        radial-gradient(circle at 50% 50%, rgba(168, 85, 247, 0.05) 0%, transparent 70%);
    background-attachment: fixed;
}
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(139, 92, 246, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(139, 92, 246, 0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
    z-index: 0;
}

/* HEADER */
.header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(10, 10, 15, 0.85);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(139, 92, 246, 0.15);
    padding: 14px 20px;
}
.header-inner {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}
.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: inherit;
}
.logo-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, #8b5cf6, #3b82f6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 0 30px rgba(139, 92, 246, 0.4);
    animation: pulse 3s ease-in-out infinite;
}
@keyframes pulse {
    0%, 100% { box-shadow: 0 0 30px rgba(139, 92, 246, 0.4); }
    50% { box-shadow: 0 0 40px rgba(139, 92, 246, 0.6); }
}
.logo-text {
    font-size: 18px;
    font-weight: 800;
    background: linear-gradient(135deg, #a78bfa, #60a5fa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.5px;
}
.logo-sub {
    font-size: 11px;
    color: #71717a;
    font-weight: 500;
    margin-top: 2px;
}
.header-links {
    display: flex;
    gap: 10px;
    align-items: center;
}
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid transparent;
    cursor: pointer;
    white-space: nowrap;
}
.btn-channel {
    background: rgba(139, 92, 246, 0.1);
    border-color: rgba(139, 92, 246, 0.3);
    color: #a78bfa;
}
.btn-channel:hover {
    background: rgba(139, 92, 246, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(139, 92, 246, 0.3);
}
.btn-premium {
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    color: #fff;
    font-weight: 700;
    box-shadow: 0 4px 20px rgba(245, 158, 11, 0.3);
}
.btn-premium:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(245, 158, 11, 0.5);
}

/* HERO */
.hero {
    text-align: center;
    padding: 60px 20px 40px;
    position: relative;
    z-index: 1;
}
.hero h1 {
    font-size: clamp(28px, 5vw, 52px);
    font-weight: 900;
    line-height: 1.1;
    letter-spacing: -1.5px;
    background: linear-gradient(135deg, #ffffff 0%, #a78bfa 50%, #60a5fa 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 16px;
}
.hero p {
    font-size: clamp(13px, 1.5vw, 16px);
    color: #a1a1aa;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}
.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: rgba(139, 92, 246, 0.1);
    border: 1px solid rgba(139, 92, 246, 0.3);
    border-radius: 100px;
    font-size: 12px;
    color: #a78bfa;
    font-weight: 600;
    margin-bottom: 20px;
}
.hero-badge .dot {
    width: 6px;
    height: 6px;
    background: #10b981;
    border-radius: 50%;
    animation: blink 2s infinite;
    box-shadow: 0 0 10px #10b981;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* STATS */
.stats {
    max-width: 1400px;
    margin: 0 auto 50px;
    padding: 0 20px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    position: relative;
    z-index: 1;
}
.stat {
    background: rgba(24, 24, 32, 0.6);
    border: 1px solid rgba(139, 92, 246, 0.15);
    border-radius: 14px;
    padding: 18px;
    text-align: center;
    backdrop-filter: blur(10px);
    transition: all 0.3s;
}
.stat:hover {
    border-color: rgba(139, 92, 246, 0.4);
    transform: translateY(-3px);
}
.stat-num {
    font-size: 26px;
    font-weight: 800;
    background: linear-gradient(135deg, #a78bfa, #60a5fa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.stat-label {
    font-size: 12px;
    color: #71717a;
    margin-top: 4px;
    font-weight: 500;
}

/* CONTAINER */
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px 60px;
    position: relative;
    z-index: 1;
}

/* SECTION */
.section {
    margin-bottom: 40px;
    animation: fadeUp 0.6s ease both;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.section-head {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(139, 92, 246, 0.12);
}
.section-icon {
    width: 40px;
    height: 40px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
.ic-purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
.ic-pink { background: linear-gradient(135deg, #ec4899, #be185d); }
.ic-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.ic-green { background: linear-gradient(135deg, #10b981, #047857); }
.ic-orange { background: linear-gradient(135deg, #f59e0b, #b45309); }
.ic-red { background: linear-gradient(135deg, #ef4444, #b91c1c); }
.ic-cyan { background: linear-gradient(135deg, #06b6d4, #0e7490); }
.ic-yellow { background: linear-gradient(135deg, #eab308, #a16207); }
.ic-indigo { background: linear-gradient(135deg, #6366f1, #4338ca); }
.ic-rose { background: linear-gradient(135deg, #f43f5e, #be123c); }
.ic-teal { background: linear-gradient(135deg, #14b8a6, #0f766e); }
.ic-violet { background: linear-gradient(135deg, #a855f7, #7e22ce); }
.ic-sky { background: linear-gradient(135deg, #0ea5e9, #0369a1); }

.section-title {
    font-size: 19px;
    font-weight: 700;
    color: #f4f4f5;
    letter-spacing: -0.3px;
}
.section-count {
    font-size: 12px;
    color: #71717a;
    font-weight: 500;
    margin-top: 2px;
}
.section-badge {
    margin-left: auto;
    padding: 4px 12px;
    background: rgba(139, 92, 246, 0.1);
    border: 1px solid rgba(139, 92, 246, 0.25);
    border-radius: 100px;
    font-size: 11px;
    color: #a78bfa;
    font-weight: 600;
}

/* GRID */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 14px;
}

/* KART */
.card {
    background: rgba(24, 24, 32, 0.7);
    border: 1px solid rgba(139, 92, 246, 0.15);
    border-radius: 14px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
    position: relative;
}
.card:hover {
    border-color: rgba(139, 92, 246, 0.5);
    transform: translateY(-4px);
    box-shadow: 0 15px 40px rgba(139, 92, 246, 0.2);
}
.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, #8b5cf6, transparent);
    opacity: 0;
    transition: opacity 0.3s;
}
.card:hover::before { opacity: 1; }
.card-body {
    padding: 16px;
}
.card-name {
    font-size: 14px;
    font-weight: 700;
    color: #f4f4f5;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.card-desc {
    font-size: 11px;
    color: #71717a;
    line-height: 1.5;
    margin-bottom: 12px;
    min-height: 32px;
}
.card-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    padding: 9px 12px;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(239, 68, 68, 0.15));
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-radius: 9px;
    color: #fbbf24;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.25s;
    font-family: inherit;
}
.card-btn:hover {
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 4px 20px rgba(245, 158, 11, 0.4);
    transform: scale(1.02);
}

/* FOOTER */
.footer {
    text-align: center;
    padding: 40px 20px;
    border-top: 1px solid rgba(139, 92, 246, 0.12);
    color: #52525b;
    font-size: 12px;
    position: relative;
    z-index: 1;
}
.footer-links {
    display: flex;
    justify-content: center;
    gap: 18px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}
.footer-links a {
    color: #a78bfa;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}
.footer-links a:hover { color: #c4b5fd; }

/* MOBILE */
@media (max-width: 640px) {
    .header-inner { flex-wrap: wrap; }
    .header-links { width: 100%; justify-content: center; }
    .logo-text { font-size: 16px; }
    .grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
    .card-name { font-size: 13px; }
    .hero { padding: 40px 16px 30px; }
}
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="header-inner">
        <a href="/" class="logo">
            <div class="logo-icon">🔍</div>
            <div>
                <div class="logo-text">LOGSuzLAR VIP</div>
                <div class="logo-sub">Sorgu Paneli v2.0</div>
            </div>
        </a>
        <div class="header-links">
            <a href="https://t.me/logsuzlarvip" target="_blank" class="btn btn-channel">
                <span>📢</span> Kanala Katıl
            </a>
            <a href="https://t.me/fbxnext" target="_blank" class="btn btn-premium">
                <span>💎</span> Premium Al
            </a>
        </div>
    </div>
</header>

<!-- HERO -->
<section class="hero">
    <div class="hero-badge">
        <span class="dot"></span> Sistem Aktif
    </div>
    <h1>Profesyonel Sorgu Paneli</h1>
    <p>Tüm sorgulara tek panelden erişin. Hızlı, güvenli, güncel.</p>
</section>

<!-- STATS -->
<div class="stats">
    <div class="stat">
        <div class="stat-num">120+</div>
        <div class="stat-label">Toplam Sorgu</div>
    </div>
    <div class="stat">
        <div class="stat-num">12</div>
        <div class="stat-label">Kategori</div>
    </div>
    <div class="stat">
        <div class="stat-num">7/24</div>
        <div class="stat-label">Kesintisiz</div>
    </div>
    <div class="stat">
        <div class="stat-num">%99.9</div>
        <div class="stat-label">Uptime</div>
    </div>
</div>

<!-- CONTAINER -->
<div class="container">

<?php
// ============================================================
// KATEGORİLER VE SORGULAR
// ============================================================
$premium_url = "https://t.me/fbxnext";

$categories = [
    "Instagram Çözümleri" => [
        "icon" => "📸",
        "icon_class" => "ic-pink",
        "items" => [
            ["Instagram Çalma", "Instagram hesap çalma servisi"],
            ["Instagram Sızma", "Instagram hesabına sızma"],
            ["Instagram Gizli Hesap Görme", "Gizli hesapları görüntüleme"],
        ],
    ],
    "WhatsApp Çözümleri" => [
        "icon" => "💬",
        "icon_class" => "ic-green",
        "items" => [
            ["WhatsApp Çalma", "WhatsApp hesap çalma"],
            ["WhatsApp Sızma", "WhatsApp hesabına sızma"],
            ["WhatsApp DM Okuma", "WhatsApp mesajlarını okuma"],
        ],
    ],
    "TikTok Çözümleri" => [
        "icon" => "🎵",
        "icon_class" => "ic-cyan",
        "items" => [
            ["TikTok Çalma", "TikTok hesap çalma"],
            ["TikTok Sızma", "TikTok hesabına sızma"],
        ],
    ],
    "Snapchat Çözümleri" => [
        "icon" => "👻",
        "icon_class" => "ic-yellow",
        "items" => [
            ["Snapchat Çalma", "Snapchat hesap çalma"],
            ["Snapchat Sızma", "Snapchat hesabına sızma"],
        ],
    ],
    "Galeri Çözümleri" => [
        "icon" => "🖼️",
        "icon_class" => "ic-indigo",
        "items" => [
            ["Galeri Sızma", "Telefon galerisine sızma"],
        ],
    ],
    "E-posta Çözümleri" => [
        "icon" => "📧",
        "icon_class" => "ic-sky",
        "items" => [
            ["E-posta Şifre Kırma", "E-posta şifre kırma servisi"],
            ["E-posta Takip ve Gözetim", "E-posta takip sistemi"],
        ],
    ],
    "Cihaz Takip" => [
        "icon" => "📍",
        "icon_class" => "ic-rose",
        "items" => [
            ["Cihaz Takip", "Cihaz konum takibi"],
        ],
    ],
    "Kimlik Sorguları" => [
        "icon" => "🆔",
        "icon_class" => "ic-purple",
        "items" => [
            ["TC", "TC kimlik sorgu"],
            ["TC Ad", "TC ile ad soyad sorgu"],
            ["TC Pro", "Detaylı TC sorgu"],
            ["Azeri TC", "Azerbaycan TC sorgu"],
            ["Vergi TC", "Vergi TC sorgu"],
            ["Vergi Ad", "Vergi ad soyad sorgu"],
            ["Vergi Ad Sade", "Basit vergi ad sorgu"],
            ["Vergi No", "Vergi numarası sorgu"],
        ],
    ],
    "Aile & Sülale" => [
        "icon" => "👨‍👩‍👧",
        "icon_class" => "ic-orange",
        "items" => [
            ["Aile", "Aile bireyleri sorgu"],
            ["Sülale", "Sülale sorgu"],
        ],
    ],
    "Telefon Sorguları" => [
        "icon" => "📱",
        "icon_class" => "ic-teal",
        "items" => [
            ["TC GSM", "TC'den GSM sorgu"],
            ["GSM TC", "GSM'den TC sorgu"],
            ["Operator", "Operatör sorgu"],
            ["Azeri Tel", "Azerbaycan telefon sorgu"],
        ],
    ],
    "Eğitim Sorguları" => [
        "icon" => "🎓",
        "icon_class" => "ic-blue",
        "items" => [
            ["Olu", "Öğrenci sorgu"],
            ["Olu Ad", "Öğrenci ad sorgu"],
            ["Ogretmen", "Öğretmen sorgu"],
            ["E-Okul", "E-Okul sorgu"],
            ["Universite", "Üniversite sorgu"],
            ["Universite Ad", "Üniversite ad sorgu"],
        ],
    ],
    "Resmi Kayıtlar" => [
        "icon" => "📋",
        "icon_class" => "ic-violet",
        "items" => [
            ["SGK", "SGK sorgu"],
            ["SGK Ad", "SGK ad sorgu"],
            ["Sicil", "Sicil sorgu"],
            ["Sicil Ad", "Sicil ad sorgu"],
            ["Secmen", "Seçmen sorgu"],
            ["Secmen Ad", "Seçmen ad sorgu"],
            ["Adres", "Adres sorgu"],
            ["Tapu", "Tapu sorgu"],
            ["Vesika", "Vesika sorgu"],
            ["Serino SKT", "Seri no SKT sorgu"],
            ["Meslek", "Meslek sorgu"],
            ["Ada Parsel", "Ada parsel sorgu"],
        ],
    ],
    "Araç & Plaka" => [
        "icon" => "🚗",
        "icon_class" => "ic-red",
        "items" => [
            ["Plaka", "Plaka sorgu"],
            ["Plaka Ad", "Plaka ile ad sorgu"],
        ],
    ],
    "Dijital Platformlar" => [
        "icon" => "💻",
        "icon_class" => "ic-indigo",
        "items" => [
            ["Discord ID", "Discord ID sorgu"],
            ["Discord Email", "Discord email sorgu"],
        ],
    ],
    "İletişim & SMS" => [
        "icon" => "✉️",
        "icon_class" => "ic-sky",
        "items" => [
            ["SMS", "SMS gönderme servisi"],
            ["AM", "Anonim mesaj servisi"],
        ],
    ],
];

// İstatistikleri hesapla
$total_items = 0;
foreach ($categories as $cat) {
    $total_items += count($cat['items']);
}

// Her kategoriyi render et
foreach ($categories as $cat_name => $cat):
    $count = count($cat['items']);
?>
    <div class="section">
        <div class="section-head">
            <div class="section-icon <?= $cat['icon_class'] ?>"><?= $cat['icon'] ?></div>
            <div>
                <div class="section-title"><?= htmlspecialchars($cat_name) ?></div>
                <div class="section-count"><?= $count ?> sorgu mevcut</div>
            </div>
            <div class="section-badge"><?= $count ?> Servis</div>
        </div>
        <div class="grid">
            <?php foreach ($cat['items'] as $item): ?>
            <div class="card">
                <div class="card-body">
                    <div class="card-name"><?= htmlspecialchars($item[0]) ?></div>
                    <div class="card-desc"><?= htmlspecialchars($item[1]) ?></div>
                    <a href="<?= $premium_url ?>" target="_blank" class="card-btn">
                        <span>💎</span> Premium Al
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

</div>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-links">
        <a href="https://t.me/logsuzlarvip" target="_blank">📢 @logsuzlarvip</a>
        <a href="https://t.me/fbxnext" target="_blank">💎 @fbxnext</a>
    </div>
    <div>© <?= date('Y') ?> Logsuzlar VIP - Tüm hakları saklıdır.</div>
</footer>

</body>
</html>
