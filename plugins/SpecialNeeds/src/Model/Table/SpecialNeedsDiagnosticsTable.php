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


class SpecialNeedsDiagnosticsTable extends ControllerActionTable
{
    const COMMENT_MAX_LENGTH = 350;
    public function initialize(array $config)
    {
        $this->table('user_special_needs_diagnostics');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('SpecialNeedsDiagnosticsTypes', ['className' => 'SpecialNeeds.SpecialNeedsDiagnosticsTypes']);
        $this->belongsTo('SpecialNeedsDiagnosticsDegree', ['className' => 'SpecialNeeds.SpecialNeedsDiagnosticsDegree']);

        $this->addBehavior('SpecialNeeds.SpecialNeeds');
        /*$this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);*/
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
            case 'special_needs_diagnostics_type_id':
                return __('Type of disability');
            case 'special_needs_diagnostics_degree_id':
                return __('Disability Degree');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onUpdateFieldSpecialNeedsDiagnosticsTypeId(Event $event, array $attr, $action, Request $request)
    {
        $attr['onChangeReload'] = true;
        return $attr;
    }

    public function onUpdateFieldSpecialNeedsDiagnosticsDegreeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if($action == 'add'){
                $degreeId = $request->data['SpecialNeedsDiagnostics']['special_needs_diagnostics_type_id'];
                $SpecialNeedsDiagnosticsDegree = TableRegistry::get('SpecialNeeds.SpecialNeedsDiagnosticsDegree');
                $degreeListOptions = $SpecialNeedsDiagnosticsDegree->getDegreeList($degreeId);
                        
                $attr['type'] = 'select';

                $attr['placeholder'] = __('--Select--');
                $attr['attr']['options'] = $degreeListOptions;
                $attr['onChangeReload'] = true;
            }else{
                $attr['value'] = $attr['entity']->special_needs_diagnostics_degree_id;
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
        $this->field('special_needs_diagnostics_type_id', ['type' => 'pg_select(connection, table_name, assoc_array)']);
        $this->field('special_needs_diagnostics_degree_id', ['type' => 'pg_select(connection, table_name, assoc_array)']);
        $this->setFieldOrder(['special_needs_diagnostics_type_id','special_needs_diagnostics_level_id']);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Diagnostics','Students - Special Needs');  
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
		// End POCOR-5188
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
        $this->field('special_needs_diagnostics_type_id', ['type' => 'select']);
        $this->field('special_needs_diagnostics_degree_id', ['type' => 'select']);
        $this->field('comment', ['type' => 'text']);
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('file_content', ['null' => true, 'attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]); //Modify for POCOR-7147

        $this->setFieldOrder(['date', 'special_needs_diagnostics_type_id','special_needs_diagnostics_degree_id', 'file_name', 'file_content', 'comment']);
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
            'field' => 'special_needs_diagnostics_type_id',
            'type' => 'string',
            'label' => __('Type of disability')
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'special_needs_diagnostics_degree_id',
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

    // Start POCOR-7467
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $monthOptions = ['1'=> '1', '2'=> '2','3'=> '3','4'=> '4', '5'=> '5', '6'=> '6','7'=> '7','8'=> '8','9'=> '9','10'=> '10', '11'=>'11', '12'=> '12'];
        $monthOptions = ['-1' => '-- ' . __('Select Month') . ' --'] + $monthOptions;    
        $selectedmonth = !is_null($this->request->query('month')) ? $this->request->query('month') : '-1';
        $AcademicPeriods = TableRegistry::get('academic_periods');
        $periodsOptions = $AcademicPeriods
                    ->find('list', ['keyField' => 'start_year', 'valueField' => 'start_year'])
                    ->order([$AcademicPeriods->aliasField('start_year') => 'DESC']);
        $periodsOptions = ['-1' => '-- ' . __('Select Period') . ' --'] + $periodsOptions->toArray();      
        $selectedPeriods = !is_null($this->request->query('period')) ? $this->request->query('period') : '-1';

        if ($selectedPeriods > 0) {
            $compare_start_date = $selectedPeriods .'-01-01';
            $compare_end_date = $selectedPeriods .'-12-31';   
            $query->where([$this->aliasField('date >=') => $compare_start_date, $this->aliasField('date <=') => $compare_end_date]); 
        }

        if ($selectedmonth > 0) {
            if ($selectedPeriods > 0) {
                $compare_start_date = $selectedPeriods .'-'. $selectedmonth.'-'.'01';
                $compare_end_date = $selectedPeriods .'-'. $selectedmonth.'-'.date("t", strtotime($compare_start_date));   
                $query->where([$this->aliasField('date >=') => $compare_start_date, $this->aliasField('date <=') => $compare_end_date]); 
            }else{
                $compare_start_date = date('Y').'-'.$selectedmonth.'-01';
                $compare_end_date = date("Y-m-t", strtotime($compare_start_date));
                $query->where([$this->aliasField('date >=') => $compare_start_date, $this->aliasField('date <=') => $compare_end_date]); 
            } 
        }
        $this->controller->set(compact('monthOptions', 'selectedmonth','periodsOptions','selectedPeriods'));
        $extra['elements']['controls'] = ['name' => 'SpecialNeeds.Diagnostics/controls', 'data' => [], 'options' => [], 'order' => 1];
    }

    // End POCOR-7467
}
