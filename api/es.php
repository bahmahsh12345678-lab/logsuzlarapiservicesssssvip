<?php
// ============================================================
// EŞ SORGU API - TC girer, eşini bulur
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
        'ZEHRA','ZÜHAL','ŞÜKRAN','ZELİHA','NESRİN','SİBEL','MELİHA','MELİKE','KADRİYE',
        'HAVVA','HÜLYA','HACER','ZEYNEP','ŞULE','SEMA','SELİN','SEVİL','SİNEM','TUBA');
    $p = explode(' ', trim($ad));
    if (in_array($p[0], $kadin)) return 'K';
    if (preg_match('/(YE|NA|LE|SE|GUL|NUR|HAN)$/', $p[0])) return 'K';
    return 'E';
}

// ============================================================
// 1) KÖK KİŞİ BİLGİSİ + ÇOCUKLARINI BUL
// ============================================================
$kokSulale = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $tc);
$kokAile   = cek('https://apiv2.ajaxsystems.fun/aile.php?tc=' . $tc);

$tumKisiler = array(); // tc => bilgi

// Sülale formatı
if ($kokSulale && isset($kokSulale['data']) && is_array($kokSulale['data'])) {
    foreach ($kokSulale['data'] as $k) {
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
if ($kokAile && is_array($kokAile)) {
    foreach ($kokAile as $k) {
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

// Kök kişi
$kokBilgi = isset($tumKisiler[$tc]) ? $tumKisiler[$tc] : null;
if (!$kokBilgi) {
    echo json_encode(array('ok' => false, 'hata' => 'Kişi bulunamadı'));
    exit;
}

// ============================================================
// 2) KÖKÜN ÇOCUKLARINI BUL
// ============================================================
$cocuklar = array(); // tc => bilgi

foreach ($tumKisiler as $ktc => $k) {
    if ($ktc === $tc) continue;

    $anneTc = isset($k['anne_tc']) ? $k['anne_tc'] : null;
    $babaTc = isset($k['baba_tc']) ? $k['baba_tc'] : null;

    if ($anneTc === $tc || $babaTc === $tc) {
        $cocuklar[$ktc] = $k;
    }
}

// ============================================================
// 3) ÇOCUKLARIN DİĞER EBEVEYNİ = KÖKÜN EŞİ
// Çocuğun annesi kök değilse → anne = eş
// Çocuğun babası kök değilse → baba = eş
// ============================================================
$esAdaylari = array(); // tc => ['bilgi' => ..., 'cocuk_sayisi' => N]

foreach ($cocuklar as $ctc => $cocuk) {
    $anneTc = isset($cocuk['anne_tc']) ? $cocuk['anne_tc'] : null;
    $babaTc = isset($cocuk['baba_tc']) ? $cocuk['baba_tc'] : null;

    // Anne kök değilse, anne = eş
    if ($anneTc && $anneTc !== $tc) {
        if (!isset($esAdaylari[$anneTc])) {
            $esAdaylari[$anneTc] = array('bilgi' => null, 'cocuk_sayisi' => 0);
        }
        $esAdaylari[$anneTc]['cocuk_sayisi']++;
    }

    // Baba kök değilse, baba = eş
    if ($babaTc && $babaTc !== $tc) {
        if (!isset($esAdaylari[$babaTc])) {
            $esAdaylari[$babaTc] = array('bilgi' => null, 'cocuk_sayisi' => 0);
        }
        $esAdaylari[$babaTc]['cocuk_sayisi']++;
    }
}

// ============================================================
// 4) EŞ ADAYLARINI LİSTEDEN BUL
// ============================================================
$liste = array();

foreach ($esAdaylari as $etc => $esBilgi) {
    // Sülale/aile listesinden bul
    if (isset($tumKisiler[$etc])) {
        $e = $tumKisiler[$etc];
    } else {
        // Listede yok → ayrı sorgu yap
        $esVeri = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $etc);
        $e = null;
        if ($esVeri && isset($esVeri['data'])) {
            foreach ($esVeri['data'] as $x) {
                if (isset($x['TC']) && $x['TC'] == $etc) {
                    $e = array(
                        'tc' => $etc,
                        'ad' => isset($x['AD']) ? $x['AD'] : null,
                        'soyad' => isset($x['SOYAD']) ? $x['SOYAD'] : null,
                        'dogum' => isset($x['DOGUM_YILI']) ? $x['DOGUM_YILI'] : null,
                        'yas' => isset($x['YAS']) ? $x['YAS'] : null,
                        'burc' => isset($x['BURC']) ? $x['BURC'] : null,
                        'il' => isset($x['MEMLEKETIL']) ? $x['MEMLEKETIL'] : null,
                        'ilce' => isset($x['MEMLEKETILCE']) ? $x['MEMLEKETILCE'] : null
                    );
                    break;
                }
            }
        }
    }

    if (!$e) continue;

    $ad = isset($e['ad']) ? $e['ad'] : '';
    $cins = cinsiyet($ad);

    $item = temizle(array(
        'TC' => $etc,
        'Ad' => $ad,
        'Soyad' => isset($e['soyad']) ? $e['soyad'] : null,
        'Cinsiyet' => ($cins === 'K') ? 'Kadın' : 'Erkek',
        'Yakinlik' => ($cins === 'K') ? 'Eşi (Karısı)' : 'Eşi (Kocası)',
        'DogumTarihi' => isset($e['dogum']) ? $e['dogum'] : null,
        'Yas' => isset($e['yas']) ? $e['yas'] : null,
        'Burc' => isset($e['burc']) ? $e['burc'] : null,
        'Il' => isset($e['il']) ? $e['il'] : null,
        'Ilce' => isset($e['ilce']) ? $e['ilce'] : null,
        'OrtakCocukSayisi' => $esBilgi['cocuk_sayisi']
    ));

    if (!empty($item)) $liste[] = $item;
}

// Çocuk sayısına göre sırala (en çok ortak çocuk = en güçlü aday)
usort($liste, function($a, $b) {
    $ca = isset($a['OrtakCocukSayisi']) ? $a['OrtakCocukSayisi'] : 0;
    $cb = isset($b['OrtakCocukSayisi']) ? $b['OrtakCocukSayisi'] : 0;
    return $cb - $ca;
});

// ============================================================
// SONUÇ
// ============================================================
echo json_encode(array(
    'ok' => true,
    'kok_kisi' => temizle(array(
        'TC' => $tc,
        'Ad' => isset($kokBilgi['ad']) ? $kokBilgi['ad'] : null,
        'Soyad' => isset($kokBilgi['soyad']) ? $kokBilgi['soyad'] : null,
        'Yas' => isset($kokBilgi['yas']) ? $kokBilgi['yas'] : null
    )),
    'toplam_es' => count($liste),
    'esler' => $liste,
    'script_sahibi' => '@fbxnext',
    'instagram' => '@logsuzlarpanel',
    'tiktok' => '@logsuzlar.inc'
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);