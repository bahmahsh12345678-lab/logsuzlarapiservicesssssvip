<?php
// ============================================================
// GSM SULALE SORGU API - Telefon numarasindan sulale bulur
// Script Owner: @fbxnext
// Instagram: @logsuzlarpanel
// TikTok: @logsuzlar.inc
// ============================================================
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(60);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

// GSM al (5xxxxxxxxx veya 05xxxxxxxxx veya +905xxxxxxxxx)
$gsm = isset($_GET['gsm']) ? preg_replace('/[^0-9]/', '', $_GET['gsm']) : '';

// Normalize: 10 hane olmali (5xxxxxxxxx)
if (strlen($gsm) === 12 && substr($gsm, 0, 2) === '90') {
    $gsm = substr($gsm, 2);
}
if (strlen($gsm) === 11 && substr($gsm, 0, 1) === '0') {
    $gsm = substr($gsm, 1);
}

if (strlen($gsm) !== 10 || substr($gsm, 0, 1) !== '5') {
    echo json_encode(array('ok' => false, 'hata' => 'Gecerli 10 haneli GSM girin (5xxxxxxxxx)'));
    exit;
}

$MAX_KISI = 500;

// ============================================================
// TEK ISTEK (CACHE)
// ============================================================
function cek($url) {
    static $cache = array();
    if (isset($cache[$url])) return $cache[$url];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
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
    $ad = isset($k['Isim']) ? $k['Isim'] : (isset($k['AD']) ? $k['AD'] : null);
    $soyad = isset($k['Soyisim']) ? $k['Soyisim'] : (isset($k['SOYAD']) ? $k['SOYAD'] : null);
    $dogum = isset($k['DogumTarihi']) ? $k['DogumTarihi'] : (isset($k['DOGUM_YILI']) ? $k['DOGUM_YILI'] : null);
    $yas = isset($k['YAS']) ? $k['YAS'] : null;
    $il = isset($k['NufusIl']) ? $k['NufusIl'] : (isset($k['MEMLEKETIL']) ? $k['MEMLEKETIL'] : null);
    $ilce = isset($k['NufusIlce']) ? $k['NufusIlce'] : (isset($k['MEMLEKETILCE']) ? $k['MEMLEKETILCE'] : null);
    $anneAd = isset($k['AnneIsim']) ? $k['AnneIsim'] : (isset($k['ANNEADI']) ? $k['ANNEADI'] : null);
    $anneTc = isset($k['AnneKimlikNo']) ? $k['AnneKimlikNo'] : (isset($k['ANNETC']) ? $k['ANNETC'] : null);
    $babaAd = isset($k['BabaIsim']) ? $k['BabaIsim'] : (isset($k['BABAADI']) ? $k['BABAADI'] : null);
    $babaTc = isset($k['BabaKimlikNo']) ? $k['BabaKimlikNo'] : (isset($k['BABATC']) ? $k['BABATC'] : null);
    $yakinlik = isset($k['Yakınlık']) ? $k['Yakınlık'] : (isset($k['YAKINLIK']) ? $k['YAKINLIK'] : null);

    if (!$tc && !$ad) return null;

    return array(
        'tc' => $tc, 'ad' => $ad, 'soyad' => $soyad, 'dogum' => $dogum, 'yas' => $yas,
        'il' => $il, 'ilce' => $ilce,
        'anne_ad' => $anneAd, 'anne_tc' => $anneTc,
        'baba_ad' => $babaAd, 'baba_tc' => $babaTc,
        'yakinlik' => $yakinlik
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
// CINSIYET
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
// YAS HESAPLA
// ============================================================
function yasHesapla($dogum) {
    if (!$dogum) return null;
    if (preg_match('/(\d{1,2})\.(\d{1,2})\.(\d{4})/', $dogum, $m)) {
        $dgun = (int)$m[1]; $day = (int)$m[2]; $dyil = (int)$m[3];
    } elseif (preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $dogum, $m)) {
        $dgun = (int)$m[3]; $day = (int)$m[2]; $dyil = (int)$m[1];
    } else return null;

    $bgun = (int)date('j'); $bay = (int)date('n'); $byil = (int)date('Y');
    $yas = $byil - $dyil;
    if ($bay < $day || ($bay == $day && $bgun < $dgun)) $yas--;
    return $yas;
}

// ============================================================
// YAKINLIK BUL
// ============================================================
function yakinlikBul($tc, $k, $kokTc, $kokBilgi, $kisiHaritasi, $cocuklar, $kardesler) {
    if ($tc === $kokTc) return 'Kendisi';

    $cins = cinsiyet($k['ad']);
    $kokSoyad = strtoupper((string)$kokBilgi['soyad']);
    $kSoyad = strtoupper((string)$k['soyad']);
    $kokAnneTc = isset($kokBilgi['anne_tc']) ? $kokBilgi['anne_tc'] : null;
    $kokBabaTc = isset($kokBilgi['baba_tc']) ? $kokBilgi['baba_tc'] : null;

    if ($tc === $kokAnneTc) return 'Annesi';
    if ($tc === $kokBabaTc) return 'Babasi';

    // Kardeş
    if (isset($kardesler[$kokTc]) && in_array($tc, $kardesler[$kokTc])) {
        return $cins === 'K' ? 'Kiz Kardesi' : 'Erkek Kardesi';
    }

    // Çocuk
    if (isset($cocuklar[$kokTc]) && in_array($tc, $cocuklar[$kokTc])) {
        return $cins === 'K' ? 'Kizi' : 'Oglu';
    }

    // Amca / Hala
    if ($kokBabaTc && isset($kardesler[$kokBabaTc]) && in_array($tc, $kardesler[$kokBabaTc])) {
        return $cins === 'K' ? 'Halasi' : 'Amcasi';
    }

    // Dayı / Teyze
    if ($kokAnneTc && isset($kardesler[$kokAnneTc]) && in_array($tc, $kardesler[$kokAnneTc])) {
        return $cins === 'K' ? 'Teyzesi' : 'Dayisi';
    }

    // Kuzen
    if (isset($kardesler[$kokTc])) {
        $kisiAnneTc = isset($k['anne_tc']) ? $k['anne_tc'] : null;
        $kisiBabaTc = isset($k['baba_tc']) ? $k['baba_tc'] : null;
        foreach ($kardesler[$kokTc] as $ktc) {
            if ($kisiAnneTc === $ktc || $kisiBabaTc === $ktc) {
                return $cins === 'K' ? 'Kuzeni (Kiz)' : 'Kuzeni (Erkek)';
            }
        }
    }

    // Yeğen
    if (isset($kardesler[$kokTc])) {
        $kisiAnneTc = isset($k['anne_tc']) ? $k['anne_tc'] : null;
        $kisiBabaTc = isset($k['baba_tc']) ? $k['baba_tc'] : null;
        foreach ($kardesler[$kokTc] as $ktc) {
            if ($kisiAnneTc === $ktc || $kisiBabaTc === $ktc) {
                return $cins === 'K' ? 'Yegeni (Kiz)' : 'Yegeni (Erkek)';
            }
        }
    }

    // Aynı soyad = akraba
    if ($kokSoyad && $kSoyad === $kokSoyad) {
        return $cins === 'K' ? 'Akrabasi (Kadin)' : 'Akrabasi (Erkek)';
    }

    return null;
}

// ============================================================
// ANA SORGU
// ============================================================
$baslangic = microtime(true);
$sayac = 0;

// 1) GSM -> TC
$gsmVeri = cek('https://apiv2.ajaxsystems.fun/gsmtc.php?gsm=' . $gsm);

if (!$gsmVeri) {
    echo json_encode(array('ok' => false, 'hata' => 'GSM bulunamadi'), JSON_UNESCAPED_UNICODE);
    exit;
}

// TC'yi bul
$tc = null;
$gsmBilgi = null;

if (is_array($gsmVeri)) {
    // Direkt dizi
    if (isset($gsmVeri[0]) && is_array($gsmVeri[0])) {
        foreach ($gsmVeri as $k) {
            $ktc = isset($k['TC']) ? $k['TC'] : (isset($k['KimlikNo']) ? $k['KimlikNo'] : null);
            if ($ktc && preg_match('/^[0-9]{11}$/', $ktc)) {
                $tc = $ktc;
                $gsmBilgi = $k;
                break;
            }
        }
    }
    // Alt diziler
    if (!$tc) {
        foreach (array('data','veri','sonuc','result') as $alan) {
            if (isset($gsmVeri[$alan]) && is_array($gsmVeri[$alan])) {
                foreach ($gsmVeri[$alan] as $k) {
                    if (is_array($k)) {
                        $ktc = isset($k['TC']) ? $k['TC'] : (isset($k['KimlikNo']) ? $k['KimlikNo'] : null);
                        if ($ktc && preg_match('/^[0-9]{11}$/', $ktc)) {
                            $tc = $ktc;
                            $gsmBilgi = $k;
                            break 2;
                        }
                    }
                }
            }
        }
    }
    // Direkt alanlardan
    if (!$tc && isset($gsmVeri['TC'])) $tc = $gsmVeri['TC'];
    if (!$tc && isset($gsmVeri['KimlikNo'])) $tc = $gsmVeri['KimlikNo'];
}

if (!$tc || strlen($tc) !== 11) {
    echo json_encode(array('ok' => false, 'hata' => 'Bu GSM icin TC bulunamadi', 'gsm' => $gsm), JSON_UNESCAPED_UNICODE);
    exit;
}

// 2) TC'nin sülalesini çek
$ziyaret = array($tc => true);
$kisiHaritasi = array();

$kokVeri = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $tc);
if (!$kokVeri) $kokVeri = cek('https://apiv2.ajaxsystems.fun/aile.php?tc=' . $tc);

