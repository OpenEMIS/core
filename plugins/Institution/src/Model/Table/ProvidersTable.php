<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;

class ProvidersTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_providers');
        parent::initialize($config);

        $this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_provider_id']);
        $this->belongsTo('Sectors', ['className' => 'Institution.Sectors', 'foreignKey' => 'institution_sector_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function addEditBeforeAction(EventInterface $event)
    {
        $this->field('institution_sector_id', ['type' => 'select', 'after' => 'name']);
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if (!$entity->isNew() && $entity->getDirty('institution_sector_id')) {
            $providerId = $entity->id;
            $newSectorId = $entity->institution_sector_id;

            // update all the institutions linked to the provider
            $this->Institutions->updateAll(['institution_sector_id' => $newSectorId], ['institution_provider_id' => $providerId]);
        }
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
