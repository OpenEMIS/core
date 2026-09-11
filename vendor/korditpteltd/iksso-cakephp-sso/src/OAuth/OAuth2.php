<?php
/*
 * Copyright 2008 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Cake\Http\Client;
use Cake\Log\Log;

/**
 * POCOR-9799: This file originally depended on the Google API PHP Client v1
 * scaffolding classes (Google_Auth_Abstract, Google_Http_Request,
 * Google_IO_Abstract, Google_Cache_Abstract, Google_Logger_Abstract,
 * Google_Auth_Exception, Google_Auth_AssertionCredentials, Google_Utils).
 * None of those classes exist in the v2 library (google/apiclient 2.12.3)
 * that is actually installed, so this file was never loadable in this
 * environment ("Class ... not found" fatals).
 *
 * Every method and code path that existed before is kept - nothing has
 * been removed - but the v1 scaffolding classes are replaced with small,
 * compatible equivalents defined at the bottom of this file
 * (Google_Http_Request, Google_IO_Http, Google_Cache_Null, Google_Logger,
 * Google_Auth_Exception, Google_Auth_AssertionCredentials, Google_Utils),
 * whose actual HTTP transport runs through Cake\Http\Client and whose
 * logging runs through Cake\Log\Log, matching how the rest of this plugin
 * already talks to the framework.
 */
class Custom_Auth_OAuth2 extends Google_Auth_Abstract
{
    private $OAUTH2_REVOKE_URI = 'https://accounts.google.com/o/oauth2/revoke';
    private $OAUTH2_TOKEN_URI = 'https://accounts.google.com/o/oauth2/token';
    private $OAUTH2_AUTH_URL = 'https://accounts.google.com/o/oauth2/auth';
    private $OAUTH2_ISSUER = 'accounts.google.com';
    private $OAUTH2_JWKS_URI = 'https://www.googleapis.com/oauth2/v3/certs';
    const CLOCK_SKEW_SECS = 300; // five minutes in seconds
    const AUTH_TOKEN_LIFETIME_SECS = 300; // five minutes in seconds
    const MAX_TOKEN_LIFETIME_SECS = 86400; // one day in seconds
    const OAUTH2_ISSUER_HTTPS = 'https://accounts.google.com';

  /** @var Google_Auth_AssertionCredentials $assertionCredentials */
    private $assertionCredentials;

  /**
   * @var string The state parameters for CSRF and other forgery protection.
   */
    private $state;

  /**
   * @var array The token bundle.
   */
    private $token = array();

  /**
   * @var Custom_Client the base client
   */
    private $client;

  /**
   * Instantiates the class, but does not initiate the login flow, leaving it
   * to the discretion of the caller.
   */
    public function __construct($client)
    {
        $this->client = $client;
    }

    public function setAuthUri($uri)
    {
        if (!empty($uri)) {
            $this->OAUTH2_AUTH_URL = $uri;
        }
    }

    public function setTokenUri($uri)
    {
        if (!empty($uri)) {
            $this->OAUTH2_TOKEN_URI = $uri;
        }
    }

    public function setRevokeUri($uri)
    {
        if (!empty($uri)) {
            $this->OAUTH2_REVOKE_URI = $uri;
        }
    }

    public function setIssuer($uri)
    {
        if (!empty($uri)) {
            $this->OAUTH2_ISSUER = $uri;
        }
    }

    public function setJwksUri($uri)
    {
        if (!empty($uri)) {
            $this->OAUTH2_JWKS_URI = $uri;
        }
    }

  /**
   * Perform an authenticated / signed apiHttpRequest.
   * This function takes the apiHttpRequest, calls apiAuth->sign on it
   * (which can modify the request in what ever way fits the auth mechanism)
   * and then calls apiCurlIO::makeRequest on the signed request
   *
   * @param Google_Http_Request $request
   * @return Google_Http_Request The resulting HTTP response including the
   * responseHttpCode, responseHeaders and responseBody.
   */
    public function authenticatedRequest(Google_Http_Request $request)
    {
        $request = $this->sign($request);
        return $this->client->getIo()->makeRequest($request);
    }

