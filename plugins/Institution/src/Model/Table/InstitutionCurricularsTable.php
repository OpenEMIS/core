<?php
namespace Institution\Model\Table;

use ArrayObject;
use stdClass;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Collection\Collection;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\Routing\Router;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Datasource\ResultSetInterface;
use Cake\Network\Session;

class InstitutionCurricularsTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->belongsTo('Institutions', ['className' => 'User.Users', 'foreignKey' => 'institution_id']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $query = $this->request->query;
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $institutionId = $extra['institution_id'];
       // $selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
        $selectedAcademicPeriodId = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
       
        $this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriodId);
        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;
        $extra['elements']['control'] = [
            'name' => 'Institution.Associations/controls',
            'data' => [
                'academicPeriodOptions'=>$academicPeriodOptions,
                'selectedAcademicPeriod'=>$selectedAcademicPeriodId
            ],
            'options' => [],
            'order' => 3
        ];

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $sortable = !is_null($this->request->query('sort')) ? true : false;
        $session = $this->controller->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $query
            ->select([
                'id',
                'name',
                'total_male_students',
                'total_female_students',
                'institution_id',
                'academic_period_id',
                'modified_user_id',
                'modified',
                'created_user_id',
                'created',
            ])
            ->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId'],
            $this->aliasField('institution_id') => $institutionId])
            ->group([$this->aliasField('id')]);

        if (!$sortable) {
            $query
                ->order([
                    $this->aliasField('name') => 'ASC'
                ]);
        }
        $this->controllerAction = $extra['indexButtons']['view']['url']['action'];
        $query = $this->request->query;
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('total_male_students', ['visible' => ['index'=>true]]);
        $this->field('total_female_students', ['visible' => ['index'=>true]]);
        $this->field('total_students', ['visible' => ['index'=>true]]);
        $this->field('type', ['visible' => ['index'=>false]]);
        $this->field('category', ['visible' => ['index'=>true]]);
        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['view' => true, 'edit' => true]]);
        $this->setFieldOrder([
            'name','category','total_male_students', 'total_female_students', 'total_students'
        ]);
    }

    
}