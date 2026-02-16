<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\Validation\Validator;

use App\Model\Traits\OptionsTrait;

class SummaryProgrammeSectorGendersTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('summary_programme_sector_genders');
        parent::initialize($config);
        
    }
}
