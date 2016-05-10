<?php

// enable the debug mode
$app['debug'] = true;

// Example overwrites REDIRECT_URI so that they can share this config file
$app['SPID_CREDENTIALS'] = array(

    VGS_Client::CLIENT_ID       => '4d00e8d6bf92fc8648000000',
    VGS_Client::CLIENT_SECRET   => 'foobar',
    VGS_Client::CLIENT_SIGN_SECRET => 'a274de',
    //VGS_Client::STAGING_DOMAIN  => 'spp.dev',
    VGS_Client::HTTPS           => false,
    VGS_Client::REDIRECT_URI    => "http://sdk.dev/explorer.php",
    VGS_Client::DOMAIN          => 'sdk.dev',
    VGS_Client::COOKIE          => true,
    VGS_Client::API_VERSION     => 2,
    VGS_Client::PRODUCTION      => false,

);