<?php
// ============================================================
// DETAYLI TC SORGU API - Kisi + Adres + Isyeri
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
    $ad = isset($k['Isim']) ? $k['Isim'] : (isset($k['AD']) ? $k['AD'] : null);
    $soyad = isset($k['Soyisim']) ? $k['Soyisim'] : (isset($k['SOYAD']) ? $k['SOYAD'] : null);
    $dogum = isset($k['DogumTarihi']) ? $k['DogumTarihi'] : (isset($k['DOGUM_YILI']) ? $k['DOGUM_YILI'] : null);
    $yas = isset($k['YAS']) ? $k['YAS'] : null;
    $burc = isset($k['BURC']) ? $k['BURC'] : null;
    $il = isset($k['NufusIl']) ? $k['NufusIl'] : (isset($k['MEMLEKETIL']) ? $k['MEMLEKETIL'] : null);
    $ilce = isset($k['NufusIlce']) ? $k['NufusIlce'] : (isset($k['MEMLEKETILCE']) ? $k['MEMLEKETILCE'] : null);
    $anneAd = isset($k['AnneIsim']) ? $k['AnneIsim'] : (isset($k['ANNEADI']) ? $k['ANNEADI'] : null);
    $anneTc = isset($k['AnneKimlikNo']) ? $k['AnneKimlikNo'] : (isset($k['ANNETC']) ? $k['ANNETC'] : null);
    $babaAd = isset($k['BabaIsim']) ? $k['BabaIsim'] : (isset($k['BABAADI']) ? $k['BABAADI'] : null);
    $babaTc = isset($k['BabaKimlikNo']) ? $k['BabaKimlikNo'] : (isset($k['BABATC']) ? $k['BABATC'] : null);
    $uyruk = isset($k['Uyruk']) ? $k['Uyruk'] : (isset($k['UYRUK']) ? $k['UYRUK'] : null);

    if (!$tc && !$ad) return null;

    return array(
        'tc' => $tc, 'ad' => $ad, 'soyad' => $soyad, 'dogum' => $dogum, 'yas' => $yas,
        'burc' => $burc, 'il' => $il, 'ilce' => $ilce,
        'anne_ad' => $anneAd, 'anne_tc' => $anneTc,
        'baba_ad' => $babaAd, 'baba_tc' => $babaTc,
        'uyruk' => $uyruk
    );
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
        'LEYLA','SEVGI','DUYGU','EDA','BETUL','MELTEM','PINAR','CEREN','BAHAR','IREM',
        'ZEHRA','ZUHAL','SUKRAN','ZELIHA','NESRIN','SIBEL','MELIHA','MELIKE','KADRIYE',
        'EMEL','ASLI','EBRU','FIGEN','FILIZ','GONUL','HALE','INCI','LALE','MINE');
    $p = explode(' ', trim($ad));
    if (in_array($p[0], $kadin)) return 'K';
    if (preg_match('/(YE|NA|LE|SE|GUL|NUR|HAN|CAN|SU|AY|EL)$/', $p[0])) return 'K';
    return 'E';
}

// ============================================================
// TARIH
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

function yasGrubu($yas) {
    if ($yas === null) return null;
    if ($yas < 1) return 'Bebek';
    if ($yas < 3) return 'Yeni yurumeye baslayan';
    if ($yas < 13) return 'Cocuk';
    if ($yas < 20) return 'Genc';
    if ($yas < 40) return 'Genc Yetiskin';
    if ($yas < 60) return 'Orta Yas';
    if ($yas < 80) return 'Yasli';
    return 'Ileri Yasli';
}

