<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('attachments', 'stylesheet', array('inline' => false));
echo $this->Html->script('dashboard', false);
?>
<?php
echo $this->Html->script('setup_variables', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('Config.name'));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'index', 'Dashboard'), array('class' => 'divider'));
if($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'dashboardEdit', $id), array('class' => 'divider'));
}
if($_delete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'dashboardDelete', $id), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody'); ?>
<?php echo $this->element('alert'); ?>

<?php
$obj = $data['ConfigAttachment'];
$fileext = strtolower(pathinfo($obj['file_name'], PATHINFO_EXTENSION));

	$ext = array_key_exists($fileext, $arrFileExtensions) ? $arrFileExtensions[$fileext] : $fileext;
?>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('Config.file');?></div>
	<div class="col-md-6"><?php echo $obj['name'];?></div>
</div>
<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('Config.default');?></div>
	<div class="col-md-6">
		<?php if($obj['active'] > 0){ ?>
			<?php echo $this->Label->get('general.yes');?>
		<?php }else{ ?>
			<?php echo $this->Label->get('general.no');?>
		<?php } ?>
	</div>
</div>
<div class="row">
	<div style="overflow:hidden;width:<?php echo $image['width']; ?>px;height:<?php echo $image['height']; ?>px;" >
        <?php 
             // echo $this->Html->image($image['imagePath'], array(
            $leftPos = "-" . $image['x'];
            if($lang_dir=='rtl'){
                $leftPos = $image['x'];
            }
             echo $this->Html->image(array("controller" => "Config", "action" => "fetchImage", $obj["id"]), array(
                'style' => "width:initial;height:initial;position:relative;top:-{$image['y']}px;left:{$leftPos}px;"
            ));
        ?>
	</div>
</div>

<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('Config.file_type');?></div>
	<div class="col-md-6"><?php echo ($fileext == 'jpg')? __('JPEG'): strtoupper(__($fileext)); ?></div>
</div>

<div class="row">
	<div class="col-md-3"><?php echo $this->Label->get('Config.uploaded_on');?></div>
	<div class="col-md-6"><?php echo $obj['created'];?></div>
</div>
<?php $this->end(); ?>