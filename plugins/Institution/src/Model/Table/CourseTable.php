<?php
namespace Institution\Model\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use App\Model\Table\ControllerActionTable;
use ArrayObject;

class CourseTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_courses');
        parent::initialize($config);

        $this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ClassStudents' => ['index']
        ]);
    }

    public function getCourseOptions($institutionsId, $periodId)
    {
        $institutionClasses = TableRegistry::getTableLocator()->get('Institution.Course');
        $query = $institutionClasses->find('list',['keyField' => 'id', 'valueField' => 'name']);
        return $query;
    }

    public function findCourseOptions(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
       
        $institutionClasses = TableRegistry::getTableLocator()->get('Institution.Unit');
        $query = $institutionClasses->find('list',['keyField' => 'id', 'valueField' => 'name']);
        return $query;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