$kokListe = listeCikar($kokVeri);
$kokBilgi = null;
foreach ($kokListe as $k) {
    if ($k['tc'] === $tc) { $kokBilgi = $k; break; }
}
if (!$kokBilgi && !empty($kokListe)) $kokBilgi = $kokListe[0];
if (!$kokBilgi) {
    echo json_encode(array('ok' => false, 'hata' => 'Sulale bilgisi bulunamadi', 'tc' => $tc), JSON_UNESCAPED_UNICODE);
    exit;
}
$kokBilgi['tc'] = $tc;
$kisiHaritasi[$tc] = $kokBilgi;
$sayac++;

foreach ($kokListe as $k) {
    if ($sayac >= $MAX_KISI) break;
    if (!$k['tc'] || isset($ziyaret[$k['tc']])) continue;
    $ziyaret[$k['tc']] = true;
    $kisiHaritasi[$k['tc']] = $k;
    $sayac++;
}

// Anne tarafı
$anneTc = isset($kokBilgi['anne_tc']) ? $kokBilgi['anne_tc'] : null;
if ($anneTc && !isset($ziyaret[$anneTc])) {
    $anneVeri = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $anneTc);
    if ($anneVeri) {
        foreach (listeCikar($anneVeri) as $k) {
            if ($sayac >= $MAX_KISI) break;
            if (!$k['tc'] || isset($ziyaret[$k['tc']])) continue;
            $ziyaret[$k['tc']] = true;
            $kisiHaritasi[$k['tc']] = $k;
            $sayac++;
        }
    }
}

