<!doctype html>
<html>
<head>
    <title>SPiD Client user login and authentication example</title>
    <meta charset="utf-8">
</head>
<body>
<h1>SPiD Client user login and authentication example</h1>

<?php if (!empty($user)) : ?>
    <h3 id="message">Welcome</h3>
    <h4>
        Logged in as <span id="name" style="color:blue"><?php echo $user['displayName'] ?></span>
        <small>id: <span id="userId" style="color:green"><?php echo $user['userId'] ?></span>
            email: <span id="email" style="color:purple"><?php echo $user['email'] ?></span>
    </h4>
<?php endif; ?>

<?php // May get credential errors
if (isset($_GET['error'])) : ?>
    <h3 id="message" style="color:red"><?php echo $_GET['error'] ?></h3>
<?php endif; ?>


<?php
// If we have session, that means we are logged in.
if ($session) :
    // Try since SDK may throw VGS_Client_Exceptions:
    //   For instance if the client is blocked, has exceeded ratelimit or lacks access right
    ?>

    <?php if (!empty($user)) : ?>
        <h3 id="message">Welcome</h3>
        <h4>Logged in as <span id="name" style="color:blue"><?php echo $user['displayName'] ?></span>
            <small>id: <span id="userId" style="color:green"><?php echo $user['userId'] ?></span>
                email: <span id="email" style="color:purple"><?php echo $user['email'] ?></span>
        </h4>
    <?php endif; ?>

    <?php if (isset($_GET['order_id'])) : ?>
        <pre><?php print_r($client->api('/order/'.$_GET['order_id']),true) ?></pre>
    <?php endif; ?>

    <p>
        <a id="login-link" href="<?php echo $client->getAccountURI(array('redirect_uri' =>
            $client->getCurrentURI(array(), array('logout','error','code', 'order_id', 'spid_page')))) ?>">My Account</a>
    </p>

    <?php // Show a logout link ?>
    <p>
        <a id="login-link" href="<?php echo $client->getLogoutURI(array('redirect_uri' =>
            $client->getCurrentURI(array('logout' => 1), array('error','code', 'order_id', 'spid_page')))) ?>">Logout</a>
    </p>

    <p>
        <a id="login-link" href="<?php echo $client->getPurchaseURI(array(
            'redirect_uri' => $client->getCurrentURI(array(), array('logout', 'error', 'code', 'order_id', 'spid_page')),
            'cancel_redirect_uri' => $client->getCurrentURI(array('cancel'=>1), array('logout', 'error', 'code', 'order_id', 'spid_page')),
        )) ?>">Buy something</a> (standard checkout flow)
    </p>

    <p>
        <a id="login-link" href="<?php echo $client->getPurchaseURI(array(
            // 'tag' => 'taggen98',
            'campaign_id' => 1, // provide a campaign id
            // 'product_id' => YYYY,
            // 'voucher_code' => ZZZZ,
            'redirect_uri' => $client->getCurrentURI(array('cameback'=>2), array('logout', 'error', 'code', 'order_id', 'spid_page')),
            'cancel_redirect_uri' => $client->getCurrentURI(array('cancel'=>1), array('logout', 'error', 'code', 'order_id', 'spid_page')),
        )) ?>">Campaign Flow</a> (checkout flow with campaign specified
    </p>

<?php else : ?>

    <h3 id="message">Please log in</h3>
    <?php // Show a login link ?>
    <p>
        <a id="login-link" href="<?php echo $client->getLoginURI(array(
            'redirect_uri' => $client->getCurrentURI(array('place' => 'oslo'), array('logout','error','code', 'default', 'cancel', 'order_id', 'spid_page')),
            'cancel_redirect_uri' => $client->getCurrentURI(array('cancel' => 1), array('logout','error','code', 'default', 'cancel', 'order_id', 'spid_page')),
        )) ?>">Login</a> (standard auth flow)
    </p>

    <h5>or</h5>
    <p>
        <a id="signup-flow-link" href="<?php echo $client->getSignupURI(array(
            'redirect_uri' => $client->getCurrentURI(array(), array('logout','error','code', 'order_id', 'spid_page')),
            'cancel_redirect_uri' => "http://google.com"
        )) ?>">Signup Flow</a> (standard auth flow with signup parameter
    </p>

    <h5>or</h5>
    <p>
        <a id="checkout-link" href="<?php echo $client->getPurchaseURI(array(
            'redirect_uri' => $client->getCurrentURI(array(), array('logout','error','code', 'order_id', 'spid_page')),
            'cancel_uri' => $client->getCurrentURI(array('cancel' => 1), array('logout','error','code', 'default', 'cancel', 'order_id', 'spid_page')),
        )) ?>">Buy</a> (standard checkout flow)
    </p>


<?php endif; ?>

</body>
</html>