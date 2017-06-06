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

class CustomReportBehavior extends Behavior
{
    public function parseQuery ($jsonArray, array $params)
    {
        if (array_key_exists('model', $jsonArray)) {
            $model = $jsonArray['model'];

            $Table = TableRegistry::get($model);
            $query = $Table->find();

            if (array_key_exists('join', $jsonArray)) {
                $joinData = $jsonArray['join'];
                $this->_join($query, $params, $joinData);
            }

            if (array_key_exists('select', $jsonArray)) {
                $selectData = $jsonArray['select'];
                $this->_select($query, $params, $selectData);
            }

            if (array_key_exists('where', $jsonArray)) {
                $whereData = $jsonArray['where'];
                $this->_where($query, $params, $whereData);
            }

            if (array_key_exists('group', $jsonArray)) {
                $groupData = $jsonArray['group'];
                $this->_group($query, $params, $groupData);
            }
        }

        return $query;
    }

    private function _join(Query $query, array $params, array $values)
    {
        $joinTypes = [
            'inner' => 'INNER',
            'left' => 'LEFT',
            'assoc_inner' => 'ASSOC_INNER',
            'assoc_left' => 'ASSOC_LEFT',
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
                                $joinConditions[] = $field . ' = ' . $value;
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

    private function _select(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            $query->select($values);
        }
    }

    private function _where(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            $conditions = [];

            foreach($values as $field => $value) {
                $startPos = strpos($value, '${');
                $endPos = strpos($value, '}');

                if ($startPos !== false && $endPos !== false) {
                    $placeholder = substr($value, $startPos + 2, $endPos - $startPos - 2);

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
}