// Baba tarafı
$babaTc = isset($kokBilgi['baba_tc']) ? $kokBilgi['baba_tc'] : null;
if ($babaTc && !isset($ziyaret[$babaTc])) {
    $babaVeri = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $babaTc);
    if ($babaVeri) {
        foreach (listeCikar($babaVeri) as $k) {
            if ($sayac >= $MAX_KISI) break;
            if (!$k['tc'] || isset($ziyaret[$k['tc']])) continue;
            $ziyaret[$k['tc']] = true;
            $kisiHaritasi[$k['tc']] = $k;
            $sayac++;
        }
    }
}

// 3) Kardeş / Çocuk haritası
$cocuklar = array();
$kardesler = array();

foreach ($kisiHaritasi as $ktc => $k) {
    if (!empty($k['anne_tc'])) {
        if (!isset($cocuklar[$k['anne_tc']])) $cocuklar[$k['anne_tc']] = array();
        $cocuklar[$k['anne_tc']][] = $ktc;
    }
    if (!empty($k['baba_tc'])) {
        if (!isset($cocuklar[$k['baba_tc']])) $cocuklar[$k['baba_tc']] = array();
        $cocuklar[$k['baba_tc']][] = $ktc;
    }
}

foreach ($kisiHaritasi as $ktc1 => $k1) {
    $a1 = isset($k1['anne_tc']) ? $k1['anne_tc'] : null;
    $b1 = isset($k1['baba_tc']) ? $k1['baba_tc'] : null;
    if (!$a1 && !$b1) continue;

    foreach ($kisiHaritasi as $ktc2 => $k2) {
        if ($ktc1 === $ktc2) continue;
        $a2 = isset($k2['anne_tc']) ? $k2['anne_tc'] : null;
        $b2 = isset($k2['baba_tc']) ? $k2['baba_tc'] : null;

        $ayni = false;
        if ($a1 && $a2 && $a1 === $a2) $ayni = true;
        if ($b1 && $b2 && $b1 === $b2) $ayni = true;

        if ($ayni) {
            if (!isset($kardesler[$ktc1])) $kardesler[$ktc1] = array();
            if (!in_array($ktc2, $kardesler[$ktc1])) $kardesler[$ktc1][] = $ktc2;
        }
    }
}

