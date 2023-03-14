<?php
namespace Institution\Model\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;

class CourseTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_courses');
        parent::initialize($config);

        $this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ClassStudents' => ['index']
        ]);
    }

    public function getCourseOptions($institutionsId, $periodId)
    {
        $institutionClasses = TableRegistry::get('institution_courses');
        $query = $institutionClasses->find('list',['keyField' => 'id', 'valueField' => 'name']);
        return $query;
    }

    public function findCourseOptions(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
       
        $institutionClasses = TableRegistry::get('institution_units');
        $query = $institutionClasses->find('list',['keyField' => 'id', 'valueField' => 'name']);
        return $query;
    }
}
