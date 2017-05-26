<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class LicenseTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('license_types');
        parent::initialize($config);

		$this->hasMany('LicenseClassifications', ['className' => 'FieldOption.LicenseClassifications', 'foreignKey' => 'license_type_id']);
        $this->hasMany('Licenses', ['className' => 'Staff.Licenses', 'foreignKey' => 'license_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
	{
		if ($entity->editable == 0) {
			$event->stopPropagation();
			$this->Alert->warning('general.delete.restrictDelete');
			return $this->controller->redirect($this->url('index'));
		}
    }
}
