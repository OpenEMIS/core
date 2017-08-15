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
     * @param string $iss Client ID or API Key that can uniquely identify the record
     * @return array Credentials that minimally contain the public_key and the scope of the credential record
     */
    protected function getApiCredential($iss)
    {
        return $this->ApiCredentials->find()
                ->where([$this->ApiCredentials->aliasField('client_id') => $iss])
                ->hydrate(false)
                ->first();
    }

    /**
     * Retreive application signature.
     *
     * @param string $signature Application signature
     * @param string|RS256 $algorithm If algorithm is RS256, the application private key is specified. If HS256, the application salt or 64 character key can be specified
     * @return array An array with two key, signature - Application signature, and algorithm - Signature algorithm
     */
    protected function getApplicationSignature()
    {
        return ['signature' => Configure::read('Application.private.key'), 'algorithm' => 'RS256'];
    }
}
