<?php

namespace Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class Index
{
    public function index(Request $request, Application $app)
    {
        return new Response('Index::index!', 201);
    }
}