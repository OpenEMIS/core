<?php

namespace App\Auth;

use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Auth\BaseAuthenticate;
use Cake\ORM\TableRegistry;

// 3rd party xlsx writer library
require_once(ROOT . DS . 'vendor' . DS  . 'googlephpapi' . DS . 'src' . DS . 'Google' . DS . 'autoload.php');

class GoogleAuthenticate extends BaseAuthenticate
{

    public function authenticate(Request $request, Response $response)
    {
        $fields = $this->_config['fields'];
        $session = $request->session();
        if ($session->check('Google.tokenData')) {
        	$tokenData = $session->read('Google.tokenData');
            // Remove session for the token data after it has been used.
            $session->delete('Google.tokenData');
            $email = $tokenData['payload']['email'];
            $emailArray = explode('@', $tokenData['payload']['email']);
            $userName = $emailArray[0];
            $hostedDomain = $emailArray[1];
            $configHD = $session->read('Google.hostedDomain');
            // Additional check just in case the hosted domain check fail
            if (strtolower($hostedDomain) != strtolower($configHD)) {
            	return false;
            } else {
            	$isFound = $this->_findUser($userName);

	            // If user is found login, if not do create user logic
	            if ($isFound) {
	                return $isFound;
	            } else {

	            	$client = $session->read('Google.client');
	                $ServiceOAuth2Object = new \Google_Service_Oauth2($client);
	        		$me = $ServiceOAuth2Object->userinfo->get();
                    $userInfo = [
                        'id' => $me->getId(),
                        'firstName' => $me->getFamilyName(),
                        'lastName' => $me->getGivenName(),
                        'gender' => $me->getGender(),
                        'email' => $me->getEmail(),
                        'verifiedEmail' => $me->getVerifiedEmail(),
                        'locale' => $me->getLocale(),
                        'link' => $me->getLink(),
                        'picture' => $me->getPicture(),
                    ];
                    $User = TableRegistry::get('User.Users');
                    $User->dispatchEvent('Model.Auth.onCreateUser', [$userName, $userInfo], $this);
	            }
            }
        } else {
            return false;
        }
    }
}
