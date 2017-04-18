<?php

namespace Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Component\Templating\TemplateNameParser;
use Symfony\Component\Templating\Loader\FilesystemLoader;

require_once('src/Client.php');

class ClientLogin
{
    public function index(Request $request, Application $app)
    {
        $SPID_CREDENTIALS = $app['SPID_CREDENTIALS'];

        // overwrite redirect url to be HERE
        $SPID_CREDENTIALS[\VGS_Client::REDIRECT_URI] = "http://{$_SERVER['HTTP_HOST']}/client-login";
        $SPID_CREDENTIALS[\VGS_Client::COOKIE] = false; // disable cookie support for SDK

        // Instantiate the SDK client
        $client = new \VGS_Client($SPID_CREDENTIALS);
        $client->argSeparator = '&';

        // When a logout redirect comes from SPiD, delete the local session
        if (isset($_GET['logout'])) {
            unset($_SESSION['sdk']);
        }

        // Code is part of the redirect back from SPiD, redirect to self to remove it from URL
        // since it may only be used once, and it has been used to create session
        if (isset($_GET['code'])) {
            // Get/Check if we have local session, creates ones if code GET param comes
            $_SESSION['sdk'] = $client->getSession();
            header( "Location: ". $client->getCurrentURI(array(), array('code','login','logout'))) ;
            exit;
        }

        $user = array();
        $session = isset($_SESSION['sdk']) ? $_SESSION['sdk'] : false;

        // If we have session, that means we are logged in.
        if ($session) {
            // Authorize the client with the session saved user token
            $client->setAccessToken($session['access_token']);
            try {
                // Grab the logged in user's User Object, /me will include the entire User object
                $user = $client->api('/me');
            } catch (VGS_Client_Exception $e) {
                if ($e->getCode() == 401) {
                    try {
                        // refresh tokens using the session saved refresh token
                        $client->refreshAccessToken($session['refresh_token']);
                        $_SESSION['sdk']['access_token'] = $client->getAccessToken();
                        $_SESSION['sdk']['refresh_token'] = $client->getRefreshToken();
                        // Sesssion refreshed with valid tokens
                        header( "Location: ". $client->getCurrentURI(array(), array('code','login','error','logout', 'order_id', 'spid_page'))) ;
                        exit;
                    } catch (Exception $e2) {
                        /* falls back to $e message bellow */

                    }
                }
                if ($e->getCode() == 400) {
                    header( "Location: ". $client->getLoginURI(array('redirect_uri' => $client->getCurrentURI(array(), array('logout','error','code', 'order_id', 'spid_page')))));
                    exit;
                }

                // API exception, show message, remove session as it is probably not usable
                unset($_SESSION['sdk']);
            }
        }

        $loader = new FilesystemLoader(__DIR__.'/../views/%name%');
        $templating = new PhpEngine(new TemplateNameParser(), $loader);

        return new Response($templating->render('client-login.php', array('user' => $user, 'client' => $client, 'session' => $session)), 201);
    }
}