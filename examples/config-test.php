<?php

// Example overwrites REDIRECT_URI so that they can share this config file
$SPID_CREDENTIALS = array(
                          VGS_Client::CLIENT_ID       => '4d06920474dea26227070000',
                          VGS_Client::CLIENT_SECRET   => 'foobar',
                          VGS_Client::CLIENT_SIGN_SECRET => 'secret',
                          VGS_Client::STAGING_DOMAIN  => 'testspp.dev',
                          VGS_Client::HTTPS           => false,
                          VGS_Client::REDIRECT_URI    => "http://testsdk.dev/examples",
                          VGS_Client::DOMAIN          => 'testsdk.dev',
                          VGS_Client::COOKIE          => true,
                          VGS_Client::API_VERSION     => 2,
                          VGS_Client::PRODUCTION      => false,
                          );

if (!$SPID_CREDENTIALS) {
    die('You must configure $SPID_CREDENTIALS in examples/config.php first!');
}
// effects only apirequest.php, having it return the full container or just the data part
DEFINE('EXPLORER_SHOW_CONTAINER', false);

?>
