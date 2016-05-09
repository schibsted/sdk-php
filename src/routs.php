<?php

$app->get('/', 'Controllers\\Index::index');

$app->get('/test/api', 'Controllers\\Api::index');

$app->get('/capture', 'Controllers\\Capture::index');
$app->get('/client-login', 'Controllers\\ClientLogin::index');

