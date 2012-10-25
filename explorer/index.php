<?php
require_once('../src/Client.php');
require_once('config.php');

if (!$SPID_CREDENTIALS) {
    die('ERROR: You must configure $SPID_CREDENTIALS  in config.php first!');
}

$SPID_CREDENTIALS[VGS_Client::REDIRECT_URI] = "http://{$_SERVER['HTTP_HOST']}/explorer/";
$client = new VGS_Client($SPID_CREDENTIALS);

if (isset($_GET['logout'])) {
    $client->deleteSession();
}
$session = $client->getSession();

if ($session && isset($_GET['code'])) {
    header( "Location: ".$SPID_CREDENTIALS[VGS_Client::REDIRECT_URI] ) ;
    exit;
}


?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<link href="css/prettify.css" type="text/css" rel="stylesheet" />	
	<link rel="stylesheet" href="css/default.css" />
	<script src="js/jquery-1.5.2.min.js"></script>
	<script src="js/jquery.tmpl.js"></script>
	<script src="js/format-json.js"></script>			
	<script src="js/global.js"></script>
	<script>
		VG.api.methods = <?php

		try {
			$res = $client->api('/endpoints');
			echo json_encode($res);
		} catch (\Exception $e) {
			echo '[]';
		}
		?>;
			
		$(document).ready(function () {
			
			
			$('#methodsTemplate').tmpl({'methods': VG.api.methods}).appendTo('#methods');

			$.each(VG.events, function (selector, object) {
				var $element = $(selector);
				$.each(object, function (type, func) {
					$element.live(type, func);
				});
			});

			$('#methodSelection').change();
		});
	</script>
		
	<script type="text/javascript" src="js/prettify.js"></script>
	<title>VG Services Explorer</title>
</head>

<body>
	
<script id="methodsTemplate" type="text/x-jquery-tmpl">
	<select id="methodSelection" name="method">
	{{each(i, item) methods}}
		<option value="${i}">${item.name}</option>
	{{/each}}
	</select>
</script>

<script id="httpMethodTemplate" type="text/x-jquery-tmpl">
	<select id="httpMethodSelection" name="httpMethod">
	{{each(i, item) methods}}
		<option value="${i}">${item.name}</option>
	{{/each}}
	</select>
</script>	

<?php if ($session) {  ?>
<script id="fieldsTemplate" type="text/x-jquery-tmpl">
	{{if fields.length == 0}}
	<div class="field">
		<p class="description" style="float:none;text-align:center;">Selected method doesn't have any parameters.</p>
	</div>
	{{/if}}
	{{each(i, field) fields}}
	<div class="field">
		<label for="">${field}</label>
		<input type="text" name="${field}" index="${requiredFields.indexOf(field)}" {{if requiredFields.indexOf(field) >= 0}}required{{/if}}>
	</div>
	{{/each}}
</script>
<div class="formContainer">
<form id="apiForm">	
	<div class="field header">
		<h1>Authenticated as user id: <?php echo $session['user_id']; ?> <a href="?logout=1" class="logout">logout</a></h1>
		<div style="clear:both;"></div>
		<h1 style="float: left;">Method</h1>
		<div id="methods"></div>
		<div id="httpMethods"></div>

	</div>	
    <div id="meta">
        <h3></h3>
        <p></p>
    </div>
	<div class="field">
		<label for="">asasdas</label>
		<input type="text" name="">
	</div>
	<div class="field footer">		
		<input class="" type="submit" value="Submit">		
	</div>			
</form>
	<div id="exampleUrlContainer">

		<h1 id="exampleUrlLink">Show Example URL's</h1>
		<ul id="exampleUrls">
			<li><a href="<?php echo $client->getPurchaseURI(array('redirect_uri' => $client->getCurrentURI(array(), array('logout')))) ?>">Purchase Any Product</a></li>
			<li><a href="<?php echo $client->getPurchaseURI(array('product_id' => '1', 'redirect_uri' => $client->getCurrentURI(array(), array('logout')))) ?>">Purchase Product 1</a></li>
			<li><a href="<?php echo $client->getPurchaseURI(array('product_id' => '1', 'display' => 'popup','redirect_uri'  => $client->getCurrentURI(array(), array('logout')))) ?>&keepThis=true&TB_iframe=true&height=380&width=300" class="thickbox">1 Click Purchase</a></li>
			<li><a href="<?php echo $client->getAccountURI() ?>">Account page</a></li>
			<li><a href="<?php echo $client->getPurchaseHistoryURI() ?>">Purchase history page</a></li>
		</ul>
	</div>	
</div>
<div id="output">
	<img id="loader" src="images/loader-small.gif" alt="loading.." />

	<pre class="prettyprint">
	</pre>
</div>
<?php
} else {
?>
	<div style="text-align:center;margin-top:200px;">
		<strong><a href="<?php echo $client->getLoginURI(array('redirect_uri' => $client->getCurrentURI(array(), array('logout'))))?>">Login</a></strong>
	</div>
<?php
}
?>
</body>
</html>

