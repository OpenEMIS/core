<?php
namespace SpecialNeeds\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;


class SpecialNeedsDevicesTable extends ControllerActionTable
{
    const COMMENT_MAX_LENGTH = 350;
    public function initialize(array $config): void
    {
        $this->setTable('user_special_needs_devices');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('SpecialNeedsDeviceTypes', ['className' => 'SpecialNeeds.SpecialNeedsDeviceTypes']);

        $this->addBehavior('SpecialNeeds.SpecialNeeds');

        $this->addBehavior('Excel', ['pages' => ['index']]);
        $this->addBehavior('Excel', ['pages' => ['index']]);
        $this->addBehavior('User.UserTab', [
            'appliedAction' => ['SpecialNeedsDevices' =>
                ['special_needs_device_type_id']
            ]
        ]);

    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
                ->add('comment', 'length', [
                'rule' => ['maxLength', self::COMMENT_MAX_LENGTH],
                'message' => __('Comment must not be more then '.self::COMMENT_MAX_LENGTH.' characters.')
                ]);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'special_needs_device_type_id':
                return __('Device Name');
            case 'comment':
                return __('Comment');
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
        $this->field('comment', ['visible' => false]);
        $this->field('security_user_id', ['visible' => false]); //POCOR-9584: Hide security_user_id in index
        $this->setFieldOrder(['special_needs_device_type_id']);


        // Start POCOR-5188
        if($this->request->getParam('controller') == 'Staff'){
            $is_manual_exist = $this->getManualUrl('Institutions','Devices','Staff - Special Needs');       
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
        }elseif($this->request->getParam('controller') == 'Students'){
            $is_manual_exist = $this->getManualUrl('Institutions','Devices','Students - Special Needs');       
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

        }elseif($this->request->getParam('controller') == 'Directories'){ 
            $is_manual_exist = $this->getManualUrl('Directory','Devices','Special Needs');       
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

        }elseif($this->request->getParam('controller') == 'Profiles'){ 
            $is_manual_exist = $this->getManualUrl('Personal','Devices','Special Needs');       
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

    private function setupFields($entity = null)
    {
        $this->field('special_needs_device_type_id', ['type' => 'select']);
        $this->field('comment', ['type' => 'text']);
        $this->field('security_user_id', ['type' => 'hidden']); //POCOR-9584: Hidden - automatically set from getUserID()

        $this->setFieldOrder(['special_needs_device_type_id', 'comment']);
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $userID = $this->getUserID();
        $query
        ->where([
            'security_user_id =' .$userID,
        ]);
    }

    // Start POCOR-7467
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $monthOptions = ['1'=> '1', '2'=> '2','3'=> '3','4'=> '4', '5'=> '5', '6'=> '6','7'=> '7','8'=> '8','9'=> '9','10'=> '10', '11'=>'11', '12'=> '12'];
        $monthOptions = ['-1' => '-- ' . __('Select Month') . ' --'] + $monthOptions;    
        $selectedmonth = !is_null($this->request->getQuery('month')) ? $this->request->getQuery('month') : '-1';
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $periodsOptions = $AcademicPeriods
                    ->find('list', ['keyField' => 'start_year', 'valueField' => 'start_year'])
                    ->order([$AcademicPeriods->aliasField('start_year') => 'DESC']);
        $periodsOptions = ['-1' => '-- ' . __('Select Period') . ' --'] + $periodsOptions->toArray();      
        $selectedPeriods = !is_null($this->request->getQuery('period')) ? $this->request->getQuery('period') : '-1';

        if ($selectedPeriods > 0) {
            $compare_start_date = $selectedPeriods .'-01-01';
            $compare_end_date = $selectedPeriods .'-12-31';   
            $query->where([$this->aliasField('created >=') => $compare_start_date, $this->aliasField('created <=') => $compare_end_date]); 
        }

        if ($selectedmonth > 0) {
            if ($selectedPeriods > 0) {
                $compare_start_date = $selectedPeriods .'-'. $selectedmonth.'-'.'01';
                $compare_end_date = $selectedPeriods .'-'. $selectedmonth.'-'.date("t", strtotime($compare_start_date));   
                $query->where([$this->aliasField('created >=') => $compare_start_date, $this->aliasField('created <=') => $compare_end_date]); 
            }else{
                $compare_start_date = date('Y').'-'.$selectedmonth.'-01';
                $compare_end_date = date("Y-m-t", strtotime($compare_start_date));
                $query->where([$this->aliasField('created >=') => $compare_start_date, $this->aliasField('created <=') => $compare_end_date]); 
            } 
        }
        $userID = $this->getUserID();
        $query->where([
            $this->aliasField('security_user_id') => $userID
        ]);
        $this->controller->set(compact('monthOptions', 'selectedmonth','periodsOptions','selectedPeriods'));
        $extra['elements']['controls'] = ['name' => 'SpecialNeeds.Devices/controls', 'data' => [], 'options' => [], 'order' => 1];
    }

    // End POCOR-7467

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
