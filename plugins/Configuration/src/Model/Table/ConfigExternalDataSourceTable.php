<?php
namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;

class ConfigExternalDataSourceTable extends ControllerActionTable {
    public $id;
    public $authenticationType;

    public function initialize(array $config) {
        $this->table('config_items');
        parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
        $this->addBehavior('Configuration.ExternalDataSource');
        $this->toggle('remove', false);

        $externalDataSourceRecord = $this
            ->find()
            ->where([$this->aliasField('type') => 'External Data Source'])
            ->first();
        $id = $externalDataSourceRecord->id;
        $this->id = $id;
        $this->externalDataSourceType = $externalDataSourceRecord->value;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('visible', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('field_type', ['visible' => false]);
        $this->field('option_type', ['visible' => false]);
        $this->field('code', ['visible' => false]);
        $this->field('name', ['visible' => ['index'=>true]]);
        $this->field('default_value', ['visible' => ['view'=>true]]);
        $this->field('type', ['visible' => ['view'=>true, 'edit'=>true], 'type' => 'readonly']);
        $this->field('label', ['visible' => ['view'=>true, 'edit'=>true], 'type' => 'readonly']);
        $this->field('value', ['visible' => true]);


        if ($this->action == 'index') {
            $url = $this->url('view');
            $url[1] = $this->ControllerAction->paramsEncode(['id' => $this->id]);
            $this->controller->redirect($url);
        } else if ($this->action == 'view') {
            $extra['elements']['controls'] = $this->buildSystemConfigFilters();
            $this->checkController();
        }
    }

    public function onUpdateFieldValue(Event $event, array $attr, $action, Request $request) {
        if (in_array($action, ['edit', 'add'])) {
            $id = $this->id;
            if (!empty($id)) {
                $entity = $this->get($id);
                if ($entity->field_type == 'Dropdown') {
                    $optionTable = TableRegistry::get('Configuration.ConfigItemOptions');
                    $options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
                        ->where([
                            'ConfigItemOptions.option_type' => $entity->option_type,
                            'ConfigItemOptions.visible' => 1
                        ])
                        ->toArray();
                    $attr['options'] = $options;
                    $attr['onChangeReload'] = true;
                }
            }
        }
        return $attr;
    }

}
