<?php

namespace Jcanda\Gotomeeting\Traits;

use Carbon\Carbon;
use Jcanda\Gotomeeting\DirectLogin;
use Jcanda\Gotomeeting\Exception\GotoAuthenticateException;

trait AccessObject
{
    private $authObject; //holds all the values returned after auth
    private $tokenExpiryMinutes = 7 * 24 * 60; //expire the authObject every 7 days - re-auth for a new one

    function getOrganizerKey()
    {
        return $this->authObject->organizer_key;
    }

    function getAccountKey()
    {
        return $this->authObject->account_key;
    }

    function getAccessToken()
    {
        return $this->authObject->access_token;
    }

    function refreshToken()
    {
        $this->clearAccessObject(); //clear cached object
        $this->directLogin(); //perform fresh directLogin to get a new authObject
    }

    function clearAccessObject()
    {
        unset($_SESSION["GOTO_ACCESS_OBJECT"]);
        return $this;
    }

    function hasAccessObject()
    {
        session_start();
        if (isset($_SESSION["GOTO_ACCESS_OBJECT"])) {
            print_r($_SESSION["GOTO_ACCESS_OBJECT"]);
            return true;
        }

        return false;
    }

    private function directLogin()
    {
        $directAuth = new DirectLogin();

        try {
            $this->authObject = $directAuth->authenticate(); //the method returns authObject
        } catch (GotoAuthenticateException $e) {
            $this->clearAccessObject(); //make sure the object is cleared from the cache to force a login retry
            throw $e; //bubble the exception up by rethrowing
        }

        if (!$this->authObject) {
            new GotoAuthenticateException('Error Sessión no iniciada, contraseña o usuario correctos?');
            $this->clearAccessObject(); //make sure the object is cleared from the cache to force a login retry
        } else {
            $this->rememberAccessObject($this->authObject); //cache the authObject
        }

        return $this->authObject;
    }

    private function rememberAccessObject($authObject)
    {
        $_SESSION['GOTO_ACCESS_OBJECT'] = $authObject;
    }

    private function checkAccessObject($authType)
    {
        //If no Authenticate Object, perform authentication to receive new access object with fresh tokens, etc.
        if (!$this->hasAccessObject()) {

            switch (strtolower($authType)) {

                case 'direct':

                    $this->directLogin();
                    break;

                case 'oauth2':

                    //not yet implemented
                    break;

                default:

                    $this->directLogin();
                    break;
            }
        } else {
            $this->authObject = $this->getAccessObject();
        }
    }

    private function getAccessObject()
    {
        return $_SESSION['GOTO_ACCESS_OBJECT'];
    }
}