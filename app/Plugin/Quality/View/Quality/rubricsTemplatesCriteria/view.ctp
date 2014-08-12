<?php
echo $this->Html->css('/Quality/css/colorpicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Quality/js/colorpicker', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>
<?php 
$obj = $data[$model]; 

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Utility->ellipsis(__($subheader), 50));
$this->start('contentActions');
 echo $this->Html->link($this->Label->get('general.back'), array('action' => 'rubricsTemplatesCriteria', $id, $rubricTemplateHeaderId), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'rubricsTemplatesCriteriaEdit', $id, $rubricTemplateHeaderId, $obj['id']), array('class' => 'divider'));
}

if ($_delete && !$disableDelete) {
    echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'rubricsTemplatesCriteriaDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody'); 
?>
    <div class="row">
        <div class="col-md-3"><?php echo __('Name'); ?></div>
        <div class="col-md-6"><?php echo $obj['name']; ?></div>
    </div>
    <div class="row">
        <div class="col-md-3"><?php echo __('Weighting'); ?></div>
        <div class="col-md-6"><?php echo $obj['weighting']; ?></div>
    </div>
    <div class="row">
        <div class="col-md-3"><?php echo __('Color'); ?></div>
        <div class="col-md-6"><div id="colorSelector"><div style="background-color:#<?php echo $obj['color']; ?>"></div></div></div>
    </div>

    <div class="row">
        <div class="col-md-3"><?php echo __('Modified by'); ?></div>
        <div class="col-md-6"><?php echo trim($data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name']); ?></div>
    </div>

    <div class="row">
        <div class="col-md-3"><?php echo __('Modified on'); ?></div>
        <div class="col-md-6"><?php echo $obj['modified']; ?></div>
    </div>

    <div class="row">
        <div class="col-md-3"><?php echo __('Created by'); ?></div>
        <div class="col-md-6"><?php echo trim($data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name']); ?></div>
    </div>

    <div class="row">
        <div class="col-md-3"><?php echo __('Created on'); ?></div>
        <div class="col-md-6"><?php echo $obj['created']; ?></div>
    </div>
<?php $this->end(); ?>