<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9306 extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('CREATE TABLE `zz_9306_security_user_codes` LIKE `security_user_codes`');
        $this->execute('INSERT INTO `zz_9306_security_user_codes` SELECT * FROM `security_user_codes`');
        
        $table = $this->table('security_user_codes');

        if ($table->hasColumn('security_user_id')) {
            $table->changeColumn('security_user_id', 'integer', [
                'null' => true,
                'default' => null,
            ]);
        }

        // Add `email` column if it doesn't exist
        if (!$table->hasColumn('email')) {
            $table->addColumn('email', 'string', [
                'limit' => 255,
                'null' => false,
                'after' => 'security_user_id',
            ]);
        }

        // Add `status` column if it doesn't exist
        if (!$table->hasColumn('status')) {
            $table->addColumn('status', 'integer', [
                'limit' => 1,
                'default' => 0,
                'null' => true,
                'after' => 'verification_otp',
            ]);
        }

        // Add `expires_at` column if it doesn't exist
        if (!$table->hasColumn('expires_at')) {
            $table->addColumn('expires_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'null' => false,
                'after' => 'status',
            ]);
        }

        // Apply the changes
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('security_user_codes');

        $table->changeColumn('security_user_id', 'integer', [
            'null' => false,
            'default' => null, 
        ]);
        // Remove added columns
        if ($table->hasColumn('email')) {
            $table->removeColumn('email');
        }
        if ($table->hasColumn('status')) {
            $table->removeColumn('status');
        }
        if ($table->hasColumn('expires_at')) {
            $table->removeColumn('expires_at');
        }
        // Drop the backup table
        $this->execute('DROP TABLE IF EXISTS `zz_9306_security_user_codes`');
        $table->update();
    }
}
