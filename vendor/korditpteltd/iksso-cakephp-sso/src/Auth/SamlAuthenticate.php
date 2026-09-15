<?php
declare(strict_types=1);
namespace SSO\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Http\ServerRequest;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use OneLogin\Saml2\Auth;

class SamlAuthenticate extends BaseAuthenticate
{
    /**
     * Authenticate a user using SAML.
     *
     * @param \Cake\Http\ServerRequest $request Request object.
     * @param \Cake\Http\Response $response Response object.
     * @return array|false User data or false on failure.
     */
    public function authenticate(ServerRequest $request, Response $response)
    {
        $samlAttributes = $this->getConfig('authAttribute');

        if (empty($samlAttributes)) {
            return false;
        }

        $setting = [
            'sp' => [
                'entityId' => $samlAttributes['sp_entity_id'] ?? '',
                'assertionConsumerService' => [
                    'url' => $samlAttributes['sp_acs'] ?? '',
                ],
                'singleLogoutService' => [
                    'url' => $samlAttributes['sp_slo'] ?? '',
                ],
                'NameIDFormat' => $samlAttributes['sp_name_id_format'] ?? '',
            ],
            'idp' => [
                'entityId' => $samlAttributes['idp_entity_id'] ?? '',
                'singleSignOnService' => [
                    'url' => $samlAttributes['idp_sso'] ?? '',
                    'binding' => $samlAttributes['idp_sso_binding'] ?? '',
                ],
                'singleLogoutService' => [
                    'url' => $samlAttributes['idp_slo'] ?? '',
                    'binding' => $samlAttributes['idp_slo_binding'] ?? '',
                ],
            ],
        ];

        $this->addCertFingerPrintInformation($setting, $samlAttributes);

        $saml = new Auth($setting);

        $saml->processResponse();

        if ($saml->getErrors()) {
            return false;
        }

        $userAttribute = $saml->getAttributes();

        if (empty($userAttribute)) {
            return false;
        }

        $fields = $this->getConfig('mappedFields');

        if (empty($fields['mapped_username'])) {
            return false;
        }

        $userNameField = $fields['mapped_username'];

        if (
            !isset($userAttribute[$userNameField]) ||
            empty($userAttribute[$userNameField][0])
        ) {
            return false;
        }

        $userName = $userAttribute[$userNameField][0];

        $isFound = $this->_findUser($userName);

        if ($isFound) {
            return $isFound;
        }

        if (!$this->getConfig('createUser')) {
            return false;
        }

        $userInfo = [
            'firstName' => $this->getMappedAttribute(
                $userAttribute,
                $fields,
                'mapped_first_name',
                ' - '
            ),
            'lastName' => $this->getMappedAttribute(
                $userAttribute,
                $fields,
                'mapped_last_name',
                ' - '
            ),
            'gender' => $this->getMappedAttribute(
                $userAttribute,
                $fields,
                'mapped_gender',
                ' - '
            ),
            'dateOfBirth' => $this->getMappedAttribute(
                $userAttribute,
                $fields,
                'mapped_date_of_birth',
                ' - '
            ),
            'role' => $this->getMappedAttribute(
                $userAttribute,
                $fields,
                'mapped_role',
                ''
            ),
            'email' => $this->getMappedAttribute(
                $userAttribute,
                $fields,
                'mapped_email',
                ''
            ),
        ];

        $userModel = $this->getConfig('userModel');

        if (empty($userModel)) {
            return false;
        }

        $User = TableRegistry::getTableLocator()->get($userModel);

        $event = $User->dispatchEvent(
            'Model.Auth.createAuthorisedUser',
            [$userName, $userInfo],
            $this
        );

        if ($event->getResult() === false) {
            return false;
        }

        return $this->_findUser($event->getResult());
    }

    /**
     * Get a mapped SAML attribute safely.
     *
     * @param array $userAttribute SAML user attributes.
     * @param array $fields Field mapping configuration.
     * @param string $field Mapping field name.
     * @param mixed $default Default value.
     * @return mixed
     */
    private function getMappedAttribute(
        array $userAttribute,
        array $fields,
        string $field,
        mixed $default = ''
    ): mixed {
        if (empty($fields[$field])) {
            return $default;
        }

        $attributeName = $fields[$field];

        if (
            !isset($userAttribute[$attributeName]) ||
            !isset($userAttribute[$attributeName][0])
        ) {
            return $default;
        }

        return $userAttribute[$attributeName][0];
    }

    /**
     * Add SAML certificate information to the configuration.
     *
     * @param array $setting SAML settings.
     * @param array $attributes SAML attributes.
     * @return void
     */
    private function addCertFingerPrintInformation(
        array &$setting,
        array $attributes
    ): void {
        $certificates = [
            'certFingerprint' => 'idp_cert_fingerprint',
            'certFingerprintAlgorithm' => 'idp_cert_fingerprint_algorithm',
            'x509cert' => 'idp_x509cert',
            'privateKey' => 'sp_private_key',
        ];

        foreach ($certificates as $cert => $value) {
            if (empty($attributes[$value])) {
                continue;
            }

            $type = explode('_', $value, 2)[0];

            if (!isset($setting[$type])) {
                $setting[$type] = [];
            }

            $setting[$type][$cert] = $attributes[$value];
        }
    }
}