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
        $this->AlertLogs->insertSystemUpdateAlertLog($method, $feature, $recipient, $subject, $message);
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $alertsTable = TableRegistry::getTableLocator()->get('Alert.Alerts');
        $alertRulesTable = TableRegistry::getTableLocator()->get('Alert.AlertRules');
        $userId = (int) ($args->getOption('user_id') ?? 0);
        $ruleId = (int) ($args->getOption('rule_id') ?? 0);
        $featureKey = 'SystemUpdates';
        $rule = $alertRulesTable->get($ruleId);
        // 🧪 Dry run: make sure contacts can be built for each rule
        if (!empty($rule['security_roles'])) {
            $contactList = $this->getRoleAssociatedContactList($rule['security_roles']);
            if (empty($contactList)) {
                Log::debug("[SystemUpdates] No contacts found for rule ID {$rule['id']}");
                return static::CODE_SUCCESS;
            }
        }

        // ✅ Proceed to API check
        $this->loadModel('System.SystemUpdates');
        $latestVersion = $this->SystemUpdates->find()
            ->order([$this->SystemUpdates->aliasField('id') => 'desc'])
            ->first();

        $maxId = $latestVersion->id ?? 0;

        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $domain = $ConfigItems->value('version_api_domain');
        $api = $domain . '/restful/v2/System-SystemUpdates.json?_fields=id,version,date_released&_limit=50&_order=-id';

        $http = new Client();
        try {
            $response = $http->get($api);
            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();
        } catch (\Exception $e) {
            Log::error('[SystemUpdates] API request failed: ' . $e->getMessage());
            return static::CODE_ERROR;
        }

        if ($status !== 200) {
            Log::error('[SystemUpdates] API returned HTTP ' . $status);
            return static::CODE_ERROR;
        }

        $json = json_decode($body, true);
        $data = array_reverse($json['data'] ?? []);

        foreach ($data as $item) {
            if ($item['id'] <= $maxId) {
                continue;
            }

            if ($this->versionAlreadyAlerted($featureKey, $item['version'])) {
                Log::debug("[SystemUpdates] Skipping already alerted version: " . $item['version']);
                continue;
            }

            $alertsTable->triggerAlertCommand($featureKey, [
                'version' => $item['version'],
                'user_id' => $userId,
                'roles' => '',
                'schools' => ''
            ]);
        }

        return static::CODE_SUCCESS;
    }

    private function getRoleAssociatedContactList(array $roleIds): array
    {
        // placeholder logic – assumes role-to-contact mapping is handled
        return $roleIds ? ['contact@example.com'] : [];
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

    public function getOptionParser(): ConsoleOptionParser
    {
        $parser = parent::getOptionParser();

        return $parser
            ->addOption('user_id', [
                'help' => 'ID of the user triggering the alert',
                'short' => 'u',
                'default' => null
            ])
            ->addOption('rule_id', [
                'help' => 'Comma-separated list of rule IDs',
                'default' => 'r'
            ])
            ;
    }


}
