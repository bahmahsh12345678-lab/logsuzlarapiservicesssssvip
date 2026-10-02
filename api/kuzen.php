<?php
// ============================================================
// KUZEN SORGU API - TC girer, kuzenleri bulur
// Script Sahibi: @izeyder
// Instagram: @logsuzlarpanel
// TikTok: @logsuzlar.inc
// ============================================================
header('Content-Type: application/json; charset=utf-8');

// TC al
$tc = isset($_GET['tc']) ? preg_replace('/[^0-9]/', '', $_GET['tc']) : '';

if (strlen($tc) !== 11) {
    echo json_encode(array('ok' => false, 'hata' => 'TC 11 haneli olmalı'));
    exit;
}

// API isteği
function cek($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $body = curl_exec($ch);
    curl_close($ch);
    return json_decode($body, true);
}

// 1) Kişinin kendisini bul
$kisi = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $tc);
if (!$kisi || !isset($kisi['data'])) {
    echo json_encode(array('ok' => false, 'hata' => 'Kişi bulunamadı'));
    exit;
}

// Kendi bilgisini al
$kok = null;
foreach ($kisi['data'] as $k) {
    if (isset($k['TC']) && $k['TC'] == $tc) { $kok = $k; break; }
}

if (!$kok) {
    echo json_encode(array('ok' => false, 'hata' => 'Kişi verisi yok'));
    exit;
}

$anneTc = isset($kok['ANNETC']) ? $kok['ANNETC'] : null;
$babaTc = isset($kok['BABATC']) ? $kok['BABATC'] : null;

// 2) Anne-babanın kardeşlerini bul
$amcaHalaDayiTeyze = array();

if ($anneTc) {
    $anneData = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $anneTc);
    if ($anneData && isset($anneData['data'])) {
        foreach ($anneData['data'] as $k) {
            if (!isset($k['TC']) || $k['TC'] == $anneTc || $k['TC'] == $tc) continue;
            $a1 = isset($k['ANNETC']) ? $k['ANNETC'] : null;
            $b1 = isset($k['BABATC']) ? $k['BABATC'] : null;
            // Anne'nin kardeşi mi?
            $anneKendi = null;
            foreach ($anneData['data'] as $x) {
                if (isset($x['TC']) && $x['TC'] == $anneTc) {
                    $anneKendi = $x;
                    break;
                }
            }
            if ($anneKendi) {
                if (($a1 && $a1 == $anneKendi['ANNETC']) || ($b1 && $b1 == $anneKendi['BABATC'])) {
                    $amcaHalaDayiTeyze[$k['TC']] = $k;
                }
            }
        }
    }
}

if ($babaTc) {
    $babaData = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $babaTc);
    if ($babaData && isset($babaData['data'])) {
        $babaKendi = null;
        foreach ($babaData['data'] as $x) {
            if (isset($x['TC']) && $x['TC'] == $babaTc) {
                $babaKendi = $x;
                break;
            }
        }
        if ($babaKendi) {
            foreach ($babaData['data'] as $k) {
                if (!isset($k['TC']) || $k['TC'] == $babaTc || $k['TC'] == $tc) continue;
                $a1 = isset($k['ANNETC']) ? $k['ANNETC'] : null;
                $b1 = isset($k['BABATC']) ? $k['BABATC'] : null;
                if (($a1 && $a1 == $babaKendi['ANNETC']) || ($b1 && $b1 == $babaKendi['BABATC'])) {
                    $amcaHalaDayiTeyze[$k['TC']] = $k;
                }
            }
        }
    }
}

// 3) Amca/hala/dayı/teyzenin çocuklarını bul = KUZENLER
$kuzenler = array();

foreach ($amcaHalaDayiTeyze as $aTc => $aKisi) {
    $aData = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $aTc);
    if (!$aData || !isset($aData['data'])) continue;

    foreach ($aData['data'] as $k) {
        if (!isset($k['TC']) || $k['TC'] == $aTc || $k['TC'] == $tc) continue;

        $a1 = isset($k['ANNETC']) ? $k['ANNETC'] : null;
        $b1 = isset($k['BABATC']) ? $k['BABATC'] : null;

        // Amca/hala/dayı/teyzenin çocuğu mu?
        if ($a1 == $aTc || $b1 == $aTc) {
            $kuzenler[$k['TC']] = $k;
        }
    }
}

// 4) Sonuç
$liste = array();
foreach ($kuzenler as $ktc => $k) {
    $item = array(
        'TC' => $ktc,
        'Ad' => isset($k['AD']) ? $k['AD'] : null,
        'Soyad' => isset($k['SOYAD']) ? $k['SOYAD'] : null,
        'DogumTarihi' => isset($k['DOGUM_YILI']) ? $k['DOGUM_YILI'] : null,
        'Yas' => isset($k['YAS']) ? $k['YAS'] : null,
        'Il' => isset($k['MEMLEKETIL']) ? $k['MEMLEKETIL'] : null,
        'Ilce' => isset($k['MEMLEKETILCE']) ? $k['MEMLEKETILCE'] : null
    );

    // Boşları sil
    $temiz = array();
    foreach ($item as $key => $val) {
        if ($val !== null && $val !== '') $temiz[$key] = $val;
    }
    $liste[] = $temiz;
}

echo json_encode(array(
    'ok' => true,
    'kok_tc' => $tc,
    'kok_ad' => isset($kok['AD']) ? $kok['AD'] : null,
    'kok_soyad' => isset($kok['SOYAD']) ? $kok['SOYAD'] : null,
    'toplam_kuzen' => count($liste),
    'kuzenler' => $liste,
    'script_sahibi' => '@izeyder',
    'instagram' => '@logsuzlarpanel',
    'tiktok' => '@logsuzlar.inc'
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);