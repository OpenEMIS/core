<?php
namespace Institution\Model\Table;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class CourseTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_courses');
        parent::initialize($config);

        $this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function getCourseOptions($institutionsId, $periodId)
    {
        $institutionClasses = TableRegistry::get('institution_courses');
        $query = $institutionClasses->find('list',['keyField' => 'id', 'valueField' => 'name']);
        return $query;
    }
}
