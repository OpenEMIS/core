<?php
/*
 * Copyright 2010 Google Inc.
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
namespace SSO\OAuth;

use Custom_Auth_OAuth2;

/**
 * The Google API Client
 * https://github.com/google/google-api-php-client
 *
 * POCOR-9799: This class originally extended the Google API PHP Client v1
 * `Google_Client` and relied on the v1 `Google_Config`/`Google_IO_*`
 * scaffolding classes. None of those classes exist in the v2 library
 * (`google/apiclient` 2.12.3) that is actually installed, so the class was
 * never loadable in this environment (fatal "Declaration ... must be
 * compatible with" / "Class ... not found" errors). This version keeps the
 * exact same public API and OAuth2 semantics used by
 * SSO\Controller\Component\OAuthAuthComponent and SSO\Auth\OAuthAuthenticate,
 * but replaces the removed v1 scaffolding with a plain configuration array
 * (in place of Google_Config) and no longer extends Google_Client (nothing
 * usable was actually inherited from it - every method consumers call was
 * already overridden here).
 */
class Custom_Client
{
    const LIBVER = "1.1.5";
    const USER_AGENT_SUFFIX = "google-api-php-client/";

  /**
   * @var Custom_Auth_OAuth2 $auth
   */
    private $auth;

  /**
   * @var \Google_IO_Http $io
   */
    private $io;

  /**
   * @var \Google_Cache_Null $cache
   */
    private $cache;

  /**
   * @var \Google_Logger $logger
   */
    private $logger;

  /**
   * Flat key/value configuration store, replacing the removed
   * Google_Config object. Written to by the setXxx() methods below and
   * read back via getClassConfig()/setClassConfig(), matching the exact
   * key names Custom_Auth_OAuth2 already reads.
   *
   * @var array
   */
    private $classConfig = [];

  /** @var array $requestedScopes Scopes requested by the client */
    protected $requestedScopes = array();

  // definitions of services that are discovered.
    protected $services = array();

  // Used to track authenticated state, can't discover services after doing authenticate()
    private $authenticated = false;

    private $oAuthAttributes = [];

  /**
   * Construct the Google Client.
   *
   * @param $config Unused; retained for call-site compatibility
   *   (callers always pass null: `new Custom_Client(null, $oAuthAttributes)`).
   * @param array $oAuthAttributes
   */
    public function __construct($config = null, $oAuthAttributes = [])
    {
        $this->oAuthAttributes = $oAuthAttributes;
    }

  /**
   * Get a string containing the version of the library.
   *
   * @return string
   */
    public function getLibraryVersion()
    {
        return self::LIBVER;
    }

  /**
   * Attempt to exchange a code for an valid authentication token.
   * If $crossClient is set to true, the request body will not include
   * the request_uri argument
   * Helper wrapped around the OAuth 2.0 implementation.
   *
   * @param $code string code from the IdP
   * @param $crossClient boolean, whether this is a cross-client authentication
   * @return string token
   */
    public function authenticate($code, $crossClient = false)
    {
        $this->authenticated = true;
        return $this->getAuth()->authenticate($code, $crossClient);
    }

  /**
   * Set the auth config from the JSON string provided.
   * @param string $json the configuration json
   * @throws \Google_Exception
   */
    public function setAuthConfig($json)
    {
        $data = json_decode($json);
        $key = isset($data->installed) ? 'installed' : 'web';
        if (!isset($data->$key)) {
            throw new \Google_Exception("Invalid client secret JSON file.");
        }
        $this->setClientId($data->$key->client_id);
        $this->setClientSecret($data->$key->client_secret);
        if (isset($data->$key->redirect_uris)) {
            $this->setRedirectUri($data->$key->redirect_uris[0]);
        }
    }

  /**
   * Set the auth config from the JSON file in the path provided.
   * @param string $file the file location of the client json
   */
    public function setAuthConfigFile($file)
    {
        $this->setAuthConfig(file_get_contents($file));
    }

  /**
   * Set the scopes to be requested. Must be called before createAuthUrl().
   * Will remove any previously configured scopes.
   * @param string|array $scope_or_scopes
   */
    public function setScopes($scope_or_scopes)
    {
        $this->requestedScopes = array();
        $this->addScope($scope_or_scopes);
    }

