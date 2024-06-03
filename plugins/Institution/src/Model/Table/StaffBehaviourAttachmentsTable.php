<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;


use App\Model\Table\ControllerActionTable;

class StaffBehaviourAttachmentsTable extends ControllerActionTable {
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->belongsTo('StaffBehaviours', ['className' => 'Institution.StaffBehaviours', 'foreignKey' => 'staff_behaviour_id']);
        $this->addBehavior('ControllerAction.FileUpload',
            ['size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true]
        );
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['StaffBehaviourAttachments' =>['id']
            ]
        ]);

    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        return $validator->requirePresence(['file_name', 'file_content']);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(Event $event, $scope, $action, $extra)
    {
        if ($action == 'download') {
            // check for the user permission to download here
            $event->stopPropagation();
            return true;
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $tabElements = $this->getStaffBehaviourTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
        $paramPass = $this->paramsDecode($this->request->getParam('pass')[1]);
        $staffBehaviourId = $paramPass['staff_behaviour_id'];
        $this->field('file_name', ['visible' =>['index' => true]]);
        $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('staff_behaviour_id', ['attr' => ['value' => $staffBehaviourId], 'type' => 'hidden']);
        $this->setFieldOrder([
            'name', 'description', 'file_name','staff_behaviour_id'
        ]);
    }

    public function getStaffBehaviourTabElements($options = [])
    {
        $tabElements = [];
        $institutionId = $this->getInstitutionID();

        $paramPass = $this->request->getParam('pass');
        $ids = isset($paramPass[1]) ? $this->paramsDecode($paramPass[1]) : [];
        $staffBehaviourId = $ids['staff_behaviour_id'];
        if(isset($ids['staff_behaviour_id'])) {
        $queryString = $this->paramsEncode(['id' => $staffBehaviourId,'institution_id'=> $institutionId]);

        $tabElements = [
            'StaffBehaviours' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffBehaviours', 'view', $queryString ],
                'text' => __('Overview')
            ],
            'StaffBehaviourAttachments' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffBehaviourAttachments', 'index', $paramPass[1]],
                'text' => __('Attachments')
            ]
        ];}
        return $this->TabPermission->checkTabPermission($tabElements);
    }
}