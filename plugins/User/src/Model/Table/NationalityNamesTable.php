<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class NationalityNamesTable extends AppTable {
    public function initialize(array $config):void {
            $this->setTable('nationalities');
            parent::initialize($config);
    }
}
