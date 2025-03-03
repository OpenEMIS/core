<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use ArrayObject;
use Cake\ORM\TableRegistry;
use App\Model\Traits\OptionsTrait;
use Cake\Http\ServerRequest;
use PDOException;

class ConfigAutomatedStudentEnrollmentsAreasTable extends ControllerActionTable
{
    public function initialize(array $config): void
    { 
        $this->setTable('area_programme_institution_areas');
        parent::initialize($config);

        // Association to the main area_programme_institutions table
        $this->belongsTo('AreaProgrammeInstitutions', [
            'className' => 'Configuration.ConfigAutomatedStudentEnrollments',
            'foreignKey' => 'area_programme_institution_id'
        ]);

        // Association to the AreaAdministratives table to retrieve actual area_administrative_id data
        $this->belongsTo('AreaAdministratives', [
            'className' => 'Area.AreaAdministratives',
            'foreignKey' => 'area_administrative_id'
        ]);

        $this->toggle('edit', 'delete', false);
    }
}

