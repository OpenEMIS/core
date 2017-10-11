<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;

class InfrastructureProjectsNeedsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('InfrastructureProjects', ['className' => 'Institution.InfrastructureProjects']);
        $this->belongsTo('InfrastructureNeeds',  ['className' => 'Institution.InfrastructureNeeds']);

        $this->addBehavior('CompositeKey');
    }
}