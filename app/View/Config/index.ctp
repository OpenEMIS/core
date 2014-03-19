<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('configuration', 'stylesheet', array('inline' => false));
$arrOptions = array('date_format' => array(
						'Y-m-d' => date('Y-m-d'),
						'd-M-Y' => date('d-M-Y'),
						'd-m-Y' => date('d-m-Y'),
						'd/m/Y' => date('d/m/Y'),
						'm/d/Y' => date('m/d/Y'),
						'd F Y' => date('d F Y'), 
						'F d, Y' => date('F d, Y'), 
						'dS F Y' => date('dS F Y')
					),
					'language' =>array(
						'ara' => 'العربية',
						'chi' => '中文',
						'eng' => 'English',
						'fre' => 'Français',
						'rus' => 'русский',
						'spa' => 'español'
					),
					'yearbook_orientation' => array(
						'P' => 'Portrait',
						'L' => 'Landscape'
					),
					'yesno' => array(0 => __('No'), 1 => __('Yes'))
				);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="config" class="content_wrapper">
	<h1>
		<span><?php echo __('System Configurations'); ?></span>
		<?php 
		if($_edit) {
			echo $this->Html->link(__('Edit'), '/Config/edit', array('class' => 'divider'));
		}
		if($_view_dashboard) {
			echo $this->Html->link(__('Dashboard Image'), array('action' => 'dashboard'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<!-- Items -->
	<?php
		if(isset($items)) {
			// pr($items);
			foreach($items as $key => $element){ 
			// pr($element);
				if(isset($element) && sizeof($element) > 0) { 
	?>
	<fieldset class="section_break">
		<legend><?php echo __(ucwords($key)); ?></legend>
		
		<div class="table">
			<div class="table_body">
		<?php 
		foreach($element as $innerKey => $innerElement){ 
				$item = $innerElement;
				if($item['name'] == 'test_connection') continue;
		?>
			<div class="table_row <?php echo ($key+1)%2==0? 'even':''; ?>">
				<div class="table_cell cell_item_name"><?php echo __($item['label']); ?></div>
		<?php if(stristr($item['name'], 'date_format')){ ?>
				<div class="table_cell"><?php
				echo date(empty($item['value'])? $item['default_value']:$item['value']); ?></div>
		<?php }elseif(stristr($item['name'], 'time_format')){ ?>
				<div class="table_cell"><?php echo date($item['value']); ?></div>
		<?php }elseif($item['name'] == 'language'){ ?>
				<div class="table_cell"><?php echo $arrOptions['language'][$item['value']]; ?></div>
		<?php }elseif(stristr($item['name'], 'yearbook_school_year')){ ?>
				<div class="table_cell"><?php echo $school_years[$item['value']]; ?></div>
		<?php }elseif(stristr($item['name'], 'yearbook_orientation')){ ?>
				<div class="table_cell"><?php echo $arrOptions['yearbook_orientation'][$item['value']]; ?></div>
		<?php }elseif(stristr($item['name'], 'yearbook_publication_date')){ ?>
				<div class="table_cell"><?php echo $this->Utility->formatDate($item['value']); ?></div>
		<?php }elseif(stristr($item['name'], 'yearbook_logo')){ ?>
				<div class="table_cell">
				<?php 
				if ($item['hasYearbookLogoContent']) {
		    		echo $this->Html->image("/Config/fetchYearbookImage/{$item['value']}", array('class' => 'profile_image', 'alt' => '90x115')); 
				}
		    	?>
				</div>	
		<?php }elseif(stristr($item['name'], 'student_prefix') || stristr($item['name'], 'teacher_prefix') || stristr($item['name'], 'staff_prefix')){ ?>
                <div class="table_cell">
                <?php 
				$val = '';
                if(substr($item['value'], -1)>0) {
                    $val = str_replace(",","",substr($item['value'],0,-1));
					//echo __($val==''? 'Enabled' : 'Enabled ('.$val.')');
					echo '<div>';
					echo __('Enabled');
					if($val!=''){
						echo ' ';
						echo __('('.$val.')');
						echo '';
					}
                                        echo "</div>";
                }
                ?>
                </div>		
        <?php }elseif(stristr($item['name'], 'country_id')){ ?>
				<div class="table_cell"><?php echo $countries[$item['value']]; ?></div>
		<?php }elseif(stristr($item['type'], 'Wizard')){ ?>
				<div class="table_cell"><?php echo $wizardOptions[$item['value']]; ?></div>
		<?php }elseif(stristr($item['name'], 'language_menu')){ ?>
				<div class="table_cell"><?php echo $arrOptions['yesno'][$item['value']]; ?></div>
        <?php }else{ ?>
				<div class="table_cell"><?php echo $item['value']; ?></div>
		<?php } ?>
			</div>
		<!-- 
		<div class="row">
			<div class="label"><?php echo $item['name']; ?></div>
			<div class="value" type="text" name="name"><?php echo $item['value']; ?></div>
		</div> -->
		<?php } ?>
		
			</div>
		</div>
	</fieldset>
		<?php 
				}
			}
		} 
		?>
</div>