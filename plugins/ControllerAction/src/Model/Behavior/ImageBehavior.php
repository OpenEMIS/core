<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\Core\Configure;
use Cake\Network\Exception\NotFoundException;
use Cake\Network\Response;

class ImageBehavior extends Behavior
{
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.image'] = 'image';
        return $events;
    }

    public function image(Event $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;
        $ids = $model->paramsDecode($model->paramsPass(0));

        $base64Format = (array_key_exists('base64', $this->_table->controller->request->query))? $this->_table->controller->request->query['base64']: false;

        $model->controller->autoRender = false;
        $model->controller->ControllerAction->autoRender = false;

        $idKeys = $model->getIdKeys($model, $ids);

        $phpResourceFile= null;

        if ($model->table() == 'security_users') {
            $photoData = $model->get($idKeys);
            if ($photoData->has('photo_content')) {
                $phpResourceFile = $photoData->photo_content;
            }
        } else if ($model->association('Users')) {
            $photoData = $model->find()
                ->contain('Users')
                ->select(['Users.photo_content'])
                ->where($idKeys)
                ->first()
                ;

            if (!empty($photoData) && $photoData->has('Users') && $photoData->Users->has('photo_content')) {
                $phpResourceFile = $photoData->Users->photo_content;
            }
        } else if ($model->table() == 'institutions') {
            $photoData = $model->get($idKeys);
            if ($photoData->has('logo_content')) {
                $phpResourceFile = $photoData->logo_content;
            }
        }

        if (is_resource($phpResourceFile)) {
            if ($base64Format) {
                $model->controller->response->body(base64_encode(stream_get_contents($phpResourceFile)));
            } else {
                $model->controller->response->type('jpg');
                $model->controller->response->body(stream_get_contents($phpResourceFile));
            }
        }
        return true;
        // required so it doesnt go to MissingActionException in ControllerActionV4Trait
    }
}
