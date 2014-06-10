<?php
$logfile = realpath(dirname(__FILE__)).'/logs/log-'.date("Y-m-d").'.txt';

$file_handle = fopen($logfile, 'a');
$logger = function($msg) use ($file_handle) {
    if (!fwrite($file_handle, $msg.PHP_EOL)) {
        throw new Exception("FILE LOG FAILED");
    }
};

require_once('../../src/Client.php');
require_once('../config.php');

$client = new VGS_Client($SPID_CREDENTIALS);
$client->auth();

$post = file_get_contents("php://input");

$logger("==NEW==");
$logger($_SERVER['REQUEST_URI'] . ' ' . time());
$logger("Payload : $post");
$logger(print_r(apache_request_headers(), true));

// USE EITHER VERBOSE EXAMPLE OR SDK EXAMPLE

/**************************************/
// START OF VERBOSE EXAMPLE

function parse_signed_request($signed_request, $secret) {
  $arr = explode('.', $signed_request, 2);
  if (!$arr || count($arr) < 2) return null;
  list($encoded_sig, $payload) = $arr;

  // decode the data
  $sig = base64_url_decode($encoded_sig);
  $data = base64_url_decode($payload);

  // check sig
  $expected_sig = hash_hmac('sha256', $payload, $secret, true);
  if ($sig !== $expected_sig) {
    return null;
  }

  return $data;
};

function base64_url_decode($input) {
  return base64_decode(strtr($input, '-_', '+/'));
}

$parsed = parse_signed_request($post, $SPID_CREDENTIALS[VGS_Client::CLIENT_SIGN_SECRET]);

$logger("Parsed : $parsed");

$data = $parsed ? json_decode($parsed, true) : false;

// END OF VERBOSE EXAMPLE
/**************************************/

// OR

/**************************************/
// START OF SDK EXAMPLE

//$data = $client->parseSignedRequest($post);

// END OF SDK EXAMPLE
/**************************************/


if (!$data) {
    $logger(" BAD SIGNATURE!");
    // for testing negative response, use a different response code
    header('HTTP/1.0 401 Unauthorized', true, 401);
    return;
}


if ($data && is_array($data)) {
    switch ($data['object']) {
        case 'order' :
            foreach ($data['entry'] as $object) {
                $logger("Looking up : Order[" .$object['orderId'].']');
//                $order = $client->api('/order/'.$object['orderId'], 'GET');
//                $logger("Order:".PHP_EOL.print_r($order, true));
            }
            break;
        case 'user' :
            foreach ($data['entry'] as $object) {
                $logger("Looking up : User[" .$object['userId'].']');
//                $order = $client->api('/suer/'.$object['userId'], 'GET');
//                $logger("User:".PHP_EOL.print_r($user, true));
            }

            break;

        default:
            $logger("I dont know this type [{$data['object']}]");

            break;
    }
}

$logger('=======');
fclose($file_handle);

// for testing negative response, use a different response code
//header('HTTP/1.0 401 Unauthorized', true, 401);

header('HTTP/1.0 202 ACCEPTED', true, 202);

/* Sample output:

==NEW==
/callback.php 1349007045
Payload : GTUVPjN1LzdyU1qwHjnMKS2oNxckfGzXWA6WOGHVOOg.eyJvYmplY3QiOiJvcmRlciIsImVudHJ5IjpbeyJvcmRlcl9pZCI6IjMwMDAxNCIsImNoYW5nZWRfZmllbGRzIjoic3RhdHVzIiwidGltZSI6IjIwMTItMDktMzAgMTM6MjE6NDMifSx7Im9yZGVyX2lkIjoiMzAwMDE2IiwiY2hhbmdlZF9maWVsZHMiOiJzdGF0dXMiLCJ0aW1lIjoiMjAxMi0wOS0zMCAxMzoyMTo0MyJ9XX0
Parsed : {"object":"order", "entry":[{"orderId":"300014","changedFields":"status","time":"2012-09-30 13:21:43"},{"orderId":"300016","changedFields":"status","time":"2012-09-30 13:21:43"}], "algorithm":"HMAC-SHA256"}
Looking up : Order[300014
Order:
Array
(
    [orderId] => 300014
    [clientId] => 4cf36fa274dea2188534224
    [clientReferenceId] =>
    [extOrderRef] => 1E831B6901C8419CB0E5EBA6511ABE34
    [extTransRef] => 760841
    [identifierId] => 24662
    [userId] => 100076
    [sellerUserId] =>
    [ocr] =>
    [totalPrice] => 37900
    [vat] => 2500
    [calculatedVat] => 7580
    [creditedAmount] => 0
    [capturedAmount] => 0
    [currency] => NOK
    [campaignId] =>
    [voucherId] =>
    [paylinkId] =>
    [tag] =>
    [status] => 2
    [transactionSynced] => 0
    [transactionStatus] => 0
    [errorCode] =>
    [errorDescription] =>
    [errorThirdparty] =>
    [statusChecked] =>
    [updated] => 2010-12-15 15:44:48
    [created] => 2010-12-15 15:43:42
    [type] => 0
)

Looking up : Order[300016
Order:
Array
(
    [orderId] => 300016
    [clientId] => 4cf36fa274dea2188534224
    [clientReferenceId] =>
    [extOrderRef] => 918DF12B7AAD41D0A025B089DEFCE346
    [extTransRef] => 762119
    [identifierId] => 15070
    [userId] => 100156
    [sellerUserId] =>
    [ocr] =>
    [totalPrice] => 59900
    [vat] => 2500
    [calculatedVat] => 11980
    [creditedAmount] => 0
    [capturedAmount] => 0
    [currency] => NOK
    [campaignId] =>
    [voucherId] =>
    [paylinkId] =>
    [tag] =>
    [status] => 2
    [transactionSynced] => 0
    [transactionStatus] => 0
    [errorCode] =>
    [errorDescription] =>
    [errorThirdparty] =>
    [statusChecked] =>
    [updated] => 2010-12-15 15:54:39
    [created] => 2010-12-15 15:53:52
    [type] => 0
)

=======


 */
