<?php
namespace SSO\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use OneLogin_Saml2_Auth;

class SamlAuthenticate extends BaseAuthenticate
{
    public function authenticate(Request $request, Response $response)
    {
        $session = $request->session();
        $samlAttributes = $this->config('authAttribute');
        $setting['sp'] = [
            'entityId' => $samlAttributes['sp_entity_id'],
            'assertionConsumerService' => [
                'url' => $samlAttributes['sp_acs'],
            ],
            'singleLogoutService' => [
                'url' => $samlAttributes['sp_slo'],
            ],
            'NameIDFormat' => $samlAttributes['sp_name_id_format'],
        ];

        $setting['idp'] = [
            'entityId' => $samlAttributes['idp_entity_id'],
            'singleSignOnService' => [
                'url' => $samlAttributes['idp_sso'],
                'binding' => $samlAttributes['idp_sso_binding']
            ],
            'singleLogoutService' => [
                'url' => $samlAttributes['idp_slo'],
                'binding' => $samlAttributes['idp_slo_binding']
            ],
        ];
        $this->addCertFingerPrintInformation($setting, $samlAttributes);
        $saml = $this->saml = new OneLogin_Saml2_Auth($setting);
        $saml->processResponse();
        $userAttribute = $saml->getAttributes();
        if ($userAttribute) {
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
                        'email' => isset($userAttribute[$fields['mapped_email']][0]) ? $userAttribute[$fields['mapped_email']][0] : '',
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

    private function addCertFingerPrintInformation(&$setting, $attributes)
    {
        $arr = [
            'certFingerprint' => 'idp_cert_fingerprint',
            'certFingerprintAlgorithm' => 'idp_cert_fingerprint_algorithm',
            'x509cert' => 'idp_x509cert',
            'privateKey' => 'sp_private_key'
        ];

        foreach ($arr as $cert => $value) {
            if (!empty($attributes[$value])) {
                $type = explode('_', $value)[0];
                $setting[$type][$cert] = $attributes[$value];
            }
        }
    }
}
