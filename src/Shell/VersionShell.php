<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\i18n\Time;
use Cake\Cache\Cache;

class VersionShell extends Shell
{
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('System.SystemUpdates');
        $this->loadModel('Configurations.ConfigItems');
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
                    'date_released' => Time::now(),
                    'date_approved' => Time::now(),
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

            Cache::clear(false, '_cake_model_');
        }
    }
}
