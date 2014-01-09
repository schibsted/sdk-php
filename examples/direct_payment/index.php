<?php
    require_once('../../src/Client.php');
    require_once('../config.php');

    $result = false;
    $errors = false;
    $action_request = false;
    $client = new VGS_Client($SPID_CREDENTIALS);
    $client->auth();

    if ($_POST) {
        $action_request = true;
        $errors = array();
        $data = array(
            'requestReference' => '',
            'clientReference' => '',
            'paymentOptions' => '',
            'purchaseFlow' => '',
            'sellerUserId' => '',
            'tag' => '',
        );

        $data = array_filter(array_intersect_key($_POST,$data));

        $items = array(array(
            'name' => $_POST['productName'],
            'price' => $_POST['productPrice']
        ));
        $data['items'] = json_encode($items);

        if (!empty($data['sellerUserId'])) {
            $data['type'] = 2;
        } else {
            $data['type'] = 4;
        }

        $hash = $client->createHash($data);
        $data['hash'] = $hash;

        try {
            $result = $client->api("/user/{$_POST['userId']}/charge", 'POST', $data);
        }  catch (VGS_Client_Exception $e) {
            $errors = $client->container['meta'] ?: ($client->container['error'] ?: $e->getMessage());
        }
    } else {
        if (!empty($_GET['action'])) {
            $action_request = true;
            if ($_GET['action'] == 'capture') {
                try {
                    $result = $client->api("/order/{$_GET['orderId']}/capture", 'POST', array());
                }  catch (VGS_Client_Exception $e) {
                    $errors = $client->container['meta'] ?: ($client->container['error'] ?: $e->getMessage());
                }
            } else if ($_GET['action'] == 'credit') {
                try {
                    $result = $client->api("/order/{$_GET['orderId']}/credit", 'POST', array('description' => 'Credited by SDK.'));
                }  catch (VGS_Client_Exception $e) {
                    $errors = $client->container['meta'] ?: ($client->container['error'] ?: $e->getMessage());
                }
            } else if ($_GET['action'] == 'cancel') {
                try {
                    $result = $client->api("/order/{$_GET['orderId']}/cancel", 'POST', array());
                }  catch (VGS_Client_Exception $e) {
                    $errors = $client->container['meta'] ?: ($client->container['error'] ?: $e->getMessage());
                }
            }
        }
    }
?>

<html>
<head>
    <title>Direct Payment example</title>
    <link rel="stylesheet" href="css/style.css" />
</head>
<body>
<?php
    if ($result) {
        if ($result['purchaseFlow'] == '1') {
            if ($result['status'] == 2) {
                echo "Payment processed OK. Order id: {$result['orderId']}. <a href=\"index.php?action=credit&orderId={$result['orderId']}\">Credit</a>";
            } else if ($result['status'] == 3) {
                echo "Payment credited OK. Order id: {$result['orderId']}.";
            }
        } else {
            if ($result['status'] == 4) {
                echo "Payment authorized OK. Order id: {$result['orderId']}. <a href=\"index.php?action=capture&orderId={$result['orderId']}\">Capture</a>&nbsp;<a href=\"index.php?action=cancel&orderId={$result['orderId']}\">Cancel</a>";
            } else if ($result['status'] == 2) {
                echo "Payment captured OK. Order id: {$result['orderId']}. <a href=\"index.php?action=credit&orderId={$result['orderId']}\">Credit</a>";
            } else if ($result['status'] == 3) {
                echo "Payment credited OK. Order id: {$result['orderId']}.";
            } else if ($result['status'] == -2) {
                echo "Payment cancelled. Order id: {$result['orderId']}.";
            } else {
                echo "Unexpected result!";
                var_dump($result);
            }
        }
    } else {
        if ( $action_request) {
            if ($errors) {
                echo "Error returned from API.";
                var_dump($errors);
            } else {
                echo "Unknown error.";
            }
        }
    }
?>
<div class="main">
    <div>
        <form method="post" action="index.php" class="direct-payment-form">
            <fieldset>
                <legend>Direct payment</legend>
                <div class="field">
                    <label for="userId">User id</label>
                    <input type="text" id="userId" name="userId" value="286662">
                </div>
                <div class="field">
                    <label for="requestReference">Request ref</label>
                    <input type="text" id="requestReference" name="requestReference" value="ABC123">
                </div>
                <div class="field">
                    <label for="clientReference">Client ref</label>
                    <input type="text" id="clientReference" name="clientReference" value="10001">
                </div>
                <div class="field">
                    <label for="paymentOptions">Payment options</label>
                    <input type="text" id="paymentOptions" name="paymentOptions" value="2">
                </div>
                <div class="field">
                    <label for="purchaseFlow">Payment flow</label>
                    <select id="purchaseFlow" name="purchaseFlow">
                        <option value="DIRECT">DIRECT</option>
                        <option value="AUTHORIZE">AUTHORIZE</option>
                    </select>
                </div>
                <div class="field">
                    <label for="sellerUserId">Seller user id(Only for p2p)</label>
                    <input type="text" id="sellerUserId" name="sellerUserId" value="">
                </div>
                <div class="field">
                    <label for="tag">Tag</label>
                    <input type="text" id="tag" name="tag" value="">
                </div>
            </fieldset>
            <fieldset>
                <legend>Item</legend>
                <div class="field">
                    <label for="productName">Name</label>
                    <input type="text" id="productName" name="productName" value="Product name" />
                </div>
                <div class="field">
                    <label for="productPrice">Price</label>
                    <input type="text" id="productPrice" name="productPrice" value="100">
                </div>
                <div class="field">
                    <label for="productId">Product id</label>
                    <input type="text" id="productId" name="productId" value="">
                </div>
                <div class="field">
                    <label for="clientItemReference">Client item ref</label>
                    <input type="text" id="clientItemReference" name="clientItemReference" value="">
                </div>
                <div class="field">
                    <label for="itemType">Type</label>
                    <input type="text" id="itemType" name="itemType" value="">
                </div>
                <div class="field">
                    <label for="itemDescription">Description</label>
                    <input type="text" id="itemDescription" name="itemDescription" value="">
                </div>
                <div class="field">
                    <label for="vat">Vat</label>
                    <input type="text" id="vat" name="vat" value="">
                </div>
                <div class="field">
                    <label for="quantity">Quantity</label>
                    <input type="text" id="quantity" name="quantity" value="">
                </div>

                <div class="submit-div">
                    <input type="submit" class="submit-button" value="Send">
                </div>
            </fieldset>
        </form>
    </div>
</div>
</body>
</html>