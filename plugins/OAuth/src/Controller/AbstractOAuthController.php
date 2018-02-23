<?php
namespace OAuth\Controller;

use Firebase\JWT\JWT;
use Cake\I18n\Time;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Core\Configure;
use Cake\Controller\Controller;

abstract class AbstractOAuthController extends Controller
{
    protected $tokenExpiry = 3600; // To set to one hour expiry

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
    }

    /**
     * Retreive api credentials from application api credential table.
     *
     * @param string $iss Payload of the assertion
     * @return array Credentials that minimally contain the public_key and the scope of the credential record
     */
    abstract protected function getApiCredential($payload);

    /**
     * Retreive application signature.
     *
     * @return array An array with two key, signature - Application signature, and algorithm - Signature algorithm
     */
    abstract protected function getSignatureParams();

    private function _requestCodeFields()
    {
        foreach (['response_type', 'client_id', 'redirect_uri'] as $field) {
            $value = $this->request->query($field);
            if (empty($value) || !is_string($value)) {
                return false;
            }
        }
        return true;
    }

    private function _requestTokenFields()
    {
        foreach (['client_id', 'client_secret', 'redirect_uri'] as $field) {
            $value = $this->request->data($field);
            if (empty($value) || !is_string($value)) {
                return false;
            }
        }
        return true;
    }

    public function auth()
    {
        $responseType = $this->request->query('response_type');
        $clientId = $this->request->query('client_id');
        $redirectUri = $this->request->query('redirect_uri');
        if ($this->request->is('post')) {
            if ($this->_requestCodeFields()) {
                $user = $this->Auth->identify();
                if ($user) {
                    $userId = $user['id'];
                    $code = JWT::encode([
                            'sub' => [
                                'user_id' => $userId,
                                'client_id' => $clientId,
                                'redirect_uri' => $redirectUri
                            ],
                            'exp' => time() + 3600
                        ], Configure::read('Application.private.key'), 'RS256');
                    $url = $redirectUri.'?code='.$code;
                    $this->redirect($url);
                }
            }
        }
        $this->set(compact('responseType', 'clientId', 'redirectUri'));
    }

    public function token()
    {
        if ($this->request->is('post')) {
            $grantType = $this->request->data('grant_type');
            switch ($grantType) {
                case 'urn:ietf:params:oauth:grant-type:jwt-bearer':
                    $assertion = $this->request->data('assertion');
                    $tks = explode('.', $assertion);
                    if (count($tks) != 3) {
                        throw new UnauthorizedException('Wrong number of segments');
                    }
                    list($headb64, $bodyb64, $cryptob64) = $tks;
                    if (null === ($header = JWT::jsonDecode(JWT::urlsafeB64Decode($headb64)))) {
                        throw new UnauthorizedException('Invalid header encoding');
                    }
                    if (null === $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64))) {
                        throw new UnauthorizedException('Invalid claims encoding');
                    }
                    if (!(property_exists($payload, 'iss'))) {
                        throw new UnauthorizedException('No client id specified');
                    }

                    $credentials = $this->getApiCredential($payload);

                    if (!$credentials) {
                        throw new UnauthorizedException();
                    }

                    if (!array_key_exists('public_key', $credentials)) {
                        throw new UnauthorizedException('Public Key is missing from getApiCredential()');
                    }

                    $scope = [];
                    if (array_key_exists('scope', $credentials)) {
                        $scope = $credentials['scope'];
                    }

                    $expireIn = $this->tokenExpiry;
                    $unixTimestamp = intval(Time::now()->toUnixString());

                    $token = JWT::decode($assertion, $credentials['public_key'], ['RS256']);
                    $token->scope = $scope;
                    $token->iat = $unixTimestamp;
                    $token->exp = $unixTimestamp + $expireIn;

                    $signatureParams = $this->getSignatureParams();
                    $accessToken = JWT::encode($token, $signatureParams['key'], $signatureParams['algorithm']);

                    $serialize = [
                        'access_token' => $accessToken,
                        'expire_in' => $expireIn,
                        'token_type' => 'Bearer',
                        '_serialize' => [
                            'token_type',
                            'expire_in',
                            'access_token'
                        ]
                    ];
                    $this->set($serialize);
                    break;

                case 'authorization_code':
                    break;

                default:
                    throw new UnauthorizedException();
                    break;
            }
        } else {
            throw new UnauthorizedException();
        }
    }
}
