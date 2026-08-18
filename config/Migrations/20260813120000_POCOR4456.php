<?php

use Phinx\Migration\AbstractMigration;

/**
 * POCOR-9772 (placeholder ticket number - update if a different ticket has been assigned)
 *
 * Adds a "Slider Type" choice (Number / Text) to the Appraisals "Slider" criteria field type.
 * - Number: existing Min/Max/Step behaviour (unchanged).
 * - Text: a repeatable list of Label/Value pairs (appraisal_slider_options) that render as a
 *   labelled slider with fixed tick positions on the Staff Appraisal fill-in form.
 */
class POCOR4456 extends AbstractMigration
{
    public function up()
    {
        // appraisal_sliders: add slider_type, and allow min/max/step to be empty for TEXT sliders
        $this->table('appraisal_sliders')
            ->addColumn('slider_type', 'string', [
                'limit' => 10,
                'null' => false,
                'default' => 'NUMBER',
                'after' => 'appraisal_criteria_id',
                'comment' => 'NUMBER or TEXT'
            ])
            ->changeColumn('min', 'decimal', [
                'null' => true,
                'precision' => 5,
                'scale' => 2
            ])
            ->changeColumn('max', 'decimal', [
                'null' => true,
                'precision' => 5,
                'scale' => 2
            ])
            ->changeColumn('step', 'decimal', [
                'null' => true,
                'precision' => 3,
                'scale' => 2
            ])
            ->save();

        // appraisal_slider_options
        $table = $this->table('appraisal_slider_options', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the label/value tick options for a Text-type slider criteria'
        ]);
        $table
            ->addColumn('label', 'string', [
                'limit' => 250,
                'null' => false
            ])
            ->addColumn('value', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false
            ])
            ->addColumn('order', 'integer', [
                'limit' => 3,
                'null' => false,
                'default' => 0
            ])
            ->addColumn('appraisal_criteria_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to appraisal_criterias.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('appraisal_criteria_id')
            ->save();

        $this->execute("ALTER TABLE appraisal_slider_options ADD CONSTRAINT `appra_slide_optio_fk_app_cri_id` FOREIGN KEY (`appraisal_criteria_id`) REFERENCES appraisal_criterias(`id`)");
    }

    public function down()
    {
        $this->execute("ALTER TABLE appraisal_slider_options DROP FOREIGN KEY `appra_slide_optio_fk_app_cri_id`");
        $this->dropTable('appraisal_slider_options');

        $this->table('appraisal_sliders')
            ->removeColumn('slider_type')
            ->changeColumn('min', 'decimal', [
                'null' => false,
                'precision' => 5,
                'scale' => 2
            ])
            ->changeColumn('max', 'decimal', [
                'null' => false,
                'precision' => 5,
                'scale' => 2
            ])
            ->changeColumn('step', 'decimal', [
                'null' => false,
                'precision' => 3,
                'scale' => 2
            ])
            ->save();
    }
}
