<?php

namespace ControllerAction\Model\Traits;

use Cake\Utility\Security;
use Cake\Controller\Exception\SecurityException;
use Cake\ORM\Table;
use Cake\Log\Log;

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
                if (isset($query[$queryStingParamName])) { //to filter if the URL already contain querystring
                    $queryString = $query[$queryStingParamName];
                } elseif (isset($query['querystring'])) { //to filter if the URL already contain querystring
                    $queryString = $query['querystring'];
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
        $checkPayload[$sessionId] = session_id();
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
        $params[$sessionId] = session_id();
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
