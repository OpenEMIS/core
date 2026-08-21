<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\EventInterface;

class ViewBehavior extends Behavior
{
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.view'] = 'view';
        return $events;
    }

    public function view(EventInterface $mainEvent, ArrayObject $extra)
    {
        $model = $this->_table;

        $event = $model->dispatchEvent('ControllerAction.Model.view.beforeAction', [$extra], $this);
        if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->getResult();
        }
        if ($event->getResult() instanceof Table) {
            $model = $event->getResult();
        }


        $sessionKey = $model->getRegistryAlias() . '.primaryKey';
        $contain = [];

        foreach ($model->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // only contain belongsTo associations
                $contain[] = $assoc->getName();
            }
        }

        $ids = empty($model->paramsPass(0)) ? [] : $model->paramsDecode($model->paramsPass(0));

        if (empty($ids)) {
            if ($model->Session->check($sessionKey)) {
                $ids = $model->Session->read($sessionKey);
            } elseif (!empty($model->ControllerAction->getQueryString())) {
                // Query string logic not implemented yet, will require to check if the query string contains the primary key
                $primaryKey = $model->getPrimaryKey();
                $ids = $model->ControllerAction->getQueryString($primaryKey);
            }
        }

        $idKeys = $model->getIdKeys($model, $ids);

        $entity = false;
        // need to change this part
        // Regression fix: if $ids covers PART of a composite primary key but not all
        // of it (e.g. a Cancel/back link built from an incomplete queryString context -
        // observed on Profile > Nationalities, whose primary key is the composite
        // (nationality_id, security_user_id) - a link carrying only security_user_id
        // leaves nationality_id unresolved), getIdKeys() faithfully returns null for
        // the missing part(s) mixed in with the real one(s); it has no other value to
        // put there. Passing that straight into exists()/where() throws
        // InvalidArgumentException ("... is missing operator (IS, IS NOT) with `null`
        // value") deep in CakePHP's query builder, which the app's exception renderer
        // then shows as a bare 404.
        // Scope is deliberately narrow: this only short-circuits when $idKeys is
        // NON-EMPTY and contains a null (the exact incomplete-composite-key shape).
        // A genuinely empty $idKeys (no pass param, no session, no query string -
        // whatever exists([[]])/find()->where([]) already did in that case)  falls
        // through to exists() completely unchanged, so no other behavior shifts.
        // getIdKeys() itself is shared by 45+ call sites and is left untouched.
        $hasIncompleteKey = !empty($idKeys) && in_array(null, $idKeys, true);
        if (!$hasIncompleteKey && $model->exists([$idKeys])) {
            $query = $model->find()->where($idKeys)->contain($contain);

            $event = $model->dispatchEvent('ControllerAction.Controller.beforeQuery', [$model, $query, $extra], $this);
            $event = $model->dispatchEvent('ControllerAction.Model.viewEdit.beforeQuery', [$query, $extra], $this);
            $event = $model->dispatchEvent('ControllerAction.Model.view.beforeQuery', [$query, $extra], $this);

            $entity = $query->first();
        }
        $event = $model->dispatchEvent('ControllerAction.Model.view.afterAction', [$entity, $extra], $this);
        if ($event->isStopped()) {
            $mainEvent->stopPropagation();
            return $event->getResult();
        }
        if (!empty($entity)) {
            $model->Session->write($sessionKey, $ids);
            $model->controller->set('data', $entity);
        } else {
            $mainEvent->stopPropagation();
            return $model->controller->redirect($model->url('index', 'QUERY'));
        }
        return $entity;
    }
}
