<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;

use App\Model\Table\AppTable;

class StaffAppraisalsCompetenciesTable extends AppTable
{
	public function initialize(array $config)
    {
		parent::initialize($config);
		$this->belongsTo('StaffAppraisal', ['className' => 'Staff.Appraisal']);
		$this->belongsTo('Competencies', ['className' => 'Staff.Competencies']);
	}

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $entity->id = Text::uuid();
        }
    }
}