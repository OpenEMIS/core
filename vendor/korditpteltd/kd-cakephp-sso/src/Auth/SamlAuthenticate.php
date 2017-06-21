<?php

namespace SSO\Auth;

use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Auth\BaseAuthenticate;
use Cake\ORM\TableRegistry;

class SamlAuthenticate extends BaseAuthenticate
{

    public function authenticate(Request $request, Response $response)
    {
        $session = $request->session();
        if ($session->check('Saml.userAttribute')) {
            $userAttribute = $session->read('Saml.userAttribute');
            $fields = $this->config('mappedFields');
            if (isset($fields['mapped_username'])) {
                $userNameField = $fields['mapped_username'];
            } else {
                return false;
            }
            $userName = $userAttribute[$userNameField][0];
            $isFound = $this->_findUser($userName);
            if ($isFound) {
                return $isFound;
            } else {
                if ($this->config('createUser')) {
                    $userInfo = [
                        'firstName' => isset($userAttribute[$fields['mapped_first_name']][0]) ? $userAttribute[$fields['mapped_first_name']][0] : ' - ',
                        'lastName' => isset($userAttribute[$fields['mapped_last_name']][0]) ? $userAttribute[$fields['mapped_last_name']][0] : ' - ',
                        'gender' => isset($userAttribute[$fields['mapped_gender']][0]) ? $userAttribute[$fields['mapped_gender']][0] : ' - ',
                        'dateOfBirth' => isset($userAttribute[$fields['mapped_date_of_birth']][0]) ? $userAttribute[$fields['mapped_date_of_birth']][0] : ' - ',
                        'role' => isset($userAttribute[$fields['mapped_role']][0]) ? $userAttribute[$fields['mapped_role']][0] : '',
                    ];

                    $User = TableRegistry::get($this->_config['userModel']);
                    $event = $User->dispatchEvent('Model.Auth.createAuthorisedUser', [$userName, $userInfo], $this);
                    if ($event->result === false) {
                        return false;
                    } else {
                        return $this->_findUser($event->result);
                    }
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }
}
