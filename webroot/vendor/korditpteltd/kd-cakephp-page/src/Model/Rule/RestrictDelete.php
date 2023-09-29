<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Page\Model\Rule;

use Cake\Datasource\EntityInterface;
use Cake\Validation\Validation;
use Cake\ORM\TableRegistry;

/**
 * Checks that a list of fields from an entity are unique in the table
 */
class RestrictDelete
{

    /**
     * The field to check
     *
     */
    protected $_field;

    /**
     * The options to use.
     *
     * @var array
     */
    protected $_options;

    /**
     * Constructor.
     *
     * ### Options
     *
     * - `excludedModels` An array of excluded models
     * - `dependent` By default it will check all dependent and non dependent records
     *
     * @param array $fields The list of fields to check uniqueness for
     */
    public function __construct($field, array $options = [])
    {
        $this->_field = $field;
        $this->_options = $options + ['excludedModels' => [], 'dependent' => [true, false]];
    }

    /**
     * Performs the restrict delete association check
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity from where to extract the fields
     *   where the `repository` key is required.
     * @param array $options Options passed to the check,
     * @return bool
     */
    public function __invoke(EntityInterface $entity, array $options)
    {
        $source = $entity->source();
        $model = TableRegistry::get($source);

        $primaryKey = $model->primaryKey();
        $ids = [];
        if (is_array($primaryKey)) {
            foreach ($primaryKey as $key) {
                $ids[$key] = $entity->{$key};
            }
        } else {
            $ids[$primaryKey] = $entity->{$primaryKey};
        }

        $totalCount = 0;
        foreach ($model->associations() as $assoc) {
            if (in_array($assoc->dependent(), $this->_options['dependent'])) {
                if ($assoc->type() == 'oneToMany' || $assoc->type() == 'manyToMany') {
                    $count = 0;
                    $assocTable = $assoc;
                    if ($assoc->type() == 'manyToMany') {
                        $assocTable = $assoc->junction();
                    }
                    $bindingKey = $assoc->bindingKey();
                    $foreignKey = $assoc->foreignKey();

                    $conditions = [];

                    if (is_array($foreignKey)) {
                        foreach ($foreignKey as $index => $key) {
                            $conditions[$assocTable->aliasField($key)] = $ids[$bindingKey[$index]];
                        }
                    } else {
                        $conditions[$assocTable->aliasField($foreignKey)] = $ids[$bindingKey];
                    }

                    $query = $assocTable->find()->where($conditions);
                    $count = $query->count();
                    $title = $assoc->name();

                    $isAssociated = true;
                    if (in_array($title, $this->_options['excludedModels'])) {
                        $isAssociated = false;
                    }
                    if ($isAssociated) {
                        $totalCount += $count;
                    }
                }
            }
        }
        return $totalCount == 0;
    }
}
