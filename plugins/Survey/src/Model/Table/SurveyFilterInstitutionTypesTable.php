<?php
namespace Survey\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\EventInterface;
//use Cake\I18n\Time;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Log\Log;
use Cake\Datasource\ResultSetInterface;
use Cake\Collection\Collection;
use Cake\Http\ServerRequest;

class SurveyFilterInstitutionTypesTable extends ControllerActionTable
{
    
    public function initialize(array $config): void
    {
        $this->setTable('survey_filter_institution_types');
        parent::initialize($config);
    }
}
