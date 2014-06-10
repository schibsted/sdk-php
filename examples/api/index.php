<div id="top">&nbsp;</div>
<?php
require_once('../../src/Client.php');
require_once('../config.php');
$SPID_CREDENTIALS[VGS_Client::REDIRECT_URI] = "http://{$_SERVER['HTTP_HOST']}/examples/api";

$client = new VGS_Client($SPID_CREDENTIALS);

if (isset($_GET['logout'])) {
    $client->deleteSession();
}

try {
    $user = $client->api('/me');
} catch (VGS_Client_Exception $e) {
    $client->setSession(null);
    echo $e->getMessage();
}
if ($session = $client->getSession()) {
    ?>
<h3>Table of contents</h3>
<ul>
    <li><a href="#api-user">/user/{orderId}</a></li>
    <li><a href="#api-me">/me</a></li>
    <li><a href="#api-user-do-settings">/user/{id}/do/settings</a></li>
    <li><a href="#api-user-orders">/user/{id}/orders</a></li>
    <li><a href="#api-user-order">/user/{id}/order/{orderId}</a></li>
    <li><a href="#api-report-template">/reports/templates/1</a></li>
    <li><a href="#api-products">/products</a></li>
    <li><a href="#api-discounts">/discounts</a></li>
    <li><a href="#api-purchase">purchase a product</a></li>
</ul>
<?php

    echo '<h2>You are logged in as [user_id] '.$client->getUserId().'</h2>';
    echo '<a href="' . $client->getLogoutURI(array(
            'redirect_uri' => $client->getCurrentURI(array(
                    'logout' => 1), array(
                    'code')))) . '">Logout</a>';
    echo '<div id="api-users">&nbsp;</div><a href="#top">Back To Top</a>';
    /**
     * /api/users
     * ---------------------------------------------------------------------------------------------------------------------
     */
    $params = array('fields' => 'email,displayName,published','limit' => 5,'offset' => 0,'since' => '2010-Nov-05 01:06:11','until' => 'now');
    echo '<br/><br/><strong>/users: </strong><br/><a href="' . $client->getApiURI('/users',$params). '" target="blank">'. $client->getApiURI('/users',$params).'</a>';
    try {
        $users = $client->api('/users', $params);
    } catch (VGS_Client_Exception $e) {
        echo $e->getMessage();
    }
    echo '<pre>' . print_r($users, true) . '</pre>';

    echo '<div id="api-user">&nbsp;</div><a href="#top">Back To Top</a>';
    /**
     * /api/user/{user_id}
     * ---------------------------------------------------------------------------------------------------------------------
     */
    echo '<br/><br/><strong>/user/'.$client->getUserId().': </strong><br/><a href="' . $client->getApiURI('/user/'.$client->getUserId(),$params). '" target="blank">'. $client->getApiURI('/user/'.$client->getUserId(),$params).'</a>';
    try {
        $user = $client->api('/user/'.$client->getUserId(), $params);
    } catch (VGS_Client_Exception $e) {
        echo $e->getMessage();
    }
    echo '<pre>' . print_r($user, true) . '</pre>';

    echo '<div id="api-me">&nbsp;</div><a href="#top">Back To Top</a>';
    /**
     * /api/me
     * ---------------------------------------------------------------------------------------------------------------------
     */
    echo '<br/><br/><strong>/me: </strong><br/><a href="' . $client->getApiURI('/me'). '" target="blank">'. $client->getApiURI('/me').'</a>';
    try {
        $user = $client->api('/me');
    } catch (VGS_Client_Exception $e) {
        echo $e->getMessage();
    }
    echo '<pre>' . print_r($user, true) . '</pre>';


    echo '<div id="api-user-do-settings">&nbsp;</div><a href="#top">Back To Top</a>';



    $path = '/user/'.$client->getUserId().'/do/settings';
    $url = $client->getApiURI($path);

    $result = $client->api($path, 'POST', array('settings' => '{"local":"nb_NO","context":"hash##"}'));

    echo "<br><br><strong>$path: </strong><br><a href='$url'>$url</a>";
    $settings = null;
    try {
        $settings = $client->api($path);
    } catch (VGS_Client_Exception $e) {
        echo '<pre>' . print_r($e->getResult(), true) . '</pre>';
    }
    echo '<pre>' . print_r($settings, true) . '</pre>';




    echo '<div id="api-user-orders">&nbsp;</div><a href="#top">Back To Top</a>';
    /**
     * /api/user/{user_id}/orders
     * ---------------------------------------------------------------------------------------------------------------------
     */
    echo '<br/><strong>/user/'.$client->getUserId().'/orders: </strong><br/><a href="' . $client->getApiURI('/user/'.$client->getUserId().'/orders'). '" target="blank">'. $client->getApiURI('/user/'.$client->getUserId().'/orders').'</a>';
    $order_id = 1;
    try {
        $orders = $client->api('/user/'.$client->getUserId().'/orders');
        if (isset($orders[0]['orderId'])) {
            $order_id = $orders[0]['orderId'];
        }
    } catch (VGS_Client_Exception $e) {
        echo $e->getMessage();
    }
    echo '<pre>' . print_r($orders, true) . '</pre>';

    echo '<div id="api-user-order">&nbsp;</div><a href="#top">Back To Top</a>';
    /**
     * /api/user/{user_id}/order/{order_id}
     * ---------------------------------------------------------------------------------------------------------------------
     */
    echo '<br/><strong>/user/'.$client->getUserId().'/order/'.$order_id.': </strong><br/><a href="' . $client->getApiURI('/user/'.$client->getUserId().'/order/'.$order_id). '" target="blank">'. $client->getApiURI('/user/'.$client->getUserId().'/order/'.$order_id).'</a>';
    $order = array();
    try {
        $order = $client->api('/user/'.$client->getUserId().'/order/'.$order_id);
    } catch (VGS_Client_Exception $e) {
        echo $e->getMessage();
    }
    echo '<pre>' . print_r($order, true) . '</pre>';

    echo '<div id="api-products">&nbsp;</div><a href="#top">Back To Top</a>';
    /**
     * /api/products
     * ---------------------------------------------------------------------------------------------------------------------
     */

    echo '<br/>create <strong>/product</strong><br/>';
    try {
        $product = $client->api('product', 'POST', array(
            'code' => 'test2','name' => 'name', 'price' => 9900,'vat' => 2500, 'paymentOptions'=>2,'type'=>1, 'currency' => 'NOK',
        ));
    } catch (VGS_Client_Exception $e) {
         echo '<pre>' . print_r($client->container, true) . '</pre>';
        echo $e->getMessage();
    }
    echo '<pre>' . print_r($product, true) . '</pre>';

    echo '<br/><strong>/products</strong><br/><a href="' . $client->getApiURI('/products'). '" target="blank">'. $client->getApiURI('/products').'</a>';
    $products = array();
    try {
        $products = $client->api('/products');
    } catch (VGS_Client_Exception $e) {
        echo $e->getMessage();
    }
    echo '<pre>' . print_r($products, true) . '</pre>';

    echo '<div id="api-report-template">&nbsp;</div><a href="#top">Back To Top</a>';
    /**
     * /api/reports/template
     * ---------------------------------------------------------------------------------------------------------------------
     */
    $path = '/reports/template/1';
    $uri = $client->getApiURI('/reports/template/1', array('from'=>'1970-01-01','to' =>'2100-01-01'));
    echo '<br/><strong>/reports/template/1</strong><br/><a href="' . $uri . '" target="blank">'. $uri.'</a>';
    try {
        $report = $client->api($path, 'GET', array('from'=>'1970-01-01','to' =>'2100-01-01'));
    } catch (VGS_Client_Exception $e) {
        echo $e->getMessage();
    }
    echo '<pre>' . print_r($report, true) . '</pre>';
    


    echo '<div id="api-discounts">&nbsp;</div><a href="#top">Back To Top</a>';
    /**
     * /api/discounts
     * ---------------------------------------------------------------------------------------------------------------------
     */
    echo '<br/><strong>/discounts</strong><br/><a href="' . $client->getApiURI('/discounts'). '" target="blank">'. $client->getApiURI('/discounts').'</a>';
    try {
        $discounts = $client->api('/discounts');
    } catch (VGS_Client_Exception $e) {
        echo $e->getMessage();
    }
    echo '<pre>' . print_r($discounts, true) . '</pre>';


} else {
    echo "<h2>VGServices Example App</h2>";
    echo '<a href="' . $client->getLoginURI(array(
            'redirect_uri' => $client->getCurrentURI(array(), array(
                    'logout')))) . '">Login</a>';
}
echo '<div id="api-purchase">&nbsp;</div><a href="#top">Back To Top</a>';
echo '<p><a href="' . $client->getPurchaseURI(array('redirect_uri' => $client->getCurrentURI(array(), array('logout')))) . '">Purchase Any Product</a></p>';

if (isset($products)) {
    foreach((array)$products as $product) {
        echo '<p><a href="' . $client->getPurchaseURI(array(
                                                    'product_id'    => $product["productId"],
                                                    'redirect_uri'  => $client->getCurrentURI(array(), array('logout')))) . '"'.">Purchase Product {$product["productId"]} ({$product["name"]})</a></p>";
    }


    foreach((array)$products as $product) {
        echo '<p><a href="' . $client->getPurchaseURI(array(
                                                    'product_id'    => $product["productId"],
                                                    'display'       => 'popup',
                                                    'redirect_uri'  => $client->getCurrentURI(array(), array('logout')))) . '&keepThis=true&TB_iframe=true&height=380&width=300" class="thickbox">1 Click Purchase of ' . $product["name"]. '</a></p>';
    }
}
echo '<p><a href="' . $client->getAccountURI() . '">Account page</a></p>';
echo '<p><a href="' . $client->getPurchaseHistoryURI() . '">Purchase history page</a></p>';
?>
