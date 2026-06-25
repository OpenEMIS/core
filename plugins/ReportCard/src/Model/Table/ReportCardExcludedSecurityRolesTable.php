<?php
namespace ReportCard\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Date;
use Cake\I18n\Time;
use App\Model\Table\ControllerActionTable;
class ReportCardExcludedSecurityRolesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('ReportCards', ['className' => 'ReportCard.ReportCards']);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles', 'foreignKey' => 'security_role_id']);
    }
    
}
    