// ============================================================
// IL KODU -> IL ADI
// ============================================================
$iller = array(
    '01'=>'ADANA','02'=>'ADIYAMAN','03'=>'AFYONKARAHISAR','04'=>'AGRI','05'=>'AMASYA',
    '06'=>'ANKARA','07'=>'ANTALYA','08'=>'ARTVIN','09'=>'AYDIN','10'=>'BALIKESIR',
    '11'=>'BILECIK','12'=>'BINGOL','13'=>'BITLIS','14'=>'BOLU','15'=>'BURDUR',
    '16'=>'BURSA','17'=>'CANAKKALE','18'=>'CANKIRI','19'=>'CORUM','20'=>'DENIZLI',
    '21'=>'DIYARBAKIR','22'=>'EDIRNE','23'=>'ELAZIG','24'=>'ERZINCAN','25'=>'ERZURUM',
    '26'=>'ESKISEHIR','27'=>'GAZIANTEP','28'=>'GIRESUN','29'=>'GUMUSHANE','30'=>'HAKKARI',
    '31'=>'HATAY','32'=>'ISPARTA','33'=>'MERSIN','34'=>'ISTANBUL','35'=>'IZMIR',
    '36'=>'KARS','37'=>'KASTAMONU','38'=>'KAYSERI','39'=>'KIRKLARELI','40'=>'KIRSEHIR',
    '41'=>'KOCAELI','42'=>'KONYA','43'=>'KUTAHYA','44'=>'MALATYA','45'=>'MANISA',
    '46'=>'KAHRAMANMARAS','47'=>'MARDIN','48'=>'MUGLA','49'=>'MUS','50'=>'NEVSEHIR',
    '51'=>'NIGDE','52'=>'ORDU','53'=>'RIZE','54'=>'SAKARYA','55'=>'SAMSUN',
    '56'=>'SIIRT','57'=>'SINOP','58'=>'SIVAS','59'=>'TEKIRDAG','60'=>'TOKAT',
    '61'=>'TRABZON','62'=>'TUNCELI','63'=>'SANLIURFA','64'=>'USAK','65'=>'VAN',
    '66'=>'YOZGAT','67'=>'ZONGULDAK','68'=>'AKSARAY','69'=>'BAYBURT','70'=>'KARAMAN',
    '71'=>'KIRIKKALE','72'=>'BATMAN','73'=>'SIRNAK','74'=>'BARTIN','75'=>'ARDAHAN',
    '76'=>'IGDIR','77'=>'YALOVA','78'=>'KARABUK','79'=>'KILIS','80'=>'OSMANIYE',
    '81'=>'DUZCE'
);

// ============================================================
// ANA SORGU
// ============================================================
$baslangic = microtime(true);

// 1) KISI BILGISI
$kisi = null;

$v1 = cek('https://apiv2.ajaxsystems.fun/sulale.php?tc=' . $tc);
if ($v1 && isset($v1['data']) && is_array($v1['data'])) {
    foreach ($v1['data'] as $k) {
        if (isset($k['TC']) && $k['TC'] == $tc) {
            $kisi = kisiCikar($k);
            break;
        }
    }
}

if (!$kisi) {
    $v2 = cek('https://apiv2.ajaxsystems.fun/aile.php?tc=' . $tc);
    if ($v2 && is_array($v2)) {
        foreach ($v2 as $k) {
            if (isset($k['KimlikNo']) && $k['KimlikNo'] == $tc) {
                $kisi = kisiCikar($k);
                break;
            }
        }
    }
}

if (!$kisi) {
    $v3 = cek('https://apiv2.ajaxsystems.fun/tc.php?tc=' . $tc);
    if ($v3) $kisi = kisiCikar($v3);
}

if (!$kisi) {
    echo json_encode(array('ok' => false, 'hata' => 'Kisi bulunamadi', 'tc' => $tc));
    exit;
}

// 2) ADRES BILGISI
$adres = null;
$adresVeri = cek('https://apiv2.ajaxsystems.fun/adres.php?tc=' . $tc);
if ($adresVeri && isset($adresVeri['data'])) {
    $ad = $adresVeri['data'];
    $ikametgah = isset($ad['Ikametgah']) ? trim($ad['Ikametgah']) : '';

    $mahalle = null; $sokak = null; $binaNo = null; $daireNo = null; $ilceA = null; $ilKodu = null;

    if (preg_match('/([A-ZÇĞİÖŞÜa-zçğıöşü\s]+)\s+MAH\./u', $ikametgah, $m)) $mahalle = trim($m[1]);
    if (preg_match('/MAH\.\s+([A-ZÇĞİÖŞÜa-zçğıöşü\s]+)\s+SK\./u', $ikametgah, $m)) $sokak = trim($m[1]);

    $parcalar = preg_split('/\s+/', trim($ikametgah));
    $toplam = count($parcalar);
    if ($toplam >= 2) {
        $ilKodu = $parcalar[$toplam - 1];
        $ilceA = $parcalar[$toplam - 2];
    }
    if ($toplam >= 4) {
        $daireNo = $parcalar[$toplam - 3];
        $binaNo = $parcalar[$toplam - 4];
    }

    $ilAdi = isset($iller[$ilKodu]) ? $iller[$ilKodu] : null;

    $adres = temizle(array(
        'KimlikNo' => isset($ad['KimlikNo']) ? $ad['KimlikNo'] : null,
        'AdSoyad' => isset($ad['AdSoyad']) ? $ad['AdSoyad'] : null,
        'DogumYeri' => isset($ad['DogumYeri']) ? $ad['DogumYeri'] : null,
        'VergiNumarasi' => isset($ad['VergiNumarasi']) ? $ad['VergiNumarasi'] : null,
        'Ikametgah' => $ikametgah,
        'Mahalle' => $mahalle,
        'Sokak' => $sokak,
        'BinaNo' => $binaNo,
        'DaireNo' => $daireNo,
        'Ilce' => $ilceA,
        'Il' => $ilAdi,
        'IlKodu' => $ilKodu
    ));
}