  /**
   * @param string $code
   * @param boolean $crossClient
   * @throws Google_Auth_Exception
   * @return string
   */
    public function authenticate($code, $crossClient = false)
    {
        if (strlen($code) == 0) {
            throw new Google_Auth_Exception("Invalid code");
        }

        $arguments = array(
          'code' => $code,
          'grant_type' => 'authorization_code',
          'client_id' => $this->client->getClassConfig($this, 'client_id'),
          'client_secret' => $this->client->getClassConfig($this, 'client_secret')
        );

        if ($crossClient !== true) {
            $arguments['redirect_uri'] = $this->client->getClassConfig($this, 'redirect_uri');
        }

        // We got here from the redirect from a successful authorization grant,
        // fetch the access token
        $request = new Google_Http_Request(
        $this->OAUTH2_TOKEN_URI,
        'POST',
        array(),
        $arguments
        );
        $request->disableGzip();
        $response = $this->client->getIo()->makeRequest($request);
        if ($response->getResponseHttpCode() == 200) {
            $this->setAccessToken($response->getResponseBody());
            $this->token['created'] = time();
            return $this->getAccessToken();
        } else {
            $decodedResponse = json_decode($response->getResponseBody(), true);
            $errorText = '';
            if (!empty($decodedResponse['error'])) {
                $errorText = $decodedResponse['error'];
                if (isset($decodedResponse['error_description'])) {
                    $errorText .= ": " . $decodedResponse['error_description'];
                }
            }
            throw new Google_Auth_Exception(
              sprintf(
              "Error fetching OAuth2 access token, message: '%s'",
              $errorText
              ),
              $response->getResponseHttpCode()
            );
        }
    }

  /**
   * Create a URL to obtain user authorization.
   * The authorization endpoint allows the user to first
   * authenticate, and then grant/deny the access request.
   * @param string $scope The scope is expressed as a list of space-delimited strings.
   * @return string
   */
    public function createAuthUrl($scope)
    {
        $params = array(
        'response_type' => 'code',
        'redirect_uri' => $this->client->getClassConfig($this, 'redirect_uri'),
        'client_id' => $this->client->getClassConfig($this, 'client_id'),
        'scope' => $scope,
        'access_type' => $this->client->getClassConfig($this, 'access_type'),
        );

        // Prefer prompt to approval prompt.
        if ($this->client->getClassConfig($this, 'prompt')) {
            $params = $this->maybeAddParam($params, 'prompt');
        } else {
            $params = $this->maybeAddParam($params, 'approval_prompt');
        }
        $params = $this->maybeAddParam($params, 'login_hint');
        $params = $this->maybeAddParam($params, 'hd');
        $params = $this->maybeAddParam($params, 'openid.realm');
        $params = $this->maybeAddParam($params, 'include_granted_scopes');

        // If the list of scopes contains plus.login, add request_visible_actions
        // to auth URL.
        $rva = $this->client->getClassConfig($this, 'request_visible_actions');
        if (strpos($scope, 'plus.login') && strlen($rva) > 0) {
            $params['request_visible_actions'] = $rva;
        }

        if (isset($this->state)) {
            $params['state'] = $this->state;
        }

        return $this->OAUTH2_AUTH_URL . "?" . http_build_query($params, '', '&');
    }

  /**
   * @param string $token
   * @throws Google_Auth_Exception
   */
    public function setAccessToken($token)
    {
        $token = json_decode($token, true);
        if ($token == null) {
            throw new Google_Auth_Exception('Could not json decode the token');
        }
        if (! isset($token['access_token'])) {
            throw new Google_Auth_Exception("Invalid token format");
        }
        $this->token = $token;
    }

    public function getAccessToken()
    {
        return json_encode($this->token);
    }

