<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\ResultSet;

class ReorderBehavior extends Behavior
{
    protected $_defaultConfig = [
        'orderField' => 'order',
        'filter' => null,
        'filterValues' => null
    ];

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $orderField = $this->getConfig('orderField');
            $filter = $this->getConfig('filter');
            $filterValues = $this->getConfig('filterValues');
            $order = 0;
            if (is_null($filter)) {
                $order = $this->_table->find()->count();
            } else {
                if (!is_null($filterValues)) {
                    $filterValue = null;
                    if (is_array($filterValues)) {
                        $filterValue = $filterValues;
                    }
                } else {
                    $filterValue = $entity->{$filter};
                }
                $table = $this->_table;
                $filterValue = (array)$filterValue; 
                //POCOR-8407 add if else condition
                if (!empty($filterValue)) {
                    $order = $table
                        ->find()
                        ->where([$table->aliasField($filter) . ' IN' => $filterValue])
                        ->count();
                } else {
                    // Handle the case when $filterValue is empty
                    $order = 0; // or any other appropriate action
                }

            }
            $entity->{$orderField} = $order + 1;
        }
    }

    private function updateOrder($entity, $orderField, $filter = null, $filterValues = null)
    {
        $table = $this->_table;
        $reorderItems = [];
        if (is_null($filter)) {
            // this checking is for the table with no parent_id column
            $reorderItems = $table->find('list')
            ->order([$table->aliasField($orderField)])
            ->toArray();
        } else {
            if (!is_null($filterValues)) {
                $filterValue = null;
                if (is_array($filterValues)) {
                    $filterValue = $filterValues;
                }
            } else {
                $filterValue = $entity->{$filter};
            }

            if (!is_null($filterValue)) {
                $where = [$table->aliasField($filter).' IN ' => $filterValue];
                $reorderItems = $table
                    ->find('list')
                    ->where($where)
                    ->order([$table->aliasField($orderField)])
                    ->toArray();
            }
        }
        $counter = 1;
        foreach ($reorderItems as $key => $item) {
            $table->updateAll(["`$orderField`" => $counter++], [$table->getPrimaryKey() => $key]);
        }
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $orderField = $this->getConfig('orderField');
        $filter = $this->getConfig('filter');
        $filterValues = $this->getConfig('filterValues');
        $this->updateOrder($entity, $orderField, $filter, $filterValues);
    }


    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $orderField = $this->getConfig('orderField');
        $filter = $this->getConfig('filter');
        $filterValues = $this->getConfig('filterValues');
        $this->updateOrder($entity, $orderField, $filter, $filterValues);
    }
}
