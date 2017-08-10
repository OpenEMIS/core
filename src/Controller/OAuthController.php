<?php
namespace App\Controller;

use Cake\Utility\Security;
use Firebase\JWT\JWT;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Core\Configure;
use App\Controller\UsersController as BaseController;

class OAuthController extends BaseController
{
    public function initialize()
    {
        // $this->allowedAction[] = 'auth';
        $this->allowedAction[] = 'token';
        parent::initialize();

        $header = getallheaders();
        if (isset($header['Origin'])) {
            $this->checkOrigin($header['Origin']);
        }
    }

    private function checkOrigin($origin)
    {
        $ServerCredentialsTable = TableRegistry::get('ServerCredentials');
        $hasServerOrigin = $ServerCredentialsTable
            ->find()
            ->where([$ServerCredentialsTable->aliasField('authorised_domain') => $origin])
            ->count();
        if ($hasServerOrigin) {
            header('Access-Control-Allow-Origin: '.$origin);
        }
    }

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

                    $ServerCredentialsTable = TableRegistry::get('ServerCredentials');

                    $credentials = $ServerCredentialsTable
                        ->find()
                        ->where([$ServerCredentialsTable->aliasField('client_id') => $payload->iss])
                        ->first();

                    if (empty($credentials)) {
                        throw new UnauthorizedException();
                    }

                    // To set to one hour expiry
                    $expireIn = 3600;

                    $token = JWT::decode($assertion, $credentials->public_key, ['RS256']);
                    $token->iat = Time::now()->toUnixString();
                    $token->exp = intval(Time::now()->toUnixString()) + $expireIn;
                    $token = JWT::encode($token, Configure::read('Application.private.key'), 'RS256');

                    $serialize = [
                        'access_token' => $token,
                        'expire_in' => $expireIn,
                        'token_type' => 'Bearer',
                        '_serialize' => [
                            'token_type',
                            'expire_in',
                            'access_token'
                        ]
                    ];
                    $this->set('access_token', $token);
                    $this->set('expire_in', $expireIn);
                    $this->set('token_type', 'Bearer');
                    $this->set('_serialize', ['token_type', 'expire_in', 'access_token']);
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
