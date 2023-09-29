<?php
namespace App\Model\Behavior;

use Exception;
use ArrayObject;

use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Network\Session;

class TrackAddBehavior extends Behavior
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
            'Model.afterSave' => 'afterSave'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {
        $this->trackAdd($entity);
    }

    public function trackAdd(Entity $entity)
    {
        try {
            // Insert only if is a external API call, to be removed in future
            if ($entity->has('action_type') && $entity->action_type == 'third_party') {
                $InsertedRecords = TableRegistry::get('InsertedRecords');
                $source = $entity->source();
                $entityTable = TableRegistry::get($source);
                $entityData = $entity->toArray();
                $session = new Session();
                $userId = $session->read('Auth.User.id') ? $session->read('Auth.User.id'): 1;

                if (!is_array($entityTable->primaryKey())) { // single primary key
                    $referenceKey = $entity->{$entityTable->primaryKey()};
                } else { // composite primary keys
                    $referenceKey = json_encode($InsertedRecords->getIdKeys($entityTable, $entityData, false));
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

            //  Change to manual insertion to due to 404 error in cakephp orm save propagation
                $query = $InsertedRecords->query();
                $query
                    ->insert(['inserted_date', 'reference_table', 'reference_key', 'data', 'action_type', 'created_user_id', 'created'])
                    ->values([
                        'inserted_date' => Time::now()->format('Ymd'),
                        'reference_table' => $source,
                        'reference_key' => $referenceKey,
                        'data' => json_encode($entityData),
                        'action_type' => $entity->action_type,
                        'created_user_id' => $userId,
                        'created' => Time::now()
                    ]);
                $statement = $query->execute();
                $statement->closeCursor();
            }
        } catch (Exception $e) {
            Log::write('error', $this->_table->alias() . ' -> ' . __METHOD__ . ': ' . $e->getMessage());
        }
    }

    private function convertBinaryResourceToString($phpResourceFile)
    {
        $file = '';
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192);
        }
        fclose($phpResourceFile);

        return $file;
    }
}
