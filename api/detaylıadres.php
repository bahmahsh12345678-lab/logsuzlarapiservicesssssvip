<?php
// ============================================================
// ADRES SORGU API - TC girer, İÇ KAPIYA KADAR detaylı adres
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
// API İSTEĞİ
// ============================================================
function cek($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
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
// ANA SORGU
// ============================================================
$veri = cek('https://apiv2.ajaxsystems.fun/adres.php?tc=' . $tc);

if (!$veri || !isset($veri['success']) || !$veri['success']) {
    echo json_encode(array('ok' => false, 'hata' => 'Adres bilgisi bulunamadı'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$d = isset($veri['data']) ? $veri['data'] : array();
$ham = isset($d['Ikametgah']) ? trim($d['Ikametgah']) : '';

// ============================================================
// ADRES PARÇALAMA (DETAYLI)
// ============================================================
$mahalle = null;
$sokak = null;
$cadde = null;
$blok = null;
$binaNo = null;
$kat = null;
$daireNo = null;
$icKapi = null;
$ilce = null;
$ilKodu = null;
$postaKodu = null;

$adres = $ham;

// 1) MAHALLE
if (preg_match('/([A-ZÇĞİÖŞÜa-zçğıöşü0-9\s\.]+?)\s+MAH\./u', $adres, $m)) {
    $mahalle = trim($m[1]);
}

// 2) SOKAK / CADDE
if (preg_match('/MAH\.\s+([A-ZÇĞİÖŞÜa-zçğıöşü0-9\s\.]+?)\s+(SK\.|SOK\.|SOKAĞI)/u', $adres, $m)) {
    $sokak = trim($m[1]);
} elseif (preg_match('/MAH\.\s+([A-ZÇĞİÖŞÜa-zçğıöşü0-9\s\.]+?)\s+(CAD\.|CD\.|CADDESİ)/u', $adres, $m)) {
    $cadde = trim($m[1]);
} elseif (preg_match('/([A-ZÇĞİÖŞÜa-zçğıöşü0-9\s\.]+?)\s+(SK\.|SOK\.|SOKAĞI)/u', $adres, $m)) {
    $sokak = trim($m[1]);
} elseif (preg_match('/([A-ZÇĞİÖŞÜa-zçğıöşü0-9\s\.]+?)\s+(CAD\.|CD\.|CADDESİ)/u', $adres, $m)) {
    $cadde = trim($m[1]);
}

// 3) BLOK
if (preg_match('/([A-Z])\s*BLOK/u', $adres, $m)) {
    $blok = trim($m[1]);
} elseif (preg_match('/BLOK\s*([A-Z0-9]+)/u', $adres, $m)) {
    $blok = trim($m[1]);
}

// 4) KAT
if (preg_match('/(\d+)\s*\.?\s*KAT/u', $adres, $m)) {
    $kat = trim($m[1]);
} elseif (preg_match('/KAT\s*[:\-]?\s*(\d+)/u', $adres, $m)) {
    $kat = trim($m[1]);
}

// 5) DAİRE / İÇ KAPI
if (preg_match('/DAİRE\s*[:\-]?\s*(\d+)/u', $adres, $m)) {
    $daireNo = trim($m[1]);
} elseif (preg_match('/DAİRESİ\s*[:\-]?\s*(\d+)/u', $adres, $m)) {
    $daireNo = trim($m[1]);
} elseif (preg_match('/İÇ\s*KAPI\s*[:\-]?\s*(\d+)/u', $adres, $m)) {
    $icKapi = trim($m[1]);
} elseif (preg_match('/NO\s*[:\-]?\s*(\d+)\s*[\/\-]\s*(\d+)/u', $adres, $m)) {
    $binaNo = trim($m[1]);
    $daireNo = trim($m[2]);
}

// 6) POSTA KODU
if (preg_match('/\b(\d{5})\b/', $adres, $m)) {
    $postaKodu = trim($m[1]);
}

// 7) SAYISAL PARÇALAR (bina no, daire, ilçe, il kodu)
$parcalar = preg_split('/\s+/', trim($adres));
$toplam = count($parcalar);

if ($toplam >= 2) {
    $ilKodu = $parcalar[$toplam - 1];
    $ilce = $parcalar[$toplam - 2];
}

// Bina no ve daire no bul (eğer regex ile bulunamadıysa)
$sayilar = array();
foreach ($parcalar as $p) {
    if (preg_match('/^\d{1,4}$/', $p)) $sayilar[] = $p;
}
// Son iki sayı = bina, daire (ilçe ve il kodundan önce)
if (!$binaNo && count($sayilar) >= 2) {
    $binaNo = $sayilar[count($sayilar) - 2];
    if (!$daireNo) $daireNo = $sayilar[count($sayilar) - 1];
} elseif (!$binaNo && count($sayilar) == 1) {
    $binaNo = $sayilar[0];
}

// ============================================================
// İL KODU → İL ADI
// ============================================================
$iller = array(
    '01'=>'ADANA','02'=>'ADIYAMAN','03'=>'AFYONKARAHİSAR','04'=>'AĞRI','05'=>'AMASYA',
    '06'=>'ANKARA','07'=>'ANTALYA','08'=>'ARTVİN','09'=>'AYDIN','10'=>'BALIKESİR',
    '11'=>'BİLECİK','12'=>'BİNGÖL','13'=>'BİTLİS','14'=>'BOLU','15'=>'BURDUR',
    '16'=>'BURSA','17'=>'ÇANAKKALE','18'=>'ÇANKIRI','19'=>'ÇORUM','20'=>'DENİZLİ',
    '21'=>'DİYARBAKIR','22'=>'EDİRNE','23'=>'ELAZIĞ','24'=>'ERZİNCAN','25'=>'ERZURUM',
    '26'=>'ESKİŞEHİR','27'=>'GAZİANTEP','28'=>'GİRESUN','29'=>'GÜMÜŞHANE','30'=>'HAKKARİ',
    '31'=>'HATAY','32'=>'ISPARTA','33'=>'MERSİN','34'=>'İSTANBUL','35'=>'İZMİR',
    '36'=>'KARS','37'=>'KASTAMONU','38'=>'KAYSERİ','39'=>'KIRKLARELİ','40'=>'KIRŞEHİR',
    '41'=>'KOCAELİ','42'=>'KONYA','43'=>'KÜTAHYA','44'=>'MALATYA','45'=>'MANİSA',
    '46'=>'KAHRAMANMARAŞ','47'=>'MARDİN','48'=>'MUĞLA','49'=>'MUŞ','50'=>'NEVŞEHİR',
    '51'=>'NİĞDE','52'=>'ORDU','53'=>'RİZE','54'=>'SAKARYA','55'=>'SAMSUN',
    '56'=>'SİİRT','57'=>'SİNOP','58'=>'SİVAS','59'=>'TEKİRDAĞ','60'=>'TOKAT',
    '61'=>'TRABZON','62'=>'TUNCELİ','63'=>'ŞANLIURFA','64'=>'UŞAK','65'=>'VAN',
    '66'=>'YOZGAT','67'=>'ZONGULDAK','68'=>'AKSARAY','69'=>'BAYBURT','70'=>'KARAMAN',
    '71'=>'KIRIKKALE','72'=>'BATMAN','73'=>'ŞIRNAK','74'=>'BARTIN','75'=>'ARDAHAN',
    '76'=>'IĞDIR','77'=>'YALOVA','78'=>'KARABÜK','79'=>'KİLİS','80'=>'OSMANİYE',
    '81'=>'DÜZCE'
);

$ilAdi = null;
if ($ilKodu && isset($iller[$ilKodu])) $ilAdi = $iller[$ilKodu];

// ============================================================
// TEMİZLE
// ============================================================
function temizle($arr) {
    $yeni = array();
    foreach ($arr as $k => $v) {
        if ($v !== null && $v !== '' && $v !== 'null') $yeni[$k] = $v;
    }
    return $yeni;
}

// ============================================================
// TAM ADRES (iç kapıya kadar)
// ============================================================
$tamParcalar = array();
if ($mahalle)    $tamParcalar[] = $mahalle . ' Mahallesi';
if ($sokak)      $tamParcalar[] = $sokak . ' Sokak';
if ($cadde)      $tamParcalar[] = $cadde . ' Caddesi';
if ($blok)       $tamParcalar[] = $blok . ' Blok';
if ($binaNo)     $tamParcalar[] = 'No: ' . $binaNo;
if ($kat)        $tamParcalar[] = 'Kat: ' . $kat;
if ($daireNo)    $tamParcalar[] = 'Daire: ' . $daireNo;
if ($icKapi)     $tamParcalar[] = 'İç Kapı: ' . $icKapi;
if ($ilce)       $tamParcalar[] = $ilce;
if ($ilAdi)      $tamParcalar[] = $ilAdi;
if ($postaKodu)  $tamParcalar[] = 'Posta Kodu: ' . $postaKodu;

$tamAdres = implode(', ', $tamParcalar);

// ============================================================
// SONUÇ
// ============================================================
$adresDetay = temizle(array(
    'Mahalle'     => $mahalle,
    'Sokak'       => $sokak,
    'Cadde'       => $cadde,
    'Blok'        => $blok,
    'BinaNo'      => $binaNo,
    'Kat'         => $kat,
    'DaireNo'     => $daireNo,
    'IcKapi'      => $icKapi,
    'PostaKodu'   => $postaKodu,
    'Ilce'        => $ilce,
    'Il'          => $ilAdi,
    'IlKodu'      => $ilKodu,
    'TamAdres'    => $tamAdres ?: $ham,
    'HamAdres'    => $ham
));

echo json_encode(array(
    'ok' => true,
    'adres' => $adresDetay,
    'script_sahibi' => '@izeyder',
    'instagram' => '@logsuzlarpanel',
    'tiktok' => '@logsuzlar.inc'
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);