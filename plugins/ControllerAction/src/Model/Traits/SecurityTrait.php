<?php

namespace ControllerAction\Model\Traits;

use Cake\Utility\Security;
use Cake\Controller\Exception\SecurityException;
use Cake\ORM\Table;

trait SecurityTrait
{
    public function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    public function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    // POCOR-8074-QueryStringProfile start
    public function getDecodedQueryArray($queryString = null)
    {
        //POCOR-8074-5 start
        $queryStingParamName = 'queryString';
        if ($queryString != null) {
            try { // POCOR-8080 for Institutions Menu
                $decodedQuery = $this->paramsDecode($queryString);
                return $decodedQuery;
            } catch (\Exception $exception) {
                $queryString = null;
                $queryStingParamName = $queryString;
            }
        } //POCOR-8074-5 end

        if ($queryString == null) {
            // POCOR-8080 if getQueryString is called from inside ControllerAction
            $request = null;
            if (!property_exists($this, 'request')) {
                try {
                    // POCOR-8157 start: for different type of objects with requests
                    $request = null;

                    if (property_exists($this, '_table')) {
                        if (method_exists($this->_table, 'getRequest')) {
                            if (!property_exists($this->_table, 'request')) {
                                $request = $this->_table->getRequest();
                            } else {
                                $request = $this->_table->request;
                            }
                        } else {
                            $request = $this->_table->request;
                        }
                    } else {
                        try {
                            $controller = $this->getController();
                                if($controller){
                                    $request = $controller->getRequest();
                                }
                        } catch (\Exception $exception) {

                        }
                    }
                } catch (\Exception $exception) {
                }
            }
            if (!$request && property_exists($this, 'request')) {
                // POCOR-8157 end
                $request = $this->request;
            }
            if ($request) {
                $params = $request->getAttribute('params');
                $query = $request->getQuery();
                $decodedQuery = null; //POCOR-9715: ensure defined before the referer-fallback check below, regardless of which branch runs
                if (isset($query[$queryStingParamName])) { //to filter if the URL already contain querystring
                    $queryString = $query[$queryStingParamName];
                    // POCOR-9788: decode it here, immediately - same as the `pass`
                    // segment branch below. Without this, $decodedQuery stayed null
                    // even though a real, valid queryString param WAS present, so
                    // the POCOR-9715 referer-fallback a few lines down incorrectly
                    // took precedence and decoded the *referer's* context instead
                    // (e.g. the index page's own institution_id) - silently
                    // discarding the real payload (class_id, report_card_id, etc.)
                    // this branch found. Falls through to the final fallback below
                    // if decode genuinely fails (e.g. a real signature mismatch).
                    try {
                        $decodedQuery = $this->paramsDecode($queryString);
                    } catch (\Exception $exception) {
                    }
                } elseif (isset($query['querystring'])) { //to filter if the URL already contain querystring
                    $queryString = $query['querystring'];
                    // POCOR-9788: same fix as the branch above.
                    try {
                        $decodedQuery = $this->paramsDecode($queryString);
                    } catch (\Exception $exception) {
                    }
                } elseif (isset($params['pass'])) { //to filter if the URL already contain querystring
                    // POCOR-8074-6
                    foreach ($params['pass'] as $queryString) {
                        try {
                            $decodedQuery = $this->paramsDecode($queryString);
                            if ($decodedQuery) {
                                break; // Exit loop if decoding successful
                            }
                        } catch (\Exception $exception) {

                        }
                    }
                }

                //POCOR-9715
                // POCOR-9675: restore staff/institution context from referer on POST/DELETE (e.g. modal delete)
                if ($decodedQuery == null) {
                    $referer = $request->getHeaderLine('Referer');
                    if ($referer && preg_match('#/index/([^/?]+)#', $referer, $matches)) {
                        try {
                            $decodedQuery = $this->paramsDecode($matches[1]);
                        } catch (\Exception $exception) {
                        }
                    }
                }
                //POCOR-9715
            } else {
//                $class = __CLASS__;
//                $line = __LINE__;
//                if ($queryString == null) {
//                    $queryString = "";
//                }
//                Log::debug('Could not process query {query} in {class}, {line}', ['query' => $queryString, 'class' => $class, 'line' => $line]);
                return null;
            }
        }
        if ($decodedQuery == null) {
            try { // POCOR-8080 for Institutions Menu
                $decodedQuery = $this->paramsDecode($queryString);
            } catch (\Exception $exception) {
                return null;
            }
        }
        return $decodedQuery;
    }

