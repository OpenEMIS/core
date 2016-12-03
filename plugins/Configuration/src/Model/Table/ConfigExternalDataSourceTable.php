<?php
namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Firebase\JWT\JWT;

class ConfigExternalDataSourceTable extends ControllerActionTable {
    public $id;
    public $authenticationType;

    public function initialize(array $config) {
        $this->table('config_items');
        parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
        // $this->addBehavior('Configuration.ExternalDataSource');
        $this->toggle('remove', false);

        $externalDataSourceRecord = $this
            ->find()
            ->where([$this->aliasField('type') => 'External Data Source'])
            ->first();
        $id = $externalDataSourceRecord->id;
        $this->id = $id;
        $this->externalDataSourceType = $externalDataSourceRecord->value;
    }

    public function editOnInitialize(Event $event)
    {
        $newKey = openssl_pkey_new([
            "digest_alg" => "sha256",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA
        ]);

        $res = openssl_pkey_new();

        openssl_pkey_export($res, $privKey);

        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];
        $this->request->data[$this->alias()]['private_key'] = $privKey;
        $this->request->data[$this->alias()]['public_key'] = $pubKey;
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
            $url[1] = $this->id;
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

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $value = $entity->value;

        switch($value) {
            case 'OpenEMIS Identity':
                $this->field('url');
                $this->field('token_uri', ['type' => 'hidden']);
                $this->field('record_uri', ['type' => 'hidden']);
                $this->field('client_id');
                $this->field('first_name_mapping', ['type' => 'hidden']);
                $this->field('middle_name_mapping', ['type' => 'hidden']);
                $this->field('third_name_mapping', ['type' => 'hidden']);
                $this->field('last_name_mapping', ['type' => 'hidden']);
                $this->field('date_of_birth_mapping', ['type' => 'hidden']);
                $this->field('external_reference_mapping', ['type' => 'hidden']);
                $this->field('gender_mapping', ['type' => 'hidden']);
                $this->field('identity_type_mapping', ['type' => 'hidden']);
                $this->field('identity_number_mapping', ['type' => 'hidden']);
                $this->field('nationality_mapping', ['type' => 'hidden']);
                
                break;

            case 'Custom':
                $this->field('token_uri');
                $this->field('record_uri');
                $this->field('client_id');
                $this->field('first_name_mapping');
                $this->field('middle_name_mapping');
                $this->field('third_name_mapping');
                $this->field('last_name_mapping');
                $this->field('date_of_birth_mapping');
                $this->field('external_reference_mapping');
                $this->field('gender_mapping');
                $this->field('identity_type_mapping');
                $this->field('identity_number_mapping');
                $this->field('nationality_mapping');
                break;

            default:

                break;
        }

        $this->field('private_key', ['type' => 'hidden']);
        $this->field('public_key', ['type' => 'text', 'attr' => ['readonly' => 'readonly']]);
    }

}
