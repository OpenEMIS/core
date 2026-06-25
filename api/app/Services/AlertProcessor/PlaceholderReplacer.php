<?php
declare(strict_types=1);

namespace App\Services\AlertProcessor;

/**
 * POCOR-9509: Service for replacing placeholders in alert templates
 *
 * Handles placeholder replacement in alert subject/message templates.
 * Placeholders follow the format: ${entity.field} or ${variable}
 *
 * Examples:
 * - ${student.name}
 * - ${institution.name}
 * - ${total_days}
 * - ${threshold}
 *
 * @package App\Services\AlertProcessor
 */
class PlaceholderReplacer
{
    /**
     * POCOR-9509: Replace placeholders in a template string
     *
     * @param string $template Template string with ${placeholders}
     * @param array $placeholders Placeholder => value mapping
     * @return string Template with placeholders replaced
     */
    public function replace(string $template, array $placeholders): string
    {
        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $template
        );
    }

    /**
     * POCOR-9509: Replace placeholders in multiple templates at once
     *
     * @param array $templates ['key' => 'template string', ...]
     * @param array $placeholders Placeholder => value mapping
     * @return array ['key' => 'replaced string', ...]
     */
    public function replaceMultiple(array $templates, array $placeholders): array
    {
        $result = [];

        foreach ($templates as $key => $template) {
            $result[$key] = $this->replace($template, $placeholders);
        }

        return $result;
    }

    /**
     * POCOR-9509: Extract all placeholders from a template
     *
     * Useful for validation and debugging
     *
     * @param string $template Template string
     * @return array List of placeholder names (e.g., ['${student.name}', '${total_days}'])
     */
    public function extractPlaceholders(string $template): array
    {
        $matches = [];
        preg_match_all('/\$\{[^}]+\}/', $template, $matches);

        return $matches[0] ?? [];
    }

    /**
     * POCOR-9509: Validate that all placeholders in template have values
     *
     * @param string $template Template string
     * @param array $placeholders Placeholder => value mapping
     * @return array List of missing placeholders
     */
    public function validatePlaceholders(string $template, array $placeholders): array
    {
        $foundPlaceholders = $this->extractPlaceholders($template);
        $missing = [];

        foreach ($foundPlaceholders as $placeholder) {
            if (!isset($placeholders[$placeholder])) {
                $missing[] = $placeholder;
            }
        }

        return $missing;
    }

    /**
     * POCOR-9509: Replace placeholders with fallback for missing values
     *
     * If a placeholder is missing, use fallback value instead of leaving ${...}
     *
     * @param string $template Template string
     * @param array $placeholders Placeholder => value mapping
     * @param string $fallback Fallback value for missing placeholders (default: '')
     * @return string
     */
    public function replaceWithFallback(string $template, array $placeholders, string $fallback = ''): string
    {
        $foundPlaceholders = $this->extractPlaceholders($template);

        // Add fallback for missing placeholders
        foreach ($foundPlaceholders as $placeholder) {
            if (!isset($placeholders[$placeholder])) {
                $placeholders[$placeholder] = $fallback;
            }
        }

        return $this->replace($template, $placeholders);
    }

    /**
     * POCOR-9509: Build placeholder array from nested object/array data
     *
     * Converts nested data like ['student' => ['name' => 'John']]
     * into flat placeholders like ['${student.name}' => 'John']
     *
     * @param array $data Nested data array
     * @param string $prefix Prefix for placeholder names
     * @return array Flat placeholder => value mapping
     */
    public function buildPlaceholders(array $data, string $prefix = ''): array
    {
        $placeholders = [];

        foreach ($data as $key => $value) {
            $placeholderKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                // Recursive for nested arrays
                $placeholders = array_merge(
                    $placeholders,
                    $this->buildPlaceholders($value, $placeholderKey)
                );
            } elseif (is_object($value)) {
                // Convert object to array and recurse
                $placeholders = array_merge(
                    $placeholders,
                    $this->buildPlaceholders((array) $value, $placeholderKey)
                );
            } else {
                // Leaf node - add placeholder
                $placeholders['${' . $placeholderKey . '}'] = (string) $value;
            }
        }

        return $placeholders;
    }

    /**
     * POCOR-9509: Format date/time placeholders
     *
     * @param array $placeholders Placeholder => value mapping
     * @param array $dateFields List of placeholder keys that contain dates
     * @param string $format Date format (default: 'Y-m-d')
     * @return array Updated placeholders with formatted dates
     */
    public function formatDates(array $placeholders, array $dateFields, string $format = 'Y-m-d'): array
    {
        foreach ($dateFields as $field) {
            if (isset($placeholders[$field])) {
                $value = $placeholders[$field];

                // Try to parse as date
                if ($value instanceof \DateTimeInterface) {
                    $placeholders[$field] = $value->format($format);
                } elseif (is_string($value) && strtotime($value)) {
                    $placeholders[$field] = date($format, strtotime($value));
                }
            }
        }

        return $placeholders;
    }
}
