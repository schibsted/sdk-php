<?php
session_start();

require_once('../../src/Client.php');
require_once('../config.php');

$SPID_CREDENTIALS[VGS_Client::REDIRECT_URI] = "http://{$_SERVER['HTTP_HOST']}/examples/api";

$client = new VGS_Client($SPID_CREDENTIALS);

if (isset($_GET['refresh']) || !(isset($_SESSION['sdk']) && isset($_SESSION['sdk']['access_token']))) {
    try {
        $_SESSION['sdk']['access_token'] = $client->auth();
    } catch (VGS_Client_Exception $e) {
        print_r($e);
        exit;
    }
    if (isset($_GET['refresh'])) {
        header( "Location: ".$SPID_CREDENTIALS[VGS_Client::REDIRECT_URI] ) ;
        exit;
    }
}

$client->setAccessToken($_SESSION['sdk']['access_token']);

if (empty($_GET['offset'])) {
    $offset = 0;
} else {
    $offset = $_GET['offset'];
}
$limit = 5;

?>
<div id="top">&nbsp;</div>
<?php

    /**
     * /api/users
     * ---------------------------------------------------------------------------------------------------------------------
     */
    $users_endpoint = $client->getApiURI('/users',array('since' => 'last year', 'until' => 'today', 'limit' => $limit, 'offset' => $offset, 'filters' => 'updated'));
    echo '<br/><br/><strong>/users: </strong> <a href="' .$users_endpoint . '" target="blank">'. $users_endpoint.'</a>';
?><br/><br/><a href="?offset=<?php echo $offset+$limit?>">Next page</a><?php
    try {
        $users = $client->api('/users', array('since' => 'last year', 'until' => 'today', 'limit' => $limit, 'offset' => $offset, 'filters' => 'updated'));
    } catch (VGS_Client_Exception $e) {
        echo $e->getMessage();
        ?>
        <br/><a href="?refresh=1">Refresh token</a>
        <?php
    }
    echo '<pre>' . print_r($users, true) . '</pre>';


    echo '<div id="api-user-do-settings">&nbsp;</div><a href="#top">Back To Top</a>';