    public function getRefreshToken()
    {
        if (array_key_exists('refresh_token', $this->token)) {
            return $this->token['refresh_token'];
        } else {
            return null;
        }
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function setAssertionCredentials(Google_Auth_AssertionCredentials $creds)
    {
        $this->assertionCredentials = $creds;
    }

  /**
   * Include an accessToken in a given apiHttpRequest.
   * @param Google_Http_Request $request
   * @return Google_Http_Request
   * @throws Google_Auth_Exception
   */
    public function sign(Google_Http_Request $request)
    {
        // add the developer key to the request before signing it
        if ($this->client->getClassConfig($this, 'developer_key')) {
            $request->setQueryParam('key', $this->client->getClassConfig($this, 'developer_key'));
        }

        // Cannot sign the request without an OAuth access token.
        if (null == $this->token && null == $this->assertionCredentials) {
            return $request;
        }

        // Check if the token is set to expire in the next 30 seconds
        // (or has already expired).
        if ($this->isAccessTokenExpired()) {
            if ($this->assertionCredentials) {
                $this->refreshTokenWithAssertion();
            } else {
                $this->client->getLogger()->debug('OAuth2 access token expired');
                if (! array_key_exists('refresh_token', $this->token)) {
                    $error = "The OAuth 2.0 access token has expired,"
                          ." and a refresh token is not available. Refresh tokens"
                          ." are not returned for responses that were auto-approved.";

                    $this->client->getLogger()->error($error);
                    throw new Google_Auth_Exception($error);
                }
                $this->refreshToken($this->token['refresh_token']);
            }
        }

        $this->client->getLogger()->debug('OAuth2 authentication');

        // Add the OAuth2 header to the request
        $request->setRequestHeaders(
        array('Authorization' => 'Bearer ' . $this->token['access_token'])
        );

        return $request;
    }

  /**
   * Fetches a fresh access token with the given refresh token.
   * @param string $refreshToken
   * @return void
   */
    public function refreshToken($refreshToken)
    {
        $this->refreshTokenRequest(
        array(
          'client_id' => $this->client->getClassConfig($this, 'client_id'),
          'client_secret' => $this->client->getClassConfig($this, 'client_secret'),
          'refresh_token' => $refreshToken,
          'grant_type' => 'refresh_token'
        )
        );
    }

  /**
   * Fetches a fresh access token with a given assertion token.
   * @param Google_Auth_AssertionCredentials $assertionCredentials optional.
   * @return void
   */
    public function refreshTokenWithAssertion($assertionCredentials = null)
    {
        if (!$assertionCredentials) {
            $assertionCredentials = $this->assertionCredentials;
        }

        $cacheKey = $assertionCredentials->getCacheKey();

        if ($cacheKey) {
            // We can check whether we have a token available in the
            // cache. If it is expired, we can retrieve a new one from
            // the assertion.
            $token = $this->client->getCache()->get($cacheKey);
            if ($token) {
                $this->setAccessToken($token);
            }
            if (!$this->isAccessTokenExpired()) {
                return;
            }
        }

        $this->client->getLogger()->debug('OAuth2 access token expired');
        $this->refreshTokenRequest(
        array(
          'grant_type' => 'assertion',
          'assertion_type' => $assertionCredentials->assertionType,
          'assertion' => $assertionCredentials->generateAssertion(),
        )
        );

        if ($cacheKey) {
            // Attempt to cache the token.
            $this->client->getCache()->set(
              $cacheKey,
              $this->getAccessToken()
            );
        }
    }

    private function refreshTokenRequest($params)
    {
        if (isset($params['assertion'])) {
            $this->client->getLogger()->info(
              'OAuth2 access token refresh with Signed JWT assertion grants.'
            );
        } else {
            $this->client->getLogger()->info('OAuth2 access token refresh');
        }

        $http = new Google_Http_Request(
        $this->OAUTH2_TOKEN_URI,
        'POST',
        array(),
        $params
        );
        $http->disableGzip();
        $request = $this->client->getIo()->makeRequest($http);

        $code = $request->getResponseHttpCode();
        $body = $request->getResponseBody();
        if (200 == $code) {
            $token = json_decode($body, true);
            if ($token == null) {
                throw new Google_Auth_Exception("Could not json decode the access token");
            }

            if (! isset($token['access_token']) || ! isset($token['expires_in'])) {
                throw new Google_Auth_Exception("Invalid token format");
            }

            if (isset($token['id_token'])) {
                $this->token['id_token'] = $token['id_token'];
            }
            $this->token['access_token'] = $token['access_token'];
            $this->token['expires_in'] = $token['expires_in'];
            $this->token['created'] = time();
        } else {
            throw new Google_Auth_Exception("Error refreshing the OAuth2 token, message: '$body'", $code);
        }
    }

  /**
   * Revoke an OAuth2 access token or refresh token. This method will revoke the current access
   * token, if a token isn't provided.
   * @throws Google_Auth_Exception
   * @param string|null $token The token (access token or a refresh token) that should be revoked.
   * @return boolean Returns True if the revocation was successful, otherwise False.
   */
    public function revokeToken($token = null)
    {
        if (!$token) {
            if (!$this->token) {
                // Not initialized, no token to actually revoke
                return false;
            } elseif (array_key_exists('refresh_token', $this->token)) {
                $token = $this->token['refresh_token'];
            } else {
                $token = $this->token['access_token'];
            }
        }
        $request = new Google_Http_Request(
        $this->OAUTH2_REVOKE_URI,
        'POST',
        array(),
        "token=$token"
        );
        $request->disableGzip();
        $response = $this->client->getIo()->makeRequest($request);
        $code = $response->getResponseHttpCode();
        if ($code == 200) {
            $this->token = null;
            return true;
        }

        return false;
    }

  /**
   * Returns if the access_token is expired.
   * @return bool Returns True if the access_token is expired.
   */
    public function isAccessTokenExpired()
    {
        if (!$this->token || !isset($this->token['created'])) {
            return true;
        }

        // If the token is set to expire in the next 30 seconds.
        $expired = ($this->token['created']
        + ($this->token['expires_in'] - 30)) < time();

        return $expired;
    }

  // Gets federated sign-on certificates to use for verifying identity tokens.
  // Returns certs as array structure, where keys are key ids, and values
  // are PEM encoded certificates.
    private function getFederatedSignOnCerts()
    {
        $url = $this->client->getClassConfig($this, 'federated_signon_certs_url');

        if (empty($url)) {
            // POCOR-9799: 'federated_signon_certs_url' is never configured
            // anywhere in this codebase - only 'jwks_uri' is, via
            // setJwksUri(). verifySignedJwtWithCerts() below fetches its
            // own keys from $this->OAUTH2_JWKS_URI and does not use the
            // value this method returns, so there is nothing to fetch
            // here; return an empty list instead of failing the whole
            // verifyIdToken() call on a config key that was never wired up.
            return [];
        }

        return $this->retrieveCertsFromLocation($url);
    }

  /**
   * Retrieve and cache a certificates file.
   *
   * @param $url string location
   * @throws Google_Auth_Exception
   * @return array certificates
   */
    public function retrieveCertsFromLocation($url)
    {
        // If we're retrieving a local file, just grab it.
        if ("http" != substr($url, 0, 4)) {
            $file = file_get_contents($url);
            if ($file) {
                return json_decode($file, true);
            } else {
                throw new Google_Auth_Exception(
                  "Failed to retrieve verification certificates: '" .
                  $url . "'."
                );
            }
        }

        // This relies on makeRequest caching certificate responses.
        $request = $this->client->getIo()->makeRequest(
        new Google_Http_Request(
            $url
        )
        );
        if ($request->getResponseHttpCode() == 200) {
            $certs = json_decode($request->getResponseBody(), true);
            if ($certs) {
                return $certs;
            }
        }
        throw new Google_Auth_Exception(
        "Failed to retrieve verification certificates: '" .
        $request->getResponseBody() . "'.",
        $request->getResponseHttpCode()
        );
    }

  /**
   * Verifies an id token and returns the authenticated apiLoginTicket.
   * Throws an exception if the id token is not valid.
   * The audience parameter can be used to control which id tokens are
   * accepted.  By default, the id token must have been issued to this OAuth2 client.
   *
   * @param $id_token
   * @param $audience
   * @return Google_Auth_LoginTicket
   */
    public function verifyIdToken($id_token = null, $audience = null)
    {
        if (!$id_token) {
            $id_token = $this->token['id_token'];
        }
        $certs = $this->getFederatedSignOnCerts();
        if (!$audience) {
            $audience = $this->client->getClassConfig($this, 'client_id');
        }

        return $this->verifySignedJwtWithCerts(
        $id_token,
        $certs,
        $audience,
        array($this->OAUTH2_ISSUER, self::OAUTH2_ISSUER_HTTPS)
        );
    }

  /**
   * Verifies the id token, returns the verified token contents.
   *
   * @param $jwt string the token
   * @param $certs array of certificates
   * @param $required_audience string the expected consumer of the token
   * @param [$issuer] the expected issues, defaults to Google
   * @param [$max_expiry] the max lifetime of a token, defaults to MAX_TOKEN_LIFETIME_SECS
   * @throws Google_Auth_Exception
   * @return mixed token information if valid, false if not
   */
    public function verifySignedJwtWithCerts(
        $jwt,
        $certs,
        $required_audience,
        $issuer = null,
        $max_expiry = null
    ) {
        if (!$max_expiry) {
            // Set the maximum time we will accept a token for.
            $max_expiry = self::MAX_TOKEN_LIFETIME_SECS;
        }

        $segments = explode(".", $jwt);
        if (count($segments) != 3) {
            throw new Google_Auth_Exception("Wrong number of segments in token: $jwt");
        }
        $signed = $segments[0] . "." . $segments[1];
        $signature = Google_Utils::urlSafeB64Decode($segments[2]);

        // Parse envelope.
        $envelope = json_decode(Google_Utils::urlSafeB64Decode($segments[0]), true);
        if (!$envelope) {
            throw new Google_Auth_Exception("Can't parse token envelope: " . $segments[0]);
        }

        $message = "$segments[0].$segments[1]";
        $http = new Client();
        $keys = [];

        $response = $http->get($this->OAUTH2_JWKS_URI, [], ['redirect' => 3]);
        if ($response->getStatusCode() == 200) {
            $body = json_decode($response->getStringBody(), true);
            if (isset($body['keys'])) {
                $keys = $body['keys'];
            }
        }

        $algorithm = [
        'HS256' => 'sha256',
        'HS512' => 'sha512',
        'HS384' => 'sha384',
        'RS256' => 'sha256',
        ];
        $verified = false;
        foreach ($keys as $key) {
            $rsa = new \phpseclib\Crypt\RSA();

            if (isset($algorithm[$key['alg']])) {
                $bigInt = substr($key['alg'], -3, 3);
                $rsa->loadKey([
                  'n' => new \phpseclib\Math\BigInteger(Google_Utils::urlSafeB64Decode($key['n']), $bigInt),
                  'e' => new \phpseclib\Math\BigInteger(Google_Utils::urlSafeB64Decode($key['e']), $bigInt)
                ]);
                $rsa->setHash($algorithm[$key['alg']]);
                $rsa->setSignatureMode($rsa::SIGNATURE_PKCS1);
                $verified = $rsa->verify($message, $signature);
                if ($verified) {
                        break;
                }
            }
        }

        if (!$verified) {
            throw new Google_Auth_Exception("Invalid token signature: $jwt");
        }

        // Parse token
        $json_body = Google_Utils::urlSafeB64Decode($segments[1]);
        $payload = json_decode($json_body, true);
        if (!$payload) {
            throw new Google_Auth_Exception("Can't parse token payload: " . $segments[1]);
        }

        // Check issued-at timestamp
        $iat = 0;
        if (array_key_exists("iat", $payload)) {
            $iat = $payload["iat"];
        }
        if (!$iat) {
            throw new Google_Auth_Exception("No issue time in token: $json_body");
        }
        $earliest = $iat - self::CLOCK_SKEW_SECS;

        // Check expiration timestamp
        $now = time();
        $exp = 0;
        if (array_key_exists("exp", $payload)) {
            $exp = $payload["exp"];
        }
        if (!$exp) {
            throw new Google_Auth_Exception("No expiration time in token: $json_body");
        }
        if ($exp >= $now + $max_expiry) {
            throw new Google_Auth_Exception(
              sprintf("Expiration time too far in future: %s", $json_body)
            );
        }

        $latest = $exp + self::CLOCK_SKEW_SECS;
        if ($now < $earliest) {
            throw new Google_Auth_Exception(
              sprintf(
              "Token used too early, %s < %s: %s",
              $now,
              $earliest,
              $json_body
              )
            );
        }
        if ($now > $latest) {
            throw new Google_Auth_Exception(
              sprintf(
              "Token used too late, %s > %s: %s",
              $now,
              $latest,
              $json_body
              )
            );
        }

        // support HTTP and HTTPS issuers
        // @see https://developers.google.com/identity/sign-in/web/backend-auth
        $iss = $payload['iss'];
        if ($issuer && !in_array($iss, (array) $issuer)) {
            throw new Google_Auth_Exception(
              sprintf(
              "Invalid issuer, %s not in %s: %s",
              $iss,
              "[".implode(",", (array) $issuer)."]",
              $json_body
              )
            );
        }

        // Check audience
        $aud = $payload["aud"];
        if ($aud != $required_audience) {
            throw new Google_Auth_Exception(
              sprintf(
              "Wrong recipient, %s != %s:",
              $aud,
              $required_audience,
              $json_body
              )
            );
        }

        // All good.
        return new Google_Auth_LoginTicket($envelope, $payload);
    }

  /**
   * Add a parameter to the auth params if not empty string.
   */
    private function maybeAddParam($params, $name)
    {
        $param = $this->client->getClassConfig($this, $name);
        if ($param != '') {
            $params[$name] = $param;
        }
        return $params;
    }
}

/**
 * POCOR-9799 compatibility layer.
 *
 * The classes below replace the Google API PHP Client v1 scaffolding that
 * Custom_Auth_OAuth2 (and Client.php's Custom_Client) were written against
 * and that no longer exists in the installed v2 library. Each one keeps
 * the same public surface the original v1 class exposed at its call
 * sites in this plugin, so every method above keeps working exactly as
 * originally written - only the underlying transport/logging/caching is
 * now real, framework-backed code (Cake\Http\Client, Cake\Log\Log)
 * instead of the removed Google_IO_* / Google_Logger_Abstract classes.
 */

/**
 * Minimal replacement for the removed Google_Auth_Abstract (v1 API).
 * Nothing in this plugin calls any parent-inherited behavior - every
 * method Custom_Auth_OAuth2 needs is declared on itself - so this exists
 * purely so `extends Google_Auth_Abstract` keeps compiling.
 */
abstract class Google_Auth_Abstract
{
}

/**
 * Minimal replacement for the removed Google_Auth_Exception (v1 API).
 */
class Google_Auth_Exception extends \Exception
{
}

/**
 * Minimal replacement for the removed Google_Auth_LoginTicket (v1 API).
 * Preserves the exact same public surface the SSO plugin relies on:
 * getAttributes() returning ['envelope' => ..., 'payload' => ...], which
 * SSO\Auth\OAuthAuthenticate and SSO\Controller\Component\OAuthAuthComponent
 * read as $tokenData['payload'].
 */
class Google_Auth_LoginTicket
{
    private $envelope;
    private $payload;

