<?php

namespace SpecialNeeds\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;


class SpecialNeedsServicesTable extends ControllerActionTable
{
    const COMMENT_MAX_LENGTH = 350;

    public function initialize(array $config): void
    {
        $this->setTable('user_special_needs_services');
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
        $this->addBehavior('User.UserTab', [
            'appliedAction' => ['SpecialNeedsServices' =>
                ['academic_period_id',
                    'special_needs_service_type_id',
                    'special_needs_service_classification_id']
            ]
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'special_needs_service_type_id':
                return __('Service Name');
            case 'file_content':
                return __('Attachment');
            case 'description':
                return __('Description');
            case 'comment':
                return __('Comment');
            case 'academic_period_id':
                return __('Academic Period');
            case 'organization':
                return __('Service Provider');
            case 'special_needs_service_classification_id':
                return __('Classification');
            case 'modified_user_id':
                return __('Modified By');  //POCOR-6873
            case 'modified':
                return __('Modified On');  //POCOR-6873
            case 'created_user_id':
                return __('Created By');  //POCOR-6873
            case 'created':
                return __('Created On');  //POCOR-6873
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        if (is_null($this->request->getQuery('academic_period_id'))) {
            $currentAcademicPeriod = $this->AcademicPeriods->getCurrent();
            $url = $this->ControllerAction->url($this->getAlias());
            // $url['academic_period_id'] = $currentAcademicPeriod;
            // $url['academic_period_id'] = '-1';
            //$this->controller->redirect($url);
        }

        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->field('description', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('organization', ['visible' => false]);
        $this->field('security_user_id', ['visible' => false]); //POCOR-9584: Hide security_user_id in index
        $this->field('special_needs_service_type_id', ['type' => 'pg_select(connection, table_name, assoc_array)']);
        $this->setFieldOrder(['special_needs_service_type_id']);

        // Start POCOR-5188
        if ($this->request->getParam('controller') == 'Staff') {
            $is_manual_exist = $this->getManualUrl('Institutions', 'Services', 'Staff - Special Needs');
            if (!empty($is_manual_exist)) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target' => '_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }
        } elseif ($this->request->getParam('controller') == 'Students') {
            $is_manual_exist = $this->getManualUrl('Institutions', 'Services', 'Students - Special Needs');
            if (!empty($is_manual_exist)) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target' => '_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        } elseif ($this->request->getParam('controller') == 'Directories') {
            $is_manual_exist = $this->getManualUrl('Directory', 'Services', 'Special Needs');
            if (!empty($is_manual_exist)) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target' => '_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        } elseif ($this->request->getParam('controller') == 'Profiles') {
            $is_manual_exist = $this->getManualUrl('Personal', 'Services', 'Special Needs');
            if (!empty($is_manual_exist)) {
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target' => '_blank'
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

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        // Academic Periods Filter
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : '-1';

        $academicPeriodOptions = ['-1' => 'All Academic Period'] + $academicPeriodOptions;
        if ($selectedAcademicPeriod != '-1') {
            $query->where([
                $this->aliasField('academic_period_id') => $selectedAcademicPeriod
            ]);
        }
        $userID = $this->getUserID();
        $query->where([
            $this->aliasField('security_user_id') => $userID
        ]);
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod'));
        $extra['elements']['controls'] = ['name' => 'SpecialNeeds.Services/controls', 'data' => [], 'options' => [], 'order' => 1];
        // Academic Periods Filter - END
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            if ($entity->has('academic_period_id')) {
                $selectedAcademicPeriodId = $entity->academic_period_id;
            } else {
                $academicPeriodQueryString = $this->request->getQuery('academic_period_id');
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

            // $attr['type'] = 'readonly'; // POCOR-7467
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
        $this->field('security_user_id', ['type' => 'hidden']); //POCOR-9584: Hidden - automatically set from getUserID()

        $this->setFieldOrder(['academic_period_id', 'special_needs_service_type_id', 'description', 'special_needs_service_classification_id', 'organization', 'file_name', 'file_content', 'comment']);
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $userId = $this->getUserID();
        $academicPeriodId = $this->request->getQuery('academic_period_id');
        if($academicPeriodId == null){
            $academicPeriodId = $this->AcademicPeriods->getCurrent();
        }

        if ($academicPeriodId == '-1') {
            $query
                ->where([
                    'security_user_id' => $userId, 
                ]);
        } else {
            $query
                ->where([
                    'academic_period_id' => $academicPeriodId,
                    'security_user_id' => $userId,
                ]);
        }
    }

    //POCOR-9584: start - Automatically set security_user_id from getUserID()
    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        // Decoded from query string: handles staff_id, student_id, or security_user_id depending on calling controller
        $entity->security_user_id = $this->getUserID();
    }

    public function editBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        // Ensures security_user_id cannot be changed by users
        $entity->security_user_id = $this->getUserID();
    }
    //POCOR-9584: end

}
