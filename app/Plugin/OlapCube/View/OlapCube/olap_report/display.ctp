<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

?>

<?php echo $this->element('breadcrumb'); ?>

<div id="olap_report" class="content_wrapper edit add">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
            echo $this->Html->link(__('Back'), array('action' => 'olapReport'), array('class' => 'divider'));
          	echo $this->Html->link(__('Export to Excel'), array('action' => 'olapReportExport'), array('class' => 'divider'));
        
		?>
	</h1>
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

</div>