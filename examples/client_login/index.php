<?php

// Start Session to save oauth token in session (instead of cookie)
session_start();
?>
<!doctype html>
<html>
<head>
    <title>SPiD Client user login and authentication example</title>
    <meta charset="utf-8">
</head>
<body>
<h1>SPiD Client user login and authentication example</h1>
<?php
// May get credential errors
if (isset($_GET['error'])) {
    echo '<h3 id="message" style="color:red">'.$_GET['error'].'</h3>';
}

// Root of php sdk repo
define('BASE_DIR', realpath('../..'));

// Include PHP SDK Client and a config file with credentials
require_once(BASE_DIR.'/src/Client.php');
require_once(BASE_DIR.'/examples/config.php');
// overwrite redirect url to be HERE
$SPID_CREDENTIALS[VGS_Client::REDIRECT_URI] = "http://{$_SERVER['HTTP_HOST']}/examples/client_login";
$SPID_CREDENTIALS[VGS_Client::COOKIE] = false; // disable cookie support for SDK

// Instantiate the SDK client
$client = new VGS_Client($SPID_CREDENTIALS);
$client->argSeparator = '&';

// When a logout redirect comes from SPiD, delete the local session
if (isset($_GET['logout'])) {
   unset($_SESSION['sdk']);
}

// Code is part of the redirect back from SPiD, redirect to self to remove it from URL
// since it may only be used once, and it has been used to create session
if (isset($_GET['code'])) {
    // Get/Check if we have local session, creates ones if code GET param comes
    $_SESSION['sdk'] = $client->getSession();
    header( "Location: ". $client->getCurrentURI(array(), array('code','login','logout'))) ;
    exit;
}

$session = isset($_SESSION['sdk']) ? $_SESSION['sdk'] : false;

// If we have session, that means we are logged in.
if ($session) {
    // Authorize the client with the session saved user token
    $client->setAccessToken($session['access_token']);

    // Try since SDK may throw VGS_Client_Exceptions:
    //   For instance if the client is blocked, has exceeded ratelimit or lacks access right
    try {
        // Grab the logged in user's User Object, /me will include the entire User object
        $user = $client->api('/me');

        echo '<h3 id="message">Welcome</h3>
            <h4>Logged in as <span id="name" style="color:blue">'.$user['displayName'].'</span> <small>id: <span id="userId" style="color:green">'.$user['userId'].'</span> email: <span id="email" style="color:purple">'.$user['email'].'</span></h4>';

        if (isset($_GET['order_id'])) {
            echo '<pre>'.print_r($client->api('/order/'.$_GET['order_id']),true).'</pre>';
        }

    } catch (VGS_Client_Exception $e) {
        if ($e->getCode() == 401) {
            // access denied, in case the access token is expired, try to refresh it
            try {
                // refresh tokens using the session saved refresh token
                $client->refreshAccessToken($session['refresh_token']);
                $_SESSION['sdk']['access_token'] = $client->getAccessToken();
                $_SESSION['sdk']['refresh_token'] = $client->getRefreshToken();
                // Sesssion refreshed with valid tokens
                header( "Location: ". $client->getCurrentURI(array(), array('code','login','error','logout'))) ;
                exit;
            } catch (Exception $e2) {
                /* falls back to $e message bellow */
            }
        }
        if ($e->getCode() == 400) {
            header( "Location: ". $client->getLoginURI(array('redirect_uri' => $client->getCurrentURI(array(), array('logout','error','code')))));
            exit;
        }

        // API exception, show message, remove session as it is probably not usable
        unset($_SESSION['sdk']);
        echo '<h3 id="error" style="color:red">'.$e->getCode().' : '.$e->getMessage().'</h3>';
    }
    // Show a logout link
    echo '<p><a id="login-link" href="' . $client->getLogoutURI(array('redirect_uri' =>
        $client->getCurrentURI(array('logout' => 1), array('error','code'))
    )) . '">Logout</a></p>';


    echo '<p><a id="login-link" href="' . $client->getPurchaseURI(array('redirect_uri' =>
        $client->getCurrentURI(array(), array('logout','error','code'))
    )) . '">Buy</a></p>';

    echo '<p><a id="login-link" href="' . $client->getAccountURI(array('redirect_uri' =>
        $client->getCurrentURI(array(), array('logout','error','code'))
    )) . '">My Account</a></p>';

    echo '<p><a id="login-link" href="' . $client->getCheckoutFlow(array(
        'redirect_uri' => $client->getCurrentURI(array(), array('logout','error','code')),
        'cancel_redirect_uri' => "http://google.com"
    )) . '">Purchase Flow</a></p>';

} else { // No session, user must log in
 
    echo '<h3 id="message">Please log in</h3>';
    // Show a login link

    echo '<p><a id="login-link" href="' . $client->getLoginFlow(array(
        'redirect_uri' => $client->getCurrentURI(array(), array('logout','error','code')),
        'cancel_redirect_uri' => "http://google.com"
    )) . '">Login Flow</a></p>';
    echo '<p><a id="login-link" href="' . $client->getLoginURI(array(
        'redirect_uri' => $client->getCurrentURI(array(), array('logout','error','code')),
        'cancel_redirect_uri' => "http://google.com"
    )) . '">Login</a></p>';

    echo '<p><a id="login-link" href="' . $client->getCheckoutFlow(array(
        'redirect_uri' => $client->getCurrentURI(array(), array('logout','error','code')),
        'cancel_redirect_uri' => "http://google.com"
    )) . '">Purchase Flow</a></p>';
}

?>
</body>
</html>
