<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class InstitutionSiteProgrammesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);
	}

	public function validationDefault(Validator $validator) {
		$validator->add('name', 'notBlank', [
			'rule' => 'notBlank'
		]);
		return $validator;
	}

	public function beforeAction() {
		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;

		$this->ControllerAction->addField('education_level', ['type' => 'select']);

		$EducationLevels = TableRegistry::get('Education.EducationLevels');
		$levelOptions = $EducationLevels
			->find('list', ['keyField' => 'id', 'valueField' => 'system_level_name'])
			->find('withSystem')
			->toArray();
			
		$this->fields['education_level']['options'] = $levelOptions;
		$this->fields['education_level']['attr'] = ['onchange' => "$('#reload').click()"];

		$this->fields['education_programme_id']['type'] = 'select';

		if ($this->action == 'add') {
			// TODO: write validation logic to check for loaded $levelOptions
			$levelId = key($levelOptions);
			if (isset($this->request->data[$this->alias()])) {
				$levelId = $this->request->data[$this->alias()]['education_level'];
			}
			$programmeOptions = $this->EducationProgrammes
				->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
				->find('withCycle')
				->where([$this->EducationProgrammes->aliasField('education_cycle_id') => $levelId])
				->toArray();
			
			$this->fields['education_programme_id']['options'] = $programmeOptions;

			//$this->ControllerAction->addField('education_grade', ['type' => 'element', 'order' => 5]);
			//$this->fields['education_grade']['options'] = [];
		}		
	}
}
