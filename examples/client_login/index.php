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
$SPID_CREDENTIALS[VGS_Client::REDIRECT_URI] = "http://sdk.dev/examples/client_login";

// Instantiate the SDK client
$client = new VGS_Client($SPID_CREDENTIALS);

// When a logout redirect comes from SPiD, delete the local session
if (isset($_GET['logout'])) {
    $client->deleteSession();
}

// Get/Check if we have local session, creates ones if code GET param comes
$session = $client->getSession();

// Code is part of the redirect back from SPiD, redirect to self to remove it from URL
// since it may only be used once, and it has been used to create session
if (isset($_GET['code'])) {
    header( "Location: ".$SPID_CREDENTIALS[VGS_Client::REDIRECT_URI] ) ;
    exit;
}


// If we have session, that means we are logged in.
if ($session) {

    // Try since SDK may throw VGS_Client_Exceptions:
    //   For instance if the client is blocked, has exceeded ratelimit or lacks access right
    try {
        // Grab the logged in user's User Object, /me will include the entire User object
        $user = $client->api('/me');

        echo '<h3 id="message">Welcome</h3>
            <h4>Logged in as <span id="name" style="color:blue">'.$user['displayName'].'</span> <small>id: <span id="userId" style="color:green">'.$user['userId'].'</span> email: <span id="email" style="color:purple">'.$user['email'].'</span></h4>';

    } catch (VGS_Client_Exception $e) {
        // API exception, show message, remove session as it is probably not usable
        $client->setSession(null);
        echo '<h3 id="message" style="color:red">'.$e->getMessage().'</h3>';
    }

    // Show a logout link
    echo '<p><a id="login-link" href="' . $client->getLogoutURI(array('redirect_uri' => $client->getCurrentURI(array('logout' => 1), array('login','error','code')))) . '">Logout</a></p>';

} else { // No session, user must log in

    echo '<h3 id="message">Please log in</h3>';
    // Show a login link
    echo '<p><a id="login-link" href="' . $client->getLoginURI(array('redirect_uri' => $client->getCurrentURI(array('login' => 1), array('logout','error','code')))) . '">Login</a></p>';
}

?>
</body>
</html>
