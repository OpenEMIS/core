<?php

namespace Student\Model\Table;

// POCOR-8870
use ArrayObject;

use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ResultSetInterface;

use App\Model\Table\ControllerActionTable;

class InstitutionStudentProgrammesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {   
        $this->setTable('institution_student_programmes'); // Ensure correct DB table name
        parent::initialize($config);

        // Add association with InstitutionStudents
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes','foreignKey' => 'education_programme_id']);

    }
}
