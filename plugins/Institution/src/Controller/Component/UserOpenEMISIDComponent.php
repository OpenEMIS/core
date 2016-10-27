<?php
namespace Institution\Controller\Component;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\Event;

class UserOpenEMISIDComponent extends Component {
    public function getUniqueOpenemisId($model = null)
    {
        $openemisId = TableRegistry::get('User.Users')->getUniqueOpenemisId(['model' => $model]);
        $openemis = ['openemis_no' => $openemisId];
        return json_encode($openemis);
    }
}
