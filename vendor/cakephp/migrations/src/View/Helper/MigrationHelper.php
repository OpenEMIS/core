<?php
/**
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Migrations\View\Helper;

use Cake\Database\Schema\Collection;
use Cake\View\Helper;
use Cake\View\View;
use InvalidArgumentException;

/**
 * Migration Helper class for output of field data in migration files.
 *
 * MigrationHelper encloses all methods needed while working with HTML pages.
 */
class MigrationHelper extends Helper
{
    /**
     * Constructor
     *
     * ### Settings
     *
     * - `collection` \Cake\Database\Schema\Collection
     *
     * @param \Cake\View\View $View The View this helper is being attached to.
     * @param array $config Configuration settings for the helper.
     */
    public function __construct(View $View, array $config = [])
    {
        parent::__construct($View, $config);
    }

    /**
     * Returns the method to be used for the Table::save()
     *
     * @param string $action Name of action to take against the table
     * @return string
     */
    public function tableMethod($action)
    {
        if ($action == 'drop_table') {
            return 'drop';
        }

        if ($action == 'create_table') {
            return 'create';
        }

        return 'update';
    }

    /**
     * Returns the method to be used for the index manipulation
     *
     * @param string $action Name of action to take against the table
     * @return string
     */
    public function indexMethod($action)
    {
        if ($action == 'drop_field') {
            return 'removeIndex';
        }

        return 'addIndex';
    }


    /**
     * Returns the method to be used for the column manipulation
     *
     * @param string $action Name of action to take against the table
     * @return string
     */
    public function columnMethod($action)
    {
        if ($action == 'drop_field') {
            return 'removeColumn';
        }

        return 'addColumn';
    }

    /**
     * Returns an array of column data for a given table
     *
     * @param string $table Name of the table to retrieve columns for
     * @return array
     */
    public function columns($table)
    {
        $collection = $this->config('collection');
        $tableSchema = $collection->describe($table);
        $columns = [];
        $tablePrimaryKeys = $tableSchema->primaryKey();
        foreach ($tableSchema->columns() as $column) {
            if (in_array($column, $tablePrimaryKeys)) {
                continue;
            }
            $columns[$column] = $this->column($tableSchema, $column);
        }

        return $columns;
    }

    /**
     * Returns the primary key data for a given table
     *
     * @param string $table Name of the table ot retrieve primary key for
     * @return array
     */
    public function primaryKeys($table)
    {
        $collection = $this->config('collection');
        $tableSchema = $collection->describe($table);
        $primaryKeys = [];
        $tablePrimaryKeys = $tableSchema->primaryKey();
        foreach ($tableSchema->columns() as $column) {
            if (in_array($column, $tablePrimaryKeys)) {
                $primaryKeys[] = ['name' => $column, 'info' => $this->column($tableSchema, $column)];
            }
        }
        return $primaryKeys;
    }


    /**
     * Returns an array of column data for a single column
     *
     * @param Cake\Database\Schema\Table $tableSchema Name of the table to retrieve columns for
     * @param string $column A column to retrieve data for
     * @return array
     */
    public function column($tableSchema, $column)
    {
        return [
            'columnType' => $tableSchema->columnType($column),
            'options' => $this->attributes($tableSchema->name(), $column),
        ];
    }

    /**
     * Returns a string-like representation of a value
     *
     * @param string $value A value to represent as a string
     * @return mixed
     */
    public function value($value)
    {
        if ($value === null || $value === 'null' || $value === 'NULL') {
            return 'null';
        }

        if ($value === 'true' || $value === 'false') {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_numeric($value) || ctype_digit($value)) {
            return (int)$value;
        }

        return sprintf("'%s'", addslashes($value));
    }

    /**
     * Returns an array of attributes for a given table column
     *
     * @param string $table Name of the table to retrieve columns for
     * @param string $column A column to retrieve attributes for
     * @return array
     */
    public function attributes($table, $column)
    {
        $collection = $this->config('collection');
        $tableSchema = $collection->describe($table);
        $validOptions = [
            'length', 'limit',
            'default', 'null',
            'precision', 'scale',
            'after', 'update',
            'comment', 'unsigned',
            'signed', 'properties'
        ];

        $attributes = [];
        $options = $tableSchema->column($column);
        foreach ($options as $_option => $value) {
            $option = $_option;
            switch ($_option) {
                case 'length':
                    $option = 'limit';
                    break;
                case 'unsigned':
                    $option = 'signed';
                    $value = (bool)!$value;
                    break;
                case 'unique':
                    $value = (bool)$value;
                    break;
            }

            if (!in_array($option, $validOptions)) {
                continue;
            }

            $attributes[$option] = $value;
        }

        ksort($attributes);
        return $attributes;
    }

    /**
     * Returns an array converted into a formatted multiline string
     *
     * @param array $list array of items to be stringified
     * @param array $options options to use
     * @return string
     */
    public function stringifyList(array $list, array $options = [])
    {
        $options += [
            'indent' => 2
        ];

        if (!$list) {
            return '';
        }

        ksort($list);
        foreach ($list as $k => &$v) {
            if (is_array($v)) {
                $v = $this->stringifyList($v, [
                    'indent' => $options['indent'] + 1
                ]);
                $v = sprintf('[%s]', $v);
            } else {
                $v = $this->value($v);
            }
            if (!is_numeric($k)) {
                $v = "'$k' => $v";
            }
        }

        $start = $end = '';
        $join = ', ';
        if ($options['indent']) {
            $join = ',';
            $start = "\n" . str_repeat("    ", $options['indent']);
            $join .= $start;
            $end = "\n" . str_repeat("    ", $options['indent'] - 1);
        }

        return $start . implode($join, $list) . ',' . $end;
    }
}
