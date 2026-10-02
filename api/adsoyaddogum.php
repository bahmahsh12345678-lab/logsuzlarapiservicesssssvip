<?php
// ============================================================
// AD SOYAD + DOGUM TARIHI ILE SORGU API
// Ad, soyad ve dogum tarihi ile kisi arar
// Script Owner: @fbxnext
// Instagram: @logsuzlarpanel
// TikTok: @logsuzlar.inc
// ============================================================
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(60);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$ad = isset($_GET['ad']) ? trim($_GET['ad']) : '';
$soyad = isset($_GET['soyad']) ? trim($_GET['soyad']) : '';
$dogum = isset($_GET['dogum']) ? trim($_GET['dogum']) : '';
$il = isset($_GET['il']) ? trim($_GET['il']) : '';

if (!$ad || !$soyad) {
    echo json_encode(array('ok' => false, 'hata' => 'ad ve soyad zorunlu'));
    exit;
}

// ============================================================
// TEK ISTEK
// ============================================================
function cek($url) {
    static $cache = array();
    if (isset($cache[$url])) return $cache[$url];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_ENCODING, '');
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
// TEMIZLE
// ============================================================
function temizle($arr) {
    if (!is_array($arr)) return $arr;
    $yeni = array();
    foreach ($arr as $k => $v) {
        if (is_array($v)) {
            $v = temizle($v);
            if (!empty($v)) $yeni[$k] = $v;
        } else {
            if ($v !== null && $v !== '' && $v !== 'null' && $v !== 'NULL') {
                $yeni[$k] = $v;
            }
        }
    }
    return $yeni;
}

// ============================================================
// KISI CIKAR
// ============================================================
function kisiCikar($k) {
    if (!is_array($k)) return null;

    $tc = isset($k['KimlikNo']) ? $k['KimlikNo'] : (isset($k['TC']) ? $k['TC'] : null);
    $adK = isset($k['Isim']) ? $k['Isim'] : (isset($k['AD']) ? $k['AD'] : null);
    $soyadK = isset($k['Soyisim']) ? $k['Soyisim'] : (isset($k['SOYAD']) ? $k['SOYAD'] : null);
    $dogumK = isset($k['DogumTarihi']) ? $k['DogumTarihi'] : (isset($k['DOGUM_YILI']) ? $k['DOGUM_YILI'] : null);
    $yas = isset($k['YAS']) ? $k['YAS'] : null;
    $ilK = isset($k['NufusIl']) ? $k['NufusIl'] : (isset($k['MEMLEKETIL']) ? $k['MEMLEKETIL'] : null);
    $ilceK = isset($k['NufusIlce']) ? $k['NufusIlce'] : (isset($k['MEMLEKETILCE']) ? $k['MEMLEKETILCE'] : null);
    $anneAd = isset($k['AnneIsim']) ? $k['AnneIsim'] : (isset($k['ANNEADI']) ? $k['ANNEADI'] : null);
    $anneTc = isset($k['AnneKimlikNo']) ? $k['AnneKimlikNo'] : (isset($k['ANNETC']) ? $k['ANNETC'] : null);
    $babaAd = isset($k['BabaIsim']) ? $k['BabaIsim'] : (isset($k['BABAADI']) ? $k['BABAADI'] : null);
    $babaTc = isset($k['BabaKimlikNo']) ? $k['BabaKimlikNo'] : (isset($k['BABATC']) ? $k['BABATC'] : null);

    if (!$tc && !$adK) return null;

    return array(
        'tc' => $tc,
        'ad' => $adK,
        'soyad' => $soyadK,
        'dogum' => $dogumK,
        'yas' => $yas,
        'il' => $ilK,
        'ilce' => $ilceK,
        'anne_ad' => $anneAd,
        'anne_tc' => $anneTc,
        'baba_ad' => $babaAd,
        'baba_tc' => $babaTc
    );
}

