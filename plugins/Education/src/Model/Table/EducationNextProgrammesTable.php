<?php
namespace Education\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\EventInterface;

class EducationNextProgrammesTable extends AppTable {
	public function initialize(array $config): void {
		$this->setTable('education_programmes');
		parent::initialize($config);
		$this->belongsToMany('EducationProgrammes', [
			'className' => 'Education.EducationProgrammes',
			'joinTable' => 'education_programmes_next_programmes',
			'foreignKey' => 'next_programme_id',
			'targetForeignKey' => 'education_programme_id',
			'through' => 'Education.EducationProgrammesNextProgrammes',
			'dependent' => false
		]);
	}
}