    public function getDecodedQueryParam($attribute = null, $decodedQuery = null)
    {
        if (empty($decodedQuery)) {
            return null; //POCOR-8115;
        }
        if (is_null($attribute)) {
            return $decodedQuery;
        } elseif (is_array($attribute)) {
            return array_intersect_key($decodedQuery, array_flip($attribute));
        } elseif (!isset($decodedQuery[$attribute])) {
            return null;
        } else {
            return $decodedQuery[$attribute];
        }
    }

    public function getQueryString($attribute = null, $queryString = null)
    {
        $decodedQuery = $this->getDecodedQueryArray($queryString);
        $decodedParam = $this->getDecodedQueryParam($attribute, $decodedQuery);

        return $decodedParam;
    }//POCOR-8074-QueryStringProfile end

    public function setQueryString($url, $params, $name = 'queryString')
    {
        if (is_array($url)) {
            $url['?'][$name] = $this->paramsEncode($params); //POCOR-8074-QueryStringProfile
        } elseif (is_string($url)) {
            if (strpos($url, '?')) {
                $url .= '&' . $name . '=' . $this->paramsEncode($params);
            } else {
                $url .= '?' . $name . '=' . $this->paramsEncode($params);
            }
        }

        return $url;
    }

    /**
     * Returns a stable, per-login secret token used to sign/verify paramsEncode()/
     * paramsDecode() payloads - generated once and persisted in session DATA
     * (not the session ID itself), so it survives whatever rotates the bare
     * session_id() between a link being rendered and being clicked.
     *
     * Root-cause fix: paramsEncode()/paramsDecode() used to sign against the raw
     * session_id(). session_id() is documented to change under session
     * regeneration while $_SESSION data is preserved (session_regenerate_id(true)
     * is the standard pattern for exactly that: keep the user logged in, issue a
     * new ID). Observed live on openemis-dev.openemis.org/POCOR-9788: users stay
     * logged in throughout (Auth data - itself session data - survives), yet a
     * "queryString" link rendered moments earlier fails to decode with "Query
     * String has been tampered", because the OLD session_id() no longer matches
     * the CURRENT one by the time the link is clicked. Binding to a token stored
     * IN session data instead of the volatile session ID removes that fragility
     * without weakening the anti-tamper property: the payload is still rejected
     * if altered, and a genuinely different session (no matching token in ITS
     * $_SESSION) still cannot forge a valid signature.
     */
    private function getSecureContextToken()
    {
        if (empty($_SESSION['_secureContextToken'])) {
            $_SESSION['_secureContextToken'] = Security::hash(uniqid('sct_', true) . random_int(0, PHP_INT_MAX), 'sha256');
        }
        return $_SESSION['_secureContextToken'];
    }

    public function paramsDecode($params)
    {
        $paramArr = explode('.', $params);
        if (count($paramArr) != 2) {
            throw new SecurityException('Wrong number of segments');
        }
        list($payload, $signature) = $paramArr;
        $payload = $this->urlsafeB64Decode($payload);
        $signature = $this->urlsafeB64Decode($signature);
        $payload = json_decode($payload, true);
        $sessionId = Security::hash('session_id', 'sha256');
        $checkPayload = $payload;
        $checkPayload[$sessionId] = $this->getSecureContextToken();
        $checkSignature = Security::hash(json_encode($checkPayload), 'sha256', true);
        if ($signature !== $checkSignature) {
            throw new SecurityException('Query String has been tampered');
        }
        return $payload;
    }

    public function paramsEncode($params = [])
    {
        // Ensure $params is an array
        if (!is_array($params)) {
            $params = [];
        }
        $sessionId = Security::hash('session_id', 'sha256');
        $jsonParam = json_encode($params);
        $base64Param = $this->urlsafeB64Encode($jsonParam);
        $params[$sessionId] = $this->getSecureContextToken();
        $jsonParamWithSessionTocken = json_encode($params);
        $signature = Security::hash($jsonParamWithSessionTocken, 'sha256', true);
        $base64Signature = $this->urlsafeB64Encode($signature);
        return "$base64Param.$base64Signature";
    }

    public function getIdKeys(Table $model, $ids, $addAlias = true)
    {
        $primaryKey = $model->getPrimaryKey();
        $idKeys = [];
        if (!empty($ids)) {
            if (is_array($primaryKey)) {
                foreach ($primaryKey as $key) {
                    if ($addAlias) {
                        $idKeys[$model->aliasField($key)] = $ids[$key];
                    } else {
                        $idKeys[$key] = $ids[$key];
                    }
                }
            } else {
                if ($addAlias) {
                    $idKeys[$model->aliasField($primaryKey)] = $ids[$primaryKey];
                } else {
                    $idKeys[$primaryKey] = $ids[$primaryKey];
                }
            }
        }
        return $idKeys;
    }
}