// ============================================================
// LISTE CIKAR
// ============================================================
function listeCikar($veri) {
    $liste = array();
    if (!is_array($veri)) return $liste;

    if (isset($veri[0]) && is_array($veri[0])) {
        foreach ($veri as $k) {
            $n = kisiCikar($k);
            if ($n) $liste[] = $n;
        }
        return $liste;
    }

    $alanlar = array('data','veri','sonuc','result','kisiler','aile','sulale','ailepro');
    foreach ($alanlar as $alan) {
        if (isset($veri[$alan]) && is_array($veri[$alan])) {
            foreach ($veri[$alan] as $k) {
                if (is_array($k)) {
                    $n = kisiCikar($k);
                    if ($n) $liste[] = $n;
                }
            }
        }
    }
    return $liste;
}

// ============================================================
// CINSIYET TAHMINI
// ============================================================
function cinsiyet($ad) {
    if (!$ad) return 'E';
    $ad = strtoupper(str_replace(
        array('i','ı','ş','ğ','ü','ö','ç'),
        array('I','I','S','G','U','O','C'),
        $ad
    ));
    $kadin = array('AYSE','FATMA','EMINE','HATICE','ZEYNEP','ELIF','MERYEM','SERIFE','SULTAN',
        'MERAL','MUZEYYEN','DURRI','KADRE','SEVIM','NUR','GUL','GULSUM','NAZLI','SEMA',
        'SEVDA','MELEK','BURCU','ECE','SELMA','AYLIN','ESRA','DILEK','OZLEM','HULYA',
        'LEYLA','SEVGI','DUYGU','EDA','BETUL','MELTEM','PINAR','CEREN','BAHAR','IREM');
    $p = explode(' ', trim($ad));
    if (in_array($p[0], $kadin)) return 'K';
    if (preg_match('/(YE|NA|LE|SE|GUL|NUR|HAN|CAN|SU|AY|EL)$/', $p[0])) return 'K';
    return 'E';
}

// ============================================================
// AY ADI
// ============================================================
function ayAdi($ay) {
    $aylar = array(1=>'Ocak',2=>'Subat',3=>'Mart',4=>'Nisan',5=>'Mayis',6=>'Haziran',
        7=>'Temmuz',8=>'Agustos',9=>'Eylul',10=>'Ekim',11=>'Kasim',12=>'Aralik');
    return isset($aylar[$ay]) ? $aylar[$ay] : null;
}

function gunAdi($gun, $ay, $yil) {
    if (!checkdate($ay, $gun, $yil)) return null;
    $ts = mktime(0, 0, 0, $ay, $gun, $yil);
    $gunler = array('Pazar','Pazartesi','Sali','Carsamba','Persembe','Cuma','Cumartesi');
    return $gunler[(int)date('w', $ts)];
}

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

