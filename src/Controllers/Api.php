<?php

namespace Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Api
{
    public function index(Request $request, Application $app)
    {
        return new Response('Api::index!', 201);
    }
}