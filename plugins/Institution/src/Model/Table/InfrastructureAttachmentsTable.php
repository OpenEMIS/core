<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Laminas\Diactoros\UploadedFile;

class InfrastructureAttachmentsTable extends ControllerActionTable
{
	use MessagesTrait;

    public function initialize(array $config): void
    {
        $this->SetTable('institution_infrastructure_attachments');
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('InfrastructureAttachmentTypes', ['className' => 'Institution.InfrastructureAttachmentTypes', 'foreignKey' => 'infrastructure_attachment_type_id']);
            $this->addBehavior('ControllerAction.FileUpload', [
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        if ($this->behaviors()->has('ControllerAction')) {
            $this->behaviors()->get('ControllerAction')->setConfig([
                'actions' => [
                    'download' => ['show' => true] // to show download on toolbar
                ]
            ]);
        }
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['InfrastructureAttachments' =>['id', 'institution_id']
            ]
        ]);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureAttachments';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('description', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('infrastructure_attachment_type_id', ['attr'=>['label' => __('Type')]]);
        $this->field('file_type');
        $this->field('created_user_id',['visible' => true]);
        $this->field('created', [
            'type' => 'datetime',
            'visible' => true
        ]);

        $this->setFieldOrder([
            'name',
            'infrastructure_attachment_type_id',
            'description',
            'file_type',
            'created_user_id',
            'created'
        ]);


		$is_manual_exist = $this->getManualUrl('Institutions','Infrastructure Attachments', 'Details');

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

	
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) {
            case 'infrastructure_attachment_type_id':
                return __('Type');
            case 'file_content':
                return __('File Content');
            case 'date_on_file':
                return __('Date On File');
            case 'file_type':
                return __('File Type');
            case 'name':
                return __('Name');
            case 'description':
                return __('Description');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'modified_user_id':
                return __('Modified By');
            case 'modified':
                return __('Modified');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
	
    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('infrastructure_attachment_type_id', ['attr' => ['label' => __('Type')]]);
        $this->setFieldOrder([
            'name', 'infrastructure_attachment_type_id','description',  'date_on_file','file_content'
        ]);
    }
    
    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $institutionId = $this->getInstitutionID();
        $InsAttachmentTypeTable = TableRegistry::getTableLocator()->get('Institution.InfrastructureAttachmentTypes');
        $InsAttachmentTypeOptions = $InsAttachmentTypeTable->find('list',['keyField'=>'id','valueField'=>'name'])->toArray();
        $this->fields['infrastructure_attachment_type_id']['type'] = 'select';
        $this->fields['infrastructure_attachment_type_id']['default'] = '1';
        $this->fields['infrastructure_attachment_type_id']['options'] = $InsAttachmentTypeOptions;
        $this->fields['infrastructure_attachment_type_id']['required'] = true;
        $this->field('file_name', ['visible' => false]);
        $this->field('infrastructure_attachment_type_id', [ 'attr' => ['label' => __('Type')]]);
        $this->field('institution_id', ['type' => 'hidden', 'value' => $institutionId]);

        $this->setFieldOrder([
            'name', 'infrastructure_attachment_type_id','description','file_content',  'date_on_file'
        ]);
    }
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('infrastructure_attachment_type_id')
            ->notEmptyString('infrastructure_attachment_type_id', 'Attachment type is required.');

        $validator
            ->requirePresence('file_content')
            ->notEmptyFile('file_content', 'File content is required.');

        return $validator;
    }
    

    public function onGetFileType(EventInterface $event, Entity $entity)
    {
        return $this->getFileTypeForView($entity->file_name);
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $institutionId = $this->getQueryString('institution_id');
        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
        $downloadUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => $this->getAlias(),
            '0' => 'download',
            $this->paramsEncode(['id' => $entity->id]),
        ];
        $buttons['download'] = [
            'label' => '<i class="fa kd-download"></i>'.__('Download'),
            'attr' => $indexAttr,
            'url' => $downloadUrl
        ];

        return $buttons;
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $sentData = $this->request->getData();
        $alias = $this->getAlias();
        $sentData = $sentData[$alias];

        $fileContent = 'file_content';
        $uploadedFile = $sentData[$fileContent];
        $fileName = 'file_name';
        if ($uploadedFile instanceof UploadedFile) {
            //$content = (string)$uploadedFile->getStream();
            if ($error === UPLOAD_ERR_OK) {
                // Accessing the file contents
                $stream = $uploadedFile->getStream();
                if ($stream) {
                    $fileContent = stream_get_contents($stream);
                    $content = $fileContent;
                    // Now you can work with $fileContent
                } else {
                    // Handle the case where the stream couldn't be retrieved
                    $error = $uploadedFile->getError();
                }
            } elseif ($error === UPLOAD_ERR_NO_FILE) {
                // Handle the case where no file was uploaded
                $error = $uploadedFile->getError();
            } else {
                // Handle other upload errors if needed
                $error = $uploadedFile->getError();
            } 
            $name = $uploadedFile->getClientFilename();
            //$error = $uploadedFile->getError();
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

}
