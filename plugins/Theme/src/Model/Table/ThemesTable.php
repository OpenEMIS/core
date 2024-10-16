<?php
namespace Theme\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\I18n\Time;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;
use Laminas\Diactoros\UploadedFile;
use Cake\Filesystem\File;

class ThemesTable extends ControllerActionTable
{
    const APPNAME = 1;
    const LOGINBGIMAGE = 2;
    const LOGO = 3;
    const FAVICON = 4;
    const COLOUR = 5;
    const COPYRIGHTNOTICE = 6;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('content', ['visible' => false]);
        $this->field('default_content', ['visible' => false]);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('content', ['visible' => false]);  
        $this->field('default_content', ['visible' => false]);

        // $this->setFieldOrder(['name','db_type_id','host','host_port','db_name','username','conn_status_id','status_checked','modified_user_id','modified','created_user_id','created']);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $encodedString = explode(".", $this->request->getAttribute('params')['pass'][1]);
        $encodedStringFirstValue = $encodedString[0];
        $id = base64_decode($encodedStringFirstValue);
        $jsondecodeValue = json_decode($id);
        $dbId = $jsondecodeValue->id;
        if($dbId == 2){
            $this->field('default_content', [
                'type' => 'readonly',
            ]);
            $this->field('name', [
                'type' => 'readonly'
            ]);
            $this->field('value', [
                'visible' => 'false'
            ]);
            $this->field('default_value', [
                'visible' => 'false'
            ]);
        }else if($dbId == 3){
            $this->field('default_content', [
                'type' => 'readonly',
            ]);
            $this->field('name', [
                'type' => 'readonly'
            ]);
            $this->field('value', [
                'visible' => 'false'
            ]);
            $this->field('default_value', [
                'visible' => 'false'
            ]);
        }else if($dbId == 5){
            $this->field('name', [
                'type' => 'readonly'
            ]);
            $this->field('default_value', [
                'type' => 'readonly',
            ]);
            $this->field('default_content', [
                'visible' => 'false'
            ]);
            $this->field('content', [
                'visible' => 'false'
            ]);
            $this->field('value', [ //POCOR-8268
                'type' => 'select'
            ]);
        }else{
            $this->field('name', [
                'type' => 'readonly'
            ]);
            $this->field('default_value', [
                'type' => 'readonly'
            ]);
            $this->field('content', [
                'visible' => 'false'
            ]);
            $this->field('default_content', [
                'visible' => 'false'
            ]);
        }
    }
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if($entity->id == 2){
            $filePath = $entity->content->getStream()->getMetadata('uri');

            if (file_exists($filePath)) {
                // Read the file contents
                $fileContent = file_get_contents($filePath);
                $fileName = $entity->content->getClientFilename();
                
                // Now $fileContent contains the content of the uploaded file
                $entity->content =  $fileContent;
                $entity->value = $fileName;
            } else {
                echo "File does not exist or could not be accessed.";
            }
        }
        if($entity->id == 3){
            $filePath = $entity->content->getStream()->getMetadata('uri');

            if (file_exists($filePath)) {
                // Read the file contents
                $fileContent = file_get_contents($filePath);
                $fileName = $entity->content->getClientFilename();
                
                // Now $fileContent contains the content of the uploaded file
                $entity->content =  $fileContent;
                $entity->value = $fileName;
            } else {
                echo "File does not exist or could not be accessed.";
            }
        }
        if($entity->id == 5){ //POCOR-8268
            $colorValue = $this->request->getData($this->aliasField('value'));
            //$entity->default_value = '';
            $entity->value = $colorValue;
        }
    }


    public function onGetDefaultValue(Event $event, Entity $entity)
    {
        if($entity->name == 'Colour'){
            $entity->default_value = '<div style="float: left; width: 150px; height: 20px; margin: 5px; border: 1px solid rgba(0, 0, 0, .2); background-color: #'.$entity->default_value.';"></div>';
            return $entity->default_value;
        }
    }

    public function onGetValue(Event $event, Entity $entity)
    {
        if($entity->name == 'Colour'){
            $entity->value = '<div style="float: left; width: 150px; height: 20px; margin: 5px; border: 1px solid rgba(0, 0, 0, .2); background-color: #'.$entity->value.';"></div>';
            return $entity->value;
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        Cache::delete('themes');
        $configItems = TableRegistry::get('Configuration.ConfigItems');
        $themeConfigItemRecord = $configItems->findByCode('themes')->first();
        $themeConfigItemRecord->value = Time::now()->toUnixString();
        $configItems->save($themeConfigItemRecord);
    }

    //POCOR-8268 , 
    public function onUpdateFieldValue(Event $event, array $attr, $action, ServerRequest $request) 
    {
        $colorListPath = WWW_ROOT . 'themecolor' . DS . 'color.php';
        if (file_exists($colorListPath)) {
            //POCOR-8652
            $colorListing = include($colorListPath);
            $colorOptions = [];
            foreach ($colorListing as $colorName => $hexValue) {
                $colorOptions[$hexValue] = $hexValue;
            }
        } else {
            throw new \Exception("Color file not found at " . $colorListPath);
        }
        // Modify the attributes based on the action
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['options'] = $colorOptions;
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }

     public function validationDefault(Validator $validator): Validator 
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
         return $validator
            ->requirePresence('value');

    }
}
