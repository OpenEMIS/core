<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class QualificationTitlesTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->hasMany('Qualifications', ['className' => 'Staff.Qualifications']);
		$this->belongsTo('QualificationLevels', ['className' => 'FieldOption.QualificationLevels']);
		
		$this->addBehavior('FieldOption.FieldOption');

		$this->setDeleteStrategy('restrict');
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->requirePresence('qualification_level_id')
		;
	}

	public function afterAction(Event $event) {
		$this->field('qualification_level_id', [
			'type' => 'select',
			'after' => 'national_code'
		]);
	}
}
