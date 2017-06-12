<?php
namespace Report\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\I18n\I18n;
use Cake\Network\Session;
use Cake\I18n\Time;
use Cake\Log\Log;

class CustomReportBehavior extends Behavior
{
    private $Table = null;

    public function parseQuery($jsonArray, array $params)
    {
        if (array_key_exists('model', $jsonArray)) {
            $model = $jsonArray['model'];
            $this->Table = TableRegistry::get($model);

            $type = array_key_exists('type', $jsonArray) ? $jsonArray['type'] : 'query';

            if ($type == 'query') {
                if (array_key_exists('get', $jsonArray)) {
                    $entity = $this->_get($params, $jsonArray['get']);

                } else {
                    $query = $this->Table->find();

                    $methods = ['join', 'contain', 'matching', 'select', 'find', 'where', 'group', 'order'];
                    foreach ($methods as $method) {
                        if (array_key_exists($method, $jsonArray)) {
                            $methodName = '_' . $method;
                            $this->$methodName($query, $params, $jsonArray[$method]);
                        }
                    }

                    $entity = $query->toArray();
                }

            } else if ($type == 'method') {
                $method = $jsonArray['method'];
                $arguments = [];
                if (array_key_exists('arguments', $jsonArray)) {
                    $arguments = $this->_arguments($params, $jsonArray['arguments']);
                }

                $entity = call_user_func_array([$this->Table, $method], $arguments);
            }

            return $entity;
        }
    }

    private function extractPlaceholder($str)
    {
        $placeholder = rtrim(ltrim($str,'${'), '}');
        return $placeholder;
    }

    private function _arguments(array $params, array $values)
    {
        if (!empty($values)) {
            $conditions = [];

            foreach ($values as $value) {
                $pos = strpos($value, '${');

                if ($pos !== false) {
                    $placeholder = $this->extractPlaceholder($value);
                    if (array_key_exists($placeholder, $params)) {
                        $conditions[] = $params[$placeholder];
                    }
                } else {
                    $conditions[] = $value;
                }
            }
            return $conditions;
        }
    }

    private function _get(array $params, array $values)
    {
        if (!empty($values)) {
            if (array_key_exists('id', $values)) {
                $value = $values['id'];
                $pos = strpos($value, '${');

                if ($pos !== false) {
                    $placeholder = $this->extractPlaceholder($value);
                    if (array_key_exists($placeholder, $params)) {
                        $id = $params[$placeholder];
                    }
                } else {
                    $id = $value;
                }

                $contain = [];
                if (array_key_exists('contain', $values) && !empty($values['contain'])) {
                    $contain = $values['contain'];
                }

                $query = $this->Table->get($id, ['contain' => $contain]);
                return $query;
            }
        }
    }

    private function _join(Query $query, array $params, array $values)
    {
        $joinTypes = [
            'inner' => 'INNER',
            'left' => 'LEFT'
        ];

        if (!empty($values)) {
            foreach($values as $obj) {
                if (array_key_exists('model', $obj) && array_key_exists('type', $obj)) {
                    $joinTable = TableRegistry::get($obj['model']);
                    $type = strtolower($obj['type']);

                    if (array_key_exists($type, $joinTypes)) {
                        $joinType = $joinTypes[$type];

                        $joinConditions = [];
                        if (array_key_exists('conditions', $obj)) {
                            $conditions = $obj['conditions'];
                            foreach($conditions as $field => $value) {
                                $joinConditions[] = $field . $value;
                            }
                        }

                        switch ($joinType) {
                            case 'INNER':
                                $query->innerJoin([$joinTable->alias() => $joinTable->table()], [$joinConditions]);
                                break;
                            case 'LEFT':
                                $query->leftJoin([$joinTable->alias() => $joinTable->table()], [$joinConditions]);
                                break;
                        }
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
                    $matching[] = $value;
                }
            }

            $query->matching($matching);
        }
    }

    private function _select(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            $query->select($values);
        }
    }

    private function _find(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            foreach($values as $data) {

                if (isset($data['name']) && $this->Table->hasFinder($data['name'])) {
                    $conditions = [];

                    if (isset($data['conditions'])) {
                        foreach($data['conditions'] as $field => $value) {
                            $pos = strpos($value, '${');

                            if ($pos !== false) {
                                $placeholder = $this->extractPlaceholder($value);
                                if (array_key_exists($placeholder, $params)) {
                                    $conditions[$field] = $params[$placeholder];
                                }
                            } else {
                                $conditions[$field] = $value;
                            }
                        }
                    }
                    $query->find($data['name'], $conditions);

                } else {
                    Log::write('debug', 'Finder (' . $data['name'] . ') does not exist.');
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
                    $placeholder = substr($value, $pos + 2, strlen($value) - 3);
                    if (array_key_exists($placeholder, $params)) {
                        $conditions[$field] = $params[$placeholder];
                    }
                } else {
                    $conditions[$field] = $value;
                }
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

    private function _order(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            $query->order($values);
        }
    }
}
