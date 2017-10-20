<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionTripPassengersTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

		$this->belongsTo('Students', ['className' => 'User.Users']);
		$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionTrips', ['className' => 'Institution.InstitutionTrips']);
    }
}
