<?php
/**
 * Bootstrap file for unit testing with PHPUnit.
 */

date_default_timezone_set('Europe/Oslo');

error_reporting(E_ALL | E_STRICT);

require_once(__DIR__ . '/../src/Client.php');

class BaseIntegrationTest extends PHPUnit_Framework_TestCase {

    public $vgservices;
    public $SPID_CREDENTIALS;

    public function setUp() {
        $this->vgservices = Bootstrap::getVgServices();
        $this->SPID_CREDENTIALS = Bootstrap::getVgsCredentials();
    }

}

class Bootstrap {

    private static $vgservices = null;
    private static $SPID_CREDENTIALS;
    private static $storage;

    private static function __initialize() {
        require_once(__DIR__ . '/config.php');

        self::$SPID_CREDENTIALS = $SPID_CREDENTIALS;
        self::$vgservices = new VGS_Client($SPID_CREDENTIALS);
    
    }

    public static function getVgServices() {
        if (!isset(self::$vgservices)) {
            self::__initialize();
        }
        return self::$vgservices;
    }

    public static function getVgsCredentials() {
        if (!isset(self::$SPID_CREDENTIALS)) {
            self::__initialize();
        }
        return self::$SPID_CREDENTIALS;
    }

    public static function getStorage($key) {
        return isset(self::$storage[$key]) ? self::$storage[$key] : null;
    }

    public static function setStorage($key, $value) {
        return self::$storage[$key] = $value;
    }

}

