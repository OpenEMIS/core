<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use Cake\Controller\Component;
use Cake\Utility\Text;
use Cake\I18n\Time;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class ExaminationStudentSubjectsTable extends ControllerActionTable {
    use OptionsTrait;

    public function initialize(array $config): void {
        parent::initialize($config);
    }
        
}