  /**
   * Adds a scope to be requested as part of the OAuth2.0 flow.
   * Will append any scopes not previously requested to the scope parameter.
   * @param string|array $scope_or_scopes
   */
    public function addScope($scope_or_scopes)
    {
        if (is_string($scope_or_scopes) && !in_array($scope_or_scopes, $this->requestedScopes)) {
            $this->requestedScopes[] = $scope_or_scopes;
        } elseif (is_array($scope_or_scopes)) {
            foreach ($scope_or_scopes as $scope) {
                $this->addScope($scope);
            }
        }
    }

  /**
   * @throws \Google_Auth_Exception
   * @return array
   * @visible For Testing
   */
    public function prepareScopes()
    {
        if (empty($this->requestedScopes)) {
            throw new \Google_Auth_Exception("No scopes specified");
        }
        $scopes = implode(' ', $this->requestedScopes);
        return $scopes;
    }

  /**
   * Set the OAuth 2.0 access token using the string that resulted from calling createAuthUrl()
   * or Custom_Client#getAccessToken().
   * @param string $accessToken JSON encoded string containing in the following format:
   * {"access_token":"TOKEN", "refresh_token":"TOKEN", "token_type":"Bearer",
   *  "expires_in":3600, "id_token":"TOKEN", "created":1320790426}
   */
    public function setAccessToken($accessToken)
    {
        if ($accessToken == 'null') {
            $accessToken = null;
        }
        $this->getAuth()->setAccessToken($accessToken);
    }

  /**
   * Set the authenticator object
   * @param mixed $auth
   */
    public function setAuth($auth)
    {
        $this->auth = $auth;
    }

  /**
   * Set the IO object
   * @param \Google_IO_Http $io
   */
    public function setIo($io)
    {
        $this->io = $io;
    }

  /**
   * Set the Cache object
   * @param \Google_Cache_Null $cache
   */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

  /**
   * Set the Logger object
   * @param \Google_Logger $logger
   */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

  /**
   * Construct the OAuth 2.0 authorization request URI.
   * @return string
   */
    public function createAuthUrl($scope = null)
    {
        if (empty($scope)) {
            $scope = $this->prepareScopes();
        }
        return $this->getAuth()->createAuthUrl($scope);
    }

  /**
   * Get the OAuth 2.0 access token.
   * @return string|null $accessToken JSON encoded string in the following format:
   * {"access_token":"TOKEN", "refresh_token":"TOKEN", "token_type":"Bearer",
   *  "expires_in":3600,"id_token":"TOKEN", "created":1320790426}
   */
    public function getAccessToken()
    {
        $token = $this->getAuth()->getAccessToken();
        // The response is json encoded, so could be the string null.
        return (null == $token || 'null' == $token || '[]' == $token) ? null : $token;
    }

  /**
   * Get the OAuth 2.0 refresh token.
   * @return string|null $refreshToken refresh token or null if not available
   */
    public function getRefreshToken()
    {
        return $this->getAuth()->getRefreshToken();
    }

  /**
   * Returns if the access_token is expired.
   * @return bool Returns True if the access_token is expired.
   */
    public function isAccessTokenExpired()
    {
        return $this->getAuth()->isAccessTokenExpired();
    }

  /**
   * Set OAuth 2.0 "state" parameter to achieve per-request customization.
   * @param string $state
   */
    public function setState($state)
    {
        $this->getAuth()->setState($state);
    }

  /**
   * @param string $accessType Possible values for access_type include:
   *  "offline" to request offline access from the user.
   *  "online" to request online access from the user.
   */
    public function setAccessType($accessType)
    {
        $this->classConfig['access_type'] = $accessType;
    }

  /**
   * @param string $approvalPrompt Possible values for approval_prompt include:
   *  "force" to force the approval UI to appear. (This is the default value)
   *  "auto" to request auto-approval when possible.
   */
    public function setApprovalPrompt($approvalPrompt)
    {
        $this->classConfig['approval_prompt'] = $approvalPrompt;
    }

