<?php
namespace Institution\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;
use Cake\Log\Log;

class InstitutionInactiveComponent extends Component
{
    private $controller = null;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->controller = $this->_registry->getController();
    }

    public function beforeFilter(Event $event)
    {
        $institutionId = $this->controller->paramsDecode($this->request->param('institutionId'));
        $Institutions = TableRegistry::get('Institution.Institutions');

        $institution = $Institutions->get($institutionId, ['contain' => 'Statuses']);

        if ($institution->status->code == 'INACTIVE') {
            $this->controller->Page->disable(['add', 'edit', 'delete']);
        }
    }
}
