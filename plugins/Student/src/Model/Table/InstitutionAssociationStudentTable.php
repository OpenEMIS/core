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

class InstitutionAssociationStudentTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('InstitutionAssociations', ['className' => 'Institution.InstitutionAssociations', 'foreignKey' => 'institution_association_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        $this->toggle('add', false);
        $this->toggle('search', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        // $this->field('average_risk', ['visible' => false]);
        // $this->field('student_id', ['visible' => false]);
        // $this->field('institution_id', ['type' => 'integer']);
        // $this->field('total_risk', ['after' => 'risk_id']);
    }


    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // $this->field('name');
        // $this->field('grade');
        // $this->field('class');
        // $this->field('risk_criterias', ['type' => 'custom_criterias', 'after' => 'total_risk']);
        // $this->field('created_user_id', ['visible' => false]);
        // $this->field('created', ['visible' => false]);
    }

   

    public function indexBeforeQuery2(Event $event, Query $query, ArrayObject $extra)
    {
        $conditions = [];

        $conditions[$this->aliasField('academic_period_id')] = $extra['selectedAcademicPeriodId'];
        $user = $this->Auth->user();

        $session = $this->request->session();
        $studentId = $session->read('Student.Students.id');

        if ($user['is_student'] == 1) {
            $query = $query
            ->where([
                $this->aliasField('student_id') => $user['id'],
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']
            ])
            ->order(['risk_id']);
        }
        else{
            $query = $query
                ->where([
                    $this->aliasField('student_id') => $studentId,
                    $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']
                ])
                ->order(['risk_id']);
        }
     
        
        return $query;
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Associations');
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    
}
