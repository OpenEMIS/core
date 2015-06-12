<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Query;

class EducationProgrammesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationCertifications', ['className' => 'Education.EducationCertifications']);
		$this->belongsTo('EducationCycles', ['className' => 'Education.EducationCycles']);
		$this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies']);
		$this->hasMany('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->hasMany('InstitutionSiteProgrammes', ['className' => 'Institution.InstitutionSiteProgrammes']);
	}

	public function findWithCycle(Query $query, array $options) {
		return $query
			->contain(['EducationCycles'])
			->order(['EducationCycles.order' => 'ASC', $this->aliasField('order') => 'ASC']);
	}
}
