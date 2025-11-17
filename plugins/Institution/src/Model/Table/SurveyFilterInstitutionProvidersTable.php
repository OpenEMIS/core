<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Log\Log;

class SurveyFilterInstitutionProvidersTable extends AppTable
{
    
    public function initialize(array $config): void
    {
        $this->setTable('survey_filter_institution_providers');
        parent::initialize($config);
    }
}
