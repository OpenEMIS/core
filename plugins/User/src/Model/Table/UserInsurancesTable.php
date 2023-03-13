<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\Validation\Validator;

use Cake\Event\Event;
use Cake\ORM\Query;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;
class UserInsurancesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('user_insurances');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('InsuranceProviders', ['className' => 'Health.InsuranceProviders', 'foreignKey' => 'insurance_provider_id']);
        $this->belongsTo('InsuranceTypes', ['className' => 'Health.InsuranceTypes', 'foreignKey' => 'insurance_type_id']);

        $this->addBehavior('Health.Health');

        $this->toggle('search', false);

        $this->addBehavior('Excel',[
            'excludes' => ['comment, security_user_id'],
            'pages' => ['index'],
        ]);
        //POCOR-6255 start
        /* $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
        ]); *///POCOR-6255 end
    }
    //POCOR-6255 start
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(Event $event, $scope, $action, $extra)
    {
        if ($action == 'download' || $action == 'image') {
            // check for the user permission to download here
            $event->stopPropagation();
            return true;
        }
    }//POCOR-6255 end

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_date', true]
            ])
        ;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        //echo $this->alias(); exit;
        $modelAlias = 'UserInsurances';
        $userType = '';
        $this->controller->changeStudentHealthHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('start_date', ['attr' => ['label' => __('Start date')]]);
        $this->field('end_date', ['attr' => ['label' => __('End date')]]);
        $this->field('insurance_provider_id', ['attr' => ['label' => __('Provider')]]);
        $this->field('insurance_type_id', ['attr' => ['label' => __('Type')]]);
        $this->field('comment',['visible' => false]);
        /*POCOR-6307 Starts*/
        $this->field('file_name',['visible' => false]);
        $this->field('file_content',['visible' => false]);
        /*POCOR-6307 Ends*/

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions','Student Insurance');       
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

    /* POCOR-6131 */
    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }
    /* POCOR-6131 */

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) {
            case 'start_date':
                return __('Start Date');
            case 'end_date':
                return __('End Date');
            case 'insurance_provider_id':
                return __('Provider');
                case 'insurance_type_id':
                    return __('Type');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $extra, Query $query){
        $session = $this->request->session();
        if($this->request->param('action') == 'StudentInsurances'){
            $staffUserId = $session->read('Institution.StudentUser.primaryKey.id');
        } else if($this->request->param('action') == 'StaffInsurances'){
            $staffUserId = $session->read('Institution.StaffUser.primaryKey.id');
        }
        $query->where([$this->aliasField('security_user_id') => $staffUserId])
        ->orderDesc($this->aliasField('created'));
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key'   => 'start_date',
            'field' => 'start_date',
            'type'  => 'date',
            'label' => __('Start Date')
        ];

        $extraField[] = [
            'key'   => 'end_date',
            'field' => 'end_date',
            'type'  => 'date',
            'label' => __('End Date')
        ];

        $extraField[] = [
            'key'   => 'insurance_provider_id',
            'field' => 'insurance_provider_id',
            'type'  => 'string',
            'label' => __('Provider')
        ];

        $extraField[] = [
            'key'   => 'insurance_type_id',
            'field' => 'insurance_type_id',
            'type'  => 'string',
            'label' => __('Type')
        ];

        $fields->exchangeArray($extraField);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('start_date',['attr' => ['label' => __('Start Date')]]);
        $this->field('end_date',['attr' => ['label' => __('End Date')]]);

        $this->fields['insurance_provider_id']['type'] = 'select';
        $this->field('insurance_provider_id', ['attr' => ['label' => __('Provider')]]);

        $this->fields['insurance_type_id']['type'] = 'select';
        $this->field('insurance_type_id', ['attr' => ['label' => __('Type')]]);
        // POCOR-6131
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }
}
