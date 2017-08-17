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
    }

    /**
     * Retreive api credentials from application api credential table.
     *
     * @param string $iss Payload of the assertion
     * @return array Credentials that minimally contain the public_key and the scope of the credential record
     */
    protected function getApiCredential($payload)
    {
        $credentials = $this->ApiCredentials->find()
                ->where([$this->ApiCredentials->aliasField('client_id') => $payload->iss])
                ->hydrate(false)
                ->first();

        return $credentials ? $credentials : [];
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
