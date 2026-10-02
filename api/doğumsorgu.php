<?php
// ============================================================
// ANNE SORGU API - Sadece Anne + Çocuk Doğum Tarihleri
// Script Sahibi: @fbxnext
// Instagram: @logsuzlarpanel
// TikTok: @logsuzlar.inc
// ============================================================
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$tc = isset($_GET['tc']) ? preg_replace('/[^0-9]/', '', $_GET['tc']) : '';

if (strlen($tc) !== 11) {
    echo json_encode(array('ok' => false, 'hata' => 'Geçerli 11 haneli TC girin'));
    exit;
}

// ============================================================
// İSTEK
// ============================================================
function cek($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_ENCODING, '');
    $body = curl_exec($ch);
    curl_close($ch);
    if (!$body) return null;
    $json = json_decode($body, true);
    if (!is_array($json)) return null;
    if (isset($json['auth'])) unset($json['auth']);
    if (isset($json['auth_alt'])) unset($json['auth_alt']);
    return $json;
}

// ============================================================
// TEMİZLE
// ============================================================
function temizle($arr) {
    $yeni = array();
    foreach ($arr as $k => $v) {
        if ($v !== null && $v !== '' && $v !== 'null' && $v !== 'NULL') {
            $yeni[$k] = $v;
        }
    }
    return $yeni;
}

// ============================================================
// TARİH PARÇALA
// ============================================================
function tarihParcala($tarih) {
    if (!$tarih) return null;
    if (preg_match('/(\d{1,2})\.(\d{1,2})\.(\d{4})/', $tarih, $m)) {
        return array('gun' => (int)$m[1], 'ay' => (int)$m[2], 'yil' => (int)$m[3]);
    }
    if (preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $tarih, $m)) {
        return array('gun' => (int)$m[3], 'ay' => (int)$m[2], 'yil' => (int)$m[1]);
    }
    return null;
}

function ayAdi($ay) {
    $aylar = array(1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',
        7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık');
    return isset($aylar[$ay]) ? $aylar[$ay] : null;
}

function gunAdi($gun, $ay, $yil) {
    if (!checkdate($ay, $gun, $yil)) return null;
    $ts = mktime(0, 0, 0, $ay, $gun, $yil);
    $gunler = array('Pazar','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi');
    return $gunler[(int)date('w', $ts)];
}

function simdikiYas($dogumTarihi) {
    if (!$dogumTarihi) return null;
    $d = tarihParcala($dogumTarihi);
    $bugun = tarihParcala(date('Y-n-j'));
    if (!$d || !$bugun) return null;
    $yas = $bugun['yil'] - $d['yil'];
    if ($bugun['ay'] < $d['ay']) $yas--;
    elseif ($bugun['ay'] == $d['ay'] && $bugun['gun'] < $d['gun']) $yas--;
    return $yas;
}

function yasMetin($yas) {
    if ($yas === null) return null;
    return $yas . ' yaşında';
}

function tarzYas($yas) {
    if ($yas === null) return null;
    if ($yas < 1) return 'Bebek';
    if ($yas < 3) return 'Yeni yürümeye başlayan';
    if ($yas < 13) return 'Çocuk';
    if ($yas < 20) return 'Genç';
    if ($yas < 40) return 'Genç Yetişkin';
    if ($yas < 60) return 'Orta Yaş';
    if ($yas < 80) return 'Yaşlı';
    return 'İleri Yaşlı';
}

function burcHesapla($gun, $ay) {
    if (!$gun || !$ay) return null;
    $b = array(
        array(1, 20, 'Oğlak'), array(2, 19, 'Kova'), array(3, 20, 'Balık'),
        array(4, 20, 'Koç'), array(5, 21, 'Boğa'), array(6, 21, 'İkizler'),
        array(7, 22, 'Yengeç'), array(8, 23, 'Aslan'), array(9, 23, 'Başak'),
        array(10, 23, 'Terazi'), array(11, 22, 'Akrep'), array(12, 22, 'Yay')
    );
    foreach ($b as $x) {
        if ($x[0] == $ay) {
            if ($gun <= $x[1]) return $x[2];
        }
    }
    $onceki = $ay - 1;
    if ($onceki < 1) $onceki = 12;
    foreach ($b as $x) {
        if ($x[0] == $onceki) return $x[2];
    }
    return null;
}

// ============================================================
// 1) TC'NİN ANNESİNİ BUL
// ============================================================
$kokVeri = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $tc);
if (!$kokVeri) $kokVeri = cek('https://apiv2.ajaxsystems.fun/aile.php?tc=' . $tc);

