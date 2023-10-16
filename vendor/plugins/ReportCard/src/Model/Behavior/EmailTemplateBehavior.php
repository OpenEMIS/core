<?php
namespace ReportCard\Model\Behavior;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\Validation\Validator;
use Cake\ORM\Behavior;

class EmailTemplateBehavior extends Behavior
{
    protected $_defaultConfig = [
        'placeholder' => []
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $model = $this->_table;
        if (!$model->hasBehavior('Section')) {
            $model->addBehavior('OpenEmis.Section');
        }

        $model->toggle('remove', false);
    }

    public function buildValidator(Event $event, Validator $validator, $name)
    {
        $validator
            ->requirePresence('subject')
            ->requirePresence('message');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.viewEdit.beforeQuery'] = 'viewEditBeforeQuery';
        $events['ControllerAction.Model.view.afterAction'] = ['callable' => 'viewAfterAction', 'priority' => 100];
        $events['ControllerAction.Model.edit.onInitialize'] = 'editOnInitialize';
        $events['ControllerAction.Model.edit.afterAction'] = ['callable' => 'editAfterAction', 'priority' => 100];
        $events['ControllerAction.Model.edit.beforeSave'] = 'editBeforeSave';

        return $events;
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $model = $this->_table;
        $EmailTemplatesTable = TableRegistry::get('Email.EmailTemplates');

        // append email_template to entity
        $query->formatResults(function (ResultSetInterface $results) use ($model, $EmailTemplatesTable) {
            return $results->map(function ($row) use ($model, $EmailTemplatesTable) {
                // email template
                $emailTemplateEntity = $EmailTemplatesTable
                    ->find()
                    ->where([
                        'model_alias' => $model->registryAlias(),
                        'model_reference' => $row->{$model->primaryKey()}
                    ])
                    ->first();
                $row->email_template = $emailTemplateEntity;
                // end

                // default email template
                $defaultEmailTemplateEntity = $EmailTemplatesTable
                    ->find()
                    ->where([
                        'model_alias' => $model->registryAlias(),
                        'model_reference' => 0
                    ])
                    ->first();
                $row->default_email_template = $defaultEmailTemplateEntity;
                // end

                return $row;
            });
        });
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity, $extra);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->has('email_template')) {
            if ($entity->email_template->has('subject')) {
                $entity->subject = $entity->email_template->subject;
            }

            if ($entity->email_template->has('message')) {
                $entity->message = $entity->email_template->message;
            }
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        if (array_key_exists('list', $toolbarButtonsArray)) {
            unset($toolbarButtonsArray['list']);
        }
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        $this->setupFields($event, $entity, $extra);
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $process = function ($model, $entity) use ($data) {
            $errors = $entity->errors();

            if (empty($errors)) {
                $EmailTemplatesTable = TableRegistry::get('Email.EmailTemplates');
                $requestData = $data[$model->alias()];

                $emailTemplateData = [
                    'model_alias' => $model->registryAlias(),
                    'model_reference' => $entity->{$model->primaryKey()},
                    'subject' => $requestData['subject'],
                    'message' => $requestData['message']
                ];
                $emailTemplateEntity = $EmailTemplatesTable->newEntity();
                $emailTemplateEntity = $EmailTemplatesTable->patchEntity($emailTemplateEntity, $emailTemplateData);

                return $EmailTemplatesTable->save($emailTemplateEntity);
            } else {
                return false;
            }
        };

        return $process;

    }

    public function onGetSubject(Event $event, Entity $entity)
    {
        if($entity->has('email_template')) {
            return $entity->email_template->subject;
        }
    }

    public function onGetMessage(Event $event, Entity $entity)
    {
        if($entity->has('email_template')) {
            return $entity->email_template->message;
        }
    }

    public function onGetDefaultSubject(Event $event, Entity $entity)
    {
        if($entity->has('default_email_template')) {
            return $entity->default_email_template->subject;
        }
    }

    public function onGetDefaultMessage(Event $event, Entity $entity)
    {
        if($entity->has('default_email_template')) {
            return $entity->default_email_template->message;
        }
    }

    public function onGetCustomEmailTemplatePlaceholdersElement(Event $event, $action, $entity, $attr, $options=[])
    {
        if ($action == 'edit') {
            $tableHeaders =[__('Keywords'), __('Remarks')];
            $tableCells = [];
            $fieldKey = 'keyword_remarks';

            $placeholder = $this->config('placeholder');

            if (!empty($placeholder)) {
                foreach ($placeholder as $placeholderKey => $placeholderObj) {
                    $rowData = [];
                    $rowData[] = __($placeholderKey);
                    $rowData[] = __($placeholderObj);

                    $tableCells[] = $rowData;
                }
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
            return $event->subject()->renderElement($fieldKey, ['attr' => $attr]);
        }
    }

    public function getPlaceholders()
    {
        return $this->config('placeholder');
    }

    private function setupFields(Event $event, Entity $entity, ArrayObject $extra)
    {
        $model = $this->_table;
        $model->field('email_content', ['type' => 'section']);
        $model->field('subject');
        $model->field('message', ['type' => 'text']);
        $model->field('default_subject', [
            'visible' => [
                'view' => true,
                'edit' => false
            ]
        ]);
        $model->field('default_message', [
            'type' => 'text',
            'visible' => [
                'view' => true,
                'edit' => false
            ]
        ]);
        $model->field('keyword_remarks', [
            'type' => 'custom_email_template_placeholders',
            'visible' => [
                'view' => false,
                'edit' => true
            ]
        ]);

        $this->reorderFields();
    }

    private function reorderFields()
    {
        $order = 0;
        $fieldsOrder = [];
        $fields = $this->_table->fields;
        uasort($fields, function ($a, $b) {
            return $a['order']-$b['order'];
        });

        $newFields = [
            'email_content',
            'subject',
            'message',
            'default_subject',
            'default_message',
            'keyword_remarks',
            'modified_user_id',
            'modified',
            'created_user_id',
            'created'
        ];
        foreach ($fields as $fieldName => $fieldAttr) {
            if (!in_array($fieldName, $newFields)) {
                $order = $fieldAttr['order'] > $order ? $fieldAttr['order'] : $order;
                if (array_key_exists($order, $fieldsOrder)) {
                    $order++;
                }
                $fieldsOrder[$order] = $fieldName;
            }
        }

        ksort($fieldsOrder);
        foreach ($newFields as $fieldName) {
            $fieldsOrder[++$order] = $fieldName;
        }

        $this->_table->setFieldOrder($fieldsOrder);
    }
}
