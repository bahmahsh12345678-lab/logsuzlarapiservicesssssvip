<?php
// ============================================================
// SULALE GSM SORGU API - curl_multi YOK (kesin calisir)
// Script Owner: @fbxnext
// Instagram: @logsuzlarpanel
// TikTok: @logsuzlar.inc
// ============================================================
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(60);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$tc = isset($_GET['tc']) ? preg_replace('/[^0-9]/', '', $_GET['tc']) : '';

if (strlen($tc) !== 11) {
    echo json_encode(array('ok' => false, 'hata' => 'Gecerli 11 haneli TC girin'));
    exit;
}

$MAX_KISI = 10;

// ============================================================
// TEK ISTEK - CACHE + KEEP-ALIVE + TCP FASTOPEN
// ============================================================
function cek($url) {
    static $cache = array();
    if (isset($cache[$url])) return $cache[$url];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
    curl_setopt($ch, CURLOPT_TCP_FASTOPEN, 1);
    $body = @curl_exec($ch);
    @curl_close($ch);

    if (!$body) { $cache[$url] = null; return null; }
    $json = json_decode($body, true);
    if (!is_array($json)) { $cache[$url] = null; return null; }
    if (isset($json['auth'])) unset($json['auth']);
    if (isset($json['auth_alt'])) unset($json['auth_alt']);
    $cache[$url] = $json;
    return $json;
}

// ============================================================
// KISI CIKAR
// ============================================================
function kisiCikar($k) {
    if (!is_array($k)) return null;
    $tc = isset($k['KimlikNo']) ? $k['KimlikNo'] : (isset($k['TC']) ? $k['TC'] : null);
    $ad = isset($k['Isim']) ? $k['Isim'] : (isset($k['AD']) ? $k['AD'] : null);
    $anneTc = isset($k['AnneKimlikNo']) ? $k['AnneKimlikNo'] : (isset($k['ANNETC']) ? $k['ANNETC'] : null);
    $babaTc = isset($k['BabaKimlikNo']) ? $k['BabaKimlikNo'] : (isset($k['BABATC']) ? $k['BABATC'] : null);
    if (!$tc && !$ad) return null;
    return array('tc' => $tc, 'anne_tc' => $anneTc, 'baba_tc' => $babaTc);
}

function listeCikar($veri) {
    $liste = array();
    if (!is_array($veri)) return $liste;
    if (isset($veri[0]) && is_array($veri[0])) {
        foreach ($veri as $k) { $n = kisiCikar($k); if ($n) $liste[] = $n; }
        return $liste;
    }
    foreach (array('data','veri','sonuc','result') as $alan) {
        if (isset($veri[$alan]) && is_array($veri[$alan])) {
            foreach ($veri[$alan] as $k) {
                if (is_array($k)) { $n = kisiCikar($k); if ($n) $liste[] = $n; }
            }
        }
    }
    return $liste;
}

function gsmNormalize($gsm) {
    if (!$gsm) return null;
    $g = preg_replace('/[^0-9]/', '', (string)$gsm);
    if (strlen($g) === 12 && substr($g, 0, 2) === '90') $g = substr($g, 2);
    if (strlen($g) === 11 && substr($g, 0, 1) === '0') $g = substr($g, 1);
    if (strlen($g) === 10 && substr($g, 0, 1) === '5') return $g;
    return null;
}

function gsmCikar($veri) {
    $gsmler = array();
    if (!is_array($veri)) return $gsmler;
    array_walk_recursive($veri, function($v) use (&$gsmler) {
        if (is_string($v) || is_numeric($v)) {
            $norm = gsmNormalize($v);
            if ($norm) $gsmler[] = $norm;
        }
    });
    return array_values(array_unique($gsmler));
}

// ============================================================
// ANA SORGU
// ============================================================
$baslangic = microtime(true);
$sayac = 0;
$ziyaret = array($tc => true);
$kisiHaritasi = array();

// 1) KOK KISI
$kokVeri = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $tc);
if (!$kokVeri) $kokVeri = cek('https://apiv2.ajaxsystems.fun/aile.php?tc=' . $tc);

$kokListe = listeCikar($kokVeri);
$kokBilgi = null;
foreach ($kokListe as $k) {
    if ($k['tc'] === $tc) { $kokBilgi = $k; break; }
}
if (!$kokBilgi && !empty($kokListe)) $kokBilgi = $kokListe[0];
if (!$kokBilgi) {
    echo json_encode(array('ok' => false, 'hata' => 'Kisi bulunamadi'));
    exit;
}
$kisiHaritasi[$tc] = $kokBilgi;
$sayac++;

foreach ($kokListe as $k) {
    if ($sayac >= $MAX_KISI) break;
    if (!$k['tc'] || isset($ziyaret[$k['tc']])) continue;
    $ziyaret[$k['tc']] = true;
    $kisiHaritasi[$k['tc']] = $k;
    $sayac++;
}

$anneTc = isset($kokBilgi['anne_tc']) ? $kokBilgi['anne_tc'] : null;
if ($anneTc && !isset($ziyaret[$anneTc]) && $sayac < $MAX_KISI) {
    $anneVeri = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $anneTc);
    foreach (listeCikar($anneVeri) as $k) {
        if ($sayac >= $MAX_KISI) break;
        if (!$k['tc'] || isset($ziyaret[$k['tc']])) continue;
        $ziyaret[$k['tc']] = true;
        $kisiHaritasi[$k['tc']] = $k;
        $sayac++;
    }
}

$babaTc = isset($kokBilgi['baba_tc']) ? $kokBilgi['baba_tc'] : null;
if ($babaTc && !isset($ziyaret[$babaTc]) && $sayac < $MAX_KISI) {
    $babaVeri = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $babaTc);
    foreach (listeCikar($babaVeri) as $k) {
        if ($sayac >= $MAX_KISI) break;
        if (!$k['tc'] || isset($ziyaret[$k['tc']])) continue;
        $ziyaret[$k['tc']] = true;
        $kisiHaritasi[$k['tc']] = $k;
        $sayac++;
    }
}

// 2) TUM TCLER ICIN GSM SORGUSU (sirali, hizli timeout)
$gsmHaritasi = array();
foreach ($kisiHaritasi as $ktc => $k) {
    $gsmVeri = cek('https://apiv2.ajaxsystems.fun/tcgsm.php?tc=' . $ktc);
    if (!$gsmVeri) continue;

    $gsmler = gsmCikar($gsmVeri);
    foreach ($gsmler as $g) {
        if (isset($gsmHaritasi[$g])) continue;
        $gsmHaritasi[$g] = $ktc;
    }
}

$liste = array();
foreach ($gsmHaritasi as $gsm => $sahipTc) {
    $liste[] = array('GSM' => $gsm, 'TC' => $sahipTc);
}
usort($liste, function($a, $b) { return strcmp($a['GSM'], $b['GSM']); });

$sure = round(microtime(true) - $baslangic, 2);

echo json_encode(array(
    'ok' => true,
    'kok_tc' => $tc,
    'toplam' => count($liste),
    'sure' => $sure,
    'liste' => $liste,
    'script_sahibi' => '@fbxnext',
    'instagram' => '@logsuzlarpanel',
    'tiktok' => '@logsuzlar.inc'
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);