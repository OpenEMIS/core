<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Network\Session;
use Cake\ORM\Entity;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use ArrayObject;

class ConfigProductListsTable extends ControllerActionTable {

	public function initialize(array $config) {
		$this->table('config_product_lists');
		parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
        $this->addBehavior('ControllerAction.FileUpload', [
                'name' => 'file_name',
                'content' => 'file_content',
                'size' => '2MB',
                'contentEditable' => true,
                'allowable_file_types' => 'image'
            ]);
	}

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('name', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ])
            ->add('url', 'invalidUrl', [
                'rule' => ['url', true]
            ])
            ->allowEmpty('url')
            ->allowEmpty('file_content')
            ;
        return $validator;
    }

    public function getDefaultImgView() {
        return "<div class='profile-image'><i class='fa fa-image'></i></div>";
    }

    public function getDefaultImgViewClass() {
        return "profile-image";
    }

    public function getDefaultImgMsg() {
        $formatSupported = __('Format Supported') . ': ';
        $photoMessage = __('Advisable photo dimension 35px by 35px');
        $defaultImgMsg = '<p>* %s <br>* %s.jpg, .jpeg, .png, .gif </p>';
        return sprintf($defaultImgMsg, __($photoMessage), $formatSupported);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('url', ['type' => 'string']);
        $this->field('file_name', ['visible' => false]);
        $this->fields['deletable']['visible'] = false;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['file_content']['visible'] = false;
        $this->fields['auto_login_url']['visible'] = false;
        $this->fields['auto_logout_url']['visible'] = false;
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('auto_login_url', ['type' => 'string']);
        $this->field('auto_logout_url', ['type' => 'string']);
        $this->field('deletable', ['type' => 'hidden', 'value' => 1]);
        $this->field('file_content', ['type' => 'image', 'defaultWidth' => 95]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->deletable) {
            $this->field('file_content', ['type' => 'image']);
        } else {
            $this->field('file_content', ['visible' => false]);
        }

        $toolbarButtons = $extra['toolbarButtons'];

        if (!$entity->deletable) {
            if (isset($toolbarButtons['remove'])) {
                unset($toolbarButtons['remove']);
            }
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (!$entity->deletable) {
            if (isset($buttons['remove'])) {
                unset($buttons['remove']);
            }
        }
        return $buttons;
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->deletable) {
            $this->field('file_content', ['type' => 'image']);
            $this->field('auto_login_url', ['type' => 'string']);
            $this->field('auto_logout_url', ['type' => 'string']);
        } else {
            $this->field('file_content', ['visible' => false]);
            $this->field('auto_login_url', ['type' => 'hidden']);
            $this->field('auto_logout_url', ['type' => 'hidden']);
        }
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!$entity->deletable) {
            $entity->auto_login_url = rtrim($entity->url, '/').'/Login';
            $entity->auto_logout_url = $entity->url;
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $session = new Session();
        $session->delete('ConfigProductLists.list');
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!empty($entity->file_name)) {
            $imagePath = WWW_ROOT . 'img' . DS . 'product_list_logo' . DS . $entity->file_name;
            $image = new File($imagePath);
            $image->delete();
        }

        $session = new Session();
        $session->delete('ConfigProductLists.list');
    }
}
