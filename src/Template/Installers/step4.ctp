<?php
$url = '/school/api/installers/';
?>

<div class="starter-template">
	<h1>Account setup</h1>
	<p>In order to access OpenEMIS School application, you will need to create an user account.</p>
    <div class="row">
        <div class="col-md-12">

        <?php
            echo $this->Form->create($superAdminCreation, ['class' => 'form-horizontal']);
        ?>
        <div class="form-group">
        <?php
            echo $this->Form->input('username', ['label' => ['class' => 'col-sm-5 control-label', 'text' => 'OpenEMIS School Login'], 'class' => 'form-control', 'value' => 'oe_school']);
        ?>
        </div>
        <div class="form-group">
        <?php
            echo $this->Form->input('password', ['label' => ['class' => 'col-sm-5 control-label'], 'class' => 'form-control', 'type' => 'password']);
        ?>
        </div>
        <div class="form-group">
        <?php
            echo $this->Form->input('confirm_password', ['label' => ['class' => 'col-sm-5 control-label'], 'class' => 'form-control', 'type' => 'password']);
        ?>
        </div>
        <div class="form-group">
                <div class="col-sm-5 control-label"></div>
                <div class="col-sm-offset-5 col-sm-10">
                <?= $this->Form->button('Back', ['type' => 'button', 'class' => 'btn btn-info', 'onclick' => "window.location.href='step3'"])?>
                <?= $this->Form->button('Next', ['type' => 'submit', 'class' => 'btn btn-success'])?>
                </div>

        </div>

        <?php
            echo $this->Form->end();
        ?>
    </div>
    </div>
</div>