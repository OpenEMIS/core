<?php

namespace Institution\Model\Table;

use App\Model\Traits\UserTrait;
use ArrayObject;
use Cake\Datasource\ResultSetInterface;
use Cake\I18n\Date;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Utility\Text;
use Cake\Log\Log;
use Cake\ORM\Locator\TableLocator;

class SubjectStudentsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_subject_students');
         parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects','foreignKey' => 'institution_subject_id',]);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects', 'foreignKey' => 'education_subject_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'education_grade_id']);  // Ensure foreign key is correct
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses', 'foreignKey' => 'student_status_id']);
        $this->belongsTo('InstitutionClassStudents', ['className' => 'Institution.InstitutionClassStudents']);

        $this->belongsTo('ClassStudents', [
            'className' => 'Institution.InstitutionClassStudents',
            'foreignKey' => [
                'institution_class_id',
                'student_id'
            ],
            'bindingKey' => [
                'institution_class_id',
                'student_id'
            ]
        ]);

        $this->addBehavior('CompositeKey');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index', 'add'],
            'SubjectStudents' => ['index'],
            'OpenEMIS_Classroom' => ['index']
        ]);
    }


    

}
