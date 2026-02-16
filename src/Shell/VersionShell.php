<?php
namespace App\Shell;

use Cake\Console\Shell;
// use Cake\i18n\Time;
use Cake\I18n\FrozenTime;
use Cake\Cache\Cache;
use Cake\Cache\Engine\FileEngine;

class VersionShell extends Shell
{
    public function initialize(): void
    {
        parent::initialize();

        $this->SystemUpdates = $this->fetchTable('System.SystemUpdates');
        $this->ConfigItems = $this->fetchTable('Configurations.ConfigItems');
    }

    public function main()
    {
        $versionFile = WWW_ROOT . 'version';

        if (file_exists($versionFile)) {
            $file = fopen($versionFile, "r");
            $version = trim(fgets($file));

            if (!$this->SystemUpdates->exists(['version' => $version])) {
                $data = [
                    'version' => $version,
                    'date_released' => FrozenTime::now(),
                    'date_approved' => FrozenTime::now(),
                    'approved_by' => 1,
                    'status' => 2,
                    'created_user_id' => 1
                ];

                $entity = $this->SystemUpdates->newEntity($data);
                $this->SystemUpdates->save($entity);

                echo "$version has been updated in the database.\n\n";

                $this->ConfigItems->updateAll(['value' => $version], ['code' => 'db_version']);
            } else {
                echo "$version is already exists in the database.\n\n";
            }
            //POCOR-8786[START]
            $fileEngine = new FileEngine();
            $fileEngine->init([
                'className' => 'File',
                'prefix' => 'myapp_cake_model_',
                'path' => CACHE . 'models/',
                'serialize' => true,
                'duration' => '+2 minutes',
                'url' => env('CACHE_CAKEMODEL_URL', null),
            ]);
            $fileEngine->clear(false);
            // Cache::clear(false, '_cake_model_');
            //POCOR-8786[END]
        }
    }
}
