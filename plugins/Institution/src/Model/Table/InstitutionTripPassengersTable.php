<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionTripPassengersTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

		$this->belongsTo('Students', ['className' => 'User.Users']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('InstitutionTrips', ['className' => 'Institution.InstitutionTrips']);
    }
}
