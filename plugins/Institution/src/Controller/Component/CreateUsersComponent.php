<?php
namespace Institution\Controller\Component;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\Event;

class CreateUsersComponent extends Component
{
    public function getUniqueOpenemisId()
    {
        $openemisId = TableRegistry::get('User.Users')->getUniqueOpenemisId();
        $openemis = ['openemis_no' => $openemisId];
        return json_encode($openemis);
    }

    public function getAutoGeneratedPassword()
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $password = ['password' => $ConfigItems->getAutoGeneratedPassword()];
        return json_encode($password);
    }
}
