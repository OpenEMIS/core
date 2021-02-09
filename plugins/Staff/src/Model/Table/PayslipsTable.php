<?php
namespace Staff\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use App\Model\Traits\MessagesTrait;

class PayslipsTable extends ControllerActionTable
{
    use MessagesTrait;
    public function initialize(array $config)
    {
        $this->table('staff_payslips');
        
        parent::initialize($config);

        $this->addBehavior('ControllerAction.FileUpload', [
            'size' => '2MB',
            'contentEditable' => false,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        if ($this->behaviors()->has('ControllerAction')) {
            $this->behaviors()->get('ControllerAction')->config([
                'actions' => [
                    'download' => ['show' => true] // to show download on toolbar
                ]
            ]);
        }

    } 

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('file_type', ['visible' => false]);
       
        $this->field('created', [
            'type' => 'datetime',
            'visible' => true
        ]);

        $this->setFieldOrder([
            'name',
            'description',
            'created'
        ]);
    }



    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
    }

    public function onGetFileType(Event $event, Entity $entity)
    {
        return $this->getFileTypeForView($entity->file_name);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
        $downloadUrl = [
            'plugin' => 'Staff',
            'controller' => 'Staff',
            'action' => $this->alias,
            'staffId' => $this->paramsEncode(['id' => $entity->staff_id]),
            '0' => 'download',
            '1' => $this->paramsEncode(['id' => $entity->id])
        ];
        $buttons['download'] = [
            'label' => '<i class="fa kd-download"></i>'.__('Download'),
            'attr' => $indexAttr,
            'url' => $downloadUrl
        ];

        return $buttons;
    } 

    public function afterAction(Event $event)
    {
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        $nonSchoolController = ['Directories', 'Profiles'];
        if (in_array($this->controller->name, $nonSchoolController)) {
            $options = [
                'type' => 'staff'
            ];
            $tabElements = $this->controller->getStaffFinanceTabElements($options);
        } else {
            $tabElements = $this->controller->getFinanceTabElements();
        }
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

   
}
