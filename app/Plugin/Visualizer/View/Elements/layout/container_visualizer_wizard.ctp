<div class="body_title"><?php echo __($bodyTitle); ?></div>
<div class="body_content">
	<?php echo $this->element('breadcrumb'); ?>
	<div id="<?php echo $this->fetch('contentId'); ?>" style='padding-bottom: 20px' class="content_wrapper <?php echo $this->fetch('contentClass'); ?>">
		<h1>
			<span><?php echo $this->fetch('contentHeader'); ?></span>
			<?php echo $this->fetch('contentActions'); ?>
		</h1>
		<?php
		echo $this->element('alert');
		echo $this->element('layout/wizardNav', $tabs, array('plugin' => 'Visualizer'));
		echo $this->fetch('contentBody');
		
		if (!empty($prevPg) || !empty($nextPg) || !empty($showVisualizeBtn)) :
		?>
		<div class="visualizer-control-group center">
			<?php
			if (!empty($prevPg)) {
				$btn_prev = '<a class="btn_cancel btn_right" href="/OpenEmisv2/Visualizer/' . $prevPg . '">' . __('Previous') . '</a>'; //$this->Html->link( __('Previous'), $this->Html->url(array("controller" => "Visualizer", "action" => $prevPg, 'plugin' => 'Visualizer')), array('class' => 'btn_cancel btn_left'));
				echo $btn_prev;
			}
			
			if (!empty($nextPg)) {
				$btn_next = $this->Form->button(__('Next'), array('class' => 'btn_save btn_left', 'type' => 'button', 'div' => false, 'onclick' => 'Visualizer.formSubmit()'));
				echo $btn_next;
			}
			
			if (!empty($showVisualizeBtn)) {
				$btn_visualize = $this->Form->button(__('Visualize'), array('class' => 'btn_save btn_left', 'type' => 'button', 'div' => false, 'onclick' => 'Visualizer.visualizeData(this)', 'url' => 'Visualizer/visualization/table'));
				echo $btn_visualize;
			}
			
			?>
		</div>
		<?php endif; ?>
	</div>
</div>