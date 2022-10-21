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

/**
 * Class is to get new tab data in dignosis in Special needs
 * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
 * @ticket POCOR-6873
 */


class SpecialNeedsDiagnosisTable extends ControllerActionTable
{
    const COMMENT_MAX_LENGTH = 350;
    public function initialize(array $config)
    {
        $this->table('user_special_needs_diagnosis');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('SpecialNeedsDiagnosisTypes', ['className' => 'SpecialNeeds.SpecialNeedsDiagnosisTypes']);
        $this->belongsTo('SpecialNeedsDiagnosisDegree', ['className' => 'SpecialNeeds.SpecialNeedsDiagnosisDegree']);

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
                ->add('comment', 'length', [
                'rule' => ['maxLength', self::COMMENT_MAX_LENGTH],
                'message' => __('Comment must not be more then '.self::COMMENT_MAX_LENGTH.' characters.')
                ]);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'special_needs_diagnosis_type_id':
                return __('Type of disability');
            case 'special_needs_diagnosis_degree_id':
                return __('Disability Degree');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onUpdateFieldSpecialNeedsDiagnosisTypeId(Event $event, array $attr, $action, Request $request)
    {
        $attr['onChangeReload'] = true;
        return $attr;
    }

    public function onUpdateFieldSpecialNeedsDiagnosisDegreeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if($action == 'add'){
                $degreeId = $request->data['SpecialNeedsDiagnosis']['special_needs_diagnosis_type_id'];
                $SpecialNeedsDiagnosisDegree = TableRegistry::get('SpecialNeeds.SpecialNeedsDiagnosisDegree');
                $degreeListOptions = $SpecialNeedsDiagnosisDegree->getDegreeList($degreeId);
                        
                $attr['type'] = 'select';

                $attr['placeholder'] = __('--Select--');
                $attr['attr']['options'] = $degreeListOptions;
                $attr['onChangeReload'] = true;
            }else{
                $attr['value'] = $attr['entity']->special_needs_diagnosis_degree_id;
            }
            return $attr;
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('comment', ['visible' => false]);
        $this->field('date', ['visible' => false]);
        $this->field('name', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('special_needs_diagnosis_type_id', ['type' => 'pg_select(connection, table_name, assoc_array)']);
        $this->field('special_needs_diagnosis_degree_id', ['type' => 'pg_select(connection, table_name, assoc_array)']);
        $this->setFieldOrder(['special_needs_diagnosis_type_id','special_needs_diagnosis_level_id']);
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

    private function setupFields($entity = null)
    {
        $this->field('special_needs_diagnosis_type_id', ['type' => 'select']);
        $this->field('special_needs_diagnosis_degree_id', ['type' => 'select']);
        $this->field('comment', ['type' => 'text']);
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('file_content', ['null' => false, 'attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);

        $this->setFieldOrder(['date', 'special_needs_diagnosis_type_id','special_needs_diagnosis_degree_id', 'file_name', 'file_content', 'comment']);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->session();
        $studentUserId = $session->read('Institution.StudentUser.primaryKey.id');

        $query
        ->where([
            'security_user_id =' .$studentUserId,
        ]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key' => '',
            'field' => 'date',
            'type' => 'date',
            'label' => __('Date')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'file_name',
            'type' => 'string',
            'label' => __('File Name')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'comment',
            'type' => 'string',
            'label' => __('Comment')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'special_needs_diagnosis_type_id',
            'type' => 'string',
            'label' => __('Type of disability')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'special_needs_diagnosis_degree_id',
            'type' => 'string',
            'label' => __('Disability Degree')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'security_user_id',
            'type' => 'string',
            'label' => __('Security User')
        ];
        $fields->exchangeArray($extraField);
    }
}
