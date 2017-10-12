<?php
namespace User\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;

class AdvancedNameSearchBehavior extends Behavior
{
    // findByNames
    // advancedNameSearch Behavior
    public function addSearchConditions(Query $query, $options = [])
    {
        $conditions = $this->getNameSearchConditions($options);
        if (array_key_exists('OR', $options)) {
            $conditions = array_merge($conditions, $options['OR']);
        }
        $query->where(['OR' => $conditions]);

        return $query;
    }

    public function getNameSearchConditions($options = [])
    {
        $conditions = [];
        $searchByUserName = false;
        if (array_key_exists('searchByUserName', $options)) {
            $searchByUserName = $options['searchByUserName'];
        }

        if (array_key_exists('searchTerm', $options)) {
            $search = $options['searchTerm'];
        }

        $alias = $this->_table->alias();
        if (array_key_exists('alias', $options)) {
            $alias = $options['alias'];
        }
        $alias = '`'.$alias.'`';

        $searchParams = explode(' ', trim($search));
        foreach ($searchParams as $key => $value) {
            if (empty($searchParams[$key])) {
                unset($searchParams[$key]);
            }
        }
        // To re-index the array
        $searchParams = array_values($searchParams);

        // this search is catered to jordon's request
        $searchString = $search . '%';
        switch (count($searchParams)) {
            case 1:
                // 1 word - search by openemis id or 1st or middle or third or last

                $conditions = [
                    'OR' => [
                        $alias . '.openemis_no LIKE' => $searchString,
                        $alias . '.first_name LIKE' => $searchString,
                        $alias . '.middle_name LIKE' => $searchString,
                        $alias . '.third_name LIKE' => $searchString,
                        $alias . '.last_name LIKE' => $searchString,
                        $alias . '.identity_number LIKE' => $searchString // Adding the search by identity.
                    ]
                ];
                if ($searchByUserName) {
                    $conditions['OR'][$alias . '.`username` LIKE '] = $searchString;
                }
                break;

            case 2:
                // 2 words - search by 1st and last name

                $nameAlias = ['first_name', 'last_name'];
                $concatCondition = [];
                foreach ($searchParams as $key => $param) {
                    $nameAlias[$key] = '`'.$nameAlias[$key].'`';
                    $concatCondition[0]["$alias.$nameAlias[$key]".' LIKE '] = $param . '%';
                }
                $conditions = [];
                $conditions['OR'] = [];
                $conditions['OR'] = $concatCondition;
                break;

            case 3:
                // 3 words - search by 1st middle last
                $nameAlias = ['first_name', 'middle_name', 'last_name'];
                $concatCondition = [];
                foreach ($searchParams as $key => $param) {
                    $nameAlias[$key] = '`'.$nameAlias[$key].'`';
                    $concatCondition[0]["$alias.$nameAlias[$key]".' LIKE '] = $param . '%';
                }

                $concatCondition[1] = [
                    'OR' => [
                        // Search by two words for the first name field and the last word on the last name field
                        [
                            "$alias.$nameAlias[0]".' LIKE ' => ($searchParams[0].' '.$searchParams[1].'%'),
                            "$alias.$nameAlias[2]".' LIKE ' => $searchParams[2].'%'
                        ],
                        // Search by two words for the last name field and the last word on the first name field
                        [
                            "$alias.$nameAlias[0]".' LIKE ' => ($searchParams[0].'%'),
                            "$alias.$nameAlias[2]".' LIKE ' => ($searchParams[1].' '.$searchParams[2].'%')
                        ]
                    ]
                ];
                $conditions = [];
                $conditions['OR'] = [];
                $conditions['OR'] = $concatCondition;
                break;

            case 4:
                // 4 words - search by 1st middle third last
                $nameAlias = ['first_name', 'middle_name', 'third_name', 'last_name'];
                $concatCondition = [];
                foreach ($searchParams as $key => $param) {
                    $nameAlias[$key] = '`'.$nameAlias[$key].'`';
                    $concatCondition[0][]["$alias.$nameAlias[$key]".' LIKE '] = $param . '%';
                }

                $concatCondition[1] = [
                    'OR' => [
                        // Search by three words for the first name field and the last word on the last name field
                        [
                            "$alias.$nameAlias[0]".' LIKE ' => ($searchParams[0].' '.$searchParams[1].' '.$searchParams[2].'%'),
                            "$alias.$nameAlias[3]".' LIKE ' => ($searchParams[3].'%')
                        ],
                        // Search by first word for the first name field and the last three word on the last name field
                        [
                            "$alias.$nameAlias[0]".' LIKE ' => ($searchParams[0].'%'),
                            "$alias.$nameAlias[3]".' LIKE ' => ($searchParams[1].' '.$searchParams[2].' '.$searchParams[3].'%')
                        ],
                        // Search by two words for the first name field and the last two words on the last name field
                        [
                            "$alias.$nameAlias[0]".' LIKE ' => ($searchParams[0].' '.$searchParams[1].'%'),
                            "$alias.$nameAlias[3]".' LIKE ' => ($searchParams[2].' '.$searchParams[3].'%')
                        ],
                        // Search by two words for the first name field, third word for the middle name and the last word on the last name field
                        [
                            "$alias.$nameAlias[0]".' LIKE ' => ($searchParams[0].' '.$searchParams[1].'%'),
                            "$alias.$nameAlias[1]".' LIKE ' => ($searchParams[2].'%'),
                            "$alias.$nameAlias[3]".' LIKE ' => ($searchParams[3].'%')
                        ],
                        // Search by two words for the first name field, third word for the third name and the last word on the last name field
                        [
                            "$alias.$nameAlias[0]".' LIKE ' => ($searchParams[0].' '.$searchParams[1].'%'),
                            "$alias.$nameAlias[2]".' LIKE ' => ($searchParams[2].'%'),
                            "$alias.$nameAlias[3]".' LIKE ' => ($searchParams[3].'%')
                        ],
                        // Search by first word for the first name field, second word for the middle name and the last two word on the last name field
                        [
                            "$alias.$nameAlias[0]".' LIKE ' => ($searchParams[0].'%'),
                        "$alias.$nameAlias[1]".' LIKE ' => ($searchParams[1].'%'),
                            "$alias.$nameAlias[3]".' LIKE ' => ($searchParams[2].' '.$searchParams[3].'%')
                        ],
                        // Search by first word for the first name field, second word for the third name and the last two word on the last name field
                        [
                            "$alias.$nameAlias[0]".' LIKE ' => ($searchParams[0].'%'),
                            "$alias.$nameAlias[2]".' LIKE ' => ($searchParams[1].'%'),
                            "$alias.$nameAlias[3]".' LIKE ' => ($searchParams[2].' '.$searchParams[3].'%')
                        ],
                        // Search by one word for the first name field, second and third word for the middle name and the last word on the last name field
                        [
                            "$alias.$nameAlias[0]".' LIKE ' => ($searchParams[0].'%'),
                            "$alias.$nameAlias[1]".' LIKE ' => ($searchParams[1].' '.$searchParams[2].'%'),
                            "$alias.$nameAlias[3]".' LIKE ' => ($searchParams[3].'%')
                        ],
                        // Search by one word for the first name field, second and third word for the third name and the last word on the last name field
                        [
                            "$alias.$nameAlias[0]".' LIKE ' => ($searchParams[0].'%'),
                            "$alias.$nameAlias[2]".' LIKE ' => ($searchParams[1].' '.$searchParams[2].'%'),
                            "$alias.$nameAlias[3]".' LIKE ' => ($searchParams[3].'%')
                        ]
                    ]
                ];
                $conditions = [];
                $conditions['OR'] = [];
                $conditions['OR'] = $concatCondition;
                break;

            default:
                foreach ($searchParams as $key => $value) {
                    $searchString = $value . '%';
                    $conditions = [
                        'OR' => [
                            $alias . '.`openemis_no` LIKE' => $searchString,
                            $alias . '.`first_name` LIKE' => $searchString,
                            $alias . '.`middle_name` LIKE' => $searchString,
                            $alias . '.`third_name` LIKE' => $searchString,
                            $alias . '.`last_name` LIKE' => $searchString
                        ]
                    ];
                }
                break;
        }
        return $conditions['OR'];
    }
}
