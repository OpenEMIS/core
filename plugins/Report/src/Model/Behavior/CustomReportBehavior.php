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
                    $this->$methodName($query, $params, $jsonArray[$method]);
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

        return $result;
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
            if (array_key_exists($field, $params) && !empty($params[$field])) {
                $value = $this->escapeSingleQuotes($params[$field]);
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
                $select[$field] = $query->newExpr()->add($value);
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
                        foreach($obj['conditions'] as $field => $value) {
                            $pos = strpos($value, '${');

                            if ($pos !== false) {
                                $placeholder = $this->extractPlaceholder($value);
                                if (array_key_exists($placeholder, $params) && !empty($params[$placeholder])) {
                                    $conditions[$field] = $params[$placeholder];
                                }
                            } else {
                                $conditions[$field] = $value;
                            }
                        }
                    }

                    $query->find($finder, $conditions);
                } else {
                    Log::write('debug', 'Finder (' . $obj['name'] . ') does not exist.');
                }
            }
        }
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
