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

    protected function _checkFields(Request $request, array $fields)
    {
        foreach ([$fields['username']] as $field) {
            $value = $request->data($field);
            if (empty($value) || !is_string($value)) {
                return false;
            }
        }
        return true;
    }

    public function authenticate(Request $request, Response $response)
    {	
        $fields = $this->_config['fields'];
        $session = $request->session();
        if ($session->check('Google.tokenData')) {
        	$tokenData = $session->read('Google.tokenData');
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

	        		// Basic user information gotten from Google
					// $lastName = $me->getFamilyName();
					// $firstName = $me->getGivenName();
					// $gender = $me->getGender();

					// $openemisNo = '';
	        		// pr($me);die;
					// $UsersTable = TableRegistry::get('Users');



	                return false;
	            }
            }
        } else {
            return false;
        }
    }
}
