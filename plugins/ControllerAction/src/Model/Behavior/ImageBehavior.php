<?php

namespace ControllerAction\Model\Behavior;
use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\Core\Configure;
use Cake\Network\Exception\NotFoundException;
use Cake\Network\Response;

class ImageBehavior extends Behavior
{
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.image'] = 'image';
        return $events;
    }

    public function image(EventInterface $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;
        $ids = $model->paramsDecode($model->paramsPass(0));

        if ($this->_table->request) { //POCOR-8082
            $base64Format = (array_key_exists('base64', $this->_table->request->getQuery()))
                ? $this->_table->request->getQuery()['base64'] : false;
        }

        $model->controller->autoRender = false;
        $model->controller->ControllerAction->autoRender = false;

        $idKeys = $model->getIdKeys($model, $ids);

        $phpResourceFile = null;

        if ($model->getTable() == 'security_users') {
            $photoData = $model->get($idKeys);
            if ($photoData->has('photo_content')) {
                $phpResourceFile = $photoData->photo_content;
            }
        } else {
            //POCOR-8080 START REMOVE ANNOING JS ERRORS
            try {
                $association = $model->getAssociation('User.Users');
            } catch (\Exception $exception) {
                $association = false;
            }
            if ($association) {
                $photoData = $model->find()
                    ->contain('Users')
                    ->select(['Users.photo_content'])
                    ->where($idKeys)
                    ->first();

                if (!empty($photoData) && $photoData->has('User.Users') && $photoData->Users->has('photo_content')) {
                    $phpResourceFile = $photoData->Users->photo_content;
                }
            } else if ($model->getTable() == 'institutions') {
                $photoData = $model->get($idKeys);
                if ($photoData->has('logo_content')) {
                    $phpResourceFile = $photoData->logo_content;
                }
            }
            //POCOR-8080 END REMOVE ANNOING JS ERRORS
        }

        if (is_resource($phpResourceFile)) {
            if ($base64Format) {
                if ($model->controller->response) { //POCOR-9440 REMOVE ANNOING JS ERRORS
                    try {
                        $model->controller->response->body(base64_encode(stream_get_contents($phpResourceFile)));
                    } catch (\Exception $exception) {
                        Log::error($exception->getMessage());
                    }
                }
            } else {
                if ($model->controller->response) { //POCOR-8080 REMOVE ANNOING JS ERRORS
                    $model->controller->response->type('jpg');
                    $model->controller->response->body(stream_get_contents($phpResourceFile));
                }
            }
        }
        return true;
        // required so it doesnt go to MissingActionException in ControllerActionV4Trait
    }
}
