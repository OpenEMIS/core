<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/OlapCube/css/olap', 'stylesheet', array('inline' => false));
echo $this->Html->script('/OlapCube/js/olap', false);


$this->extend('/Elements/layout/container');
$this->assign('contentHeader',__($subheader));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'olapReport'), array('class' => 'divider'));
echo $this->Html->link(__('Export to Excel'), array('action' => 'olapReportExport'), array('class' => 'divider'));    
$this->end();
$this->start('contentBody'); ?>
<?php 
  if(isset($data)) { ?>
  <div class="table" style="overflow:auto;width:670px;display:block;">
	<table style="width:99%;border:none;" cellpadding="0" cellspacing="0" border="0">
      <thead>
    	  <tr>
          <td class="table_cell"><?php echo key($data); ?></td>
          <?php 
          foreach($column as $col){ ?>
          <td class="table_cell"><?php echo $col;?></td>
          <?php } ?>
        </tr>
      </thead>
      <tbody>
		<?php 
    $i = 0;
    foreach($data as $key=>$value){ 
        $i++;
       if($i==1){
          continue;
        }
      ?>
       <tr>
        	<td class="table_cell"><?php echo $key; ?></td>
          <?php foreach($column as $col){
              $val = '';
              if(array_key_exists($col, $value)){
                $val = $value[$col];
              }?>
              <td><?php echo $val; ?></td>
            <?php
            }
           ?>
            
        </tr>
     	<?php } ?>
    </tbody>
    </table>
  </div>
  <?php } ?>

  <?php $this->end(); ?>