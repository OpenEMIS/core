<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class InstitutionAttachmentsTable extends ControllerActionTable
{
	use MessagesTrait;

    public function initialize(array $config)
    {

        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->addBehavior('ControllerAction.FileUpload', ['size' => '2MB', 'contentEditable' => false, 'allowable_file_types' => 'all', 'useDefaultName' => true]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
    	$this->field('institution_id', ['type' => 'hidden', 'visible' => ['edit' => true]]);
    	$this->field('modified', ['visible' => ['view' => true]]);
    	$this->field('modified_user_id', ['visible' => ['view' => true]]);
    	$this->field('created', ['type' => 'datetime', 'visible' => ['index'=>true, 'view'=>true]]);
    	$this->field('created_user_id', ['visible' => ['view' => true]]);
    	$this->field('file_name', ['visible' => false]);
    	$this->field('file_content', ['type' => 'binary', 'visible' => ['edit' => true]]);
    	$this->field('date_on_file', ['type' => 'date', 'visible' => true]);
    	$this->field('name', ['type' => 'string', 'visible' => true]);
    	$this->field('description', ['type' => 'text', 'visible' => true]);
    	$this->field('file_type', ['type' => 'string', 'visible' => ['index'=>true]]);
    }

/******************************************************************************************************************
**
** index action logics
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['name', 'description', 'file_type', 'date_on_file', 'created']);
    }


/******************************************************************************************************************
**
** view action logics
**
******************************************************************************************************************/
    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {

        $this->fields['created_user_id']['options'] = [$entity->created_user_id => $entity->created_user->name];
        if (!empty($entity->modified_user_id)) {
            $this->fields['modified_user_id']['options'] = [$entity->modified_user_id => $entity->modified_user->name];
        }

        $toolbarButtons = $extra['toolbarButtons'];

        $toolbarButtons['download']['type'] = 'button';
        $toolbarButtons['download']['label'] = '<i class="fa kd-download"></i>';
        $toolbarButtons['download']['attr'] = $attr;
        $toolbarButtons['download']['attr']['title'] = __('Download');
        $url = $this->ControllerAction->url('download');
        if (!empty($url['action'])) {
            $toolbarButtons['download']['url'] = $url;
        }
        $extra['toolbarButtons'] = $toolbarButtons;

        return $entity;
    }


/******************************************************************************************************************
**
** edit action logics
**
******************************************************************************************************************/
    public function editBeforeAction(Event $event)
    {
        $this->fields['date_on_file']['visible'] = true;
    }


/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
    public function onGetFileType(Event $event, Entity $entity)
    {
        return $this->getFileTypeForView($entity->file_name);
    }


/******************************************************************************************************************
**
** adding download button to index page
**
******************************************************************************************************************/
    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];

        $buttons['download']['label'] = '<i class="kd-download"></i>' . __('Download');
        $buttons['download']['attr'] = $indexAttr;
        $buttons['download']['url']['plugin'] = 'Institution';
        $buttons['download']['url']['controller'] = 'Institutions';
        $buttons['download']['url']['institutionId'] = $this->request->param('institutionId');
        $buttons['download']['url']['action'] = $this->alias;
        $buttons['download']['url'][0] = 'download';
        $buttons['download']['url'][1] = $this->paramsEncode(['id' => $entity->id]);

        return $buttons;
    }
}
