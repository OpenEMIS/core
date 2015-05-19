<?php 
// $firstName = AuthComponent::user('first_name');
// $lastName = AuthComponent::user('last_name');
$firstName = '';
$lastName = '';
$userId = 1;

$dropdown = [
	'About' => [
		'url' => ['plugin' => false, 'controller' => 'Config', 'action' => 'about'], 
		'icon' => 'fa-info-circle'
	],
	'Preferences' => [
		'url' => ['plugin' => false, 'controller' => 'Admin', 'action' => 'edit', $userId], 
		'icon' => 'fa-cog'
	],
	'_divider',
	'Logout' => [
		'url' => ['plugin' => false, 'controller' => 'Users', 'action' => 'logout'],
		'icon' => 'fa-power-off'
	]
];

$home = ['plugin' => false, 'controller' => 'Users', 'action' => 'index'];
?>

<div class="header-navigation">
	<div class="username">
		<span><?php echo sprintf('%s %s', $firstName, $lastName) ?>System Administrator</span>
		<a class="btn" data-toggle="tooltip" data-placement="bottom" title="User Role: Principal"><i class="kd-role"></i></a>
	</div>

	<div class="btn-group">
        <a class="btn" href="<?php echo $this->Url->build($home) ?>">
            <i class="fa fa-home"></i>
        </a>
    </div>

    <!--?php echo $this->element('Localization.languages') ?-->
	<?php echo $this->element('OpenEmis.product_list') ?>

	<div class="btn-group">
		<button class="btn dropdown-toggle" data-toggle="dropdown" href="#">
			<i class="fa kd-ellipsis"></i>
		</button>

		<ul aria-labelledby="dropdownMenu" role="menu" class="dropdown-menu more-menu">
			<div class="dropdown-arrow"><i class="fa fa-caret-up"></i></div>
			<div class="more-menu-item">
				<?php
				foreach ($dropdown as $name => $attr) {
					if ($name != '_divider') {
						echo '<li>';
						echo '<a class="fa ' . $attr['icon'] . '" href="' . $this->Url->build($attr['url']) . '">';
						echo '<span> ' . __($name) . '</span>';
						echo '</a>';
						echo '</li>';
					} else {
						echo '<li class="divider"></li>';
					}
				}
				?>
			</div>			
		</ul>
	</div>
</div>


