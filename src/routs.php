<?php

$app->get('/', 'Controllers\\Index::index');

$app->get('/test/api', 'Controllers\\Api::index');

