<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class InstitutionSectionSubjectsTable extends AppTable {
	
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('InstitutionSections', ['className' => 'Institution.InstitutionSections']);
		$this->belongsTo('InstitutionSubjects',  ['className' => 'Institution.InstitutionSubjects']);
	}
}