// 4) Liste oluştur
$liste = array();
foreach ($kisiHaritasi as $ktc => $k) {
    if ($ktc === $tc) continue;

    $yakinlik = yakinlikBul($ktc, $k, $tc, $kokBilgi, $kisiHaritasi, $cocuklar, $kardesler);
    if (!$yakinlik) continue;

    $cins = cinsiyet($k['ad']);
    $yas = isset($k['yas']) ? $k['yas'] : yasHesapla($k['dogum']);

    $item = temizle(array(
        'TC' => $ktc,
        'Ad' => $k['ad'],
        'Soyad' => $k['soyad'],
        'Cinsiyet' => ($cins === 'K') ? 'Kadin' : 'Erkek',
        'Yakinlik' => $yakinlik,
        'DogumTarihi' => $k['dogum'],
        'Yas' => $yas,
        'Il' => $k['il'],
        'Ilce' => $k['ilce'],
        'AnneAdi' => $k['anne_ad'],
        'BabaAdi' => $k['baba_ad']
    ));

    if (!empty($item)) $liste[] = $item;
}

// Yakınlığa göre sırala
$sirala = array(
    'Kendisi' => 0, 'Annesi' => 1, 'Babasi' => 2,
    'Kiz Kardesi' => 3, 'Erkek Kardesi' => 3,
    'Kizi' => 4, 'Oglu' => 4,
    'Amcasi' => 5, 'Halasi' => 5, 'Dayisi' => 5, 'Teyzesi' => 5,
    'Kuzeni (Kiz)' => 6, 'Kuzeni (Erkek)' => 6,
    'Yegeni (Kiz)' => 7, 'Yegeni (Erkek)' => 7
);
usort($liste, function($a, $b) use ($sirala) {
    $ya = isset($a['Yakinlik']) ? $a['Yakinlik'] : '';
    $yb = isset($b['Yakinlik']) ? $b['Yakinlik'] : '';
    $sa = isset($sirala[$ya]) ? $sirala[$ya] : 99;
    $sb = isset($sirala[$yb]) ? $sirala[$yb] : 99;
    if ($sa === $sb) {
        return strcmp(isset($a['Ad']) ? $a['Ad'] : '', isset($b['Ad']) ? $b['Ad'] : '');
    }
    return $sa - $sb;
});

$sure = round(microtime(true) - $baslangic, 2);

// 5) Sonuç
$sonuc = array(
    'ok' => true,
    'gsm' => $gsm,
    'kok_kisi' => temizle(array(
        'TC' => $tc,
        'Ad' => $kokBilgi['ad'],
        'Soyad' => $kokBilgi['soyad'],
        'DogumTarihi' => $kokBilgi['dogum'],
        'Yas' => isset($kokBilgi['yas']) ? $kokBilgi['yas'] : yasHesapla($kokBilgi['dogum']),
        'Il' => $kokBilgi['il'],
        'Ilce' => $kokBilgi['ilce']
    )),
    'toplam_yakin' => count($liste),
    'sure' => $sure,
    'liste' => $liste,
    'script_sahibi' => '@fbxnext',
    'instagram' => '@logsuzlarpanel',
    'tiktok' => '@logsuzlar.inc'
);

$sonuc = temizle($sonuc);

echo json_encode($sonuc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);