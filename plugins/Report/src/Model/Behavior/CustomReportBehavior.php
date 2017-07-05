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

    public function parseJson($jsonArray, array $params)
    {
        $result = [];
        if (array_key_exists('model', $jsonArray)) {
            $model = $jsonArray['model'];
            $this->Table = TableRegistry::get($model);

            if (array_key_exists('get', $jsonArray)) {
                $result = $this->_get($params, $jsonArray['get']);

            } else {
                $query = $this->Table->find();

                $methods = ['join', 'contain', 'matching', 'select', 'find', 'where', 'whereExpression', 'group', 'having', 'order'];
                foreach ($methods as $method) {
                    if (array_key_exists($method, $jsonArray)) {
                        $methodName = '_' . $method;
                        $this->$methodName($query, $params, $jsonArray[$method]);
                    }
                }

                $result = $query;
            }
        } else if (array_key_exists('sql', $jsonArray)) {
            // parameters for prepared statement
            $stmtParameters = array_key_exists('parameters', $jsonArray) ? $jsonArray['parameters'] : [];
            $result = $this->_sql($params, $jsonArray['sql'], $stmtParameters);
        }

        return $result;
    }

    private function extractPlaceholder($str)
    {
        $placeholder = rtrim(ltrim($str,'${'), '}');
        return $placeholder;
    }

    private function _sql(array $params, $sqlStmt, $stmtParameters)
    {
        foreach ($stmtParameters as $key => $field) {
            $pos = strpos($field, '${');

            if ($pos !== false) {
                $placeholder = $this->extractPlaceholder($field);
                if (array_key_exists($placeholder, $params) && !empty($params[$placeholder])) {
                    $stmtParameters[$key] = $params[$placeholder];
                }
            }
        }

        $connection = ConnectionManager::get('default');
        $results = $connection->execute($sqlStmt, $stmtParameters)->fetchAll('assoc');
        return $results;
    }

    private function _get(array $params, array $values)
    {
        $query = [];
        if (!empty($values)) {
            if (array_key_exists('id', $values)) {
                $value = $values['id'];
                $pos = strpos($value, '${');

                $id = '';
                if ($pos !== false) {
                    $placeholder = $this->extractPlaceholder($value);
                    if (array_key_exists($placeholder, $params) && !empty($params[$placeholder])) {
                        $id = $params[$placeholder];
                    }
                } else {
                    $id = $value;
                }

                if (!empty($id)) {
                    $contain = [];
                    if (array_key_exists('contain', $values) && !empty($values['contain'])) {
                        $contain = $values['contain'];
                    }

                    $query = $this->Table->get($id, ['contain' => $contain]);

                }
            }
        }
        return $query;
    }

    private function _join(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            foreach($values as $obj) {
                if (array_key_exists('model', $obj) && array_key_exists('type', $obj)) {
                    $joinTable = TableRegistry::get($obj['model']);
                    $type = strtoupper($obj['type']);

                    // must supply '=' or other sign in $field for join conditions
                    $joinConditions = [];
                    if (array_key_exists('conditions', $obj)) {
                        foreach($obj['conditions'] as $field => $value) {
                            $pos = strpos($value, '${');

                            if ($pos !== false) {
                                $placeholder = $this->extractPlaceholder($value);
                                if (array_key_exists($placeholder, $params) && !empty($params[$placeholder])) {
                                    $joinConditions[] = $field . $params[$placeholder];
                                }
                            } else {
                                $joinConditions[] = $field . $value;
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
                        $table = $table->$model;
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
                        $table = $table->$model;
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

            // must supply '=' or other sign in $field for where conditions
            foreach($values as $field => $value) {
                $pos = strpos($value, '${');

                if ($pos !== false) {
                    $placeholder = $this->extractPlaceholder($value);
                    if (array_key_exists($placeholder, $params) && !empty($params[$placeholder])) {
                        $conditions[] = $field . $params[$placeholder];
                    }
                } else {
                    $conditions[] = $field . $value;
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
