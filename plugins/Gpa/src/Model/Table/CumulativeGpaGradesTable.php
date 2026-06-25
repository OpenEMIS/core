<?php
namespace Gpa\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;

/**
 * POCOR-8222
 * Develop GPA features in system
 * */
class CumulativeGpaGradesTable extends ControllerActionTable {
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('education_grades_cumulative_gpa');
        $this->belongsTo('EducationGrades', [
            'foreignKey' => 'education_grade_id',
            'joinType' => 'INNER',
            'className' => 'Education.EducationGrades',
        ]);

        $this->belongsTo('EducationGradesGpa', [
            'foreignKey' => 'education_grade_gpa_id',
            'joinType' => 'INNER',
            'className' => 'Gpa.Cumulative',
        ]);
    }

    


    
}
