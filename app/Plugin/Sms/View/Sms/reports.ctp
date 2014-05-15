<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Reports'));
$this->start('contentActions');
$this->end();

$this->start('contentBody');
?>
<?php if(count($data)>0) { ?>
       <?php foreach($data as $module => $arrVals) {   ?>
        <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered">
            <thead action="Reports/<?php echo $this->action;?>/">
                <tr class="table_head">
    				<td class="table_cell col_name"><?php echo __('Name'); ?></td>
    				<td class="table_cell" style="width:100px"><?php echo __('Types'); ?></td> 
                </tr> 
            </thead>
            <tbody class="table_body">
            <?php foreach($arrVals as $arrTypVals) { ?>
                <tr row-id="<?php echo $arrTypVals['name']; ?>">
					<td class="table_cell col_name"><?php echo __($arrTypVals['name']);?></td>
					<td class="table_cell"  style="width:100px;text-align: center">
						<?php foreach($arrTypVals['types'] as $val){?>
                        <?php if($_execute){ ?>
						   <?php 
						   echo $this->Html->link( __($val),
								array('controller' => 'Sms', 'action' =>'genReport', $arrTypVals['name'], $val));
						  ?>
                         <?php }else{ ?>
                            <?php echo $val;?>
                         <?php } 
                            }
                         ?>
					</td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
       </div>
        <?php } ?>
<?php } ?>
<?php $this->end(); ?>  