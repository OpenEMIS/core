<?php

namespace App\Auth;

use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Auth\BaseAuthenticate;
use Cake\ORM\TableRegistry;

class Saml2Authenticate extends BaseAuthenticate
{

    public function authenticate(Request $request, Response $response)
    {   
        $session = $request->session();

        if ($session->check('Saml2.userAttribute')) {
            $userAttribute = $session->read('Saml2.userAttribute');
            $AuthenticationTypeAttributesTable = TableRegistry::get('AuthenticationTypeAttributes');
            $userNameField = $AuthenticationTypeAttributesTable->find()
                ->where([
                    $AuthenticationTypeAttributesTable->aliasField('authentication_type') => 'Saml2',
                    $AuthenticationTypeAttributesTable->aliasField('attribute_field') => 'saml_username_mapping'
                ])
                ->first();
            if (isset($userNameField['value'])) {
                $userNameField = $userNameField['value'];
            } else {
                return false;
            }
            $userName = $userAttribute[$userNameField][0];
            // $emailArray = explode('@', $email);
            // $userName = $emailArray[0];
            // $hostedDomain = $emailArray[1];
            $isFound = $this->_findUser($userName);
            if ($isFound) {
                return $isFound;
            } else {
                return false;
            }
        } else {
            return false;
        }
        

        // if ($session->check('Google.tokenData')) {
        // 	$tokenData = $session->read('Google.tokenData');
        //     // Remove session for the token data after it has been used.
        //     $session->delete('Google.tokenData');
        //     $email = $tokenData['payload']['email'];
        //     $emailArray = explode('@', $tokenData['payload']['email']);
        //     $userName = $emailArray[0];
        //     $hostedDomain = $emailArray[1];
        //     $configHD = $session->read('Google.hostedDomain');
        //     // Additional check just in case the hosted domain check fail
        //     if (!empty($configHD) && strtolower($hostedDomain) != strtolower($configHD)) {
        //     	return false;
        //     } else {
        //     	$isFound = $this->_findUser($userName);

	       //      // If user is found login, if not do create user logic
	       //      if ($isFound) {
	       //          return $isFound;
	       //      } else {
	       //      	$client = $session->read('Google.client');
	       //          $ServiceOAuth2Object = new \Google_Service_Oauth2($client);
	       //  		$me = $ServiceOAuth2Object->userinfo->get();
        //             $userInfo = [
        //                 'id' => $me->getId(),
        //                 'firstName' => $me->getFamilyName(),
        //                 'lastName' => $me->getGivenName(),
        //                 'gender' => $me->getGender(),
        //                 'email' => $me->getEmail(),
        //                 'verifiedEmail' => $me->getVerifiedEmail(),
        //                 'locale' => $me->getLocale(),
        //                 'link' => $me->getLink(),
        //                 'picture' => $me->getPicture(),
        //             ];

        //             $User = TableRegistry::get('User.Users');
        //             $event = $User->dispatchEvent('Model.Auth.createAuthorisedUser', [$userName, $userInfo], $this);
        //             return $this->_findUser($event->result);
	       //      }
        //     }
        // } else {
        //     return false;
        // }
    }
}
