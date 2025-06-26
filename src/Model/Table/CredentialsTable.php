<?php
namespace App\Model\Table;

use ArrayObject;

use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\I18n\I18n;
use Cake\I18n\Date;
use Cake\ORM\ResultSet;
use Cake\Http\Session;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\Datasource\ResultSetInterface;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Http\ServerRequest;
use Institution\Model\Behavior\LatLongBehavior as LatLongOptions;

class CredentialsTable extends ControllerActionTable
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('api_credentials');
        parent::initialize($config);
    }
}
