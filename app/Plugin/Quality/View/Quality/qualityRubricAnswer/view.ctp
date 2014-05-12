<?php
echo $this->Html->css('/Quality/css/rubrics', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Quality/js/quality.rubric', false);
//echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
//echo $this->Html->script('/Students/js/students', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Utility->ellipsis(__($subheader), 50));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'qualityRubricHeader', $selectedQualityRubricId, $rubricTemplateId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>
<?php //$obj = $data[$modelName];      ?>

<div id="student" class="content_wrapper">
    <?php
    /*echo $this->Form->create($modelName, array(
        //'url' => array('controller' => 'Quality','plugin'=>'Quality'),
        //'type' => 'file',
        'novalidate' => true,
        'inputDefaults' => array('label' => false, 'div' => array('class' => 'input_wrapper'), 'autocomplete' => 'off')
    ));*/
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
    <!--<div class="controls ">
        <?php if($editable === 'true'){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php } ?>
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'qualityRubricHeader', $selectedQualityRubricId, $rubricTemplateId), array('class' => 'btn_cancel btn_left')); ?>
    </div>
    <?php echo $this->Form->end(); ?> -->
</div>
<?php $this->end(); ?>  
