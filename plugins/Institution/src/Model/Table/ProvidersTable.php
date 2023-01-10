<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;

class ProvidersTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_providers');
        parent::initialize($config);

        $this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_provider_id']);
        $this->belongsTo('Sectors', ['className' => 'Institution.Sectors', 'foreignKey' => 'institution_sector_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function addEditBeforeAction(Event $event)
    {
        $this->field('institution_sector_id', ['type' => 'select', 'after' => 'name']);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!$entity->isNew() && $entity->dirty('institution_sector_id')) {
            $providerId = $entity->id;
            $newSectorId = $entity->institution_sector_id;

            // update all the institutions linked to the provider
            $this->Institutions->updateAll(['institution_sector_id' => $newSectorId], ['institution_provider_id' => $providerId]);
        }
    }
}
