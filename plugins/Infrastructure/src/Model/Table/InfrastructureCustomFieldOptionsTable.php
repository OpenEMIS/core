<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;

class InfrastructureCustomFieldOptionsTable extends CustomFieldsTable
{
    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_custom_field_options');
        parent::initialize($config);
        $this->belongsTo('CustomFields', ['className' => 'Infrastructure.LandCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
        if ($this->behaviors()->has('Reorder')) {
            $reorderBehavior = $this->behaviors()->get('Reorder');
            $reorderBehavior->setConfig('filter', 'infrastructure_custom_field_id');
        }
    }
}
