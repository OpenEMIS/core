<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;

/**
 * POCOR-4477: Fields Configurations admin screen. Lets an admin mark each
 * non-mandatory standard field on the Institution/Student/Staff Add/Edit/View
 * pages as Visible or Hidden, and set its display order. Mandatory fields are
 * locked - always visible, no Edit/Delete action - since they can't safely be
 * hidden without breaking the underlying page.
 */
class ConfigFieldsConfigurationsTable extends ControllerActionTable
{
    const MODULES = ['Institution' => 'Institution', 'Staff' => 'Staff', 'Student' => 'Student'];

    public function initialize(array $config): void
    {
        $this->setTable('field_configurations');
        parent::initialize($config);

        // Same shared behavior every System Configurations tab uses for the
        // primary "Fields Configurations" dropdown + controller redirect.
        $this->addBehavior('Configuration.ConfigItems');

        // Drag-reorder, scoped per module so reordering Institution's fields
        // never touches Staff's/Student's order values.
        $this->addBehavior('ControllerAction.Reorder', [
            'orderField' => 'order',
            'filter' => 'module',
        ]);

        $this->toggle('add', false);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->requirePresence('visible', 'update')->boolean('visible');

        return $validator;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('module', ['visible' => false]);
        $this->field('field_name', ['visible' => false]);
        $this->field('is_mandatory', ['visible' => false]);

        $this->field('name', [
            'type' => 'readonly',
            'attr' => ['label' => __('Name')],
        ]);

        $this->field('visible', [
            'type' => 'select',
            'options' => ['1' => __('Yes'), '0' => __('No')],
            'empty' => true, // use the framework's native empty-option mechanism instead of the auto-injected one, which was rendering blank text
            'select' => false, // ...and disable the auto-inject so there's only one blank-option mechanism active, not two colliding
            'attr' => ['label' => __('Value')],
        ]);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('module', ['visible' => false]);
        $this->field('is_mandatory', ['visible' => false]);
        $this->field('order', ['visible' => false]);

        $this->field('name', [
            'visible' => true,
            'attr' => ['label' => __('Name')],
        ]);
        $this->field('visible', [
            'visible' => true,
            'attr' => ['label' => __('Value')],
        ]);

        $this->setFieldOrder(['name', 'visible']);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $selectedModule = $this->request->getQueryParams()['module'] ?? 'Institution';
        if (!array_key_exists($selectedModule, self::MODULES)) {
            $selectedModule = 'Institution';
        }

        $query
            ->where([$this->aliasField('module') => $selectedModule])
            ->order([$this->aliasField('order') => 'ASC']);
    }

    public function onGetVisible(EventInterface $event, Entity $entity)
    {
        return $entity->visible ? __('Visible') : __('Hidden');
    }

    /**
     * Mandatory rows show an info icon + tooltip next to the name, matching
     * the reference UI, instead of the plain name non-mandatory rows show.
     */
    public function onGetName(EventInterface $event, Entity $entity)
    {
        if (empty($entity->is_mandatory)) {
            return h($entity->name);
        }

        $tooltip = __('This field is mandatory you cannot change it.');
        return h($entity->name) . ' <i class="fa fa-info-circle fa-lg fa-right icon-blue" '
            . 'tooltip-placement="bottom" uib-tooltip="' . h($tooltip) . '" '
            . 'tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>';
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('field_name', ['visible' => false]);
        $this->field('is_mandatory', ['visible' => false]);
        $this->field('module', ['visible' => true, 'attr' => ['label' => __('Type')]]);

        $this->setFieldOrder([
            'name', 'module', 'visible', 'modified_user_id', 'modified', 'created_user_id', 'created',
        ]);
    }

    public function editBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('module', ['visible' => false]);
        $this->field('field_name', ['visible' => false]);
        $this->field('is_mandatory', ['visible' => false]);
        $this->setFieldOrder(['name', 'visible']);
    }

    /**
     * Mandatory fields are locked: no Edit/Delete, view-only - they can't be
     * hidden without breaking the page they belong to. This filters the
     * index row's Actions dropdown.
     */
    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if (!empty($entity->is_mandatory)) {
            unset($buttons['edit']);
            unset($buttons['remove']);
        }

        return $buttons;
    }

    /**
     * The view page's Edit/Delete toolbar buttons are NOT covered by
     * onUpdateActionButtons() above (that only filters the index row's
     * Actions dropdown). They're built entity-unaware in
     * OpenEmisBehavior::initializeButtons() and only finalized once the
     * entity is known in OpenEmisBehavior::afterAction() (priority 100),
     * which sets the 'toolbarButtons' view var. This runs at the trait's
     * default (earlier) priority so mandatory rows get edit/remove
     * stripped from the shared $extra['toolbarButtons'] ArrayObject before
     * that finalization reads it.
     */
    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        if ($this->action !== 'view' || empty($extra['toolbarButtons'])) {
            return;
        }

        $entity = $extra['entity'] ?? null;
        if (empty($entity) || empty($entity->is_mandatory)) {
            return;
        }

        $toolbarButtons = $extra['toolbarButtons'];
        $toolbarButtons->offsetUnset('edit');
        $toolbarButtons->offsetUnset('remove');
    }

    /**
     * Defensive server-side guard mirroring the UI lock above - a direct
     * edit/delete request against a mandatory field's URL must not succeed
     * even if someone bypasses the hidden Actions menu.
     */
    public function editBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('is_mandatory') => 0]);
    }

    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        if (!empty($entity->is_mandatory)) {
            $this->Alert->error('general.notExists', ['reset' => true]);
            $url = $this->url('index');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }
    }
}
