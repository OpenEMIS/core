<?php
echo $this->Html->css('/Quality/css/rubrics', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Quality/js/quality.rubric', false);
//echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
//echo $this->Html->script('/Students/js/students', false);
?>
<?php //$obj = $data[$modelName];      ?>
<?php echo $this->element('breadcrumb'); ?>

<div id="student" class="content_wrapper">
    <h1>
        <span><?php echo $this->Utility->ellipsis(__($subheader), 50); ?></span>
        <?php
        /* echo $this->Html->link(__('List'), array('action' => 'qualityRubric'), array('class' => 'divider'));

          echo $this->Html->link(__('View Rubric'), array('action' => 'qualityRubricDetailView', $obj['id']), array('class' => 'divider'));

          if ($_edit) {
          echo $this->Html->link(__('Edit'), array('action' => 'qualityRubricEdit', $obj['id']), array('class' => 'divider'));
          }



          if ($_delete) {
          echo $this->Html->link(__('Delete'), array('action' => 'qualityRubricDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
          } */
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php
    echo $this->Form->create($modelName, array(
        //'url' => array('controller' => 'Quality','plugin'=>'Quality'),
        //'type' => 'file',
        'novalidate' => true,
        'inputDefaults' => array('label' => false, 'div' => array('class' => 'input_wrapper'), 'autocomplete' => 'off')
    ));
    ?>
    <table class='rubric-table'>
        <?php
        $this->RubricsView->defaultNoOfColumns = $totalColumns;
        //$this->RubricsView->defaultNoOfRows = $totalRows;
        //$options = array('columnHeader'=> $columnHeaderData);
        //pr($options);
        foreach ($this->data['RubricsTemplateDetail'] as $key => $item) {
            $item['editable'] = $editable;
            
            if (array_key_exists('RubricsTemplateSubheader', $item)) {
                echo $this->RubricsView->insertRubricTableHeader($item, $key, NULL);
            } else {
                $item['columnHeader'] = $columnHeaderData;
                echo $this->RubricsView->insertQualityRubricTableQuestionRow($item, $key, NULL);
            }
        }
        ?>
    </table>
    <div class="controls ">
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'qualityRubricHeader', $selectedQualityRubricId, $rubricTemplateId), array('class' => 'btn_cancel btn_left')); ?>
    </div>
    <?php echo $this->Form->end(); ?>
</div>
