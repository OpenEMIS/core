<?php
namespace Restful\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class ListingBehavior extends Behavior {

    public function findListing(Query $query, array $options)
    {
        $options = new ArrayObject($options);
        $model = $this->_table;
        $model->dispatchEvent('Restful.CRUD.beforeQuery', [$query, $options], $model);
        $model->dispatchEvent('Restful.CRUD.index.beforeQuery', [$query, $options], $model);
        $searchableFields = new ArrayObject($options['extra']['searchableFields']);
        $selectedFields = isset($options['extra']['fields']) ? new ArrayObject($options['extra']['fields']) : new ArrayObject([]);
        $excludedFields = new ArrayObject($this->config('excludedFields'));

        $model->dispatchEvent('Restful.CRUD.index.beforeSearch', [$query, $options, $searchableFields, $selectedFields, $excludedFields], $model);

        $search = isset($options['search']) ? $options['search'] : null;
        $searchCondition = [];
        $fullWildCard = isset($option['fullWildCard']) ? $option['fullWildCard'] : [];
        if ($search) {
            foreach ($searchableFields as $field) {
                if ($selectedFields->getArrayCopy()) {
                    if (in_array($field, $selectedFields->getArrayCopy()) && !in_array($field, $excludedFields->getArrayCopy())) {
                        if (!array_key_exists('OR', $searchCondition)) {
                            $searchCondition['OR'] = [];
                        }
                        $searchCondition['OR'][] = $this->generateSearchQuery($field, $search, $fullWildCard);
                    }
                } else {
                    if (!in_array($field, $excludedFields->getArrayCopy())) {
                        if (!array_key_exists('OR', $searchCondition)) {
                            $searchCondition['OR'] = [];
                        }
                        $searchCondition['OR'][] = $this->generateSearchQuery($field, $search, $fullWildCard);
                    }
                }
            }
        }
        if ($options->offsetExists('extra') && array_key_exists('OR', $options['extra'])) {
            $searchCondition = array_merge($searchCondition, $options['extra']['OR']);
        }

        if ($searchCondition) {
            $query->where($searchCondition);
        }

        $model->dispatchEvent('Restful.CRUD.index.formatResults', [$query, $options], $model);

        return $query;
    }

    private function generateSearchQuery($field, $search, array $fullWildCard)
    {
        if (in_array($field, $fullWildCard)) {
            return [$field.' LIKE ' => "%$search%"];
        } else {
            return [$field.' LIKE ' => "$search%"];
        }
    }
}
