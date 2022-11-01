<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionStudentAbsenceDaysTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
        $this->addBehavior('AcademicPeriod.Period');
        $this->hasMany('InstitutionStudentAbsences', ['className' => 'Institution.InstitutionStudentAbsences', 'foreignKey' => 'institution_student_absence_id']);
    }
}
