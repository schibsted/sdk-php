<?php if (!isset($_GET['callback'])) {
    die("ERROR: Example needs 'callback' and 'cancel' GET parameters. These are URLs for where the user is taken after survey!");
}
?>
<html>
<head>
    <title>Cancellation survey</title>
    <style>
        body {
            text-align: center;
            font-family: verdana;
            font-size: 90%;
        }
        #container {
            width: 600px;
            text-align: left;
            margin: 0px auto;


        }
    </style>
</head>
<body>
<div id="container">
    <h1>Survey</h1>
    <?php if ($_POST) : ?>
    <p>Thank you for taking this time to help us improve our service.</p>
    <a href="<?php echo $_GET['callback']?>">Cancel subscription now</a>

    <?php var_dump(array('GET' => $_GET, 'POST' => $_POST)); ?>
    <?php else: ?>
    <p>Please take this time to fill out this form to help us improve our service.</p>
    <h2>Questions</h2>
    <form method="POST">
        <div class="question">
            <h3>1. What is the primary reason for canceling your subscription?</h3>
            <ol>
                <li><label><input type="radio" name="q1"  value="Just want to try the initial period first."/> Just want to try the initial period first.</label></li>
                <li><label><input type="radio" name="q1"  value="It costs too much."/> It costs too much.</label></li>
                <li><label><input type="radio" name="q1"  value="The content is not good enough."/> The content is not good enough.</label></li>
                <li><label><input type="radio" name="q1"  value="I am going to a competing service."/> I am going to a competing service.</label></li>
                <li><label><input type="radio" name="q1"  value="I was not using it enough."/> I was not using it enough.</label></li>
                <li><label><input type="radio" name="q1"  value="No reaons."/> No reaons.</label></li>
            </ol>
        </div>

        <div class="question">
            <h3>2. I would/will renew subscription if/when</h3>
            <ol>
                <li><label><input type="radio" name="q2"  value="Never."/> Never.</label></li>
                <li><label><input type="radio" name="q2"  value="I don`t know."/> I don`t know.</label></li>
                <li><label><input type="radio" name="q2"  value="My initial period runs out and i used the service."/> My initial period runs out and i used the service.</label></li>
                <li><label><input type="radio" name="q2"  value="If it becomes cheaper."/> If it becomes cheaper.</label></li>
                <li><label><input type="radio" name="q2"  value="A friend tells me it has improved."/> A friend tells me it has improved.</label></li>
            </ol>
        </div>

        <div class="question">
            <h3>3. If we gave you a car, right now, would you stay?</h3>
            <ol>
                <li><label><input type="checkbox" name="q3" value="Offer rejected" /> No.</li>
                <li><a href="http://www.youtube.com/watch?v=oHg5SJYRHA0">Hell Yes, take me there now</a>
            </ol>
        </div>
        <input type="submit" />
        <a href="<?php echo isset($_GET['cancel'])?$_GET['cancel']:'http://example.com'?>">Cancel</a>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