  /**
   * Set the login hint, email address or sub id.
   * @param string $loginHint
   */
    public function setLoginHint($loginHint)
    {
        $this->classConfig['login_hint'] = $loginHint;
    }

  /**
   * Set the application name, this is included in the User-Agent HTTP header.
   * @param string $applicationName
   */
    public function setApplicationName($applicationName)
    {
        $this->classConfig['application_name'] = $applicationName;
    }

  /**
   * Set the OAuth 2.0 Client ID.
   * @param string $clientId
   */
    public function setClientId($clientId)
    {
        $this->classConfig['client_id'] = $clientId;
    }

  /**
   * Set the OAuth 2.0 Client Secret.
   * @param string $clientSecret
   */
    public function setClientSecret($clientSecret)
    {
        $this->classConfig['client_secret'] = $clientSecret;
    }

  /**
   * Set the OAuth 2.0 Redirect URI.
   * @param string $redirectUri
   */
    public function setRedirectUri($redirectUri)
    {
        $this->classConfig['redirect_uri'] = $redirectUri;
    }

  /**
   * If 'plus.login' is included in the list of requested scopes, you can use
   * this method to define types of app activities that your app will write.
   * @param array $requestVisibleActions Array of app activity types
   */
    public function setRequestVisibleActions($requestVisibleActions)
    {
        if (is_array($requestVisibleActions)) {
            $requestVisibleActions = join(" ", $requestVisibleActions);
        }
        $this->classConfig['request_visible_actions'] = $requestVisibleActions;
    }

  /**
   * Set the developer key to use, these are obtained through the API Console.
   * @param string $developerKey
   */
    public function setDeveloperKey($developerKey)
    {
        $this->classConfig['developer_key'] = $developerKey;
    }

  /**
   * The hd (hosted domain) parameter streamlines the login process for
   * hosted accounts. By including the domain of the user, you restrict
   * sign-in to accounts at that domain.
   * @param $hd string - the domain to use.
   */
    public function setHostedDomain($hd)
    {
        $this->classConfig['hd'] = $hd;
    }

  /**
   * Set the prompt hint. Valid values are none, consent and select_account.
   * @param $prompt string
   */
    public function setPrompt($prompt)
    {
        $this->classConfig['prompt'] = $prompt;
    }

  /**
   * openid.realm is a parameter from the OpenID 2.0 protocol, not from OAuth
   * 2.0. It is used in OpenID 2.0 requests to signify the URL-space for which
   * an authentication request is valid.
   * @param $realm string - the URL-space to use.
   */
    public function setOpenidRealm($realm)
    {
        $this->classConfig['openid.realm'] = $realm;
    }

  /**
   * If this is provided with the value true, and the authorization request is
   * granted, the authorization will include any previous authorizations
   * granted to this user/application combination for other scopes.
   * @param $include boolean
   */
    public function setIncludeGrantedScopes($include)
    {
        $this->classConfig['include_granted_scopes'] = $include;
    }

  /**
   * Fetches a fresh OAuth 2.0 access token with the given refresh token.
   * @param string $refreshToken
   */
    public function refreshToken($refreshToken)
    {
        $this->getAuth()->refreshToken($refreshToken);
    }

  /**
   * Revoke an OAuth2 access token or refresh token. This method will revoke the current access
   * token, if a token isn't provided.
   * @param string|null $token The token (access token or a refresh token) that should be revoked.
   * @return boolean Returns True if the revocation was successful, otherwise False.
   */
    public function revokeToken($token = null)
    {
        return $this->getAuth()->revokeToken($token);
    }

  /**
   * Verify an id_token. This method will verify the current id_token, if one
   * isn't provided.
   * @param string|null $token The token (id_token) that should be verified.
   * @return \Google_Auth_LoginTicket Returns a login ticket if the verification was successful.
   */
    public function verifyIdToken($token = null)
    {
        return $this->getAuth()->verifyIdToken($token);
    }