    public function __construct($envelope, $payload)
    {
        $this->envelope = $envelope;
        $this->payload = $payload;
    }

    public function getAttributes()
    {
        return [
            'envelope' => $this->envelope,
            'payload' => $this->payload,
        ];
    }

    public function getUserId()
    {
        return $this->payload['sub'] ?? null;
    }
}

/**
 * Minimal replacement for the removed Google_Utils (v1 API). Only the
 * base64url helpers this plugin actually uses are implemented.
 */
class Google_Utils
{
    public static function urlSafeB64Decode($b64)
    {
        $b64 = str_replace(array('-', '_'), array('+', '/'), $b64);
        return (string) base64_decode($b64);
    }

    public static function urlSafeB64Encode($data)
    {
        return rtrim(strtr(base64_encode((string) $data), '+/', '-_'), '=');
    }
}

/**
 * Minimal replacement for the removed Google_Http_Request (v1 API).
 * Acts as both the outgoing request description and, once
 * Google_IO_Http::makeRequest() has run, the response container -
 * exactly like the original v1 class did (callers read
 * getResponseHttpCode()/getResponseBody() off the same object they built
 * the request with).
 */
class Google_Http_Request
{
    private $url;
    private $requestMethod;
    private $requestHeaders;
    private $postBody;
    private $queryParams = array();
    private $gzipDisabled = false;
    private $responseHttpCode;
    private $responseBody;

