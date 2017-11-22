<?php
namespace OpenEmis\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Event\Event;

class OpenEmisBehavior extends Behavior
{
    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 4];
        $events['ControllerAction.Model.afterAction'] = ['callable' => 'afterAction', 'priority' => 100];
        $events['ControllerAction.Model.index.afterAction'] = ['callable' => 'indexAfterAction', 'priority' => 4];
        $events['ControllerAction.Model.view.afterAction'] = ['callable' => 'viewAfterAction', 'priority' => 4];
        $events['ControllerAction.Model.add.afterSave'] = ['callable' => 'addAfterSave', 'priority' => 4];
        $events['ControllerAction.Model.edit.afterSave'] = ['callable' => 'editAfterSave', 'priority' => 4];
        $events['ControllerAction.Model.edit.afterAction'] = ['callable' => 'editAfterAction', 'priority' => 4];
        $events['ControllerAction.Model.delete.afterAction'] = ['callable' => 'deleteAfterAction', 'priority' => 4];
        $events['ControllerAction.Model.transfer.afterAction'] = ['callable' => 'transferAfterAction', 'priority' => 4];
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $action = $this->_table->action;
        switch ($action) {
            case 'index':
                $extra['elements']['table'] = ['name' => 'OpenEmis.ControllerAction/index', 'order' => 5];
                $extra['elements']['pagination'] = ['name' => 'OpenEmis.pagination', 'order' => 8];
                break;
            case 'view':
                $extra['elements']['view'] = ['name' => 'OpenEmis.ControllerAction/view', 'order' => 5];
                break;
            case 'edit':
            case 'add':
            case 'remove':
            case 'transfer':
                $extra['config']['form'] = true;
                $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit', 'order' => 5];
                break;
            default:
                break;
        }
        $form = false; // deprecated
        if ($action == 'index') {
            $form = true;
        } // deprecated
        $this->_table->controller->set('form', $form); // deprecated

        $this->initializeButtons($extra);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;
        if ($model->action == 'index' || $model->action == 'view') {
            $modal = [];
            $modal['title'] = $model->getHeader($model->alias()); //$modal['title'] = $model->alias();
            $modal['content'] = __('All associated information related to this record will also be removed.');
            $modal['content'] .= '<br><br>';
            $modal['content'] .= __('Are you sure you want to delete this record?');

            $modal['form'] = [
                'model' => $model,
                'formOptions' => ['type' => 'delete', 'url' => $model->url('remove')],
                'fields' => ['primaryKey' => ['type' => 'hidden', 'id' => 'recordId', 'unlockField' => true]]
            ];

            $modal['buttons'] = [
                '<button type="submit" class="btn btn-default">' . __('Delete') . '</button>'
            ];
            $modal['cancelButton'] = true;

            if (!isset($model->controller->viewVars['modals'])) {
                $model->controller->set('modals', ['delete-modal' => $modal]);
            } else {
                $modals = array_merge($model->controller->viewVars['modals'], ['delete-modal' => $modal]);
                $model->controller->set('modals', $modals);
            }
        }
        // deprecated
        $model->controller->set('action', $this->_table->action);
        $model->controller->set('indexElements', []);
        // end deprecated
        $this->attachEntityInfoToToolBar($extra);

        $access = $model->AccessControl;
        $toolbarButtons = $extra['toolbarButtons'];
        foreach ($toolbarButtons->getArrayCopy() as $key => $buttons) {
            if (array_key_exists('url', $buttons)) {
                if ($buttons['url'] != '#' && !$access->check($buttons['url'])) {
                    $toolbarButtons->offsetUnset($key);
                }
            }
        }

        $indexButtons = $extra['indexButtons'];
        foreach ($indexButtons->getArrayCopy() as $key => $buttons) {
            if ($buttons['url'] != '#' && array_key_exists('url', $buttons)) {
                if (!$access->check($buttons['url'])) {
                    $indexButtons->offsetUnset($key);
                }
            }
        }

        $extra['toolbarButtons'] = $toolbarButtons;
        $extra['indexButtons'] = $indexButtons;

        if ($model->actions('reorder') && $indexButtons->offsetExists('edit')) {
            $model->controller->set('reorder', true);
        }

        if ($extra->offsetExists('indexButtons')) {
            $model->controller->set('indexButtons', $extra['indexButtons']);
        }
        if ($extra['toolbarButtons']->offsetExists('back')) {
            $model->controller->set('backButton', $extra['toolbarButtons']['back']);
        }
    }

    private function attachEntityInfoToToolBar($extra)
    {
        $model = $this->_table;
        // further modification on toolbar buttons attributes where entity information will be available
        if ($extra->offsetExists('toolbarButtons')) {
            $toolbarButtons = $extra['toolbarButtons'];
            $entity = $extra['entity'];
            $isViewPage = $model->action == 'view';

            if ($isViewPage) {
                $isDeleteButtonEnabled = $toolbarButtons->offsetExists('remove');
                $isNotTransferOperation = $model->actions('remove') != 'transfer';
                $isNotRestrictOperation = $model->actions('remove') != 'restrict';
                $primaryKey = $model->primaryKey();

                $ids = [];
                if (is_array($primaryKey)) {
                    foreach ($primaryKey as $key) {
                        $ids[$key] = $entity->getOriginal($key);
                    }
                } else {
                    $ids[$primaryKey] = $entity->getOriginal($primaryKey);
                }

                $encodedIds = $model->paramsEncode($ids);

                if ($isDeleteButtonEnabled && $isNotTransferOperation && $isNotRestrictOperation) {
                    // not checking existence of entity in $extra so that errors will be shown if entity is removed unexpectedly
                    // to attach primary key to the button attributes for delete operation
                    if (array_key_exists('remove', $toolbarButtons)) {
                        $toolbarButtons['remove']['attr']['field-value'] = $encodedIds;
                    }
                }

                $isDownloadButtonEnabled = $toolbarButtons->offsetExists('download');
                if ($isDownloadButtonEnabled) {
                    $model = $this->_table;
                    if ($download = $model->actions('download')) {
                        $determineShow = $download['show'];
                        if (is_callable($download['show'])) {
                            $determineShow = $determineShow();
                        }
                    }
                    if ($determineShow) {
                        $toolbarButtons['download']['url'][] = $encodedIds;
                    } else {
                        $toolbarButtons->offsetUnset('download'); // removes download button
                    }
                }
            }

            $actions = ['index', 'add', 'edit', 'remove'];
            $disabledActions = [];
            foreach ($toolbarButtons as $action => $attr) {
                if (in_array($action, $actions) && !$model->actions($action)) {
                    $disabledActions[] = $action;
                }
            }
            foreach ($disabledActions as $action) {
                $toolbarButtons->offsetUnset($action);
            }
            $model->controller->set('toolbarButtons', $toolbarButtons);
        }
    }

    public function indexAfterAction(Event $event, Query $query, $resultSet, ArrayObject $extra)
    {
        if (count($resultSet) == 0) {
            $this->_table->Alert->info('general.noData');
        }
        $extra['config']['form'] = ['class' => ''];
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if (!$entity) {
            $this->_table->Alert->warning('general.notExists');
        }
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $model = $this->_table;
        $errors = $entity->errors();
        if (empty($errors)) {
            $model->Alert->success('general.add.success');
        } else {
            $model->Alert->error('general.add.failed');
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $model = $this->_table;
        $errors = $entity->errors();
        if (empty($errors)) {
            $model->Alert->success('general.edit.success');
        } else {
            $model->Alert->error('general.edit.failed');
        }
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if (!$entity) {
            $this->_table->Alert->warning('general.notExists');
        }
    }

    public function deleteAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $model = $this->_table;
        if ($model->request->is('delete') || $extra['forceDeleteRecord']) {
            if ($extra['result']) {
                if (isset($extra['Alert']['message'])) {
                    $model->Alert->success($extra['Alert']['message']);
                } else {
                    $model->Alert->success('general.delete.success');
                }
            } else {
                if (isset($extra['Alert']['message'])) {
                    $model->Alert->error($extra['Alert']['message']);
                } else {
                    $model->Alert->error('general.delete.failed');
                }
            }
        }
    }

    public function transferAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $model = $this->_table;
        if ($model->request->is('delete')) {
            if ($extra['result']) {
                $model->Alert->success('general.delete.success');
            } else {
                if (empty($entity->convert_to)) {
                    $model->Alert->error('general.deleteTransfer.restrictDelete');
                    return $model->controller->redirect($model->url('transfer'));
                } else {
                    $model->Alert->error('general.delete.failed');
                }
            }
        }
    }

    private function initializeButtons(ArrayObject $extra)
    {
        $model = $this->_table;
        $controller = $model->controller;

        $toolbarButtons = new ArrayObject([]);
        $indexButtons = new ArrayObject([]);

        $backActions = ['add' => 'index', 'view' => 'index', 'edit' => 'view'];

        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];

        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];

        $action = $model->action;

        $backAction = array_key_exists($action, $backActions) ? $backActions[$action] : $action;

        if ($action != 'index' && $model->actions($action)) {
            $toolbarButtons['back']['type'] = 'button';
            $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
            $toolbarButtons['back']['attr'] = $toolbarAttr;
            $toolbarButtons['back']['attr']['title'] = __('Back');

            if ($action == 'remove' && ($model->actions('remove') == 'transfer' || $model->actions('remove') == 'restrict')) {
                $toolbarButtons['list']['url'] = $model->url('index', 'QUERY');
                $toolbarButtons['list']['type'] = 'button';
                $toolbarButtons['list']['label'] = '<i class="fa kd-lists"></i>';
                $toolbarButtons['list']['attr'] = $toolbarAttr;
                $toolbarButtons['list']['attr']['title'] = __('List');
            }
        }

        if ($action == 'index') {
            if ($model->actions('add')) {
                $toolbarButtons['add']['url'] = $model->url('add');
                $toolbarButtons['add']['type'] = 'button';
                $toolbarButtons['add']['label'] = '<i class="fa kd-add"></i>';
                $toolbarButtons['add']['attr'] = $toolbarAttr;
                $toolbarButtons['add']['attr']['title'] = __('Add');
            }
            if ($model->actions('search')) {
                $toolbarButtons['search'] = [
                    'type' => 'element',
                    'element' => 'OpenEmis.search',
                    'data' => ['url' => $model->url('index')],
                    'options' => []
                ];
            }
        } elseif ($action == 'add' || $action == 'edit') {
            $toolbarButtons['back']['url'] = $model->url($backAction, 'QUERY');
            if ($action == 'edit' && $model->actions('index')) {
                $toolbarButtons['back']['url'] = $model->url($backAction);
                $toolbarButtons['list']['url'] = $model->url('index', 'QUERY');
                $toolbarButtons['list']['type'] = 'button';
                $toolbarButtons['list']['label'] = '<i class="fa kd-lists"></i>';
                $toolbarButtons['list']['attr'] = $toolbarAttr;
                $toolbarButtons['list']['attr']['title'] = __('List');
            }
        } elseif ($action == 'view') {
            // edit button
            $toolbarButtons['back']['url'] = $model->url($backAction, 'QUERY');
            if ($model->actions('edit')) {
                $toolbarButtons['edit']['url'] = $model->url('edit');
                $toolbarButtons['edit']['type'] = 'button';
                $toolbarButtons['edit']['label'] = '<i class="fa kd-edit"></i>';
                $toolbarButtons['edit']['attr'] = $toolbarAttr;
                $toolbarButtons['edit']['attr']['title'] = __('Edit');
            }

            if ($model->actions('remove') && $model->actions('remove') != 'transfer') {
                $toolbarButtons['remove']['url'] = $model->url('remove');
                $toolbarButtons['remove']['type'] = 'button';
                $toolbarButtons['remove']['label'] = '<i class="fa fa-trash"></i>';
                $toolbarButtons['remove']['attr'] = $toolbarAttr;
                $toolbarButtons['remove']['attr']['title'] = __('Delete');
                if ($model->actions('remove') != 'restrict') {
                    $toolbarButtons['remove']['attr']['data-toggle'] = 'modal';
                    $toolbarButtons['remove']['attr']['data-target'] = '#delete-modal';
                    $toolbarButtons['remove']['attr']['field-target'] = '#recordId';
                    $toolbarButtons['remove']['attr']['onclick'] = 'ControllerAction.fieldMapping(this)';
                }
            }

            if ($download = $model->actions('download')) {
                if ($download['show']) {
                    $toolbarButtons['download']['url'] = $model->url('download', false);
                    $toolbarButtons['download']['type'] = 'button';
                    $toolbarButtons['download']['label'] = '<i class="fa kd-download"></i>';
                    $toolbarButtons['download']['attr'] = $toolbarAttr;
                    $toolbarButtons['download']['attr']['title'] = __('Download');
                }
            }
        } elseif ($action == 'transfer' || ($action == 'remove' && $model->actions('remove') == 'restrict')) {
            $toolbarButtons['back']['url'] = $model->url('index', 'QUERY');
            $toolbarButtons['back']['type'] = 'button';
            $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
            $toolbarButtons['back']['attr'] = $toolbarAttr;
            $toolbarButtons['back']['attr']['title'] = __('Back');
        }

        if ($model->actions('view')) {
            $indexButtons['view']['url'] = $model->url('view');
            $indexButtons['view']['label'] = '<i class="fa fa-eye"></i>' . __('View');
            $indexButtons['view']['attr'] = $indexAttr;
        }

        if ($model->actions('edit')) {
            $indexButtons['edit']['url'] = $model->url('edit');
            $indexButtons['edit']['label'] = '<i class="fa fa-pencil"></i>' . __('Edit');
            $indexButtons['edit']['attr'] = $indexAttr;
        }

        if ($model->actions('remove')) {
            $indexButtons['remove']['strategy'] = $model->actions('remove');
            $removeUrl = 'remove';
            if ($model->actions('remove') == 'transfer') {
                $removeUrl = 'transfer';
            }
            $indexButtons['remove']['url'] = $model->url($removeUrl);
            $indexButtons['remove']['label'] = '<i class="fa fa-trash"></i>' . __('Delete');
            $indexButtons['remove']['attr'] = $indexAttr;
        }

        $backButton = $extra->offsetExists('back') ? $extra['back'] : false;
        if ($backButton) {
            $toolbarButtons['back']['url'] = $backButton;
            $toolbarButtons['back']['type'] = 'button';
            $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
            $toolbarButtons['back']['attr'] = $toolbarAttr;
            $toolbarButtons['back']['attr']['title'] = __('Back');
        }

        $extra['toolbarButtons'] = $toolbarButtons;
        $extra['indexButtons'] = $indexButtons;

        // entity information will be attached to toolbar in afterAction()
        // refer to attachEntityInfoToToolBar() in afterAction()
    }

    public function getButtonTemplate()
    {
        $btnAttr = [
            'type' => 'button',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false
            ]
        ];
        return $btnAttr;
    }

    private function isCAv4()
    {
        return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
    }
}
