<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionAssessmentsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_classes');
		parent::initialize($config);

		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

		$this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades', 'dependent' => true]);
		$this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'dependent' => true]);
		$this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true]);
	}
}
