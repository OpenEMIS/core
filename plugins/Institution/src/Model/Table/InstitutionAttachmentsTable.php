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

class InstitutionAttachmentsTable extends ControllerActionTable
{
	use MessagesTrait;

    public function initialize(array $config)
    {

        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('InstitutionAttachmentTypes', ['className' => 'InstitutionAttachmentTypes', 'foreignKey' => 'institution_attachment_type_id']);//POCOR-5067
        $this->addBehavior('ControllerAction.FileUpload', [
            'size' => '2MB',
            'contentEditable' => false,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        if ($this->behaviors()->has('ControllerAction')) {
            $this->behaviors()->get('ControllerAction')->config([
                'actions' => [
                    'download' => ['show' => true] // to show download on toolbar
                ]
            ]);
        }
    }
    //START:POCOR-5067
    // public function beforeAction(Event $event, ArrayObject $extra)
    // { 
    //     $this->field('description', ['visible' => false]);
    // }
    // //END:POCOR-5067

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
        $InsAttachmentTypeTable = TableRegistry::get('institution_attachment_types');
        $InsAttachmentTypeOptions = $InsAttachmentTypeTable->find('list',['keyField'=>'id','valueField'=>'name'])->toArray();
        $this->fields['institution_attachment_type_id']['type'] = 'select';
        $this->fields['institution_attachment_type_id']['default'] = '1';
        $this->fields['institution_attachment_type_id']['options'] = $InsAttachmentTypeOptions;
        $this->fields['institution_attachment_type_id']['required'] = true;
       
        $this->field('institution_attachment_type_id', [ 'attr' => ['label' => __('Type')]]);
        $this->field('file_name', ['visible' => false]);
        $this->setFieldOrder([
            'name', 'institution_attachment_type_id','description','file_content',  'date_on_file'
        ]);
    }
    public function validationDefault(Validator $validator)
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

        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
        $downloadUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => $this->alias,
            'institutionId' => $this->paramsEncode(['id' => $entity->institution_id]),
            '0' => 'download',
            '1' => $this->paramsEncode(['id' => $entity->id])
        ];
        $buttons['download'] = [
            'label' => '<i class="fa kd-download"></i>'.__('Download'),
            'attr' => $indexAttr,
            'url' => $downloadUrl
        ];

        return $buttons;
    }
}
