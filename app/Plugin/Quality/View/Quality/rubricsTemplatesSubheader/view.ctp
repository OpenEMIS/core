<?php
echo $this->Html->css('/Quality/css/rubrics', 'stylesheet', array('inline' => false));
echo $this->Html->script('Quality/js/rubrics', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Utility->ellipsis(__($subheader), 50));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'rubricsTemplatesHeader', $rubricTemplateId), array('class' => 'divider'));
        
if ($_edit) {
    if(empty($this->data['RubricsTemplateDetail'])){
        $linkName = 'Create Rubric Table';
    }
    else{
        $linkName = 'Edit';
    }
    echo $this->Html->link(__($linkName), array('action' => 'rubricsTemplatesSubheaderEdit', $rubricTemplateHeaderId), array('class' => 'divider'));
    
    echo $this->Html->link(__('Setup Criteria Column'), array('action' => 'rubricsTemplatesCriteria', $rubricTemplateId, $rubricTemplateHeaderId), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody'); ?>
<?php echo $this->element('alert'); ?>
<table class='rubric-table'>
    <?php
    $this->RubricsView->defaultNoOfColumns = $totalColumns;
    
    foreach ($this->data['RubricsTemplateDetail'] as $key => $item) {
        if (array_key_exists('RubricsTemplateSubheader', $item)) {
            echo $this->RubricsView->insertRubricTableHeader($item, $key, NULL);
        } else {
            $item['columnHeader'] = $columnHeaderData;
            echo $this->RubricsView->insertRubricTableQuestionRow($item, $key, NULL);
        }
    }
    ?>
</table>
<?php $this->end(); ?>
