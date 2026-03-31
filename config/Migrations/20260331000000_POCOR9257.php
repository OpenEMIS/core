<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

//POCOR-9257: start - Consolidated migration merging POCOR9257 (webhook_queue + webhook_logs) into a single CakePHP migration
class POCOR9257 extends AbstractMigration
{
    private const TICKET = '9257';

    // Tables that existed before this migration and need backup/restore
    private const BACKUP_TABLES = [
        'webhook_queue',
        'webhook_logs',
    ];

    public function up(): void
    {
        $this->backupTables();
        $this->createWebhookQueue(); //POCOR-9257: POCOR9257 - operational webhook delivery queue
        $this->createWebhookLogs();   //POCOR-9257: POCOR9257 - permanent webhook audit trail
    }

    public function down(): void
    {
        $this->restoreTables();
    }

    // -------------------------------------------------------------------------
    // Backup / Restore
    // -------------------------------------------------------------------------

    private function backupTables(): void
    {
        foreach (self::BACKUP_TABLES as $table) {
            if (!$this->hasTable($table)) {
                continue;
            }
            $backup = 'z_' . self::TICKET . '_' . $table;
            if ($this->hasTable($backup)) {
                continue;
            }
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute("CREATE TABLE `$backup` LIKE `$table`");
            $this->execute("INSERT INTO `$backup` SELECT * FROM `$table`");
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function restoreTables(): void
    {
        foreach (self::BACKUP_TABLES as $table) {
            $backup = 'z_' . self::TICKET . '_' . $table;
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            if ($this->hasTable($backup)) {
                $this->execute("DROP TABLE IF EXISTS `$table`");
                $this->execute("RENAME TABLE `$backup` TO `$table`");
            } else {
                // No backup means table was created fresh — just drop it
                $this->execute("DROP TABLE IF EXISTS `$table`");
            }
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    // -------------------------------------------------------------------------
    // POCOR9257: webhook_queue
    // -------------------------------------------------------------------------

    private function createWebhookQueue(): void
    {
        if ($this->hasTable('webhook_queue')) {
            return;
        }

        $this->table('webhook_queue', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
        ])
            ->addColumn('id', 'biginteger', [
                'signed' => false,
                'identity' => true,
            ])
            ->addColumn('webhook_id', 'integer', [
                'null' => true,
                'comment' => 'References webhooks.id (can be NULL if webhook deleted)',
            ])
            ->addColumn('event_key', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'Trigger event (student_create, staff_update, etc)',
            ])
            ->addColumn('target_url', 'string', [
                'limit' => 512,
                'null' => false,
                'comment' => 'Full URL with query params applied',
            ])
            ->addColumn('http_method', 'string', [
                'limit' => 10,
                'null' => false,
                'default' => 'POST',
                'comment' => 'POST, PUT, PATCH, GET, DELETE',
            ])
            ->addColumn('headers', 'json', [
                'null' => true,
                'comment' => 'HTTP headers (auth, content-type, custom)',
            ])
            ->addColumn('payload', 'json', [
                'null' => false,
                'comment' => 'Request body data',
            ])
            ->addColumn('auth_type', 'string', [
                'limit' => 20,
                'null' => true,
                'comment' => 'bearer, basic, api_key, hmac',
            ])
            ->addColumn('auth_credentials', 'json', [
                'null' => true,
                'comment' => 'Auth details (encrypted if sensitive)',
            ])
            ->addColumn('signature', 'string', [
                'limit' => 255,
                'null' => true,
                'comment' => 'HMAC signature for payload validation',
            ])
            ->addColumn('status', 'integer', [
                'default' => 0,
                'null' => false,
                'comment' => '0=pending, 1=processing, 2=sent, -1=failed',
            ])
            ->addColumn('retry_count', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('max_retries', 'integer', [
                'default' => 3,
                'null' => false,
            ])
            ->addColumn('last_error', 'text', [
                'null' => true,
            ])
            ->addColumn('available_at', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Do not process before this time (delay support)',
            ])
            ->addColumn('next_retry_at', 'datetime', [
                'null' => true,
                'comment' => 'Next retry timestamp (exponential backoff)',
            ])
            ->addColumn('response_status', 'integer', [
                'null' => true,
                'comment' => 'HTTP status code (200, 404, 500, etc)',
            ])
            ->addColumn('response_body', 'text', [
                'null' => true,
                'comment' => 'Response from webhook endpoint',
            ])
            ->addColumn('duration_ms', 'integer', [
                'null' => true,
                'comment' => 'Request duration in milliseconds',
            ])
            ->addColumn('sent_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => true,
                'comment' => 'User who triggered the webhook',
            ])
            ->addIndex(['status', 'available_at'], ['name' => 'idx_status_available'])
            ->addIndex(['event_key'], ['name' => 'idx_event_key'])
            ->addIndex(['webhook_id'], ['name' => 'idx_webhook_id'])
            ->addIndex(['next_retry_at'], ['name' => 'idx_next_retry'])
            ->addIndex(['created'], ['name' => 'idx_created'])
            ->create();
    }

    // -------------------------------------------------------------------------
    // POCOR9257: webhook_logs
    // -------------------------------------------------------------------------

    private function createWebhookLogs(): void
    {
        if ($this->hasTable('webhook_logs')) {
            return;
        }

        $this->table('webhook_logs', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
        ])
            ->addColumn('id', 'biginteger', [
                'signed' => false,
                'identity' => true,
            ])
            ->addColumn('webhook_id', 'integer', [
                'null' => true,
                'comment' => 'References webhooks.id',
            ])
            ->addColumn('webhook_queue_id', 'biginteger', [
                'signed' => false,
                'null' => true,
                'comment' => 'References webhook_queue.id',
            ])
            ->addColumn('event_key', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('target_url', 'string', [
                'limit' => 512,
                'null' => false,
            ])
            ->addColumn('http_method', 'string', [
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('payload', 'json', [
                'null' => true,
                'comment' => 'Request body sent',
            ])
            ->addColumn('headers', 'json', [
                'null' => true,
                'comment' => 'HTTP headers sent',
            ])
            ->addColumn('response_status', 'integer', [
                'null' => true,
                'comment' => 'HTTP status code',
            ])
            ->addColumn('response_body', 'text', [
                'null' => true,
                'comment' => 'Response from webhook endpoint',
            ])
            ->addColumn('response_headers', 'json', [
                'null' => true,
                'comment' => 'Response headers',
            ])
            ->addColumn('duration_ms', 'integer', [
                'null' => true,
                'comment' => 'Request duration',
            ])
            ->addColumn('success', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => '1=success, 0=failure',
            ])
            ->addColumn('error_message', 'text', [
                'null' => true,
            ])
            ->addColumn('retry_attempt', 'integer', [
                'default' => 0,
                'null' => false,
                'comment' => 'Which retry attempt (0=first, 1=first retry, etc)',
            ])
            ->addColumn('checksum', 'string', [
                'limit' => 64,
                'null' => true,
                'comment' => 'SHA256 for duplicate detection',
            ])
            ->addColumn('created', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ])
            ->addColumn('created_user_id', 'integer', [
                'null' => true,
                'comment' => 'User who triggered the webhook',
            ])
            ->addIndex(['webhook_id'], ['name' => 'idx_webhook_id'])
            ->addIndex(['webhook_queue_id'], ['name' => 'idx_webhook_queue_id'])
            ->addIndex(['event_key'], ['name' => 'idx_event_key'])
            ->addIndex(['checksum'], ['name' => 'idx_checksum'])
            ->addIndex(['created'], ['name' => 'idx_created'])
            ->addIndex(['success'], ['name' => 'idx_success'])
            ->create();
    }
}
