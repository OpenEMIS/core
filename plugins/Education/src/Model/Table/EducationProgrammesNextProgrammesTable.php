<?php
namespace Education\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;

class EducationProgrammesNextProgrammesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes', 'foreignKey' => 'education_programme_id']);
		$this->belongsTo('EducationnNextProgrammes', ['className' => 'Education.EducationnNextProgrammes', 'foreignKey' => 'next_programme_id']);
	}

    /**
     * Function to get the list of the next programme base on a given programme id
     *
     * @param $id Education programme id
     * @return array List of next education programmes id
     */
	public function getNextProgrammeList($id) {
		return $this
			->find('list', ['keyField' => 'next_programme_id', 'valueField' => 'next_programme_id'])
			->where([
				$this->aliasField('education_programme_id') => $id
			])
			->toArray();
	}

    /**
     * Function to get the list of the next education grade base on a given education programme id
     *
     * @param $id Education programme id
     * @return array List of next education grades id
     */
	public function getNextGradeList($id) {
		$EducationGrades = TableRegistry::get('Education.EducationGrades');

		$nextProgrammeList = $this->getNextProgrammeList($id);
		if (!empty($nextProgrammeList)) {
			$results = $EducationGrades
				->find('list', ['keyField' => 'id', 'valueField' => 'programme_grade_name'])
				->find('visible')
				->where([
					$EducationGrades->aliasField('education_programme_id IN') => $nextProgrammeList
				])
				->toArray();
		} else {
			$results = [];
		}

		return $results;
	}

	/**
     * Function to get the list of the next first education grade from next programme base on a given education programme id
     *
     * @param $id Education programme id
     * @return array List of next education grades id
     */
	public function getNextProgrammeFirstGradeList($id) {
		$EducationGrades = TableRegistry::get('Education.EducationGrades');

		$nextProgrammeList = $this->getNextProgrammeList($id);
		if (!empty($nextProgrammeList)) {
			$results = [];

			foreach ($nextProgrammeList as $nextProgrammeId) {
				$nextProgrammeGradeResults = $EducationGrades
					->find('list', ['keyField' => 'id', 'valueField' => 'programme_grade_name'])
					->find('visible')
					->find('order')
					->where([
						$EducationGrades->aliasField('education_programme_id') => $nextProgrammeId
					])
					->toArray();

				$results = $results + [key($nextProgrammeGradeResults) => current($nextProgrammeGradeResults)];
			}
		} else {
			$results = [];
		}

		return $results;
	}
}
