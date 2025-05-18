<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Laminas\Diactoros\UploadedFile;

class InstitutionAttachmentsTable extends ControllerActionTable
{
	use MessagesTrait;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('InstitutionAttachmentTypes', ['className' => 'Institution.InstitutionAttachmentTypes', 'foreignKey' => 'institution_attachment_type_id']);//POCOR-5067 // comment in cakephp4
            $this->addBehavior('ControllerAction.FileUpload', [
             //'name' => 'file_name',
             //'content' => 'file_content',
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
            'appliedAction' => ['Attachments' =>['institution_attachment_type_id']
            ]
        ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('description', ['visible' => false]); //POCOR-5067
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('institution_attachment_type_id', ['attr'=>['label' => __('Type')]]); //POCOR-5067
        $this->field('file_type');
        $this->field('created_user_id',['visible' => false]);
        $this->field('created', [
            'type' => 'datetime',
            'visible' => true
        ]);

        $this->setFieldOrder([
            'name',
            'institution_attachment_type_id',  //POCOR-5067
            'description',
            'file_type',
            'date_on_file',
            'created'
        ]);


        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Attachments','General');       
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

	//Start: POCOR-5067
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        switch ($field) {
            case 'institution_attachment_type_id':
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
                return __('Uploaded On');
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
	//End: POCOR-5067
    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('institution_attachment_type_id', ['attr' => ['label' => __('Type')]]); //POCOR-5067
        $this->setFieldOrder([
            'name', 'institution_attachment_type_id','description',  'date_on_file','file_content'
        ]);
    }
    //START:POCOR-5067
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $institutionId = $this->getInstitutionID();
        $InsAttachmentTypeTable = TableRegistry::getTableLocator()->get('Institution.InstitutionAttachmentTypes');
        $InsAttachmentTypeOptions = $InsAttachmentTypeTable->find('list',['keyField'=>'id','valueField'=>'name'])->toArray();
        $this->fields['institution_attachment_type_id']['type'] = 'select';
        $this->fields['institution_attachment_type_id']['default'] = '1';
        $this->fields['institution_attachment_type_id']['options'] = $InsAttachmentTypeOptions;
        $this->fields['institution_attachment_type_id']['required'] = true;
        $this->field('file_name', ['visible' => false]);
        $this->field('institution_attachment_type_id', [ 'attr' => ['label' => __('Type')]]);
        $this->field('institution_id', ['type' => 'hidden', 'value' => $institutionId]);

        $this->setFieldOrder([
            'name', 'institution_attachment_type_id','description','file_content',  'date_on_file'
        ]);
    }
    public function validationDefault(Validator $validator): Validator
    {
        $validator->requirePresence('institution_attachment_type_id', 'create')->notEmpty('institution_attachment_type_id');
        return $validator;
    }
    //END:POCOR-5067

    public function onGetFileType(Event $event, Entity $entity)
    {
        return $this->getFileTypeForView($entity->file_name);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $institutionId = $this->getQueryString('institution_id');
        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
        $downloadUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => $this->getAlias(),
            'institutionId' => $this->paramsEncode(['id' => $entity->institution_id]),
            '0' => 'download',
        ];
        $buttons['download'] = [
            'label' => '<i class="fa kd-download"></i>'.__('Download'),
            'attr' => $indexAttr,
            'url' => $downloadUrl
        ];

        return $buttons;
    }

    public function editBeforeSave(Event $event, $entity, $requestData, $extra)
    {
       //echo "<pre>"; print_r($entity); die;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
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
