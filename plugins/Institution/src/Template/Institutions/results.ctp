<?php
$this->extend('OpenEmis./Layout/Panel');
$this->start('panelBody');
?>

	<div class="scrolltabs">
		<scrollable-tabset show-tooltips="false" show-drop-down="false">
			<uib-tabset justified="true">
				<uib-tab heading="Tab 1">
					<h4>This is content for tab 1: Sample Text below.</h4> 
		        	<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>
				</uib-tab>
				<uib-tab heading="Tab 2">
					<h4>This is content for tab 2: Sample Text below.</h4> 
		        	<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>
				</uib-tab>
			</uib-tabset>
			<div class="tabs-divider"></div>
		</scrollable-tabset>
	</div>

<?php
$this->end();
?>
