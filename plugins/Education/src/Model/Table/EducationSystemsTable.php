<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;

use App\Model\Table\AppTable;

class EducationSystemsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('EducationLevels', ['className' => 'Education.EducationLevels', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $options) {
		
		// $institutionId = $this->Session->read('Institutions.id');
		// $query->where([$this->aliasField('institution_site_id') => $institutionId]);
	}
}
