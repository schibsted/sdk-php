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
        'purchaseFlow' => $_POST['purchaseFlow'],
        'title' => $_POST['title'],
        'items' => array(
            array('vat' => 2500, 'price' => $_POST['price'] * 100, 'description' => $_POST['description'],)
        )
    );
    try {
        $result = $client->api('/paylink', 'POST', $data);
    }  catch (VGS_Client_Exception $e) {
        $errors = $client->container['meta'] ?: ($client->container['error'] ?: $e->getMessage());
    }
}
if ($result) {
    ?>
    <table>
        <tr><th>title</th><td><?php echo $result['title']?></td></tr>
        <tr><th>expires</th><td><?php echo $result['expires']?></td></tr>
        <tr><th>url</th><td><a href="<?php echo $result['shortUrl']?>"><?php echo $result['shortUrl']?></a></td></tr>
        <tr><th colspan="2" align="left">Paylink Items:</th></tr>
        <?php foreach ($result['items'] as $item) : ?>
            <tr><th>&nbsp;</th><td><?php echo $item['description']?> : <?php echo $item['currency']?> <?php echo $item['price']/100;?>,- (<?php echo $item['vat']/100;?>% VAT)</td></tr>
        <?php endforeach; ?>
    </table>
    <pre><?php print_r($result); ?></pre>
<?php      
} else {
 ?>
    <h3>Create paylink</h3>
    <form method="POST">
        <label>Paylink Title<br><textarea name="title"><?php echo isset($_POST['title'])?$_POST['title']:''?></textarea></label><br>
        <?php if (array_key_exists('title', $errors)): ?><strong style="color:red"><?php echo $errors['title']?></strong><br><?php unset($errors['title']); endif;?>
        <label>Payment Flow<br><select name="purchaseFlow"><option value="DIRECT">Direct</option><option value="AUTHORIZE">Reservation (Authorize/Capture)</option></select></label><br>
        <?php if (array_key_exists('purchaseFlow', $errors)): ?><strong style="color:red"><?php echo $errors['purchaseFlow']?></strong><br><?php unset($errors['purchaseFlow']); endif;?>
        <label>Paylink Item Description<br><textarea name="description"><?php echo isset($_POST['description'])?$_POST['description']:''?></textarea></label><br>
        <?php if (array_key_exists('description', $errors)): ?><strong style="color:red"><?php echo $errors['description']?></strong><br><?php unset($errors['description']); endif;?>
        <label>Item Price<br><input type="number" name="price" value="<?php echo isset($_POST['price'])?$_POST['price']:''?>" /></label><br>
        <?php if (array_key_exists('price', $errors)): ?><strong style="color:red"><?php echo $errors['price']?></strong><br><?php unset($errors['price']); endif;?>
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
}
    ?>
</body>
</html>