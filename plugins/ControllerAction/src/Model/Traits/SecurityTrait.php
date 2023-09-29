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
        $cookieName = 'session_token';
        $desiredPath = "/";
        $paramArr = explode('.', $params);
        if (count($paramArr) != 2) {
            throw new SecurityException('Wrong number of segments');
        }
        $payload = $this->urlsafeB64Decode($paramArr[0]);
        $signature = $this->urlsafeB64Decode($paramArr[1]);
        $payload = json_decode($payload, true);
        $params = $payload;
        foreach ($_COOKIE as $name => $value) {
            if ($name === $cookieName) {
                // Check if the cookie's path matches the desired path
                $cookiePath = $_SERVER['REQUEST_URI']; // Get the current request path
                if ($cookiePath === $desiredPath) {
                    // Select this cookie
                    $selectedSessionToken = $value;
                    break; // Exit the loop once the desired cookie is found
                }
            }
        }
        $sessionToken = $selectedSessionToken ?? null;
        $params['session_token'] = $sessionToken;
        $jsonParamWithSessionTocken = json_encode($params);
        $new_signature = Security::hash($jsonParamWithSessionTocken, 'sha256', true);
        if ($signature !== $new_signature) {
            throw new SecurityException('Wrong session token');
        }
        return $payload;
    }

    public function paramsEncode($params = [])
    {
        $sessionToken = bin2hex(random_bytes(32));
        $cookieName = 'session_token';
        $desiredPath = "/";
        // Set the session token as an HTTP cookie
        setcookie($cookieName, $sessionToken, [
            'expires' => 0,
            'path' => $desiredPath,
            'secure' => true,
            'httponly' => true
        ]);
        foreach ($_COOKIE as $name => $value) {
            if ($name === $cookieName) {
                $cookiePath = $_SERVER['REQUEST_URI']; // Get the current request path
                if ($cookiePath === $desiredPath) {
                    $selectedSessionToken = $value;
                    break; // Exit the loop once the desired cookie is found
                }
            }
        }
        $sessionToken = $selectedSessionToken ?? null;
        $jsonParam = json_encode($params);
        $base64Param = $this->urlsafeB64Encode($jsonParam);
        $params['session_token'] = $sessionToken;
        $jsonParamWithSessionTocken = json_encode($params);
        $signature = Security::hash($jsonParamWithSessionTocken, 'sha256', true);
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
