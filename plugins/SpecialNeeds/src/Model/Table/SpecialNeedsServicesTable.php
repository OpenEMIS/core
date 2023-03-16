<?php
namespace SpecialNeeds\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;


class SpecialNeedsServicesTable extends ControllerActionTable
{
    const COMMENT_MAX_LENGTH = 350;
    public function initialize(array $config)
    {
        $this->table('user_special_needs_services');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('SpecialNeedsServiceTypes', ['className' => 'SpecialNeeds.SpecialNeedsServiceTypes']);
        $this->belongsTo('SpecialNeedsServiceClassification', ['className' => 'SpecialNeeds.SpecialNeedsServiceClassification']);

        $this->addBehavior('SpecialNeeds.SpecialNeeds');
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Excel', ['pages' => ['index']]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
                // ->add('description', 'length', [
                // 'rule' => ['maxLength', self::COMMENT_MAX_LENGTH],
                // 'message' => __('Description must not be more then '.self::COMMENT_MAX_LENGTH.' characters.')
                // ])
                // ->add('comment', 'length', [
                // 'rule' => ['maxLength', self::COMMENT_MAX_LENGTH],
                // 'message' => __('Comment must not be more then '.self::COMMENT_MAX_LENGTH.' characters.')
                // ])
                ->allowEmpty('file_content');
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'special_needs_service_type_id':
                return __('Service Name');
            case 'organization':
                return __('Service Provider');
            case 'special_needs_service_classification_id':
                return __('Classification');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        if (is_null($this->request->query('academic_period_id'))) {
            $currentAcademicPeriod = $this->AcademicPeriods->getCurrent();
            $url = $this->ControllerAction->url($this->alias());
            // $url['academic_period_id'] = $currentAcademicPeriod;
            $url['academic_period_id'] = '-1';
            $this->controller->redirect($url);
        }

        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->field('description', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('organization', ['visible' => false]);
        $this->field('special_needs_service_type_id', ['type' => 'pg_select(connection, table_name, assoc_array)']);
        $this->setFieldOrder(['special_needs_service_type_id']);

        // Start POCOR-5188
        if($this->request->params['controller'] == 'Staff'){
            $is_manual_exist = $this->getManualUrl('Institutions','Services','Staff - Special Needs');       
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
        
                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }
        }elseif($this->request->params['controller'] == 'Students'){
            $is_manual_exist = $this->getManualUrl('Institutions','Services','Students - Special Needs');       
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];
        
                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        }
        // End POCOR-5188
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Academic Periods Filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : '-1';

        $academicPeriodOptions = ['-1' => 'All Academic Period'] + $academicPeriodOptions;
        if ($selectedAcademicPeriod != '-1') {
            $query->where([
                $this->aliasField('academic_period_id') => $selectedAcademicPeriod
            ]);
        }

        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $extra['elements']['controls'] = ['name' => 'SpecialNeeds.Services/controls', 'data' => [], 'options' => [], 'order' => 1];
        // Academic Periods Filter - END
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

     public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            if ($entity->has('academic_period_id')) {
                $selectedAcademicPeriodId = $entity->academic_period_id;
            } else {
                $academicPeriodQueryString = $this->request->query('academic_period_id');
                if (!is_null($academicPeriodQueryString) && $this->AcademicPeriods->exists($academicPeriodQueryString)) {
                    $selectedAcademicPeriodId = $academicPeriodQueryString;
                } else {
                    $selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();
                }
            }

            if ($selectedAcademicPeriodId == '-1') {
                $selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();
            }
            $academicPeriodName = $this->AcademicPeriods
                ->get($selectedAcademicPeriodId)
                ->name;

            $attr['type'] = 'readonly';
            $attr['value'] = $selectedAcademicPeriodId;
            $attr['attr']['value'] = $academicPeriodName;

            return $attr;
        }
    }

    private function setupFields($entity = null)
    {
        $this->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('special_needs_service_type_id', ['type' => 'select']);
        $this->field('description', ['type' => 'text']);
        $this->field('special_needs_service_classification_id', ['type' => 'select']);
        $this->field('organization');
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment'), 'required' => true], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('comment', ['type' => 'text']);

        $this->setFieldOrder(['academic_period_id', 'special_needs_service_type_id', 'description', 'special_needs_service_classification_id', 'organization', 'file_name', 'file_content', 'comment']);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->session();
        $studentUserId = $session->read('Institution.StudentUser.primaryKey.id');
        $academicPeriodId = $this->request->query['academic_period_id'];

        if($academicPeriodId == '-1'){
            $query
            ->where([
                'security_user_id =' .$studentUserId,
            ]);
        }else{
            $query
            ->where([
                'academic_period_id =' .$academicPeriodId,
                'security_user_id =' .$studentUserId,
            ]);
        }
    }
}