  /**
   * Verify a JWT that was signed with your own certificates.
   *
   * @param $id_token string The JWT token
   * @param $cert_location array of certificates
   * @param $audience string the expected consumer of the token
   * @param $issuer string the expected issuer, defaults to Google
   * @param [$max_expiry] the max lifetime of a token, defaults to MAX_TOKEN_LIFETIME_SECS
   * @return mixed token information if valid, false if not
   */
    public function verifySignedJwt($id_token, $cert_location, $audience = null, $issuer = null, $max_expiry = null)
    {
        $auth = $this->getAuth();
        if (is_null($audience)) {
            $audience = $this->getClassConfig($auth, 'client_id');
        }
        if (is_null($issuer)) {
            $issuer = $this->oAuthAttributes['issuer'] ?? null;
        }
        $certs = [];
        return $auth->verifySignedJwtWithCerts($id_token, $certs, $audience, $issuer, $max_expiry);
    }

  /**
   * @param $creds \Google_Auth_AssertionCredentials
   */
    public function setAssertionCredentials($creds)
    {
        $this->getAuth()->setAssertionCredentials($creds);
    }

  /**
   * @return Custom_Auth_OAuth2 Authentication implementation
   */
    public function getAuth()
    {
        if (!isset($this->auth)) {
            $this->auth = new Custom_Auth_OAuth2($this);

            $oAuthAttributes = $this->oAuthAttributes;

            if (isset($oAuthAttributes['authorization_endpoint'])) {
                $this->auth->setAuthUri($oAuthAttributes['authorization_endpoint']);
            }

            if (isset($oAuthAttributes['token_endpoint'])) {
                $this->auth->setTokenUri($oAuthAttributes['token_endpoint']);
            }

            if (isset($oAuthAttributes['revocation_endpoint'])) {
                $this->auth->setRevokeUri($oAuthAttributes['revocation_endpoint']);
            }

            if (isset($oAuthAttributes['issuer'])) {
                $this->auth->setIssuer($oAuthAttributes['issuer']);
            }

            if (isset($oAuthAttributes['jwks_uri'])) {
                $this->auth->setJwksUri($oAuthAttributes['jwks_uri']);
            }
        }
        return $this->auth;
    }

  /**
   * @return \Google_IO_Http IO implementation
   */
    public function getIo()
    {
        if (!isset($this->io)) {
            $this->io = new \Google_IO_Http($this);
        }
        return $this->io;
    }

  /**
   * @return \Google_Cache_Null Cache implementation
   */
    public function getCache()
    {
        if (!isset($this->cache)) {
            $this->cache = new \Google_Cache_Null($this);
        }
        return $this->cache;
    }

  /**
   * @return \Google_Logger Logger implementation
   */
    public function getLogger()
    {
        if (!isset($this->logger)) {
            $this->logger = new \Google_Logger($this);
        }
        return $this->logger;
    }

  /**
   * Retrieve custom configuration for a specific class.
   * The $class argument is accepted for call-site compatibility but is
   * not used to scope storage - this codebase only ever configures a
   * single OAuth2 handler instance per client.
   * @param $class string|object - unused, kept for compatibility
   * @param $key string optional - key to retrieve
   * @return mixed
   */
    public function getClassConfig($class, $key = null)
    {
        if ($key === null) {
            return $this->classConfig;
        }
        return $this->classConfig[$key] ?? null;
    }

  /**
   * Set configuration specific to a given class.
   * @param $class string|object - unused, kept for compatibility
   * @param $config string key or an array of configuration values
   * @param $value string optional - if $config is a key, the value
   */
    public function setClassConfig($class, $config, $value = null)
    {
        if (is_array($config)) {
            foreach ($config as $configKey => $configValue) {
                $this->classConfig[$configKey] = $configValue;
            }
            return;
        }
        $this->classConfig[$config] = $value;
    }

  /**
   * @return string the base URL to use for calls to the APIs
   */
    public function getBasePath()
    {
        return $this->classConfig['base_path'] ?? '';
    }

  /**
   * @return string the name of the application
   */
    public function getApplicationName()
    {
        return $this->classConfig['application_name'] ?? '';
    }

  /**
   * Are we running in Google AppEngine?
   * return bool
   */
    public function isAppEngine()
    {
        return (isset($_SERVER['SERVER_SOFTWARE']) &&
        strpos($_SERVER['SERVER_SOFTWARE'], 'Google App Engine') !== false);
    }
}
