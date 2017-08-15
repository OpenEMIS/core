<?php
namespace App\Controller;

use Cake\Core\Configure;
use OAuth\Controller\AbstractOAuthController;

class OAuthController extends AbstractOAuthController
{
    protected $tokenExpiry = 3600; // overwrite this value if you need the token to last longer

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');

        // Load the model that contains the Credentials
        //$this->loadModel('ApiCredentials');
    }

    /**
     * Retreive api credentials from application api credential table.
     *
     * @param object $payload The payload containing the Client ID or API Key that can uniquely identify the record
     * @return array Credentials that minimally contain the public_key and the scope of the credential record
     */
    protected function getApiCredential($payload)
    {
        /*
        return $this->ApiCredentials->find()
                ->where([$this->ApiCredentials->aliasField('client_id') => $payload->iss])
                ->hydrate(false)
                ->first();
        */

        return ['public_key' => '', 'scope' => ''];
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
