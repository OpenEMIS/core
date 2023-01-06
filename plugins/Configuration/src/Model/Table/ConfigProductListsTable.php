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

class ConfigProductListsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
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

    public function getDefaultImgView()
    {
        return "<div class='profile-image'><i class='fa fa-image'></i></div>";
    }

    public function getDefaultImgViewClass()
    {
        return "profile-image";
    }

    public function getDefaultImgMsg()
    {
        $formatSupported = __('Format Supported: %s');
        $formatSupported = sprintf($formatSupported, '.jpg, .jpeg, .png, .gif');
        $width = 35;
        $height = 35;
        $photoMessage = __('Advisable photo dimension %width by %height');
        $photoMessage = str_replace('%width', $width, $photoMessage);
        $photoMessage = str_replace('%height', $height, $photoMessage);
        $defaultImgMsg = '<p>* %s <br>* %s</p>';
        return sprintf($defaultImgMsg, __($photoMessage), $formatSupported);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('url', ['type' => 'string']);
        $this->field('file_name', ['visible' => false]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['file_content']['visible'] = false;
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_content', ['type' => 'image', 'defaultWidth' => 95]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_content', ['type' => 'image']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_content', ['type' => 'image']);
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
