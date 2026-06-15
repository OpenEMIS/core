<?php
//POCOR-9590: single source of truth for mapping an external identity-source payload onto OE Core
//user fields. Lookups are case-insensitive and accept caller-supplied default keys, because the
//external source (e.g. Seychelles) returns keys with different casing between its test and its
//production payloads — an exact-case lookup silently dropped first_name/last_name. The SyncUser
//action resolves mappings through here.
namespace User\Lib;

class ExternalIdentityMapper
{
    public const USER_FIELDS = ['first_name', 'middle_name', 'third_name', 'last_name', 'gender', 'date_of_birth', 'nationality'];

    //POCOR-9590: well-known Seychelles Civil Status payload keys, used as defaults when the
    //source row has no explicit *_mapping configured (mirrors the wizard's prev lenient behaviour).
    public const SEYCHELLES_DEFAULT_MAPPINGS = [
        'first_name'    => 'givennames',
        'last_name'     => 'presentsurname',
        'gender'        => 'sex',
        'date_of_birth' => 'dob',
        'nationality'   => 'nationality',
    ];

    /**
     * Resolve user fields from an external API payload using the source's configured mappings.
     * Returns ['mapped' => [field => value], 'missing' => [field => configuredPath]] so callers
     * can either consume the values or report which mappings did not match a key in the payload.
     */
    public static function map(array $apiData, array $configs, array $defaults = []): array
    {
        $mapped = [];
        $missing = [];
        foreach (self::USER_FIELDS as $field) {
            $path = trim((string)($configs[$field . '_mapping'] ?? ''));
            //POCOR-9590: fall back to the caller's default key (e.g. Seychelles 'givennames')
            //when the source has no explicit mapping — restores the prev lenient behaviour.
            if ($path === '' && isset($defaults[$field])) {
                $path = trim((string)$defaults[$field]);
            }
            if ($path === '') {
                continue;
            }
            $value = self::walk($apiData, $path);
            if ($value === null) {
                $missing[$field] = $path;
                continue;
            }
            $mapped[$field] = $value;
        }
        if (isset($mapped['date_of_birth'])) {
            $mapped['date_of_birth'] = substr((string)$mapped['date_of_birth'], 0, 10);
        }
        return ['mapped' => $mapped, 'missing' => $missing];
    }

    private static function walk(array $data, string $path)
    {
        $value = $data;
        foreach (explode('.', $path) as $key) {
            if (!is_array($value)) {
                return null;
            }
            //POCOR-9590: case-insensitive key match — the source varies key casing between its
            //test and production payloads, so an exact-case lookup silently dropped fields.
            $matchedKey = array_key_exists($key, $value) ? $key : self::matchKey($value, $key);
            if ($matchedKey === null) {
                return null;
            }
            $value = $value[$matchedKey];
        }
        return $value;
    }

    //POCOR-9590: resolve $key against $data's keys ignoring case; null when no key matches.
    private static function matchKey(array $data, string $key): ?string
    {
        $lower = strtolower($key);
        foreach (array_keys($data) as $candidate) {
            if (strtolower((string)$candidate) === $lower) {
                return (string)$candidate;
            }
        }
        return null;
    }
}
