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
        VGS_CLIENT::API_VERSION         => 2,
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


        $this->client->SERVER = array('HTTPS' => 'on', 'HTTP_HOST' => 'exam.ple', 'REQUEST_URI' => '/index.php?what=about&that=43&this=willdissapear');

        $this->client->argSeparator = '&';
        $expected = "https://exam.ple/index.php?what=about&that=43&this=overwrites&some=extra&parameters=12";
        $result = $this->client->getCurrentURI(array(
            'this' => 'overwrites',
            'some' => 'extra',
            'parameters' => 12
        ));
        $this->assertEquals($expected, $result);

        $this->client->argSeparator = $preTestSEPARATOR;
        $this->client->SERVER = $preTestSERVER;
    }

    public function testGetSpidURL() {
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

    public function testPurchaseHistoryURI() {
        $expected = "http://spp.dev/account/purchasehistory?" . join($this->client->argSeparator,array(
            'client_id=' . $this->SPID_CREDENTIALS['client_id'],
            'response_type=code',
            'redirect_uri=' . urlencode('http://' . $this->client->SERVER['HTTP_HOST'] . $this->client->SERVER['REQUEST_URI']),
            'v=' . TestableClient::VERSION
        ));
        $result = $this->client->getPurchaseHistoryURI();
        $this->assertEquals($expected, $result);

        $expected .= $this->client->argSeparator . join($this->client->argSeparator,array('par1=12','par2=two'));
        $result = $this->client->getPurchaseHistoryURI(array('par1' => 12,'par2' => 'two'));
        $this->assertEquals($expected, $result);
    }

    public function testAccountURI() {
        $expected = "http://spp.dev/account?" . join($this->client->argSeparator,array(
            'client_id=' . $this->SPID_CREDENTIALS['client_id'],
            'response_type=code',
            'redirect_uri=' . urlencode('http://' . $this->client->SERVER['HTTP_HOST'] . $this->client->SERVER['REQUEST_URI']),
            'v=' . TestableClient::VERSION
        ));
        $result = $this->client->getAccountURI();
        $this->assertEquals($expected, $result);

        $expected .= $this->client->argSeparator . join($this->client->argSeparator,array('par1=12','par2=two'));
        $result = $this->client->getAccountURI(array('par1' => 12,'par2' => 'two'));
        $this->assertEquals($expected, $result);
    }

    public function testApiUrl() {
        $expected = 'Missing argument';
        $result = '';
        try {
            $uri = $this->client->getApiURI();
            $this->assertEquals($expected, $result);
        } catch (Exception $e) {
            $result = substr($e->getMessage(), 0, strlen($expected));
        }
        $this->assertEquals($expected, $result);
        
        $expected = 'http://spp.dev/api/2/endpoints?oauth_token='.$this->SPID_CREDENTIALS['client_id'];
        $result  = $this->client->getApiURI('/endpoints');
        $this->assertEquals($expected, $result);
    }

    public function testEncodeSerializedUrlVariable() {
        $expected = 'eNortjKxUipJrShRsgZcMBQ-A2A,';
        $result   = $this->client->encodeSerializedUrlVariable('text');
        $this->assertEquals($expected, $result);
        
        $var = '@LfH>2d%pL@-zGYLPg|*jZr[pSS9uZUy#q>df';
        $expected = strtr(base64_encode(addslashes(gzcompress(serialize($var),9))), '+/=', '-_,');
        $result   = $this->client->encodeSerializedUrlVariable($var);
        $this->assertEquals($expected, $result);
    }

    public function testConfigSetGet() {

        $expected = '345';
        $result   = $this->client->getClientID();
        $this->assertEquals($expected, $result);

        $result   = $this->client->setClientID('456');
        $this->assertEquals($this->client, $result);

        $expected = '456';
        $result   = $this->client->getClientID();
        $this->assertEquals($expected, $result);
    
        $expected = '';
        $result   = $this->client->getContextClientID();
        $this->assertEquals($expected, $result);

        $result   = $this->client->setContextClientID('765');
        $this->assertEquals($this->client, $result);

        $expected = '765';
        $result   = $this->client->getContextClientID();
        $this->assertEquals($expected, $result);
        
        $expected = 'foobar';
        $result   = $this->client->getClientSecret();
        $this->assertEquals($expected, $result);

        $result   = $this->client->setClientSecret('apocalypse');
        $this->assertEquals($this->client, $result);

        $expected = 'apocalypse';
        $result   = $this->client->getClientSecret();
        $this->assertEquals($expected, $result);
    
        $expected = 'foobar';
        $result   = $this->client->getClientSignSecret();
        $this->assertEquals($expected, $result);

        $result   = $this->client->setClientSignSecret('now');
        $this->assertEquals($this->client, $result);

        $expected = 'now';
        $result   = $this->client->getClientSignSecret();
        $this->assertEquals($expected, $result);
    
        $this->client->setClientID('345')->setContextClientID('')->setClientSecret('foobar')->setClientSignSecret('foobar');
    }

    public function testXiti() {
        $expected_xiti_hash = urlencode(strtr(base64_encode(addslashes(gzcompress(serialize(array('xiti' => 'test')),9))), '+/=', '-_,'));
        $this->client->setXitiConfiguration(array('xiti' => 'test'));

        $expected = "http://spp.dev/auth/start?" . join($this->client->argSeparator,array(
            'flow=payment',
            'client_id=' . $this->SPID_CREDENTIALS['client_id'],
            'response_type=code',
            'redirect_uri=' . urlencode('http://' . $this->client->SERVER['HTTP_HOST'] . $this->client->SERVER['REQUEST_URI']),
            'xiti=' . $expected_xiti_hash,
            'v=' . TestableClient::VERSION
        ));
        $result = $this->client->getPurchaseURI();
        $this->assertEquals($expected, $result);
    }
}
