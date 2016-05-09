<?php

namespace Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Component\Templating\TemplateNameParser;
use Symfony\Component\Templating\Loader\FilesystemLoader;

class Capture
{
    public function index(Request $request, Application $app)
    {
        $loader = new FilesystemLoader(__DIR__.'/../views/%name%');
        $templating = new PhpEngine(new TemplateNameParser(), $loader);

        return new Response($templating->render('capture-index.php', array()), 201);
    }
}