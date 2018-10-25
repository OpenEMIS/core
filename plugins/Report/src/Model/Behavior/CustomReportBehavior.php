<?php
namespace Report\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;

class CustomReportBehavior extends Behavior
{
    private $Table = null;
    private $system_condition_keywords = [
        "keyField",
        "valueField"
    ];

    public function buildQuery($jsonArray, array $params, $byaccess = false, $returnSql = false)
    {
        $result = [];
        if (array_key_exists('model', $jsonArray)) {
            $model = $jsonArray['model'];
            $this->Table = TableRegistry::get($model);
            $query = $this->Table->find();

            // rename to avoid duplicate finders
            $this->Table->addBehavior('Report.InstitutionSecurity', [
                'implementedFinders' => [
                    'byReportAccess' => 'findByAccess',
                ]
            ]);

            // find byAccess (not used for filters)
            $userId = $params['user_id'];
            $superAdmin = $params['super_admin'];
            if ($byaccess) {
                // don't implement super admin checking for now as not consistent with sql type
                // if (!$superAdmin) {
                    $query->find('byReportAccess', ['user_id' => $userId, 'institution_field_alias' => 'Institutions.id']);
                // }
            }

            $methods = ['join', 'contain', 'matching', 'select', 'find', 'where', 'whereExpression', 'group', 'having', 'order'];
            foreach ($methods as $method) {
                if (array_key_exists($method, $jsonArray)) {
                    $methodName = '_' . $method;
                    if ($this->$methodName($query, $params, $jsonArray[$method]) === false) {
                        // return empty option as unable to construct the right query
                        return [];
                    }
                }
            }

            // return sql or array
            if ($returnSql) {
                $result = $query->sql();
            } else {
                $result = $query->toArray();
            }

        } else if (array_key_exists('sql', $jsonArray)) {
            $stmtParameters = array_key_exists('parameters', $jsonArray) ? $jsonArray['parameters'] : [];
            $query = $this->_sql($params, $jsonArray['sql'], $stmtParameters);

            // return sql or array
            if ($returnSql) {
                $result = $query->__get('queryString');
            } else {
                $result = $query->fetchAll('assoc');
            }
        }

        //To prevent memory leak
        $this->Table = null;
        TableRegistry::clear();
        gc_collect_cycles();

        return $result;
    }

    /**
    * To check if the additional options should be displayed. 
    * See CustomReportsTable@addBeforeAction
    *
    * @param array $optionsCondition - decoded json from filter.json
    * @param array $params           - parameters from other filters or filter.json
    *
    * @return - boolean | If the query returns any rows/results
    **/
    public function checkOptionCondition($optionsCondition, array $params)
    {
        // conditions should not be applied to super_admin
        if ($this->_table->Auth->user('super_admin')) {
            return true;
        }

        if (!isset($optionsCondition["model"])) {
            return false;
        }
        $optionModel = $optionsCondition["model"];
        $optionJoins = isset($optionsCondition["joins"]) ? $optionsCondition["joins"] : [];
        $optionConditions = isset($optionsCondition["conditions"]) ? $optionsCondition["conditions"] : [];

        $queryTable = TableRegistry::get($optionModel);
        $query = $queryTable->find();

        // do joins
        foreach ($optionJoins as $joinData) {
            if (!isset($joinData["model"])) {
                continue;
            }
            $joinTable = TableRegistry::get($joinData["model"]);
            $joinType = isset($joinData["type"]) ? $joinData["type"] : "INNER";
            $joinConditions = isset($joinData["conditions"]) ? $joinData["conditions"] : [];

            $joinConditions = $this->_processConditions($joinConditions, $params);

            $alias = isset($joinData["alias"]) ? $joinData["alias"] : $joinTable->alias();

            switch ($joinType) {
            case 'INNER':
                $query->innerJoin([$alias => $joinTable->table()], [$joinConditions]);
                break;
            case 'LEFT':
                $query->leftJoin([$alias => $joinTable->table()], [$joinConditions]);
                break;
            }
        }

        // do conditions
        foreach ($optionConditions as $conditionData) {
            $query->where($this->_processConditions($conditionData, $params));
        }

        return $query->count() > 0;
    }

    private function extractPlaceholder($str)
    {
        $placeholder = rtrim(ltrim($str,'${'), '}');
        return $placeholder;
    }

    private function escapeSingleQuotes($str)
    {
        $value = str_replace("'", "''", $str);
        return $value;
    }

    private function _sql(array $params, $sqlStmt, $stmtParameters)
    {
        foreach ($stmtParameters as $field) {
            if (array_key_exists($field, $params)) {
                if (!empty($params[$field])) {
                    $value = $this->escapeSingleQuotes($params[$field]);
                } else {
                    $value = 0;
                    Log::write('debug', "No value has been provided for $field parameter");
                }
                $sqlStmt = str_replace(":$field", $value, $sqlStmt);
            }
        }

        $connection = ConnectionManager::get('default');
        $results = $connection->query($sqlStmt);
        return $results;
    }

    // private function _get(array $params, array $values)
    // {
    //     $query = [];
    //     if (!empty($values)) {
    //         if (array_key_exists('id', $values)) {
    //             $value = $values['id'];
    //             $pos = strpos($value, '${');

