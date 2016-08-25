<?php

namespace SSO;
use Firebase\JWT\JWT;

class ProcessToken
{
    public static function generateToken($sub, $exp = null, $options = [])
    {
        $keyConfig = [
            'private_key_bits' => 2048
        ];
        $privateKey = openssl_pkey_new($keyConfig);
        $publicKey = openssl_pkey_get_details($privateKey)['key'];
        $payload = [];
        $payload = array_merge($payload, $options);
        $payload['sub'] = $sub;
        if (!is_null($exp)) {
            $payload['exp'] = $exp;
        }
        $token = JWT::encode($payload, $privateKey, 'RS256', null, ['pkey' => $publicKey]);
        return $token;
    }

    public static function decodeToken($token)
    {
        $tks = explode('.', $token);
        $decodedToken = JWT::decode($token, JWT::jsonDecode(JWT::urlsafeB64Decode($tks[0]))->pkey, ['RS256']);
        return $decodedToken;
    }
}
