<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
if($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'feeAdd'), array('class' => 'divider', 'id'=>'add'));
}
$this->end();

$this->start('contentBody');
echo $this->element('templates/year_options', array('url' => $_action));
?>
<?php if(isset($programmes)) { ?>
    <?php foreach($programmes as $programme){ ?>
    <fieldset class="section_group">
    <legend><?php echo $programme['education_programme_name']; ?></legend>
    <div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead url="<?php echo $this->params['controller'];?>/session/">
            <tr>
                <th>
                    <span class="left"><?php echo __('Grade'); ?></span>
                </th>
                <th>
                    <span class="left"><?php echo __('Fees'); ?></span>
                </th>
            </tr>
       </thead>
        <tbody>
        	<?php 
            if(!empty($data)){ 
            foreach($data[$programme['education_programme_id']] as $id=>$val) {  ?>
            <tr row-id="<?php echo $val['id']; ?>">
            	<td class="table_cell"><?php echo $this->Html->link($val['grade'], array('action' => 'feeView', $val['id']), array('escape' => false)); ?></td>
                <td class="table_cell"><?php echo $val['total_fee']; ?></td>
            </tr>
           <?php } 
            }
           ?>
        </tbody>
    </table>
    </div>
    </fieldset>
    <?php } ?>
<?php } ?>
<?php $this->end(); ?>  
