<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
            // POCOR-9509: Guard against missing table in sparse/dev databases
            if (!Schema::hasTable('system_updates')) {
                return [];
            }

            // Get latest local version ID and current installed version
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

            // POCOR-9509: Fetch current installed version once, attach to every item
            $currentVersion = DB::table('config_items')
                ->where('code', 'db_version')
                ->value('value') ?? 'unknown';

            // Filter new versions not yet alerted
            $newVersions = [];
            foreach ($data as $item) {
                if ($item['id'] > $maxId && !$this->versionAlreadyAlerted($featureKey, $item['version'])) {
                    $item['current_version'] = $currentVersion; //POCOR-9509: carry installed version into fillPlaceholders
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
     * Available placeholders:
     *   ${new_version}     — the new version available (e.g. 5.7.0)
     *   ${release_date}    — the date it was released (e.g. 11.12.2025)
     *   ${current_version} — the currently installed version (e.g. 5.5.0)
     *
     * Example message:
     *   "Version ${new_version} was released on ${release_date}.
     *    Your current version is ${current_version}. Please update the system."
     *
     * @param array $item System update data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        $releaseDate = '';
        if (!empty($item['date_released'])) {
            try {
                $releaseDate = (new \DateTime($item['date_released']))->format('d.m.Y');
            } catch (\Throwable $e) {
                $releaseDate = $item['date_released'];
            }
        }

        return [
            '${new_version}'     => $item['version'] ?? '',
            '${release_date}'    => $releaseDate,
            '${current_version}' => $item['current_version'] ?? '',
            '${version}'         => $item['version'] ?? '', //POCOR-9509: legacy alias — not shown in UI but kept for backward compatibility
        ];
    }
}
