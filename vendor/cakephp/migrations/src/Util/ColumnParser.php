<?php
namespace Migrations\Util;

use Cake\Collection\Collection;
use Cake\Utility\Hash;
use Phinx\Db\Adapter\AdapterInterface;
use ReflectionClass;

class ColumnParser
{
    /**
     * Parses a list of arguments into an array of fields
     *
     * @param array $arguments A list of arguments being parsed
     * @return array
     **/
    public function parseFields($arguments)
    {
        $fields = [];
        $arguments = $this->validArguments($arguments);
        foreach ($arguments as $field) {
            preg_match('/^(\w*)(?::(\w*))?(?::(\w*))?(?::(\w*))?/', $field, $matches);
            $field = $matches[1];
            $type = Hash::get($matches, 2);

            if (in_array($type, ['primary', 'primary_key'])) {
                $type = 'primary';
            }

            $type = $this->getType($field, $type);
            $length = $this->getLength($type);
            $fields[$field] = [
                'columnType' => $type,
                'options' => [
                    'null' => false,
                    'default' => null,
                ]
            ];

            if ($length !== null) {
                $fields[$field]['options']['limit'] = $length;
            }
        }

        return $fields;
    }

    /**
     * Parses a list of arguments into an array of indexes
     *
     * @param array $arguments A list of arguments being parsed
     * @return array
     **/
    public function parseIndexes($arguments)
    {
        $indexes = [];
        $arguments = $this->validArguments($arguments);
        foreach ($arguments as $field) {
            preg_match('/^(\w*)(?::(\w*))?(?::(\w*))?(?::(\w*))?/', $field, $matches);
            $field = $matches[1];
            $type = Hash::get($matches, 2);
            $indexType = Hash::get($matches, 3);
            $indexName = Hash::get($matches, 4);

            if (in_array($type, ['primary', 'primary_key'])) {
                $indexType = 'primary';
            }

            if ($indexType === null) {
                continue;
            }

            $indexUnique = false;
            if ($indexType == 'primary') {
                $indexUnique = true;
            } elseif ($indexType == 'unique') {
                $indexUnique = true;
            }

            $indexName = $this->getIndexName($field, $indexType, $indexName, $indexUnique);

            if (empty($indexes[$indexName])) {
                $indexes[$indexName] = [
                    'columns' => [],
                    'options' => [
                        'unique' => $indexUnique,
                        'name' => $indexName,
                    ],
                ];
            }

            $indexes[$indexName]['columns'][] = $field;
        }

        return $indexes;
    }

    /**
     * Returns a list of only valid arguments
     *
     * @param array $arguments A list of arguments
     * @return array
     **/
    public function validArguments($arguments)
    {
        $collection = new Collection($arguments);
        return $collection->filter(function ($value, $field) {
            $value;
            return preg_match('/^(\w*)(?::(\w*))?(?::(\w*))?(?::(\w*))?/', $field);
        })->toArray();
    }

    /**
     * Retrieves a type that should be used for a specific field
     *
     * @param string $field Name of field
     * @param string $type User-specified type
     * @return string
     **/
    public function getType($field, $type)
    {
        $reflector = new ReflectionClass('Phinx\Db\Adapter\AdapterInterface');
        $collection = new Collection($reflector->getConstants());
        $validTypes = $collection->filter(function ($value, $constant) {
            $value;
            return substr($constant, 0, strlen('PHINX_TYPE_')) === 'PHINX_TYPE_';
        })->toArray();

        if ($type === null || !in_array($type, $validTypes)) {
            if ($type == 'primary') {
                $type = 'integer';
            } elseif ($field == 'id') {
                $type = 'integer';
            } elseif (in_array($field, ['created', 'modified', 'updated'])) {
                $type = 'datetime';
            } else {
                $type = 'string';
            }
        }

        return $type;
    }

    /**
     * Returns the default length to be used for a given fie
     *
     * @param string $type User-specified type
     * @return int
     **/
    public function getLength($type)
    {
        $length = null;
        if ($type == 'string') {
            $length = 255;
        } elseif ($type == 'integer') {
            $length = 11;
        } elseif ($type == 'biginteger') {
            $length = 20;
        }

        return $length;
    }

    /**
     * Returns the default length to be used for a given fie
     *
     * @param string $field Name of field
     * @param string $indexType Type of index
     * @param string $indexName Name of index
     * @param bool $indexUnique Whether this is a unique index or not
     * @return string
     **/
    public function getIndexName($field, $indexType, $indexName, $indexUnique)
    {
        if (empty($indexName)) {
            $indexName = strtoupper('BY_' . $field);
            if ($indexType == 'primary') {
                $indexName = 'PRIMARY';
            } elseif ($indexUnique) {
                $indexName = strtoupper('UNIQUE_' . $field);
            }
        }

        return $indexName;
    }
}
