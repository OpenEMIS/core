<?php

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Http\Client;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Console\ConsoleOptionParser;
use App\Command\AlertCommandBase;
use Cake\ORM\Locator\LocatorAwareTrait;

class AlertSystemUpdatesCommand extends AlertCommandBase
{
    use LocatorAwareTrait;

    public function logAlert($method, $feature, $recipient, $subject, $message)
    {
        $this->AlertLogs->insertAlertLog($method, $feature, $recipient, $subject, $message);
        $shortSubject = mb_strimwidth((string)$subject, 0, 100, '...');
        $shortMessage = mb_strimwidth((string)$message, 0, 100, '...');

        $this->logMsg("✅ Alert {$feature} logged via {$method} to {$recipient}. Subject: {$shortSubject} Message: {$shortMessage}");

    }

    protected function getPendingItems(string $featureKey): array
    {
        $this->SystemUpdates = $this->fetchTable('System.SystemUpdates');
        $latestVersion = $this->SystemUpdates->find()
            ->order([$this->SystemUpdates->aliasField('id') => 'desc'])
            ->first();
        $maxId = $latestVersion->id ?? 0;

        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $domain = $ConfigItems->value('version_api_domain');
        $api = $domain . '/restful/v2/System-SystemUpdates.json?_fields=id,version,date_released&_limit=50&_order=-id';

        $http = new Client();
        $response = $http->get($api);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException("API returned HTTP " . $response->getStatusCode());
        }

        $json = json_decode($response->getBody()->getContents(), true);
        $data = array_reverse($json['data'] ?? []);

        $newVersions = [];
        foreach ($data as $item) {
            if ($item['id'] > $maxId && !$this->versionAlreadyAlerted($featureKey, $item['version'])) {
                $newVersions[] = $item;
            }
        }

        return $newVersions;
    }

    protected function fillPlaceholders($item): array
    {
        return ['${version}' => $item['version']];
    }


    public function execute(Arguments $args, ConsoleIo $io): int
    {
        if (!$this->prepareContext($args, $io)) {
            return static::CODE_SUCCESS;
        }

        return $this->runFeatureAlert('SystemUpdates');
    }


    private function versionAlreadyAlerted(string $feature, string $version): bool
    {
        $logTable = TableRegistry::getTableLocator()->get('Alert.AlertLogs');

        return $logTable->find()
                ->where([
                    'feature' => $feature,
                    'message LIKE' => "%$version%"
                ])
                ->count() > 0;
    }

}