    public function __construct($url, $method = 'GET', $headers = array(), $postBody = null)
    {
        $this->url = $url;
        $this->requestMethod = $method;
        $this->requestHeaders = (array) $headers;
        $this->postBody = $postBody;
    }

    public function disableGzip()
    {
        $this->gzipDisabled = true;
    }

    public function isGzipDisabled()
    {
        return $this->gzipDisabled;
    }

    public function setQueryParam($key, $value)
    {
        $this->queryParams[$key] = $value;
    }

    public function getQueryParams()
    {
        return $this->queryParams;
    }

    public function setRequestHeaders(array $headers)
    {
        $this->requestHeaders = array_merge($this->requestHeaders, $headers);
    }

    public function getRequestHeaders()
    {
        return $this->requestHeaders;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    public function getPostBody()
    {
        return $this->postBody;
    }

    public function setResponseHttpCode($code)
    {
        $this->responseHttpCode = $code;
    }

    public function getResponseHttpCode()
    {
        return $this->responseHttpCode;
    }

    public function setResponseBody($body)
    {
        $this->responseBody = $body;
    }

    public function getResponseBody()
    {
        return $this->responseBody;
    }
}

/**
 * Replacement for the removed Google_IO_Curl/Google_IO_Stream (v1 API).
 * Executes a Google_Http_Request over Cake\Http\Client - the same HTTP
 * client already used elsewhere in this plugin (e.g.
 * SSO\Auth\OAuthAuthenticate, SSO\Controller\Component\OAuthAuthComponent)
 * - instead of the removed Google_IO_* curl/stream wrappers, and writes
 * the result back onto the same request object, matching the original
 * makeRequest() contract.
 */
class Google_IO_Http
{
    public function __construct($client = null)
    {
        // $client accepted for constructor-signature compatibility with
        // the removed Google_IO_Abstract subclasses; unused.
    }

