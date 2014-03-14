<?php
echo $this->Html->css('/Quality/css/rubrics', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Quality/js/rubrics', false);
echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
//echo $this->Html->script('config', false);
?>
<?php echo $this->element('breadcrumb'); ?>

<div id="student" class="content_wrapper">
    <h1>
        <span><?php echo $this->Utility->ellipsis(__($subheader), 50); ?></span>
        <?php
        /* if($_edit) {
          echo $this->Html->link(__('Edit'), array('action' => 'rubricsTemplatesDetails', $id ), array('class' => 'divider'));
          } */
         //echo $this->Html->link(__('Add Level / Column'), array('action' => 'rubricsTemplatesCriteria', $rubricTemplateId, $rubricTemplateHeaderId), array('class' => 'divider'));
    
      //  if (!empty($columnHeaderData)) {
            echo $this->Html->link(__('Add Header'), 'javascript:void(0)', array('class' => 'divider', 'onclick' => 'rubricsTemplate.addHeader(' . $rubricTemplateHeaderId . ')'));
            echo $this->Html->link(__('Add Criteria'), 'javascript:void(0)', array('class' => 'divider', 'onclick' => 'rubricsTemplate.addRow(' . $rubricTemplateHeaderId . ')'));
      //  }
        
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php
    $this->RubricsView->defaultNoOfColumns = $totalColumns;

    echo $this->Form->create($modelName, array(
        //'url' => array('controller' => 'Quality','plugin'=>'Quality'),
        'type' => 'file',
        'novalidate' => true,
        'inputDefaults' => array('label' => false, 
        'div' => array('class' => 'input_wrapper'), 
        'autocomplete' => 'off')
    ));
    ?>
    
    <?php
    $lastId = 0;
    if (!empty($this->data['RubricsTemplateDetail'])) {
        $lastId = count($this->data['RubricsTemplateDetail']);
    }
        echo $this->Form->hidden('setting.last_id', array('value' => $lastId, 'id' => 'last_id',  'autocomplete' => 'off'));
    ?>
    
    <?php
    //$index = 0;
    echo $this->Utility->getListStart();
    if (!empty($this->data['RubricsTemplateDetail'])) {
        foreach ($this->data['RubricsTemplateDetail'] as $key => $item) {
            $item['columnHeader'] = $columnHeaderData;
            echo $this->Utility->getListRowStart($key, true);
            if (array_key_exists('RubricsTemplateSubheader', $item)) {
                echo $this->RubricsView->insertRubricHeader($item, $key);
            } else {
                echo $this->RubricsView->insertRubricQuestionRow($item, $key);
            }
            echo $this->Utility->getListRowEnd();
        }
    } 
    echo $this->Utility->getListEnd();
    ?>

    <div class="controls">
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right"/>
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'rubricsTemplatesSubheaderView', $rubricTemplateHeaderId), array('class' => 'btn_cancel btn_left')); ?>
    </div>

    <?php echo $this->Form->end(); ?>
</div>
<?php
//Addind auto insert function
if ($_edit) {
    if (!empty($columnHeaderData) && empty($this->data['RubricsTemplateDetail'])) {
?>
<script type="text/javascript">
    <?php echo 'rubricsTemplate.initHeader(' . $rubricTemplateHeaderId . ');'; ?>
    <?php echo 'rubricsTemplate.initRow(' . $rubricTemplateHeaderId . ');'; ?>
</script>
<?php
    }
}
?>