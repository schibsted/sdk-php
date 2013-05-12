<?php
/**
 * Bootstrap file for unit testing with PHPUnit.
 */

date_default_timezone_set('Europe/Oslo');

error_reporting(E_ALL | E_STRICT);

require_once(__DIR__ . '/../src/Client.php');
require_once(__DIR__ . '/setUp/TestableClient.php');

class BaseIntegrationTest extends PHPUnit_Framework_TestCase {

    public $client;
    public $SPID_CREDENTIALS;

    public function setUp() {
        if (empty($this->SPID_CREDENTIALS)) {
            require_once(__DIR__ . '/config.php');
            $this->SPID_CREDENTIALS = $SPID_CREDENTIALS;
        }
        $this->client = new TestableClient($SPID_CREDENTIALS);
    }

}

class BaseUnitTest extends PHPUnit_Framework_TestCase {

    public $client;
    public $SPID_CREDENTIALS;

    public function setUp() {
        if (empty($this->SPID_CREDENTIALS)) {
            require_once(__DIR__ . '/config.php');
            $this->SPID_CREDENTIALS = $SPID_CREDENTIALS;
        }
        $this->client = new TestableClient($this->SPID_CREDENTIALS);
        $this->client->SERVER = array(
            'HTTP_HOST'     => 'sdk.dev',
            'HTTPS'         => 'ON',
            'REQUEST_URI'   => '/tests/test.php',
        );
    }

}