    public function makeRequest(Google_Http_Request $request)
    {
        $http = new Client();

        $url = $request->getUrl();
        $params = $request->getQueryParams();
        if (!empty($params)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        }

        $options = [
            'headers' => $request->getRequestHeaders(),
            'redirect' => 3,
        ];

        $method = strtoupper((string) $request->getRequestMethod());
        $body = $request->getPostBody();

        switch ($method) {
            case 'POST':
                $response = $http->post($url, $body, $options);
                break;
            case 'PUT':
                $response = $http->put($url, $body, $options);
                break;
            case 'DELETE':
                $response = $http->delete($url, $body, $options);
                break;
            case 'GET':
            default:
                $response = $http->get($url, [], $options);
                break;
        }

        $request->setResponseHttpCode($response->getStatusCode());
        $request->setResponseBody($response->getStringBody());

        return $request;
    }
}

/**
 * Replacement for the removed Google_Cache_Abstract (v1 API). Used only
 * by the assertion-credentials (service-account) refresh path, which
 * nothing in this codebase currently configures - a safe no-op cache
 * (always a miss) keeps that path correct (it will simply always fetch a
 * fresh token via refreshTokenRequest()) without needing a persistence
 * backend wired up.
 */
class Google_Cache_Null
{
    public function __construct($client = null)
    {
        // Unused; accepted for constructor-signature compatibility.
    }

