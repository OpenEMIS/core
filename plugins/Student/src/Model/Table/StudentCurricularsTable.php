<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\ResultSet;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;

//POCOR-6673
class StudentCurricularsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_curricular_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionCurriculars', ['className' => 'Institution.InstitutionCurriculars']);
        
        $this->addBehavior('Restful.RestfulAccessControl', [
            'AssociationStudent' => ['index','add','edit'],
        ]);
        $this->toggle('add', false);
        $this->toggle('search', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);
    }
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{

	}

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {

        $this->setFieldOrder([
            'name', 'staff_id','category','total_male_students', 'total_female_students', 'total_students'
        ]);
           
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        
    }


    
}
