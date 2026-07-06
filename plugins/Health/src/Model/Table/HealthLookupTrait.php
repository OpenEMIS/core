<?php
namespace Health\Model\Table;

use Cake\ORM\TableRegistry;

//POCOR-9718: shared populator for belongsTo lookup selects across Health tables.
//Replaces 8 near-identical onUpdateField<X>Id() bodies that all did the same:
//hydrate options from a Health lookup table on add/edit.
trait HealthLookupTrait
{
    /**
     * Populate a belongsTo FK select with options from a lookup table.
     * Call from any onUpdateField<X>Id handler — passes attr through unchanged
     * when action is neither add nor edit (e.g. view/index).
     */
    protected function populateLookupSelect(array $attr, string $action, string $lookupTableAlias): array
    {
        if ($action === 'add' || $action === 'edit') {
            $lookupTable = TableRegistry::getTableLocator()->get($lookupTableAlias);
            $attr['type'] = 'select';
            $attr['placeholder'] = __('--Select--');
            $attr['options'] = $lookupTable->find('list')->toArray();
        }
        return $attr;
    }
}
