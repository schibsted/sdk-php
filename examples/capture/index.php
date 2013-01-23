<!doctype html>
<html>
<head>
    <title>Paylink App</title>
    <meta charset="utf-8">
</head>
<body>

<?php


$result = false;
$errors = array();
if ($_POST) {
    require_once('../../src/Client.php');
    require_once('../config.php');

    $client = new VGS_Client($SPID_CREDENTIALS);
    $client->auth();

    $data = array(
        'orderItemId' => $_POST['order_item_id'],
        'amount' => $_POST['amount']
    );
    try {
        $result = $client->api('/order/'.$_POST['order_id'].'/capture', 'POST', $data);
    }  catch (VGS_Client_Exception $e) {
        $errors = $client->container['meta'] ?: ($client->container['error'] ?: $e->getMessage());
    }
}
?>
    <h3>Capture order</h3>
    <form method="POST">
        <label>Order ID<br><input type="text" name="order_id" value="" /></label><br>
        <label>Order Item ID<br><input type="text" name="order_item_id" value="" /></label><br>
        <label>Amount<br><input type="text" name="amount" value="" /></label><br>
        <input type="submit" />
    </form>
    <?php
    if ($errors) {
        echo '<div class="border:1px solid red">';
        foreach ((array)$errors as $field => $msg) {
            echo '<strong>'.$field.'</strong> : '.$msg. '<br>';
        }
        echo '</div>';
    }
    if ($result) {?>
        <pre><?php print_r($result); ?></pre>
    <?php } ?>
</body>
</html>