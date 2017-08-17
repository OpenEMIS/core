<?php
namespace Page\Traits;

use Cake\ORM\Query;
use Cake\Utility\Hash;

trait OptionListTrait
{
    public function findOptionList(Query $query, array $options)
    {
        $options += [
            'keyField' => $this->primaryKey(),
            'valueField' => $this->displayField(),
            'groupField' => null
        ];

        if (!$query->clause('select') &&
            !is_object($options['keyField']) &&
            !is_object($options['valueField']) &&
            !is_object($options['groupField'])
        ) {
            $fields = array_merge(
                (array)$options['keyField'],
                (array)$options['valueField'],
                (array)$options['groupField']
            );
            $columns = $this->schema()->columns();
            if (count($fields) === count(array_intersect($fields, $columns))) {
                $query->select($fields);
            }
        }

        $options = $this->_setFieldMatchers(
            $options,
            ['keyField', 'valueField', 'groupField']
        );

        return $query->formatResults(function ($results) use ($options) {
            $returnResult = [];
            $groupField = $options['groupField'];
            $keyField = $options['keyField'];
            $valueField = $options['valueField'];
            if (array_key_exists('defaultOption', $options) && !$options['defaultOption']) {
                $returnResult = [];
            } else if ($results->count() == 0) {
                $returnResult[] = ['value' => '', 'text' => __('No Options')];
            } else if (array_key_exists('defaultOption', $options) && is_string($options['defaultOption'])) {
                $returnResult[] = ['value' => '', 'text' => __($options['defaultOption'])];
            } else {
                $returnResult[] = ['value' => '', 'text' => '-- '.__('Select').' --'];
            }
            foreach ($results as $result) {
                $result = $result->toArray();

                if (array_key_exists('flatten', $options) && $options['flatten']) {
                    $result = Hash::flatten($result);
                }
                $key = array_key_exists($keyField, $result) ? $result[$keyField] : null;
                $value = array_key_exists($valueField, $result) ? $result[$valueField] : null;
                if ($options['groupField']) {
                    $group = array_key_exists($groupField, $result) ? $result[$groupField] : null;
                    $returnResult[] = ['group' => $group, 'value' => $key, 'text' => $value];
                } else {
                    $returnResult[] = ['value' => $key, 'text' => $value];
                }
            }
            return $returnResult;
        });
    }
}
