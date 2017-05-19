<?php
namespace App\Model\Behavior;

use Exception;

use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Network\Session;
use Cake\Utility\Security;

class TrackDeleteBehavior extends Behavior
{
/******************************************************************************************************************
**
** Link/Map ControllerActionComponent events
**
******************************************************************************************************************/
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.beforeDelete' => 'beforeDelete'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $this->trackDelete($entity);
    }

    public function trackDelete(Entity $entity)
    {
        try {
            $DeletedRecords = TableRegistry::get('DeletedRecords');
            $source = $entity->source();
            $entityTable = TableRegistry::get($source);
            $entityData = $entity->toArray();
            $session = new Session();
            $userId = $session->read('Auth.User.id');
            if (!is_array($entityTable->primaryKey())) { // single primary key
                $referenceKey = $entity->{$entityTable->primaryKey()};
            } else { // composite primary keys
                $referenceKey = Security::hash(implode(',', $DeletedRecords->getIdKeys($entityTable, $entityData, false)), 'sha256');
            }
            // catering for 'binary' field type start
            $binaryDataFieldNames = [];
            $schema = $entityTable->schema();
            foreach ($schema->columns() as $key => $value) {
                $schemaColumnData = $schema->column($value);
                if (array_key_exists('type', $schemaColumnData) && $schemaColumnData['type'] == 'binary') {
                    $binaryDataFieldNames[] = $value;
                }
            }
            if ($binaryDataFieldNames) {
                foreach ($binaryDataFieldNames as $key => $value) {
                    if (array_key_exists($value, $entityData)) {
                        if (is_null($entityData[$value])) {
                            continue;
                        }
                        $file = base64_encode($this->convertBinaryResourceToString($entityData[$value]));
                        $entityData[$value] = $file;
                    }
                }
            }
            // catering for 'binary' field type end
            $newEntity = $DeletedRecords->newEntity([
                'reference_table' => $source,
                'reference_key' => $referenceKey,
                'data' => json_encode($entityData),
                'created_user_id' => $userId,
                'created' => Time::now()
            ]);
            $DeletedRecords->save($newEntity, ['checkExisting' => false, 'atomic' => false]);
        } catch (Exception $e) {
            Log::write('error', __METHOD__ . ': ' . $e->getMessage());
        }
    }

    public function convertBinaryResourceToString($phpResourceFile)
    {
        $file = '';
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192);
        }
        fclose($phpResourceFile);

        return $file;
    }
}
