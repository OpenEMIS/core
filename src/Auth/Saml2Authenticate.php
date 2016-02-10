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
            $userNameField = $AuthenticationTypeAttributesTable->getTypeAttributeValues('Saml2');
            if (isset($userNameField['saml_username_mapping'])) {
                $userNameField = $userNameField['saml_username_mapping'];
            } else {
                return false;
            }
            $userName = $userAttribute[$userNameField][0];
            $isFound = $this->_findUser($userName);
            if ($isFound) {
                return $isFound;
            } else {
                $fields = $AuthenticationTypeAttributesTable->getTypeAttributeValues('Saml2');
                $userInfo = [
                    'firstName' => isset($userAttribute[$fields['saml_first_name_mapping']][0]) ? $userAttribute[$fields['saml_first_name_mapping']][0] : ' - ',
                    'lastName' => isset($userAttribute[$fields['saml_last_name_mapping']][0]) ? $userAttribute[$fields['saml_last_name_mapping']][0] : ' - ',
                    'gender' => isset($userAttribute[$fields['saml_gender_mapping']][0]) ? $userAttribute[$fields['saml_gender_mapping']][0] : ' - ',
                    'dateOfBirth' => isset($userAttribute[$fields['saml_date_of_birth_mapping']][0]) ? $userAttribute[$fields['saml_date_of_birth_mapping']][0] : ' - ',
                ];

                $User = TableRegistry::get('User.Users');
                $event = $User->dispatchEvent('Model.Auth.createAuthorisedUser', [$userName, $userInfo], $this);
                if ($event->result === false) {
                    return false;
                } else {
                    return $this->_findUser($event->result);
                }
            }
        } else {
            return false;
        }
    }
}
