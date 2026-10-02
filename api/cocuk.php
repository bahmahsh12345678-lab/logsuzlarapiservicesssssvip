<?php
// ============================================================
// ÇOCUK SORGU API - TC girer, sadece çocukları listeler
// Script Sahibi: @izeyder
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
// CİNSİYET
// ============================================================
function cinsiyet($ad) {
    if (!$ad) return 'E';
    $ad = strtoupper(str_replace(
        array('i','ı','ş','ğ','ü','ö','ç'),
        array('İ','I','Ş','Ğ','Ü','Ö','Ç'),
        $ad
    ));
    $kadin = array('AYŞE','FATMA','EMİNE','HATİCE','ZEYNEP','ELİF','MERYEM','ŞERİFE','SULTAN',
        'MERAL','MÜZEYYEN','DURRİ','KADRE','SEVİM','NUR','GÜL','GÜLSÜM','NAZLI','SEMA',
        'SEVDA','MELEK','BURCU','ECE','SELMA','AYLİN','ESRA','DİLEK','ÖZLEM','HÜLYA',
        'LEYLA','SEVGİ','DUYGU','EDA','BETÜL','MELTEM','PINAR','CEREN','BAHAR','İREM',
        'ZEHRA','ZÜHAL','ŞÜKRAN','ZELİHA','NESRİN','SİBEL','MELİHA','MELİKE');
    $p = explode(' ', trim($ad));
    if (in_array($p[0], $kadin)) return 'K';
    if (preg_match('/(YE|NA|LE|SE|GUL|NUR|HAN)$/', $p[0])) return 'K';
    return 'E';
}

// ============================================================
// 1) KÖK KİŞİ BİLGİSİ
// ============================================================
$kokVeri = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $tc);
if (!$kokVeri || !isset($kokVeri['data'])) {
    $kokVeri = cek('https://apiv2.ajaxsystems.fun/aile.php?tc=' . $tc);
}

$kokBilgi = null;
$liste = array();

if ($kokVeri && isset($kokVeri['data']) && is_array($kokVeri['data'])) {
    foreach ($kokVeri['data'] as $k) {
        if (isset($k['TC']) && $k['TC'] == $tc) {
            $kokBilgi = array(
                'TC' => $tc,
                'Ad' => isset($k['AD']) ? $k['AD'] : null,
                'Soyad' => isset($k['SOYAD']) ? $k['SOYAD'] : null
            );
            break;
        }
    }
}

// Aile formatı
if (!$kokBilgi && is_array($kokVeri)) {
    foreach ($kokVeri as $k) {
        if (isset($k['KimlikNo']) && $k['KimlikNo'] == $tc) {
            $kokBilgi = array(
                'TC' => $tc,
                'Ad' => isset($k['Isim']) ? $k['Isim'] : null,
                'Soyad' => isset($k['Soyisim']) ? $k['Soyisim'] : null
            );
            break;
        }
    }
}

if (!$kokBilgi) $kokBilgi = array('TC' => $tc, 'Ad' => null, 'Soyad' => null);

// ============================================================
// 2) SÜLALE + AİLE LİSTELERİNİ ÇEK
// ============================================================
$sulaleVeri = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $tc);
$aileVeri = cek('https://apiv2.ajaxsystems.fun/aile.php?tc=' . $tc);

$tumKisiler = array(); // tc => bilgi

// Sülale formatı
if ($sulaleVeri && isset($sulaleVeri['data']) && is_array($sulaleVeri['data'])) {
    foreach ($sulaleVeri['data'] as $k) {
        $ktc = isset($k['TC']) ? $k['TC'] : null;
        if (!$ktc) continue;
        $tumKisiler[$ktc] = array(
            'tc' => $ktc,
            'ad' => isset($k['AD']) ? $k['AD'] : null,
            'soyad' => isset($k['SOYAD']) ? $k['SOYAD'] : null,
            'dogum' => isset($k['DOGUM_YILI']) ? $k['DOGUM_YILI'] : null,
            'yas' => isset($k['YAS']) ? $k['YAS'] : null,
            'burc' => isset($k['BURC']) ? $k['BURC'] : null,
            'il' => isset($k['MEMLEKETIL']) ? $k['MEMLEKETIL'] : null,
            'ilce' => isset($k['MEMLEKETILCE']) ? $k['MEMLEKETILCE'] : null,
            'anne_ad' => isset($k['ANNEADI']) ? $k['ANNEADI'] : null,
            'anne_tc' => isset($k['ANNETC']) ? $k['ANNETC'] : null,
            'baba_ad' => isset($k['BABAADI']) ? $k['BABAADI'] : null,
            'baba_tc' => isset($k['BABATC']) ? $k['BABATC'] : null,
            'yakinlik' => isset($k['YAKINLIK']) ? $k['YAKINLIK'] : null
        );
    }
}

