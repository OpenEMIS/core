<div class="starter-template">
	<h1>Installation Completed</h1>
	<p>You have successfully installed OpenEMIS School. Please click Start to launch OpenEMIS School.</p>


    <div class="row">
        <div class="col-md-12">
            <?php
                echo $this->Form->create(null, ['class' => 'form-horizontal', 'url' => '/Users/postLogin']);
            ?>
            <div class="form-group">
            <?php
                echo $this->Form->hidden('username', ['value' => 'admin']);
                echo $this->Form->hidden('password', ['value' => 'demo']);
                echo $this->Form->hidden('submit', ['value' => 'login']);
            ?>
            </div>
            <div class="form-group">
                    <div class="col-sm-5 control-label"></div>
                    <div class="col-sm-offset-5 col-sm-10">
                    <?= $this->Form->button('Start', ['type' => 'submit', 'class' => 'btn btn-success'])?>
                    </div>

            </div>
            <?php
                echo $this->Form->end();
            ?>
        </div>
    </div>
</div>