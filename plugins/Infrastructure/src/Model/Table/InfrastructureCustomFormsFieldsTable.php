<?php
namespace Infrastructure\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Http\ServerRequest;
use Cake\Event\Event;

class InfrastructureCustomFormsFieldsTable extends CustomFormsTable
{
    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_custom_forms_fields');
        parent::initialize($config);
        $this->belongsToMany('InfrastructureCustomFields', [
            'className' => 'Infrastructure.RoomCustomFields',
            'joinTable' => 'infrastructure_custom_forms_fields',
            'foreignKey' => 'infrastructure_custom_form_id',
            'targetForeignKey' => 'infrastructure_custom_field_id',
            'through' => 'Infrastructure.InfrastructureCustomFormsFields',
            'dependent' => true
        ]);

        $this->belongsToMany('LandCustomFields', [
                'className' => 'Infrastructure.LandCustomFields',
                'joinTable' => 'infrastructure_custom_forms_fields',
                'foreignKey' => 'infrastructure_custom_form_id',
                'targetForeignKey' => 'infrastructure_custom_field_id',
                'through' => 'Infrastructure.InfrastructureCustomFormsFields',
                'dependent' => true
            ]);


    }
}
