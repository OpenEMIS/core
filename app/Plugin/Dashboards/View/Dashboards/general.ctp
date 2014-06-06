<?php /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
//$this->assign('contentHeader', $header);
$this->assign('contentHeader', __(ucwords(key($data))));
$this->start('contentBody');

?>

       <?php foreach($data as $module => $arrVals) {   ?>
        <table class="table table-striped table-hover table-bordered">
			<thead class="table_head">
				<tr>
					<td class="table_cell col_name"><?php echo __('Name'); ?></td>
					<td class="table_cell col_desc"><?php echo __('Types'); ?></td>
				</tr> 
			</thead> 
			<tbody class="table_body">
					<?php 
						$ctr = 1;
						foreach ($arrTypVals as $key => $value) { 
					?>
					<tr class="table_row" row-id="<?php echo $value['id']; ?>">
						<td class="table_cell col_name"><?php echo __($value['name']);?></td>
						<td class="table_cell col_desc"><?php echo $this->Html->link(__($value['name']), array('action' => $this->action, $value['id']), array('escape' => false)); ?></td>
					</tr>
					<?php  $ctr++;  } ?>
			</tbody>
		</table>			
						
						
						
        <div class="table  full_width" action="Reports/<?php echo $this->action;?>/">
            <div class="table_head">
				<div class="table_cell col_name"><?php echo __('Name'); ?></div>
				<div class="table_cell" style="width:100px"><?php echo __('Types'); ?></div> 
            </div> 
            <div class="table_body">
            <?php foreach($arrVals as $arrTypVals) { ?>
                <div class="table_row" row-id="<?php echo $arrTypVals['name']; ?>">
					<div class="table_cell col_name"><?php echo __($arrTypVals['name']);?></div>
					<div class="table_cell"  style="width:100px;text-align: center">
						<?php foreach($arrTypVals['types'] as $val){?>
						   <?php 
						   echo $this->Html->link( __($val),
								array('action' => $actionName, $arrTypVals['name'], $val));
						   }?>
					</div>
                </div>
            <?php } ?>
            </div>
       </div>
        <?php } ?>

<?php $this->end();*/?>

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');
?>

<?php if (count($data) > 0) : ?>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo __('Name'); ?></th>
					<th style="width:100px"><?php echo __('Types'); ?></th> 
				</tr>
			</thead> 
			<tbody>
				<?php foreach ($data as $i => $obj) : ?>
					<tr>
						<td><?php echo __($obj['name']); ?></td>
						<td class="" style="width:100px;">
							<?php foreach ($obj['formats'] as $name => $action) {
								$url = array('action' => $action);
								echo $this->Html->link(strtoupper($name), $url);
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php
endif;
$this->end();
?>
