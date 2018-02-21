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

        $this->belongsTo('ApiCredentials', [
            'className' => 'ApiCredentials',
            'foreignKey' => 'api_credential_id'
        ]);

        $this->belongsTo('ApiSecurities', [
            'className' => 'ApiSecurities',
            'foreignKey' => 'api_security_id'
        ]);
    }

    // public function validationDefault(Validator $validator)
    // {
    //     parent::validationDefault($validator);

    //     $validator
    //         ->add('add', 'ruleCheckAllActionDeny', [
    //             'rule' => function ($check, $global) {
    //                 $denyValue = 0;

    //                 $record = $global['data'];

    //                 return !(
    //                     $record['add'] == $denyValue &&
    //                     $record['view'] == $denyValue &&
    //                     $record['edit'] == $denyValue &&
    //                     $record['delete'] == $denyValue &&
    //                     $record['list'] == $denyValue &&
    //                     $record['execute'] == $denyValue

    //                 );
    //             }
    //         ]);

    //     return $validator;
    // }
}
