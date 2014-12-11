<?php 
$firstName = AuthComponent::user('first_name');
$lastName = AuthComponent::user('last_name');

$dropdown = array(
	'About' => array('url' => array('plugin' => false, 'controller' => 'Home', 'action' => 'support'), 'icon' => 'fa-info-circle'),
	'Preferences' => array('url' => array('plugin' => false, 'controller' => 'Preferences'), 'icon' => 'fa-cog'),
	'_divider',
	'Logout' => array('url' => array('plugin' => false, 'controller' => 'Security', 'action' => 'logout'), 'icon' => 'fa-power-off')
);

$home = array('controller' => 'Home', 'action' => 'index');
?>

<div class="header-side-nav">
	<div class="username"><?php echo sprintf('%s %s', $firstName, $lastName) ?></div>

	<div class="btn-group">
        <a class="btn dropdown-toggle" href="<?php echo $this->Html->url($home) ?>">
            <i class="fa fa-home fa-lg"></i>
        </a>
    </div>

	<?php echo $this->element('layout/product_list') ?>

	<div class="btn-group">
		<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
			<i class="fa fa-caret-down fa-lg"></i>
		</a>

		<ul aria-labelledby="dropdownMenu" role="menu" class="dropdown-menu">

			<?php
			foreach ($dropdown as $name => $attr) {
				if ($name != '_divider') {
					echo '<li>';
					echo '<a href="' . $this->Html->url($attr['url']) . '">';
					echo '<i class="fa ' . $attr['icon'] . '"></i>';
					echo '<span>' . __($name) . '</span>';
					echo '</a>';
					echo '</li>';
				} else {
					echo '<li class="divider"></li>';
				}
			}
			?>
			
		</ul>
	</div>
</div>
