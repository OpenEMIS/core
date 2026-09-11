<?php

namespace SSO;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ProcessToken
{
    public static function generateToken(
        $sub,
        $exp = null,
        array $options = []
    ): string {
        $keyConfig = [
            'private_key_bits' => 2048,
        ];

        $privateKey = openssl_pkey_new($keyConfig);

        if ($privateKey === false) {
            throw new \RuntimeException('Unable to generate private key.');
        }

        $keyDetails = openssl_pkey_get_details($privateKey);

        if ($keyDetails === false || empty($keyDetails['key'])) {
            throw new \RuntimeException('Unable to retrieve public key.');
        }

        $publicKey = $keyDetails['key'];

        $payload = array_merge($options, [
            'sub' => $sub,
        ]);

        if ($exp !== null) {
            $payload['exp'] = $exp;
        }

        return JWT::encode(
            $payload,
            $privateKey,
            'RS256',
            null,
            [
                'pkey' => $publicKey,
            ]
        );
    }

    public static function decodeToken($token)
    {
        $tks = explode('.', $token);

        if (count($tks) !== 3) {
            throw new \InvalidArgumentException('Invalid JWT token.');
        }

        $header = JWT::jsonDecode(
            JWT::urlsafeB64Decode($tks[0])
        );

        if (empty($header->pkey)) {
            throw new \RuntimeException('Public key not found in JWT header.');
        }

        return JWT::decode(
            $token,
            new Key($header->pkey, 'RS256')
        );
    }
}