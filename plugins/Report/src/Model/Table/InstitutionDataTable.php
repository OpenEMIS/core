<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\Validation\Validator;

class InstitutionDataTable extends AppTable
{ 
    public function initialize(array $config): void
    {
        $this->setTable('institutions');
        parent::initialize($config);
        
        $this->classificationOptions = [
            Institutions::ACADEMIC => 'Academic Institution',
            Institutions::NON_ACADEMIC => 'Non-Academic Institution'
        ];
    }

    
}
