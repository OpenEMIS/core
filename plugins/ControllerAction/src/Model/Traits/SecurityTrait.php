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

    public function getQueryString($queryString = null, $name = 'queryString')
    {
        $query = isset($_GET[$name]) ? $_GET[$name] : null;

        if (is_null($query)) {
            return null;
        }

        $query = $this->paramsDecode($query);

        if (is_null($queryString)) {
            return $query;
        } elseif (is_array($queryString)) {
            return array_intersect_key($query, array_flip($queryString));
        } elseif (!isset($query[$queryString])) {
            return null;
        } else {
            return $query[$queryString];
        }
    }

    public function setQueryString($url, $params, $name = 'queryString')
    {
        if (is_array($url)) {
            $url[$name] = $this->paramsEncode($params);
        } elseif (is_string($url)) {
            if (strpos($url, '?')) {
                $url .= '&'.$name.'='.$this->paramsEncode($params);
            } else {
                $url .= '?'.$name.'='.$this->paramsEncode($params);
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
        if (!isset($payload[$sessionId])) {
            throw new SecurityException('No session id in payload');
        } else {
            $checkPayload = $payload;
            $checkPayload[$sessionId] = session_id();
            $checkSignature = Security::hash(json_encode($checkPayload), 'sha256', true);
            if ($signature !== $checkSignature) {
                throw new SecurityException('Query String has been tampered');
            }
        }
        unset($payload[$sessionId]);
        return $payload;
    }

    public function paramsEncode($params = [])
    {
        $sessionId = Security::hash('session_id', 'sha256');
        $params[$sessionId] = session_id();
        $jsonParam = json_encode($params);
        $base64Param = $this->urlsafeB64Encode($jsonParam);
        $signature = Security::hash($jsonParam, 'sha256', true);
        $base64Signature = $this->urlsafeB64Encode($signature);
        return "$base64Param.$base64Signature";
    }

    public function getIdKeys(Table $model, $ids, $addAlias = true)
    {
        $primaryKey = $model->primaryKey();
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
