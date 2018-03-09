<?php
namespace App\Controller;

use Cake\Core\Configure;
use OAuth\Controller\AbstractOAuthController;

class OAuthController extends AbstractOAuthController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadModel('ApiCredentials');
        $this->loadModel('ApiScopes');
    }

    /**
     * Retreive api credentials from application api credential table.
     *
     * @param string $iss Payload of the assertion
     * @return array Credentials that minimally contain the public_key and the scope of the credential record
     */
    protected function getApiCredential($payload)
    {
        $issuer = $payload->iss;
        
        $credential = $this->ApiCredentials
            ->find()
            ->contain(['ApiScopes'])
            ->where([$this->ApiCredentials->aliasField('client_id') => $issuer])
            ->hydrate(false)
            ->first();

        if (!is_null($credential)) {
            $scopeList = [];

            if (!empty($credential['api_scopes'])) {
                foreach ($credential['api_scopes'] as $obj) {
                    $scopeList[] = $obj['name'];
                }

                if (property_exists($payload, 'scope') && !empty($payload->scope)) {
                    $requestedScope = [];
                    $tempList = explode(',', trim($payload->scope));

                    foreach ($tempList as $obj) {
                        $requestedScope[] = trim($obj);
                    }
                    $scopeList = array_intersect($scopeList, $requestedScope);
                }
            }

            $credential['scope'] = $scopeList;
            unset($credential['api_scopes']);
            return $credential;
        }

        return [];
    }

    /**
     * Retreive application signature.
     *
     * @return array An array with two key, signature - Application signature, and algorithm - Signature algorithm
     */
    protected function getSignatureParams()
    {
        return ['key' => Configure::read('Application.private.key'), 'algorithm' => 'RS256'];
    }
}
