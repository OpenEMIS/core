<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\Validation\Validator;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;
use Laminas\Diactoros\UploadedFile;
class InsurancesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('user_insurances');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('InsuranceProviders', ['className' => 'Health.InsuranceProviders', 'foreignKey' => 'insurance_provider_id']);
        $this->belongsTo('InsuranceTypes', ['className' => 'Health.InsuranceTypes', 'foreignKey' => 'insurance_type_id']);

        $this->addBehavior('Health.Health');
        $this->addBehavior('User.UserTab', [
            'appliedAction' => ['HealthInsurances' =>
                ['insurance_provider_id', 'insurance_type_id']
            ]
        ]);
       // $this->toggle('search', false);

        // $this->addBehavior('Excel',[
        //     'excludes' => ['comment, security_user_id'],
        //     'pages' => ['index'],
        // ]);
        // $this->addBehavior('ControllerAction.FileUpload', [
        //     'name' => 'file_name',
        //     'content' => 'file_content',
        //     'size' => '10MB',
        //     'contentEditable' => true,
        //     'allowable_file_types' => 'all',
        //     'useDefaultName' => true
        // ]);
        //POCOR-6255 start
        /* $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
        ]); *///POCOR-6255 end
        $this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
    }
    //POCOR-6255 start
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }


    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $sentData = $this->request->getData();
        $alias = $this->getAlias();
        $sentData = $sentData[$alias];
        $name = '';
        $fileContent = 'file_content';
        $uploadedFile = $sentData[$fileContent];
        $fileName = 'file_name';
    
        if ($uploadedFile instanceof UploadedFile) {
            //$content = (string)$uploadedFile->getStream();
            $error = $uploadedFile->getError();
            if ($error === UPLOAD_ERR_OK) {
                // Accessing the file contents
                $content = (string)$uploadedFile->getStream();
            }
            $name = $uploadedFile->getClientFilename();
        }

        if (isset($content) && isset($error) && $error == UPLOAD_ERR_OK) {
            $data[$fileName] = $name;
            $data[$fileContent] = $content;
        } elseif (isset($error) && $error == UPLOAD_ERR_NO_FILE) {
            $data->offsetUnset($fileContent);
            if ($data->offsetExists($fileName)) {
                $data->offsetUnset($fileName);
            }
        } elseif (isset($data[$fileContent . '_remove']) && $data[$fileContent . '_remove'] == 1) {
            $data[$fileName] = null;
            $data[$fileContent] = null;
        } elseif (!isset($data[$fileName])) {
            $var = null;
            $data[$fileName] = null;
            $data[$fileContent] = null;
        }
    }

    public function isAuthorized(Event $event, $scope, $action, $extra)
    {
        if ($action == 'download' || $action == 'image') {
            // check for the user permission to download here
            $event->stopPropagation();
            return true;
        }
    }//POCOR-6255 end

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->add('start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_date', true]
            ])
        ;
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
        $is_manual_exist = $this->getManualUrl('Institutions','Student Insurance','Students - Health');       
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

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $userID = $this->getUserID();
        $query->where([$this->aliasField('security_user_id') => $userID]);
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
        $userID = $this->getUserID();
        $query->where([$this->aliasField('security_user_id') => $userID])
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
        $userID = $this->getUserID();
        $this->field('security_user_id', ['after' => 'file_content', 'attr' => ['value' => $userID], 'type' => 'hidden']);
    }


}


