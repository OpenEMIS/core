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
        if ($queryString == null) {
            if (property_exists($this, 'request')) {
                $request = $this->request;
                $params = $request->getAttribute('params');
                $query = $request->getQuery();
                if (isset($query['queryString'])) { //to filter if the URL already contain querystring
                    $queryString = $query['queryString'];
                    $this->request = $request->withQueryParams(['querystring' => $queryString,
                        'queryString' => $queryString]);
                } elseif (isset($query['querystring'])) { //to filter if the URL already contain querystring
                    $queryString = $query['querystring'];
                    $this->request = $request->withQueryParams(['querystring' => $queryString,
                        'queryString' => $queryString]);
                } elseif (isset($params['pass'])) { //to filter if the URL already contain querystring
                    if (isset($params['pass'][1])) {
                        $queryString = $params['pass'][1];
                        $this->request = $request->withQueryParams(['querystring' => $queryString,
                            'queryString' => $queryString]);
                    }
                }
            }else{
                return null;
            }
        }

        $decodedQuery = $this->paramsDecode($queryString);
        return $decodedQuery;
    }

    public function getDecodedQueryParam($queryString = null, $decodedQuery = null)
    {
        if (is_null($queryString)) {
            return $decodedQuery;
        } elseif (is_array($queryString)) {
            return array_intersect_key($decodedQuery, array_flip($queryString));
        } elseif (!isset($decodedQuery[$queryString])) {
            return null;
        } else {
            return $decodedQuery[$queryString];
        }
    }

    public function getQueryString($queryString = null)
    {
        $decodedQuery = $this->getDecodedQueryArray();
        $decodedParam = $this->getDecodedQueryParam($queryString, $decodedQuery);
        return $decodedParam;
    }
    //POCOR-8074-QueryStringProfile end

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