function burcHesapla($gun, $ay) {
    if (!$gun || !$ay) return null;
    $b = array(
        array(1, 20, 'Oglak'), array(2, 19, 'Kova'), array(3, 20, 'Balik'),
        array(4, 20, 'Koc'), array(5, 21, 'Boga'), array(6, 21, 'Ikizler'),
        array(7, 22, 'Yengec'), array(8, 23, 'Aslan'), array(9, 23, 'Basak'),
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

function yasHesapla($dogum) {
    if (!$dogum) return null;
    $d = tarihParcala($dogum);
    $bugun = tarihParcala(date('Y-n-j'));
    if (!$d || !$bugun) return null;
    $yas = $bugun['yil'] - $d['yil'];
    if ($bugun['ay'] < $d['ay']) $yas--;
    elseif ($bugun['ay'] == $d['ay'] && $bugun['gun'] < $d['gun']) $yas--;
    return $yas;
}

// ============================================================
// AD SOYAD + DOGUM TARIHI ILE ARAMA
// ============================================================
$baslangic = microtime(true);

// Ad-soyad + dogum tarihi ile arama
$queryParams = array(
    'ad' => $ad,
    'soyad' => $soyad
);
if ($dogum) $queryParams['dogum'] = $dogum;
if ($il) $queryParams['il'] = $il;

$query = http_build_query($queryParams);

// 1) adsoyad.php API'si
$adsoyadVeri = cek('https://solidarksystems.alwaysdata.net/adsoyad.php?' . $query);
if (!$adsoyadVeri) {
    $adsoyadVeri = cek('https://apiv2.ajaxsystems.fun/adsoyad.php?' . $query);
}

// 2) Alternatif adres API'si
if (!$adsoyadVeri) {
    $adsoyadVeri = cek('https://apiv2.ajaxsystems.fun/adsoyad.php?ad=' . urlencode($ad) . '&soyad=' . urlencode($soyad));
}

$liste = listeCikar($adsoyadVeri);

// Dogum tarihine gore filtrele
if ($dogum && !empty($liste)) {
    $filtreli = array();
    $dogumNorm = str_replace(array('.', '-', '/', ' '), '', $dogum);

    foreach ($liste as $k) {
        if (!$k['dogum']) continue;
        $kDogumNorm = str_replace(array('.', '-', '/', ' '), '', $k['dogum']);

        // Tam esleme
        if ($dogumNorm === $kDogumNorm) {
            $filtreli[] = $k;
            continue;
        }

        // Yil esleme
        if (strlen($dogumNorm) === 4 && strpos($kDogumNorm, $dogumNorm) !== false) {
            $filtreli[] = $k;
            continue;
        }

        // Gun ay yil esleme (herhangi bir formatta)
        $dp1 = tarihParcala($dogum);
        $dp2 = tarihParcala($k['dogum']);
        if ($dp1 && $dp2) {
            if ($dp1['gun'] === $dp2['gun'] && $dp1['ay'] === $dp2['ay'] && $dp1['yil'] === $dp2['yil']) {
                $filtreli[] = $k;
            }
        }
    }

    // Eger filtreli sonuc varsa onu kullan, yoksa tum listeyi kullan
    if (!empty($filtreli)) $liste = $filtreli;
}

// Il filtresi
if ($il && !empty($liste)) {
    $filtreli = array();
    $ilBuyuk = strtoupper(str_replace(
        array('i','ı','ş','ğ','ü','ö','ç'),
        array('I','I','S','G','U','O','C'),
        $il
    ));

    foreach ($liste as $k) {
        if (!$k['il']) continue;
        $kIl = strtoupper(str_replace(
            array('i','ı','ş','ğ','ü','ö','ç'),
            array('I','I','S','G','U','O','C'),
            $k['il']
        ));
        if ($kIl === $ilBuyuk || strpos($kIl, $ilBuyuk) !== false) {
            $filtreli[] = $k;
        }
    }
    if (!empty($filtreli)) $liste = $filtreli;
}

// Sonuclari formatla
$sonucListe = array();
foreach ($liste as $k) {
    $cins = cinsiyet($k['ad']);
    $dogumParcali = tarihParcala($k['dogum']);
    $yas = isset($k['yas']) ? $k['yas'] : yasHesapla($k['dogum']);
    $burc = $dogumParcali ? burcHesapla($dogumParcali['gun'], $dogumParcali['ay']) : null;

    $item = temizle(array(
        'TC' => $k['tc'],
        'Ad' => $k['ad'],
        'Soyad' => $k['soyad'],
        'Cinsiyet' => ($cins === 'K') ? 'Kadin' : 'Erkek',
        'DogumTarihi' => $k['dogum'],
        'DogumGunu' => $dogumParcali ? $dogumParcali['gun'] : null,
        'DogumAyi' => $dogumParcali ? ayAdi($dogumParcali['ay']) : null,
        'DogumYili' => $dogumParcali ? $dogumParcali['yil'] : null,
        'DogumGunAdi' => $dogumParcali ? gunAdi($dogumParcali['gun'], $dogumParcali['ay'], $dogumParcali['yil']) : null,
        'Yas' => $yas,
        'Burc' => $burc,
        'Il' => $k['il'],
        'Ilce' => $k['ilce'],
        'AnneAdi' => $k['anne_ad'],
        'BabaAdi' => $k['baba_ad']
    ));

    if (!empty($item)) $sonucListe[] = $item;
}

$sure = round(microtime(true) - $baslangic, 2);

// Sonuc
$sonuc = array(
    'ok' => true,
    'arama' => temizle(array(
        'Ad' => $ad,
        'Soyad' => $soyad,
        'DogumTarihi' => $dogum,
        'Il' => $il
    )),
    'toplam' => count($sonucListe),
    'sure' => $sure,
    'sonuclar' => $sonucListe,
    'script_sahibi' => '@fbxnext',
    'instagram' => '@logsuzlarpanel',
    'tiktok' => '@logsuzlar.inc'
);

$sonuc = temizle($sonuc);

echo json_encode($sonuc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);