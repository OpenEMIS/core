<?php
echo $this->Html->css('timeline', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('History'));
$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('action' => 'view'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>

<style type="text/css">
.timeline {
	list-style: none;
	padding: 20px 0 20px;
	position: relative;
	margin-left: 50px;
	margin-top: 20px;
}

.timeline:before {
	top: 0;
	bottom: 0;
	position: absolute;
	content: " ";
	width: 3px;
	background-color: #eeeeee;
	margin-left: -1.5px;
	margin-bottom: 40px;
}

.timeline > li {
  margin-bottom: 10px;
  position: relative;
}
.timeline > li:before,
.timeline > li:after {
  content: " ";
  display: table;
}
.timeline > li:after {
  clear: both;
}
.timeline > li:before,
.timeline > li:after {
  content: " ";
  display: table;
}
.timeline > li:after {
  clear: both;
}

.timeline > li > .timeline-panel {
  float: left;
  border: 1px solid #d4d4d4;
  border-radius: 5px;
  padding: 15px;
  position: relative;
  -webkit-box-shadow: 0 1px 6px rgba(0, 0, 0, 0.175);
  box-shadow: 0 1px 6px rgba(0, 0, 0, 0.175);
}
.timeline > li > .timeline-panel:before {
  position: absolute;
  top: 26px;
  right: -15px;
  display: inline-block;
  border-top: 15px solid transparent;
  border-left: 15px solid #ccc;
  border-right: 0 solid #ccc;
  border-bottom: 15px solid transparent;
  content: " ";
}
.timeline > li > .timeline-panel:after {
  position: absolute;
  top: 27px;
  right: -14px;
  display: inline-block;
  border-top: 14px solid transparent;
  border-left: 14px solid #fff;
  border-right: 0 solid #fff;
  border-bottom: 14px solid transparent;
  content: " ";
}

.timeline > li > .timeline-badge {
  color: #fff;
  width: 50px;
  height: 50px;
  line-height: 50px;
  font-size: 1.4em;
  text-align: center;
  position: absolute;
  top: 16px;
  margin-left: -25px;
  background-color: #999999;
  z-index: 100;
  border-top-right-radius: 50%;
  border-top-left-radius: 50%;
  border-bottom-right-radius: 50%;
  border-bottom-left-radius: 50%;
}
.timeline > li.timeline-inverted > .timeline-panel {
	margin-left: 45px;
}
.timeline > li.timeline-inverted > .timeline-panel:before {
	border-left-width: 0;
	border-right-width: 15px;
	left: -15px;
	right: auto;
}
.timeline > li.timeline-inverted > .timeline-panel:after {
	border-left-width: 0;
	border-right-width: 14px;
	left: -14px;
	right: auto;
}
.timeline-badge.primary {
  background-color: #2e6da4 !important;
}
.timeline-badge.success {
  background-color: #3f903f !important;
}
.timeline-badge.warning {
  background-color: #f0ad4e !important;
}
.timeline-badge.danger {
  background-color: #d9534f !important;
}
.timeline-badge.info {
  background-color: #5bc0de !important;
}
.timeline-title {
  margin-top: 0;
  color: inherit;
}
.timeline-body > p,
.timeline-body > ul {
  margin-bottom: 0;
}
.timeline-body > p + p {
  margin-top: 5px;
}
</style>

<!--div class="row">
	<div class="col-md-4">
	<?php
	echo $this->Form->input('field', array(
		'label' => false,
		'class' => 'form-control',
		'options' => array(
			'' => 'All', 'name' => 'Name', 'institution_site_provider_id' => 'Provider'
		)
	));
	?>
	</div>
</div-->

<ul class="nav nav-pills">
	<li role="presentation" class="active"><a href="#">All</a></li>
	<li role="presentation"><a href="#">Name</a></li>
	<li role="presentation"><a href="#">Code</a></li>
	<li role="presentation"><a href="#">Sector</a></li>
	<li role="presentation"><a href="#">Provider</a></li>
	<li role="presentation"><a href="#">Type</a></li>
	<li role="presentation"><a href="#">Ownership</a></li>
	<li role="presentation"><a href="#">Gender</a></li>
	<li role="presentation"><a href="#">Status</a></li>
	<li role="presentation"><a href="#">Address</a></li>
	<li role="presentation"><a href="#">Postal Code</a></li>
</ul>

<ul class="timeline">
	<li class="timeline-inverted">
		<div class="timeline-badge info"><i class="fa fa-edit"></i></div>
		<div class="timeline-panel">
			<div class="timeline-heading">
				<h5 class="timeline-title"><i>2015-01-01 09:00am</i></h5>
			</div>
			<div class="timeline-body">
				Jeff has updated [<span style="color: #5B8BAF">Status</span>] from '<span style="color: red">Open</span>' to '<span style="color: green">Suspended</span>'.
			</div>
		</div>
	</li>

	<li class="timeline-inverted">
		<div class="timeline-badge info"><i class="fa fa-edit"></i></div>
		<div class="timeline-panel">
			<div class="timeline-heading">
				<h5 class="timeline-title"><i>2015-01-01 08:30am</i></h5>
			</div>
			<div class="timeline-body">
				Umairah has updated [<span style="color: #5B8BAF">Name</span>] from '<span style="color: red">Umairah School</span>' to '<span style="color: green">Umairah School of Technology</span>'.
			</div>
		</div>
	</li>

	<li class="timeline-inverted">
		<div class="timeline-badge info"><i class="fa fa-edit"></i></div>
		<div class="timeline-panel">
			<div class="timeline-heading">
				<h5 class="timeline-title"><i>2015-01-01 08:00am</i></h5>
			</div>
			<div class="timeline-body">
				Umairah has updated [<span style="color: #5B8BAF">Provider</span>] from '<span style="color: red">Government</span>' to '<span style="color: green">Public</span>'.
			</div>
		</div>
	</li>

	<li class="timeline-inverted">
		<div class="timeline-badge info"><i class="fa fa-edit"></i></div>
		<div class="timeline-panel">
			<div class="timeline-heading">
				<h5 class="timeline-title"><i>2015-01-01 07:00am</i></h5>
			</div>
			<div class="timeline-body">
				Umairah has updated [<span style="color: #5B8BAF">Gender</span>] from '<span style="color: red">Boys</span>' to '<span style="color: green">Mixed</span>'.
			</div>
		</div>
	</li>

	<li class="timeline-inverted">
		<div class="timeline-badge success"><i class="fa fa-plus-square"></i></div>
		<div class="timeline-panel">
			<div class="timeline-heading">
				<h5 class="timeline-title"><i>2015-01-01 06:00am</i></h5>
			</div>
			<div class="timeline-body">
				Umairah has created [<span style="color: green">Umairah School</span>]'.
			</div>
		</div>
	</li>
</ul>

<?php $this->end(); ?>