    public function get($key, $expiration = null)
    {
        return null;
    }

    public function set($key, $value)
    {
        // No-op.
    }

    public function delete($key)
    {
        // No-op.
    }
}

/**
 * Replacement for the removed Google_Logger_Abstract (v1 API). Delegates
 * to Cake\Log\Log, the same logger the rest of this plugin already uses
 * (e.g. SSO\Model\Table\SingleLogoutTable).
 */
class Google_Logger
{
    public function __construct($client = null)
    {
        // Unused; accepted for constructor-signature compatibility.
    }

    public function debug($message)
    {
        Log::write('debug', (string) $message);
    }

    public function info($message)
    {
        Log::write('info', (string) $message);
    }

    public function warning($message)
    {
        Log::write('warning', (string) $message);
    }

    public function error($message)
    {
        Log::write('error', (string) $message);
    }
}

/**
 * Replacement for the removed Google_Auth_AssertionCredentials (v1 API),
 * used for the service-account / JWT Bearer Grant flow (RFC 7523). No
 * service-account credentials are configured anywhere in this codebase
 * today, so this path is not exercised in production, but the class is
 * kept fully implemented (not stubbed) so setAssertionCredentials() /
 * refreshTokenWithAssertion() above keep working if that flow is
 * configured in the future. Signing uses the same phpseclib RSA API
 * already used for ID-token verification in verifySignedJwtWithCerts()
 * above.
 */
class Google_Auth_AssertionCredentials
{
    public $serviceAccountName;
    public $scopes;
    public $privateKey;
    public $privateKeyPassword;
    public $assertionType = 'http://oauth.net/grant_type/jwt/1.0/bearer';
    public $sub;
    public $prn;

