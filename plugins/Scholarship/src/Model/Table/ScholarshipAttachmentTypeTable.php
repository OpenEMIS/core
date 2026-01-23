<?php
namespace Scholarship\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Controller\Component;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;

class ScholarshipAttachmentTypeTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('scholarship_attachment_types');
        parent::initialize($config);

    }
}