// 3) ISYERI BILGISI
$isyeri = null;
$isyeriVeri = cek('https://apiv2.ajaxsystems.fun/isyeri.php?tc=' . $tc);
if ($isyeriVeri && isset($isyeriVeri['data']) && is_array($isyeriVeri['data'])) {
    $isyeriListe = array();
    foreach ($isyeriVeri['data'] as $i) {
        $isyeriListe[] = temizle(array(
            'IsyeriUnvani' => isset($i['isyeriUnvani']) ? $i['isyeriUnvani'] : null,
            'IsyeriSgkSicilNo' => isset($i['isyeriSgkSicilNo']) ? $i['isyeriSgkSicilNo'] : null,
            'IsyeriTehlikeSinifi' => isset($i['isyeriTehlikeSinifi']) ? $i['isyeriTehlikeSinifi'] : null,
            'IsyeriNaceKodu' => isset($i['isyeriNaceKodu']) ? $i['isyeriNaceKodu'] : null,
            'IsyeriSektoru' => isset($i['isyeriSektoru']) ? $i['isyeriSektoru'] : null,
            'IseGirisTarihi' => isset($i['iseGirisTarihi']) ? $i['iseGirisTarihi'] : null,
            'CalismaDurumu' => isset($i['calismaDurumu']) ? $i['calismaDurumu'] : null
        ));
    }
    if (!empty($isyeriListe)) $isyeri = $isyeriListe;
}

// ============================================================
// KOK KISI DETAYLI
// ============================================================
$dogumParcali = tarihParcala($kisi['dogum']);
$cins = cinsiyet($kisi['ad']);
$yas = isset($kisi['yas']) ? $kisi['yas'] : yasHesapla($kisi['dogum']);
$burc = $dogumParcali ? burcHesapla($dogumParcali['gun'], $dogumParcali['ay']) : (isset($kisi['burc']) ? $kisi['burc'] : null);

$kisiBilgi = temizle(array(
    'TC' => $kisi['tc'],
    'Ad' => $kisi['ad'],
    'Soyad' => $kisi['soyad'],
    'TamAd' => trim($kisi['ad'] . ' ' . $kisi['soyad']),
    'Cinsiyet' => ($cins === 'K') ? 'Kadin' : 'Erkek',
    'Uyruk' => $kisi['uyruk'],
    'DogumTarihi' => $kisi['dogum'],
    'DogumGunu' => $dogumParcali ? $dogumParcali['gun'] : null,
    'DogumAyi' => $dogumParcali ? ayAdi($dogumParcali['ay']) : null,
    'DogumYili' => $dogumParcali ? $dogumParcali['yil'] : null,
    'DogumGunAdi' => $dogumParcali ? gunAdi($dogumParcali['gun'], $dogumParcali['ay'], $dogumParcali['yil']) : null,
    'Yas' => $yas,
    'YasGrubu' => yasGrubu($yas),
    'Burc' => $burc,
    'Il' => $kisi['il'],
    'Ilce' => $kisi['ilce'],
    'AnneAdi' => $kisi['anne_ad'],
    'AnneTC' => $kisi['anne_tc'],
    'BabaAdi' => $kisi['baba_ad'],
    'BabaTC' => $kisi['baba_tc']
));

// ============================================================
// SONUC
// ============================================================
$sure = round(microtime(true) - $baslangic, 2);

$sonuc = array(
    'ok' => true,
    'tc' => $tc,
    'sure' => $sure,
    'kisi' => $kisiBilgi,
    'adres' => $adres,
    'isyeri' => $isyeri,
    'script_sahibi' => '@fbxnext',
    'instagram' => '@logsuzlarpanel',
    'tiktok' => '@logsuzlar.inc'
);

$sonuc = temizle($sonuc);

echo json_encode($sonuc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);