<?php

ini_set('memory_limit', '1G');
set_time_limit(7200);

require_once('../../src/Client.php');

define('DEBUG', false);
define('VERBOSE', true);

$config = array(
    // Config for the SPiD PHP SDK 
    'api' => array(
        VGS_Client::CLIENT_ID       => '<YOUR CLIENT ID HERE>',
        VGS_Client::CLIENT_SECRET   => '<YOUR CLIENT SECRET HERE>',
        // A valid and configured redirect uri for this client
        VGS_Client::REDIRECT_URI    => "http://<YOUR URI HERE>",
        // A valid domain for this client
        VGS_Client::DOMAIN          => '<YOUR DOMAIN HERE>',
        VGS_Client::PRODUCTION      => true, // if false will use stage.payment.schibsted.no
    ),
    // Config for the MYSQLI adapter at buttom of file
    'sql' => array(
        'user' => '<YOUR USERNAME HERE>',
        'password' => '<YOUR PASSWORD HERE>',
        'database' => 'spid_reports',
    ),
    // Where to store the downloaded dump tars
    'dumps_dir' => '<YOUR PATH HERE>/dumps/',
    // Where to temporary unpack the dump tars 
    'tmp_dir' => '<YOUR USERNAME HERE>/tmp/',
);

$worker = new Reports($config);

try {

    $worker->init();

} catch (Exception $e) {
    if (VERBOSE) echo PHP_EOL,PHP_EOL,$e->getMessage() ,PHP_EOL;
    die('INIT FAILED!'.PHP_EOL);
}
try {

    $worker->importDumps();

} catch (Exception $e) {
    if (VERBOSE) echo PHP_EOL,PHP_EOL,$e->getMessage() ,PHP_EOL;
    die('IMPORT FAILED!'.PHP_EOL);
}


/**
 * Connect to API and database backend, get all report dumps and for
 * any file not previously downloaded from api, unpack it and import into db.
 *
 * Requires config of api (minimum clientId, clientSecret, redirectUri and domain)
 * and database (minimum user, password and database)
 * and tmp_dir path (for where to unpack the dumps to, will be deleted)
 * and dumps_dir path (for where dumps are stored, will be kept to stop reapply)
 *
 * Usage:
 *
 *   $worker = new Reports($config);
 *   // call init to check if config will provide good connections
 *   $worker->init(); // throws excpetion if not
 *   // commence import
 *   $worker->importDumps(); // throws exception if fails
 *
 *
 * NOTE: Reports::$modelsToTable must be maintained
 */
class Reports {

    /**
     * Converts dump file names (models) into backend table names (tables)
     */
    protected $modelsToTable = array(
        'campaignProducts' => 'spp_campaigns_products',
        'campaigns' => 'spp_campaign',
        'churnLog' => 'spp_user_subscriptions_churn_log',
        'discounts' => 'spp_discounts',
        'logins' => 'spp_logins',
        'orderItems' => 'spp_order_items',
        'orders' => 'spp_orders',
        'paymentIdentifiers' => 'spp_payment_identifiers',
        'paymentMethodFees' => 'spp_payment_method_fees',
        'products' => 'spp_products',
        'userProducts' => 'spp_user_products',
        'users' => 'spp_users',
        'userSubscriptions' => 'spp_user_subscriptions',
        'voucherGroups' => 'spp_voucher_group',
        'vouchers' => 'spp_voucher',
    );

    /*********************************************************/

    protected $db = null;
    protected $api = null;
    protected $default_api_config = array(
        VGS_Client::HTTPS           => true,
        VGS_Client::COOKIE          => false,
        VGS_Client::API_VERSION     => 2,
    );
    protected $tmp_dir = null;
    protected $dumps_dir = null;

    public function __construct(array $config = array()) {
        if (!isset($config['api']) || !isset($config['sql']) || !isset($config['tmp_dir'])) {
            throw new Exception('Missing config, need booth "api" and "sql"');
        }
        $this->api = new VGS_Client($config['api'] + $this->default_api_config);
        $this->db = new Db($config['sql']);
        $this->tmp_dir = $config['tmp_dir'];
        $this->dumps_dir = $config['dumps_dir'];
    }

