<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9599 extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('assessment_periods');

        $table->changeColumn('weight', 'decimal', [
            'precision' => 6,
            'scale' => 5,
            'null' => true,
            'default' => '0.000',
        ]);

        $table->update();
    }
}
