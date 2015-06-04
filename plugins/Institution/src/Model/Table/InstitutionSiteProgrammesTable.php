<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class InstitutionSiteProgrammesTable extends AppTable {
	public $EducationLevels;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);
	}

	public function beforeAction($event) {
		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;
		$this->fields['education_programme_id']['type'] = 'select';
		$this->fields['education_programme_id']['attr'] = ['onchange' => "$('#reload').click()"];

		$this->ControllerAction->addField('education_level', [
			'type' => 'select',
			'attr' => ['onchange' => "$('#reload').click()"]
		]);

		$this->EducationLevels = TableRegistry::get('Education.EducationLevels');
	}

	public function addBeforeAction($event, $entity) {
		$levelOptions = $this->EducationLevels
			->find('list', ['keyField' => 'id', 'valueField' => 'system_level_name'])
			->find('withSystem')
			->toArray();
			
		$this->fields['education_level']['options'] = $levelOptions;

		// TODO-jeff: write validation logic to check for loaded $levelOptions
		$levelId = key($levelOptions);
		if ($this->request->data($this->aliasField('education_level'))) {
			$levelId = $this->request->data($this->aliasField('education_level'));
		}

		$programmeOptions = $this->EducationProgrammes
			->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
			->find('withCycle')
			->where([$this->EducationProgrammes->aliasField('education_cycle_id') => $levelId])
			->toArray();

		$this->fields['education_programme_id']['options'] = $programmeOptions;

		// start Education Grade field
		$this->ControllerAction->addField('education_grade', [
			'type' => 'element', 
			'order' => 5,
			'element' => 'Institution.Programmes/grades'
		]);

		$programmeId = key($programmeOptions);
		if ($this->request->data($this->aliasField('education_programme_id'))) {
			$programmeId = $this->request->data($this->aliasField('education_programme_id'));
		}
		// TODO-jeff: need to check if programme id is empty

		$EducationGrades = $this->EducationProgrammes->EducationGrades;
		$gradeData = $EducationGrades->find()
			->find('visible')->find('order')
			->where([$EducationGrades->aliasField('education_programme_id') => $programmeId])
			->all();

		$this->fields['education_grade']['data'] = $gradeData;
		// end Education Grade field
	}
}
