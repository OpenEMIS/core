<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="messageView" class="content_wrapper">
    <h1>
        <span><?php echo __('Messages'); ?></span>
		<?php
		$data = $obj[0]['SmsMessage'];
		echo $this->Html->link(__('List'), array('action' => 'messages'), array('class' => 'divider'));
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'messagesEdit', $data['id']), array('class' => 'divider'));
		}
		if($_delete) {
			echo $this->Html->link(__('Delete'), array('action' => 'messagesDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
		}
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <div class="row">
		<div class="label"><?php echo __('Message'); ?></div>
		<div class="value"><?php echo $data['message']; ?></div>
	</div>

    <div class="row">
        <div class="label"><?php echo __('Enabled'); ?></div>
        <div class="value"><?php echo $this->Utility->checkOrCrossMarker($data['enabled']==1); ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Order'); ?></div>
         <div class="value"><?php echo $data['order']; ?></div>
    </div>

     <div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($obj[0]['ModifiedUser']['first_name'] . ' ' . $obj[0]['ModifiedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $data['modified']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($obj[0]['CreatedUser']['first_name'] . ' ' . $obj[0]['CreatedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $data['created']; ?></div>
    </div>
    <div class="controls">
        <div style="text-align:left;padding-bottom:10px;color:#666666;font-size:10px;"><?php echo __('Note: Please clear the Responses page as existing responses may no longer match the updated Messages.');?></div>
    </div>
    
</div>
