<?php

namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use Cake\Core\Configure;
use Cake\Validation\Validator;
use Cake\ORM\Query;

/**
 * POCOR-8386
 * develop functionality for moodle credentials 
 * */
class ExternalDataSourceLMSTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('config_items');
        parent::initialize($config);
        $this->addBehavior('Configuration.ConfigItems');
        $this->toggle('remove', false);
        $this->toggle('add', false);

        $externalDataSourceRecord = $this
            ->find()
            ->where([$this->aliasField('type') => 'External Data Source - LMS'])
            ->first();
        $id = $externalDataSourceRecord->id;
        $this->id = $id;
        $this->externalDataSourceType = $externalDataSourceRecord->value;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
            ->requirePresence('api_token')
            ->requirePresence('base_url')
            ->requirePresence('enable_user_creation')
            ->requirePresence('label');
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('visible', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('field_type', ['visible' => false]);
        $this->field('option_type', ['visible' => false]);
        $this->field('code', ['visible' => false]);
        $this->field('name', ['visible' => ['index' => true]]);
        $this->field('default_value', ['visible' => ['view' => true]]);
        $this->field('type', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
        $this->field('label', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
        $is_manual_exist = $this->getManualUrl('Administration', 'External Data Source - Exams', 'System Configurations');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->where([
            $this->aliasField('type') => 'External Data Source - LMS',
            $this->aliasField('value_selection IS NOT') => 0
        ]);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('value', ['visible' => false]);
        $this->field('status', ['visible' => true]);
        if ($entity->value != 'None') {
            $this->field('attributes', ['type' => 'custom_external_source']);
        }
        $this->field('value_selection', ['type' => 'hidden']);
        $this->field('default_value', ['type' => 'hidden']);
    }

    public function onGetCustomExternalSourceElement(EventInterface $event, $action, Entity $entity, $attr, $options = [])
    {
        $tableHeaders = [__('Attribute Name'), __('Value')];
        $tableCells = [];
        $ExternalDataSourceAttributes = TableRegistry::getTableLocator()->get('Configuration.ExternalDataSourceAttributes');
        $attributes = $ExternalDataSourceAttributes
            ->find('list', [
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->where([
                $ExternalDataSourceAttributes->aliasField('external_data_source_type') => $entity->type
            ])
            ->order('attribute_field')
            ->toArray();
        if ($action == 'view') {
            foreach ($attributes as $key => $obj) {
                //POCOR-8706
                if ($key == "enable_user_creation")
                    $obj = $obj == 1 ? "Yes" : "No";
                $rowData = [];
                $rowData[] = __(Inflector::humanize($key));
                $rowData[] = nl2br($obj);
                $tableCells[] = $rowData;
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->getSubject()->renderElement('Configuration.external_data_exam_source', ['attr' => $attr]);
    }

    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $patchOption, ArrayObject $extra)
    {

        $errors = $entity->getErrors();
        if (!empty($errors) || empty($entity->base_url) || empty($entity->api_token) || !in_array($entity->enable_user_creation, [0, 1]) || !in_array($entity->status, [0, 1])) {
            $message = 'Please enter the required details.';
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $url = $this->request->referer();
            $event->stopPropagation();
            return $this->controller->redirect($url);
        } else {
            $ExternalDataSourceAttributes = TableRegistry::getTableLocator()->get('Configuration.ExternalDataSourceAttributes');

            $record = $this->find()->where([
                'type' => $entity->type,
                'code' => 'external_source_status'
            ])->first();

            if ($record) {
                $this->updateAll(
                    ['value' => $entity->status],
                    ['type' => $entity->type, 'code' => 'external_source_status']
                );
            }


            $ExternalDataSourceAttributes->updateAll(
                ['value' => $entity->api_token],    // field to update
                [
                    'external_data_source_type' => $entity->type,
                    'attribute_field' => 'api_token',
                    'attribute_name' => 'api_token'
                ] // conditions
            );

            $ExternalDataSourceAttributes->updateAll(
                ['value' => $entity->base_url],    // field to update
                [
                    'external_data_source_type' => $entity->type,
                    'attribute_field' => 'base_url',
                    'attribute_name' => 'base_url'
                ] // conditions
            );

            $ExternalDataSourceAttributes->updateAll(
                ['value' => $entity->enable_user_creation],    // field to update
                [
                    'external_data_source_type' => $entity->type,
                    'attribute_field' => 'enable_user_creation',
                    'attribute_name' => 'enable_user_creation'
                ] // conditions
            );
        }
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('name', ['attr' => ['label' => __('Source')]]);
        $this->field('status');
        $this->field('value', ['visible' => false]);
        $this->field('value_selection', ['visible' => false]);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'name') {
            return __('Source');
        } else if ($field == 'label') {
            return  __('Source');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('value', ['visible' => false]);
        $this->field('value_selection', ['visible' => false]);
        $this->field('status');
        $this->field('api_token');
        $this->field('base_url');
        $this->field('enable_user_creation');
    }

    public function onGetStatus(EventInterface $event, Entity $entity)
    {
        $getRecord = $this->find()->where(['type' => $entity->type, 'name' => 'Status'])->first()->value;
        $entity->status = $getRecord == 1 ? 'Enabled' : 'Disabled';
        return $entity->status;
    }

    public function onUpdateFieldApiToken(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            $getQueryString = $this->getQueryString();
            $id = $getQueryString['id'];
            $type = $this->find()->where(['id' => $id])->first()->type;

            $ExternalDataSourceAttributes = TableRegistry::getTableLocator()->get('Configuration.ExternalDataSourceAttributes');
            $apiAttribute = $ExternalDataSourceAttributes->find()->where(['external_data_source_type' => $type, 'attribute_field' => 'api_token'])->first()->value;

            $attr['attr']['value'] = $apiAttribute;

            return $attr;
        }
    }

    public function onUpdateFieldBaseUrl(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            $getQueryString = $this->getQueryString();
            $id = $getQueryString['id'];
            $type = $this->find()->where(['id' => $id])->first()->type;

            $ExternalDataSourceAttributes = TableRegistry::getTableLocator()->get('Configuration.ExternalDataSourceAttributes');
            $urlAttribute = $ExternalDataSourceAttributes->find()->where(['external_data_source_type' => $type, 'attribute_field' => 'base_url'])->first()->value;

            $attr['attr']['value'] = $urlAttribute;

            return $attr;
        }
    }
    public function onUpdateFieldEnableUserCreation(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            $getQueryString = $this->getQueryString();
            $id = $getQueryString['id'];
            $type = $this->find()->where(['id' => $id])->first()->type;

            $ExternalDataSourceAttributes = TableRegistry::getTableLocator()->get('Configuration.ExternalDataSourceAttributes');
            $userAttribute = $ExternalDataSourceAttributes->find()->where(['external_data_source_type' => $type, 'attribute_field' => 'enable_user_creation'])->first()->value;

            $options = $this->getSelectOptions('general.yesno');
            $attr['options'] = $options;
            $attr['type'] = 'select';
            $attr['attr']['value'] = $userAttribute;

            return $attr;
        }
    }

    public function onUpdateFieldStatus(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
            $getQueryString = $this->getQueryString();
            $id = $getQueryString['id'];
            $gettype = $this->find()->where(['id' => $id])->first()->type;
            $type = $this->find()->where(['type' => $gettype, 'name' => 'Status'])->first()->value;
            $options = $this->getSelectOptions('general.yesno');
            $attr['options'] = $options;
            $attr['type'] = 'select';
            $attr['attr']['value'] = $type;
            return $attr;
        }
    }
}