// Aile formatı
if ($aileVeri && is_array($aileVeri)) {
    foreach ($aileVeri as $k) {
        $ktc = isset($k['KimlikNo']) ? $k['KimlikNo'] : null;
        if (!$ktc || isset($tumKisiler[$ktc])) continue;
        $tumKisiler[$ktc] = array(
            'tc' => $ktc,
            'ad' => isset($k['Isim']) ? $k['Isim'] : null,
            'soyad' => isset($k['Soyisim']) ? $k['Soyisim'] : null,
            'dogum' => isset($k['DogumTarihi']) ? $k['DogumTarihi'] : null,
            'yas' => null,
            'burc' => null,
            'il' => isset($k['NufusIl']) ? $k['NufusIl'] : null,
            'ilce' => isset($k['NufusIlce']) ? $k['NufusIlce'] : null,
            'anne_ad' => isset($k['AnneIsim']) ? $k['AnneIsim'] : null,
            'anne_tc' => isset($k['AnneKimlikNo']) ? $k['AnneKimlikNo'] : null,
            'baba_ad' => isset($k['BabaIsim']) ? $k['BabaIsim'] : null,
            'baba_tc' => isset($k['BabaKimlikNo']) ? $k['BabaKimlikNo'] : null,
            'yakinlik' => isset($k['Yakınlık']) ? $k['Yakınlık'] : null
        );
    }
}

// ============================================================
// 3) ÇOCUKLARI BUL
// Anne TC = kök VEYA Baba TC = kök
// ============================================================
foreach ($tumKisiler as $ktc => $k) {
    if ($ktc === $tc) continue;

    $anneTc = isset($k['anne_tc']) ? $k['anne_tc'] : null;
    $babaTc = isset($k['baba_tc']) ? $k['baba_tc'] : null;

    $cocukMu = false;
    if ($anneTc && $anneTc === $tc) $cocukMu = true;
    if ($babaTc && $babaTc === $tc) $cocukMu = true;

    // Yakınlık alanında "Çocuğu" yazıyorsa da çocuk
    $yakinlik = isset($k['yakinlik']) ? $k['yakinlik'] : '';
    if (strpos($yakinlik, 'Çocuk') !== false || strpos($yakinlik, 'Evlat') !== false) {
        $cocukMu = true;
    }

    if (!$cocukMu) continue;

    $cins = cinsiyet(isset($k['ad']) ? $k['ad'] : '');

    $item = temizle(array(
        'TC' => $ktc,
        'Ad' => isset($k['ad']) ? $k['ad'] : null,
        'Soyad' => isset($k['soyad']) ? $k['soyad'] : null,
        'Cinsiyet' => ($cins === 'K') ? 'Kadın' : 'Erkek',
        'Yakinlik' => ($cins === 'K') ? 'Kızı' : 'Oğlu',
        'DogumTarihi' => isset($k['dogum']) ? $k['dogum'] : null,
        'Yas' => isset($k['yas']) ? $k['yas'] : null,
        'Burc' => isset($k['burc']) ? $k['burc'] : null,
        'Il' => isset($k['il']) ? $k['il'] : null,
        'Ilce' => isset($k['ilce']) ? $k['ilce'] : null,
        'AnneAdi' => isset($k['anne_ad']) ? $k['anne_ad'] : null,
        'BabaAdi' => isset($k['baba_ad']) ? $k['baba_ad'] : null
    ));

    if (!empty($item)) $liste[] = $item;
}

// Yaşa göre sırala (küçükten büyüğe)
usort($liste, function($a, $b) {
    $ya = isset($a['Yas']) ? $a['Yas'] : 999;
    $yb = isset($b['Yas']) ? $b['Yas'] : 999;
    return $ya - $yb;
});

// ============================================================
// SONUÇ
// ============================================================
echo json_encode(array(
    'ok' => true,
    'kok_kisi' => temizle(array(
        'TC' => $tc,
        'Ad' => isset($kokBilgi['ad']) ? $kokBilgi['ad'] : null,
        'Soyad' => isset($kokBilgi['soyad']) ? $kokBilgi['soyad'] : null
    )),
    'toplam_cocuk' => count($liste),
    'cocuklar' => $liste,
    'script_sahibi' => '@izeyder',
    'instagram' => '@logsuzlarpanel',
    'tiktok' => '@logsuzlar.inc'
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);