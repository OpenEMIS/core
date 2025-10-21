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
        $this->toggle('add', false);
// POCOR-8951 start
        $this->belongsTo('ConfigItems', ['className' => 'Configuration.ConfigItems']);
        $this->addBehavior('Configuration.ConfigItems'); //POCOR-8951
// POCOR-8951 end
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('content', ['visible' => false]);
        $this->field('default_content', ['visible' => false]);

        //POCOR-8741 start(remove add button)
        if(isset($extra['toolbarButtons']['add'])){
            unset($extra['toolbarButtons']['add']);
        }
        //POCOR-8741 end
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //$selectedFeature = $extra['selectedFeature'];

        $data = $this->request->getQuery();
        $selectedFeature = $data['online_service'] ?? 'openemis_core';
        $query->contain('ConfigItems')
        ->where(['ConfigItems.code' => $selectedFeature]);

    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('content', ['visible' => false]);
        $this->field('default_content', ['visible' => false]);
    }


    /**
     * POCOR-8951 refactured
     * This function handles the action to add or edit after performing some operations.
     * It modifies the 'color_themes' field by setting its type to 'element'
     * and associates it with a custom element named 'themecolor'.
     *
     * @param Event $event The event that triggered the action
     * @param Entity $entity The entity being processed
     */
    public function addEditAfterAction(Event $event, Entity $entity)
    {
        $configName = $entity->name;

        // Common field configurations
        $this->configureField('config_item_id', ['type' => 'select', 'entity' => $entity]);
        $this->configureField('name', ['type' => 'readonly']);
        $this->configureField('default_value', ['type' => 'readonly', 'entity' => $entity]);
        $this->configureField('content', ['visible' => false]);
        $this->configureField('default_content', ['visible' => false]);

        // Configuration based on entity name
        switch ($configName) {
            case 'Color':
            case 'Colour':
                $this->configureField('color_themes', ['type' => 'element', 'element' => 'themecolor']);
                $this->configureField('value', ['visible' => 'false']); // POCOR-8268
                break;
            case 'Login Page Image':
            case 'Logo':

            $this->configureField('default_content', ['type' => 'readonly']);
            $this->configureField('content', ['visible' => true]);
            $this->configureField('value', ['visible' => false]);
            $this->configureField('default_value', ['visible' => 'false']);
            break;
        }
    }

    /**
     * POCOR-8951 refactured
     */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $entityName = $entity->name;

        // Handle file content for specific entity names
        if (in_array($entityName, ['Login Page Image', 'Logo'])) {
            $filePath = $entity->content->getStream()->getMetadata('uri');

            if (file_exists($filePath)) {
                $fileContent = file_get_contents($filePath);
                $fileName = $entity->content->getClientFilename();

                $entity->content = $fileContent;
                $entity->value = $fileName;
            } else {
                echo "File does not exist or could not be accessed.";
            }
        }

        // Handle color value formatting
        if (in_array($entityName, ['Color', 'Colour'])) { // POCOR-8268
            $entity->value = ltrim($entity->value, '#');
        }
    }

    /**
     * POCOR-8951
     * Helper method to configure fields
     */
    private function configureField(string $fieldName, array $options)
    {
        $this->field($fieldName, $options);
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

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
         return $validator
            ->requirePresence('value');

    }
    //POCOR-8716 START
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        switch ($data['name']) { // POCOR-8951
            case 'Login Page Image': // POCOR-8951
            case 'Logo': // POCOR-8951
                $this->behaviors()->get('FileUpload')->setConfig([
                    'allowable_file_types' => [
                        'value' => ['jpeg', 'jpg', 'gif', 'png'],
                        'default_value' => ['jpeg', 'jpg', 'gif', 'png']
                    ]
                ]);
                $file = $data['content'];

                // Get the filename from the uploaded file
                $data['value'] = $file->getClientFilename();
                break;
        }
        if ($data->offsetExists('default_content')) {
            $data->offsetUnset('default_content');
        }
        if ($data->offsetExists('default_value')) {
            $data->offsetUnset('default_value');
        }
    }

    /**
     * POCOR-8951
     */
    public function onUpdateFieldConfigItemId(Event $event, array $attr, $action)
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $configOptions = $ConfigItems->find('list')
            ->toArray();
        $entity = $attr['entity'];
        $configItemId = $entity->config_item_id;
        $attr['type'] = 'disabled';
        $attr['attr']['label'] = __('Theme For Product');
        $attr['attr']['value'] = $configOptions[$configItemId];
        return $attr;
    }
}
