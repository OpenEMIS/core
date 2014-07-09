<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
if($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'feeStudentAdd'), array('class' => 'divider', 'id'=>'add'));
}
$this->end();

$this->start('contentBody');
echo $this->element('templates/year_options', array('url' => $_action));
?>
<?php if(isset($programmes)) { ?>
    <?php foreach($programmes as $programme){ ?>
    <fieldset class="section_group">
    <legend><?php echo $programme['education_programme_name']; ?></legend>

      <?php 
      if(isset($programme['education_grades'])){
      foreach($programme['education_grades'] as $key=>$val){ ?>
            <fieldset class="section_group">
            <legend><?php echo $val; ?></legend>
            <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered">
                <thead url="<?php echo $this->params['controller'];?>/session/">
                    <tr>
                        <th>
                            <span class="left"><?php echo __('ID'); ?></span>
                        </th>
                        <th>
                            <span class="left"><?php echo __('Name'); ?></span>
                        </th>
                        <th>
                            <span class="left"><?php echo __('Paid'); ?></span>
                        </th>
                          <th>
                            <span class="left"><?php echo __('Outstanding'); ?></span>
                        </th>
                    </tr>
               </thead>
                <tbody>
                	<?php 
                    if(!empty($data)){ 
                    foreach($data[$programme['education_programme_id'].'_'.$key] as $id=>$val) {  ?>
                    <tr row-id="<?php echo $val['id']; ?>">
                        <td class="table_cell"><?php echo $val['identification_no']; ?></td>
                    	<td class="table_cell"><?php echo $this->Html->link($val['name'], array('action' => 'feeView', $val['id']), array('escape' => false)); ?></td>
                        <td class="table_cell"><?php echo $val['total_paid']; ?></td>
                        <td class="table_cell"><?php echo $val['total_outstanding']; ?></td>
                    </tr>
                   <?php } 
                    }
                   ?>
                </tbody>
            </table>
            </div>
        </fieldset>
        <?php 
            }
        }
        ?>
    </fieldset>
    <?php } ?>
<?php } ?>
<?php $this->end(); ?>  