    /**
     * Connects sql and authenticates with client token to the api
     *
     * Throws exception on failure to connect either or missing directories
     */
    public function init() {
        if (VERBOSE) echo 'INIT:' ,PHP_EOL;
        if (VERBOSE) echo ' CONNECTING API' ,PHP_EOL;
        $this->api->auth();
        if (VERBOSE) echo ' API CONNECTED' ,PHP_EOL;
        if (VERBOSE) echo ' CONNECTING SQL' ,PHP_EOL;
        $this->db->connect();
        if (VERBOSE) echo ' SQL CONNECTED' ,PHP_EOL;
        if (VERBOSE) echo ' CHECKING PATHS' ,PHP_EOL;
        if (DEBUG) echo '  CHECKING TMP DIR AT : '.$this->tmp_dir ,PHP_EOL;
        if (!file_exists($this->tmp_dir)) {
            throw new Exception("Path for 'tmp_dir' not found at '".$this->tmp_dir."'!");
        }
        if (DEBUG) echo '  CHECKING DUMP DIR AT : '.$this->dumps_dir ,PHP_EOL;
        if (!file_exists($this->dumps_dir)) {
            throw new Exception("Path for 'dumps_dir' not found at '".$this->dumps_dir."'!");
        }
        if (VERBOSE) echo ' PATHS FOUND' ,PHP_EOL;
    }

    /**
     * Grab all report dumps and go through them in the order they were created
     *
     * This calls Reports::dump($id) on each dump
     */
    public function importDumps() {
        if (VERBOSE) echo 'GETTING DUMP LIST',PHP_EOL;
        $dumps = $this->api->api('/reports/dumps', 'GET');

        if (VERBOSE) echo 'GOT DUMP LIST',PHP_EOL;
        $dumps = array_reverse($dumps);
        if (VERBOSE) echo 'EXECUTING DUMPS!',PHP_EOL;
        foreach ($dumps as $dump) {
            if ($dump['status'] == 1) {
                $this->dump($dump['dumpId']);
            }
        }
    }

    /**
     * If dump id file does not exist, download and inject it to db
     *
     * Download dump from api, unpack it
     * Foreach unpacked file, inject it into db and then delete it.
     *
     * @param int $id 
     * @return boolean false if file exists and it therefore skipped it
     */
    public function dump($id) {
        if (VERBOSE) echo "  DUMP $id" ,PHP_EOL;
        $filename = $this->dumps_dir . 'dump-' . $id . '.tar.gz';
        if (file_exists($filename)) {
            // skip
            if (VERBOSE) echo "   SKIPPING : $filename exists" ,PHP_EOL;
            return false;
        } else {
            try {
                $tgz_data = $this->api->api("/reports/dump/{$id}.tgz", 'GET');
            } catch (Exception $e) {
                throw new Exception('Could not find dump with id '.$id);
                exit;
            }
            file_put_contents($filename, $tgz_data);
        }
        $unpacked_files = array();
        chdir(dirname($filename));
        if (exec("/bin/tar -zxvf $filename -C ".$this->tmp_dir, $output)) {

            
            $injects = array();
            foreach ($output as $file) {
                $thisFile = $this->tmp_dir . $file;
                $unpacked_files[] = $thisFile;
                if (file_exists($thisFile)) {
                    list($model, ) = explode('-', $file);
                    
                    if (isset($this->modelsToTable[$model])) { 
                        $table = $this->modelsToTable[$model];
                    } else {
                        // or auto guess?
                        if (DEBUG) echo "   SKIPPING $model : table not found!" ,PHP_EOL;
                        continue;
                    }
                    $sql = "LOAD DATA INFILE '$thisFile' REPLACE INTO TABLE $table CHARACTER SET utf8 IGNORE 1 LINES";
                    if (DEBUG) echo PHP_EOL, 'DEBUG : $sql : ' . $sql, PHP_EOL;
                    $this->db->query($sql);
                }
            }
        } else {
            throw new Exception('File uncompression failed for file ' . $filename);
        }
        foreach ($unpacked_files as $file) {
           unlink($file);
        }
        return true;
    }
}

/**
 * Config and SQL adapter
 *
 * To replace it, implement a singleton pattern, with connect() and query($sql) instance methods
 */
class Db {
    protected static $instance;

    protected $config;

    protected $connection = null;
    protected $stmt = null;

    public function __construct(array $config = array()) {
        $this->config = $config + array(
            'host' => 'localhost',
            'user' => 'spid_reports',
            'password' => '',
            'database' => 'spid_reports',
        );
    }

    public static function instance(array $config = array()) {
        if (!static::$instance) {
            static::$instance = new Db($config);
        }
        return static::$instance;
    }

    public function connect() {
        $this->connection = new mysqli($this->config['host'], $this->config['user'], $this->config['password'], $this->config['database']);
        if ($this->connection->connect_errno) {
            throw new Exception('MySQL connect fail: '.$this->connection->connect_errno);
            exit;
        }
    }

    public function query($sql) {
        $result = $this->connection->query($sql);
        if (!$result) {
            throw new Exception('Query fail : '.$this->connection->errno.' : '.$this->connection->error);
            exit;
        }
        return $result;
    }
}

?>