    public function __construct(
        $serviceAccountName,
        $scopes,
        $privateKey,
        $privateKeyPassword = 'notasecret',
        $signingAlgorithm = 'RS256',
        $sub = null
    ) {
        $this->serviceAccountName = $serviceAccountName;
        $this->scopes = is_array($scopes) ? implode(' ', $scopes) : $scopes;
        $this->privateKey = $privateKey;
        $this->privateKeyPassword = $privateKeyPassword;
        $this->sub = $sub;
        $this->prn = $sub;
    }

    public function getCacheKey()
    {
        return $this->serviceAccountName . ':' . md5($this->scopes . ($this->sub ?: ''));
    }

    public function generateAssertion()
    {
        $now = time();

        $claims = [
            'iss' => $this->serviceAccountName,
            'scope' => $this->scopes,
            'aud' => 'https://accounts.google.com/o/oauth2/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        if ($this->sub) {
            $claims['sub'] = $this->sub;
        }

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];

        $segments = [
            Google_Utils::urlSafeB64Encode(json_encode($header)),
            Google_Utils::urlSafeB64Encode(json_encode($claims)),
        ];

        $signingInput = implode('.', $segments);

        $rsa = new \phpseclib\Crypt\RSA();
        $rsa->loadKey($this->privateKey);
        if ($this->privateKeyPassword) {
            $rsa->setPassword($this->privateKeyPassword);
        }
        $rsa->setHash('sha256');
        $rsa->setSignatureMode($rsa::SIGNATURE_PKCS1);
        $signature = $rsa->sign($signingInput);

        $segments[] = Google_Utils::urlSafeB64Encode($signature);

        return implode('.', $segments);
    }
}
