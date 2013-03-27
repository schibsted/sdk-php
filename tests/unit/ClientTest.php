<?php

class ClientTest extends PHPUnit_Framework_TestCase {

    public function testConfig() {
        $SPID_CREDENTIALS = array(
            VGS_Client::CLIENT_ID       => '4cf36fa274dea2117e030000',//4d06920474dea26227070000',//
            VGS_Client::CLIENT_SECRET   => 'foobar',
            VGS_Client::CLIENT_SIGN_SECRET => 'foobar',
            VGS_Client::STAGING_DOMAIN  => 'spp.dev',
            VGS_Client::HTTPS           => false,
            VGS_Client::REDIRECT_URI    => "http://sdk.dev",
            VGS_Client::DOMAIN          => 'sdk.dev',
            VGS_Client::COOKIE          => false,
            VGS_Client::API_VERSION     => 2,
            VGS_Client::PRODUCTION      => true,
        );
        $client = new VGS_Client($SPID_CREDENTIALS);
        $expected = 'https://payment.schibsted.no';
        $result   = $client->getServerURL();
        $this->assertEquals($expected, $result);
    }

    public function testFail() {
        $this->assertTrue(false, "This should fail");
    }
}
