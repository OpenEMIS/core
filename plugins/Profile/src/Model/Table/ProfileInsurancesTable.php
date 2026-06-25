<?php
namespace Profile\Model\Table;

use ArrayObject;

use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class ProfileInsurancesTable extends ControllerActionTable
{
    // public $InstitutionStudent;

    // these constants are being used in AdvancedPositionSearchBehavior as well
    // remember to check AdvancedPositionSearchBehavior if these constants are being modified
    const ALL = 0;
    const STUDENT = 1;
    const STAFF = 2;
    const GUARDIAN = 3;
    const OTHER = 4;
    const STUDENTNOTINSCHOOL = 5;
    const STAFFNOTINSCHOOL = 6;

    private $dashboardQuery;

    public function initialize(array $config): void {
        $this->setTable('user_insurances');
       // $this->setEntityClass('User.User');
        parent::initialize($config);
    }


    
}
