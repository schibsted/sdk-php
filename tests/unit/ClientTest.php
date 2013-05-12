<?php

class ClientTest extends BaseUnitTest {

    public $SPID_CREDENTIALS = array(
        VGS_Client::CLIENT_ID           => '345',
        VGS_Client::CLIENT_SECRET       => 'foobar',
        VGS_Client::CLIENT_SIGN_SECRET  => 'foobar',
        VGS_Client::STAGING_DOMAIN      => 'spp.dev',
        VGS_Client::PRODUCTION          => false,
        VGS_Client::HTTPS               => false,
        VGS_Client::REDIRECT_URI        => "http://sdk.dev",
        VGS_Client::DOMAIN              => "sdk.dev",
    );

    public function testClient() {
        $expected = 'http://spp.dev';
        $result   = $this->client->getServerURL();
        $this->assertEquals($expected, $result);

        $expected = 'sdk.dev';
        $result   = $this->client->getBaseDomain();
        $this->assertEquals($expected, $result);

        $this->client->setProduction(true);

        $expected = 'http://payment.schibsted.no';
        $result   = $this->client->getServerURL();
        $this->assertEquals($expected, $result);

        $this->client->setProduction(false);
    }

    public function testParseSignedRequest() {
        $request = array(
            'algorithm' => 'HMAC-SHA256',
            0 => 'payload',
        );

        $payload = rtrim(strtr(base64_encode(json_encode($request)), '+/', '-_'), '=');
        $hash = $this->client->createHash($request);

        $signedRequest = sprintf('%s.%s', $hash, $payload);

        $this->assertSame($request, $this->client->parseSignedRequest($signedRequest));
    }

    public function testgetCurrentURI() {

        $expected = 'http://sdk.dev/tests/test.php'; // as defined  in $_SERVER by test_bootstrap
        $result = $this->client->getCurrentURI();
        $this->assertEquals($expected, $result);

        $preTestSEPARATOR = $this->client->argSeparator;
        $preTestSERVER = $this->client->SERVER;
        $this->client->SERVER = array('HTTPS' => 'on', 'HTTP_HOST' => 'exam.ple', 'REQUEST_URI' => '/index.php');

        $this->client->argSeparator = '&';
        $expected = "https://exam.ple/index.php?this=is&some=extra&parameters=12";
        $result = $this->client->getCurrentURI(array(
            'this' => 'is',
            'some' => 'extra',
            'parameters' => 12
        ));
        $this->assertEquals($expected, $result);

        $this->client->argSeparator = '&amp;';
        $expected = "https://exam.ple/index.php?this=is&amp;some=extra&amp;parameters=12";
        $result = $this->client->getCurrentURI(array(
            'this' => 'is',
            'some' => 'extra',
            'parameters' => 12
        ));
        $this->assertEquals($expected, $result);

        $this->client->argSeparator = $preTestSEPARATOR;
        $this->client->SERVER = $preTestSERVER;
    }

    public function testGetApiServerURL() {
        $config = array(
            'client_id'     => '1234',
            'client_secret' => '1234',
            'production'    => true,
            'redirect_uri'  => 'http://sdk.dev/test',
        );
        $client = new TestableClient($config);

        $expected = 'https://payment.schibsted.no';
        $result   = $client->getServerURL();
        $this->assertEquals($expected, $result);

        $config = array(
            'client_id'     => '1234',
            'client_secret' => '1234',
            'production'    => false,
            'redirect_uri'  => 'http://sdk.dev/test',
        );
        $client = new TestableClient($config);

        $expected = 'https://stage.payment.schibsted.no';
        $result   = $client->getServerURL();
        $this->assertEquals($expected, $result);
    }

    public function testGetLoginURI() {
        $expected = "http://spp.dev/login?" . join($this->client->argSeparator,array(
            'client_id=' . $this->SPID_CREDENTIALS['client_id'],
            'response_type=code',
            'redirect_uri=' . urlencode('http://' . $this->client->SERVER['HTTP_HOST'] . $this->client->SERVER['REQUEST_URI']),
            'flow=signup',
            'v=' . TestableClient::VERSION
        ));
        $result = $this->client->getLoginURI();
        $this->assertEquals($expected, $result);

        $expected .= $this->client->argSeparator . join($this->client->argSeparator,array('par1=12','par2=two'));
        $result = $this->client->getLoginURI(array('par1' => 12,'par2' => 'two'));
        $this->assertEquals($expected, $result);
    }

    public function testGetSingupURI() {
        $expected = "http://spp.dev/signup?" . join($this->client->argSeparator,array(
            'client_id=' . $this->SPID_CREDENTIALS['client_id'],
            'response_type=code',
            'redirect_uri=' . urlencode('http://' . $this->client->SERVER['HTTP_HOST'] . $this->client->SERVER['REQUEST_URI']),
            'flow=signup',
            'v=' . TestableClient::VERSION
        ));
        $result = $this->client->getSignupURI();
        $this->assertEquals($expected, $result);

        $expected .= $this->client->argSeparator . join($this->client->argSeparator,array('par1=12','par2=two'));
        $result = $this->client->getSignupURI(array('par1' => 12,'par2' => 'two'));
        $this->assertEquals($expected, $result);
    }

    public function testGetPurchaseURI() {
        $expected = "http://spp.dev/auth/start?" . join($this->client->argSeparator,array(
            'flow=payment',
            'client_id=' . $this->SPID_CREDENTIALS['client_id'],
            'response_type=code',
            'redirect_uri=' . urlencode('http://' . $this->client->SERVER['HTTP_HOST'] . $this->client->SERVER['REQUEST_URI']),
            'v=' . TestableClient::VERSION
        ));
        $result = $this->client->getPurchaseURI();
        $this->assertEquals($expected, $result);

        $expected .= $this->client->argSeparator . join($this->client->argSeparator,array('par1=12','par2=two'));
        $result = $this->client->getPurchaseURI(array('par1' => 12,'par2' => 'two'));
        $this->assertEquals($expected, $result);
    }

    public function dtest() {
        
    }
}