    //             $id = '';
    //             if ($pos !== false) {
    //                 $placeholder = $this->extractPlaceholder($value);
    //                 if (array_key_exists($placeholder, $params) && !empty($params[$placeholder])) {
    //                     $id = $params[$placeholder];
    //                 }
    //             } else {
    //                 $id = $value;
    //             }

    //             if (!empty($id)) {
    //                 $contain = [];
    //                 if (array_key_exists('contain', $values) && !empty($values['contain'])) {
    //                     $contain = $values['contain'];
    //                 }

    //                 $query = $this->Table->get($id, ['contain' => $contain]);

    //             }
    //         }
    //     }
    //     return $query;
    // }

    private function _join(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            foreach($values as $obj) {
                if (array_key_exists('model', $obj) && array_key_exists('type', $obj)) {
                    $joinTable = TableRegistry::get($obj['model']);
                    $type = strtoupper($obj['type']);

                    $joinConditions = [];
                    if (array_key_exists('conditions', $obj)) {
                        foreach($obj['conditions'] as $field => $value) {
                            $pos = strpos($value, '${');

                            if ($pos !== false) {
                                $placeholder = $this->extractPlaceholder($value);
                                if (array_key_exists($placeholder, $params) && !empty($params[$placeholder])) {
                                    $joinConditions[] = $field . ' = ' . $this->escapeSingleQuotes($params[$placeholder]);
                                }
                            } else {
                                $joinConditions[] = $value;
                            }
                        }
                    }

                    // allow same table to be joined twice
                    $alias = array_key_exists('alias', $obj) ? $obj['alias'] : $joinTable->alias();

                    switch ($type) {
                        case 'INNER':
                            $query->innerJoin([$alias => $joinTable->table()], [$joinConditions]);
                            break;
                        case 'LEFT':
                            $query->leftJoin([$alias => $joinTable->table()], [$joinConditions]);
                            break;
                    }
                }
            }
        }
    }

    private function _contain(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            $contain = [];

            foreach ($values as $value) {
                $models = explode('.', $value);

                // check if models are associated
                $associated = true;
                $table = $this->Table;
                foreach ($models as $model) {
                    if (!$table->associations()->has($model)) {
                        $associated = false;
                        break;
                    } else {
                        $table = $table->{$model};
                    }
                }

                if ($associated) {
                    $contain[] = $value;
                }
            }

            $query->contain($contain);
        }
    }

    private function _matching(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            $matching = [];

            foreach ($values as $value) {
                $models = explode('.', $value);

                // check if models are associated
                $associated = true;
                $table = $this->Table;
                foreach ($models as $model) {
                    if (!$table->associations()->has($model)) {
                        $associated = false;
                        break;
                    } else {
                        $table = $table->{$model};
                    }
                }

                if ($associated) {
                    $query->matching($value);
                }
            }
        }
    }

    private function _select(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            $select = [];

            foreach($values as $field => $value) {
                // manually append backticks to column alias to cater for arabic text
                $select['`'.$field.'`'] = $query->newExpr()->add($value);
            }

            $query->select($select);
        }
    }

    // cannot use finders when exporting to csv for now
    private function _find(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            foreach($values as $obj) {
                $finder = $obj['name'];

                if (isset($finder) && $this->Table->hasFinder($finder)) {
                    $conditions = [];

                    if (isset($obj['conditions'])) {
                        $conditions = $this->_processConditions($obj['conditions'], $params);
                        if ($conditions === false) {
                            return false;
                        }
                    }

                    // add $options["super_admin"] to finders since they do not have access to auth component
                    $conditions["super_admin"] = $this->_table->Auth->user('super_admin');

                    $query->find($finder, $conditions);
                } else {
                    Log::write('debug', 'Finder (' . $obj['name'] . ') does not exist.');
                }
            }
        }
    }

    private function _processConditions($aryConditions, $params)
    {
        $conditions = [];
        foreach ($aryConditions as $field => $value) {
            $pos = strpos($value, '${');

            if ($pos !== false) {
                $placeholder = $this->extractPlaceholder($value);
                if (array_key_exists($placeholder, $params) && !empty($params[$placeholder])) {
                    $conditions[$field] = $params[$placeholder];
                } 
                
                if (array_key_exists($placeholder, $params) && empty($params[$placeholder])) { 
                    // condition value that are using placeholders are assumed to have dependencies. 
                    // if no value is found, option should not be constructed.
                    return false;
                }
            } else {
                if (in_array($field, $this->system_condition_keywords)) {
                    $conditions[$field] = $value;
                } else {
                    $conditions[] = $field . " = " . $value;
                }
            }
        }
        return $conditions;
    }

    private function _where(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            $conditions = [];

            foreach($values as $field => $value) {
                $pos = strpos($value, '${');

                if ($pos !== false) {
                    $placeholder = $this->extractPlaceholder($value);
                    if (array_key_exists($placeholder, $params) && !empty($params[$placeholder])) {
                        $conditions[] = $field . ' = ' . $this->escapeSingleQuotes($params[$placeholder]);
                    }
                } else {
                    $conditions[] = $value;
                }
            }

            $query->where([$conditions]);
        }
    }

    private function _whereExpression(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            $conditions = [];
            foreach($values as $value) {
                $conditions[] = $query->newExpr()->add($value);
            }

             $query->where([$conditions]);
        }
    }

    private function _group(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            $query->group($values);
        }
    }

    private function _having(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            $query->having($values);
        }
    }

    private function _order(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            $query->order($values);
        }
    }
}