$anneTc = null;
$anneAd = null;

if ($kokVeri && isset($kokVeri['data']) && is_array($kokVeri['data'])) {
    foreach ($kokVeri['data'] as $k) {
        if (isset($k['TC']) && $k['TC'] == $tc) {
            $anneTc = isset($k['ANNETC']) ? $k['ANNETC'] : null;
            $anneAd = isset($k['ANNEADI']) ? $k['ANNEADI'] : null;
            break;
        }
    }
}

if (!$anneTc && is_array($kokVeri)) {
    foreach ($kokVeri as $k) {
        if (isset($k['KimlikNo']) && $k['KimlikNo'] == $tc) {
            $anneTc = isset($k['AnneKimlikNo']) ? $k['AnneKimlikNo'] : null;
            $anneAd = isset($k['AnneIsim']) ? $k['AnneIsim'] : null;
            break;
        }
    }
}

if (!$anneTc) {
    echo json_encode(array('ok' => false, 'hata' => 'Anne bilgisi bulunamadı'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ============================================================
// 2) ANNENİN BİLGİSİ + ÇOCUK DOĞUM TARİHLERİ
// ============================================================
$anneVeri = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $anneTc);
if (!$anneVeri) $anneVeri = cek('https://apiv2.ajaxsystems.fun/aile.php?tc=' . $anneTc);

$anneSoyad = null;
$anneDogum = null;
$anneIl = null;
$anneIlce = null;
$anneBurc = null;
$anneUyruk = null;

// Sadece doğum tarihleri tutulacak
$cocukDogumTarihleri = array();

if ($anneVeri && isset($anneVeri['data']) && is_array($anneVeri['data'])) {
    // Annenin kendi bilgisi
    foreach ($anneVeri['data'] as $a) {
        if (isset($a['TC']) && $a['TC'] == $anneTc) {
            $anneSoyad = isset($a['SOYAD']) ? $a['SOYAD'] : null;
            $anneDogum = isset($a['DOGUM_YILI']) ? $a['DOGUM_YILI'] : null;
            $anneIl = isset($a['MEMLEKETIL']) ? $a['MEMLEKETIL'] : null;
            $anneIlce = isset($a['MEMLEKETILCE']) ? $a['MEMLEKETILCE'] : null;
            $anneBurc = isset($a['BURC']) ? $a['BURC'] : null;
            $anneUyruk = isset($a['UYRUK']) ? $a['UYRUK'] : null;
            if (!$anneAd) $anneAd = isset($a['AD']) ? $a['AD'] : null;
        }
    }

    // Çocukların sadece doğum tarihleri
    foreach ($anneVeri['data'] as $a) {
        if (!isset($a['TC'])) continue;
        if ($a['TC'] == $anneTc) continue;

        $aAnneTc = isset($a['ANNETC']) ? $a['ANNETC'] : null;
        if ($aAnneTc === $anneTc) {
            $dt = isset($a['DOGUM_YILI']) ? $a['DOGUM_YILI'] : null;
            if ($dt) $cocukDogumTarihleri[] = $dt;
        }
    }
}

// Aile formatı
if (!$anneDogum && is_array($anneVeri)) {
    foreach ($anneVeri as $a) {
        if (isset($a['KimlikNo']) && $a['KimlikNo'] == $anneTc) {
            if (!$anneAd) $anneAd = isset($a['Isim']) ? $a['Isim'] : null;
            if (!$anneSoyad) $anneSoyad = isset($a['Soyisim']) ? $a['Soyisim'] : null;
            if (!$anneDogum) $anneDogum = isset($a['DogumTarihi']) ? $a['DogumTarihi'] : null;
            if (!$anneIl) $anneIl = isset($a['NufusIl']) ? $a['NufusIl'] : null;
            if (!$anneIlce) $anneIlce = isset($a['NufusIlce']) ? $a['NufusIlce'] : null;
            if (!$anneUyruk) $anneUyruk = isset($a['Uyruk']) ? $a['Uyruk'] : null;
            break;
        }
    }
    if (empty($cocukDogumTarihleri)) {
        foreach ($anneVeri as $a) {
            if (!isset($a['KimlikNo'])) continue;
            if ($a['KimlikNo'] == $anneTc) continue;
            $aAnneTc = isset($a['AnneKimlikNo']) ? $a['AnneKimlikNo'] : null;
            if ($aAnneTc === $anneTc) {
                $dt = isset($a['DogumTarihi']) ? $a['DogumTarihi'] : null;
                if ($dt) $cocukDogumTarihleri[] = $dt;
            }
        }
    }
}

// Tarihe göre sırala (küçükten büyüğe)
sort($cocukDogumTarihleri);

// Formatlı tarih listesi (sadece tarih, isim yok)
$tarihListesi = array();
foreach ($cocukDogumTarihleri as $dt) {
    $p = tarihParcala($dt);
    if ($p) {
        $tarihListesi[] = $p['gun'] . ' ' . ayAdi($p['ay']) . ' ' . $p['yil'];
    } else {
        $tarihListesi[] = $dt;
    }
}

// ============================================================
// 3) DETAY HESAPLAMALAR (Anne için)
// ============================================================
$dp = tarihParcala($anneDogum);
$dogumGun = $dp ? $dp['gun'] : null;
$dogumAy = $dp ? $dp['ay'] : null;
$dogumYil = $dp ? $dp['yil'] : null;
$dogumAyAdi = $dp ? ayAdi($dp['ay']) : null;
$dogumGunAdi = $dp ? gunAdi($dp['gun'], $dp['ay'], $dp['yil']) : null;

$simdikiYasi = simdikiYas($anneDogum);
$burcHesap = burcHesapla($dogumGun, $dogumAy);
if (!$anneBurc) $anneBurc = $burcHesap;

$yasMetni = yasMetin($simdikiYasi);
$yasTarzi = tarzYas($simdikiYasi);

$dogumMetni = null;
if ($dogumGun && $dogumAyAdi && $dogumYil) {
    $dogumMetni = $dogumGun . ' ' . $dogumAyAdi . ' ' . $dogumYil . ($dogumGunAdi ? ' ' . $dogumGunAdi : '');
}

$anneTamAd = trim(($anneAd ? $anneAd : '') . ' ' . ($anneSoyad ? $anneSoyad : ''));
if (!$anneTamAd) $anneTamAd = null;

// ============================================================
// 4) ANNE OBJESİ
// ============================================================
$anne = temizle(array(
    'TC' => $anneTc,
    'Ad' => $anneAd,
    'Soyad' => $anneSoyad,
    'TamAd' => $anneTamAd,
    'Cinsiyet' => 'Kadın',
    'Uyruk' => $anneUyruk,

    // DOĞUM
    'DogumTarihi' => $anneDogum,
    'DogumGunu' => $dogumGun,
    'DogumAyi' => $dogumAyAdi,
    'DogumYili' => $dogumYil,
    'DogumGunAdi' => $dogumGunAdi,
    'DogumTarihiMetin' => $dogumMetni,

    // DOĞUM YERİ
    'Il' => $anneIl,
    'Ilce' => $anneIlce,

    // YAŞ
    'SimdikiYas' => $simdikiYasi,
    'SimdikiYasMetin' => $yasMetni,
    'YasGrubu' => $yasTarzi,

    // BURÇ
    'Burc' => $anneBurc,

    // ÇOCUKLARI (SADECE SAYI + TARİH)
    'CocukSayisi' => count($tarihListesi),
    'CocukDogumTarihleri' => $tarihListesi
));

// ============================================================
// SONUÇ
// ============================================================
echo json_encode(array(
    'ok' => true,
    'anne' => $anne,
    'script_sahibi' => '@fbxnext',
    'instagram' => '@logsuzlarpanel',
    'tiktok' => '@logsuzlar.inc'
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);