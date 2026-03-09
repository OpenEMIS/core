<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * POCOR-9509: Laravel port of CakePHP's AlertSystemUpdatesCommand
 *
 * Checks for new system updates from version API and sends alerts.
 *
 * Usage:
 *   php artisan alerts:system-updates
 *       --user_id=1
 *       --rule_id=5
 *       --process_id=123
 *
 * @package App\Console\Commands\Alerts
 */
class AlertSystemUpdatesCommand extends AlertCommandBase
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:system-updates
                            {--user_id= : User ID triggering the alert}
                            {--rule_id= : Alert rule ID}
                            {--process_id= : System process ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'POCOR-9509: Send alerts for new system updates (Laravel port)';

    /**
     * POCOR-9509: Execute the console command
     *
     * @return int
     */
    public function handle(): int
    {
        if (!$this->prepareContext()) {
            return self::FAILURE;
        }

        return $this->runFeatureAlert('SystemUpdates');
    }

    /**
     * POCOR-9509: Get pending system updates from version API
     *
     * Queries external API for new versions not yet in local database.
     *
     * @param string $featureKey Feature identifier
     * @return array List of new version items
     */
    protected function getPendingItems(string $featureKey): array
    {
        try {
            // Get latest local version ID
            $latestVersion = DB::table('system_updates')
                ->orderByDesc('id')
                ->first();

            $maxId = $latestVersion->id ?? 0;

            // Get version API domain from config
            $domain = DB::table('config_items')
                ->where('code', 'version_api_domain')
                ->value('value');

            if (!$domain) {
                $this->warn("version_api_domain not configured");
                return [];
            }

            $api = $domain . '/restful/v2/System-SystemUpdates.json?_fields=id,version,date_released&_limit=50&_order=-id';

            // Fetch from API
            $response = Http::timeout(10)->get($api);

            if (!$response->successful()) {
                $this->error("API returned HTTP " . $response->status());
                return [];
            }

            $json = $response->json();
            $data = array_reverse($json['data'] ?? []);

            // Filter new versions not yet alerted
            $newVersions = [];
            foreach ($data as $item) {
                if ($item['id'] > $maxId && !$this->versionAlreadyAlerted($featureKey, $item['version'])) {
                    $newVersions[] = $item;
                }
            }

            return $newVersions;
        } catch (\Throwable $e) {
            Log::error("[POCOR-9509] Failed to fetch system updates", [
                'exception' => $e->getMessage(),
            ]);
            $this->error("Failed to fetch system updates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * POCOR-9509: Check if version was already alerted
     *
     * @param string $feature Feature name
     * @param string $version Version string
     * @return bool True if already alerted
     */
    protected function versionAlreadyAlerted(string $feature, string $version): bool
    {
        return DB::table('alert_logs')
            ->where('feature', $feature)
            ->where('message', 'LIKE', "%$version%")
            ->exists();
    }

    /**
     * POCOR-9509: Fill placeholders for system update alert
     *
     * @param array $item System update data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        return [
            '${version}' => $item['version'] ?? '',
        ];
    }
}
