<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class ApiSecuritiesCredentialsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
    
        $this->belongsTo('');
    }
}
