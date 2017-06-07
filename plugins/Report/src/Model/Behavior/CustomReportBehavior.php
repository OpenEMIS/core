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

    public function parseQuery ($jsonArray, array $params)
    {
        if (array_key_exists('model', $jsonArray)) {
            $model = $jsonArray['model'];

            $this->Table = TableRegistry::get($model);
            $query = $this->Table->find();

            if (array_key_exists('join', $jsonArray)) {
                $this->_join($query, $params, $jsonArray['join']);
            }

            if (array_key_exists('contain', $jsonArray)) {
                $this->_contain($query, $params, $jsonArray['contain']);
            }

            if (array_key_exists('matching', $jsonArray)) {
                $this->_matching($query, $params, $jsonArray['matching']);
            }

            if (array_key_exists('select', $jsonArray)) {
                $this->_select($query, $params, $jsonArray['select']);
            }

            if (array_key_exists('find', $jsonArray)) {
                $this->_find($query, $params, $jsonArray['find']);
            }

            if (array_key_exists('where', $jsonArray)) {
                $this->_where($query, $params, $jsonArray['where']);
            }

            if (array_key_exists('group', $jsonArray)) {
                $this->_group($query, $params, $jsonArray['group']);
            }

            if (array_key_exists('order', $jsonArray)) {
                $this->_order($query, $params, $jsonArray['order']);
            }

            return $query;
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
            foreach ($values as $associatedModel) {
                if ($this->Table->associations()->has($associatedModel)) {
                    $contain[] = $associatedModel;
                }
            }

            $query->contain($contain);
        }
    }

    private function _matching(Query $query, array $params, array $values)
    {
        if (!empty($values)) {
            $matching = [];
            foreach ($values as $associatedModel) {
                if ($this->Table->associations()->has($associatedModel)) {
                    $matching[] = $associatedModel;
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
                                $placeholder = substr($value, $pos + 2, strlen($value) - 3);
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
