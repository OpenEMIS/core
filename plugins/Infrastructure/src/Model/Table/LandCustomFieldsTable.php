<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use ArrayObject;

// Infrastructure field tab

class LandCustomFieldsTable extends CustomFieldsTable
{
    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_custom_fields');
        $this->supportedFieldTypes = $this->getSupportedFieldTypesByModel('Institution.InstitutionLands');
        parent::initialize($config);
        $this->hasMany('CustomFieldOptions', ['className' => 'Infrastructure.LandCustomFieldOptions', 'foreignKey' => 'infrastructure_custom_field_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->hasMany('CustomFieldValues', ['className' => 'Infrastructure.LandCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('CustomForms', [
            'className' => 'Infrastructure.LandCustomForms',
            'joinTable' => 'infrastructure_custom_forms_fields',
            'foreignKey' => 'infrastructure_custom_field_id',
            'targetForeignKey' => 'infrastructure_custom_form_id',
            'through' => 'Infrastructure.InfrastructureCustomFormsFields',
            'dependent' => true
        ]);
    }


    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['submit']) && $data['submit'] == 'save') {
            $entityId = $data['id'];
            $queryString = $this->getQueryString();
            $data['id'] = $queryString['id'];
        }
    }

    
}
