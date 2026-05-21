<?php
//POCOR-9590: single source of truth for mapping an external identity-source payload onto OE Core
//user fields. Strict, case-sensitive lookups by design — silent case-fixups would hide config
//drift on a system-of-record input. Both the add-from-external wizard and the SyncUser action
//resolve mappings through here.
namespace User\Lib;

class ExternalIdentityMapper
{
    public const USER_FIELDS = ['first_name', 'middle_name', 'third_name', 'last_name', 'gender', 'date_of_birth', 'nationality'];

    /**
     * Resolve user fields from an external API payload using the source's configured mappings.
     * Returns ['mapped' => [field => value], 'missing' => [field => configuredPath]] so callers
     * can either consume the values or report which mappings did not match a key in the payload.
     */
    public static function map(array $apiData, array $configs): array
    {
        $mapped = [];
        $missing = [];
        foreach (self::USER_FIELDS as $field) {
            $path = trim((string)($configs[$field . '_mapping'] ?? ''));
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
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }
        return $value;
    }
}
