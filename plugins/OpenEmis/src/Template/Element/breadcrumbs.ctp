<ul class="breadcrumb panel-breadcrumb">
	<li><a href="/Dashboard"><i class="fa fa-home"></i></a></li>
	
	<?php foreach($_breadcrumbs as $b) : ?>
	<li>
		<?php
		$title = $this->Text->truncate(__($b['title']), '30', ['ellipsis' => '...', 'exact' => false]);
		echo $b['selected'] ? $title : $this->Html->link($title, $b['link']['url']);
		?>
	</li>
	<?php endforeach ?>
</ul>
