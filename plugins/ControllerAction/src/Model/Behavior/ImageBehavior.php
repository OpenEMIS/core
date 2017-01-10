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

        $this->_table->controller->autoRender = false;
        $this->_table->controller->ControllerAction->autoRender = false;

        $currModel = $this->_table;
        $idKeys = $model->getIdKeys($currModel, $ids);
        $photoData = $currModel->find()
            ->contain('Users')
            ->select(['Users.photo_content'])
            ->where($idKeys)
            ->first()
            ;

        if (!empty($photoData) && $photoData->has('Users') && $photoData->Users->has('photo_content')) {
            $phpResourceFile = $photoData->Users->photo_content;

            if ($base64Format) {
                echo base64_encode(stream_get_contents($phpResourceFile));
            } else {
                $this->_table->controller->response->type('jpg');
                $this->_table->controller->response->body(stream_get_contents($phpResourceFile));
            }
        }
        // required so it doesnt go to MissingActionException in ControllerActionV4Trait
        return true;
    }
}
