<?php require CONFIG . 'installer_mode_config.php'; ?>
<style type="text/css">
    html, body, body > div:nth-child(2), .wizard-wrapper, .startup-wizard {
        height: 100%;
    }

    .startup-wizard  {
        overflow: auto;
    }
</style>

<div class="wizard-wrapper">
    <div id="spinner" class="spinner-wrapper" style="display: none;">
        <div class="spinner-text">
            <div class="spinner lt-ie9"></div>
            <p><?= __('Loading'); ?> ...</p>
        </div>
    </div>
    <div class="wizard startup-wizard" data-initialize="wizard" id="wizard">

        <div class="steps-container">
            <ul class="steps" style="margin-left: 0">
                <li class="<?=$action == '1' ? 'active' : '' ?>">
                    <div class="step-wrapper">
                        <span><i class="fa fa-lg fa-hand-o-up"></i></span>
                        Step 1: License
                        <span class="chevron"></span>
                    </div>
                </li>
                <li class="<?=$action == '2' ? 'active' : '' ?>">
                    <div class="step-wrapper">
                        <span><i class="fa fa-lg fa-arrows-h"></i></span>
                        Step 2: Setting Up
                        <span class="chevron"></span>
                    </div>
                </li>
                <li class="<?=$action == '3' ? 'active' : '' ?>">
                    <div class="step-wrapper">
                        <span><i class="fa fa-lg fa-map-marker"></i></span>
                        Step 3: Launch Application
                        <span class="chevron"></span>
                    </div>
                </li>
            </ul>
        </div>

        <div class="step-content">
            <?= $this->element('OpenEmis.alert') ?>
            <div class="step-pane sample-pane <?=$action == '1' ? 'active' : '' ?>" data-restrict="1" data-step="1">
                <div class="step-pane-wrapper">
                    <h1>Welcome to <?= APPLICATION_NAME;  ?> </h1>
                    <p style="margin-top: 30px">
                    <?php 
                        if (file_exists(ROOT . DS . LICENSE)) {
                            echo file_get_contents(ROOT . DS . LICENSE); 
                        }
                    ?>
                    </p>

                    <p style="margin-top: 30px">
                    Copyright © 2017 KORD IT. This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or any later version. This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program. If not, see GNU. For more information please wire to contact@openemis.org.

                    By clicking Next, you agree to the terms stated in the <?=$productName?> License Agreement above.
                    </p>
                    <div class="form-group">
                        <a href=<?=$this->Url->build(['plugin' => 'Installer', 'controller' => 'Installer', 'action' => 'step2']); ?> type="submit" class="btn btn-default" onClick="(function(){document.querySelector('.spinner-wrapper').style.display='block';})();">Start</a>
                    </div>
                </div>
            </div>
            <?php
                if ($action == '2'):
            ?>


            <div class="step-pane sample-pane <?=$action == '2' ? 'active' : '' ?>" data-restrict="2" data-step="2">
                <div class="step-pane-wrapper">
                    <h1>Setting Environment</h1>
                    <p>All fields are required and case sensitive.</p>

                    <?php
                        echo $this->Form->create($databaseConnection, ['class' => 'form-horizontal']);
                    ?>
                    <div class="section-header">Database Connection Information</div>
                    <div class="clearfix">&nbsp;</div>
                    <?php
                        echo $this->Form->input('database_server_host', ['class' => 'form-control db-host', 'value' => 'localhost']);
                        echo $this->Form->input('database_server_port', ['class' => 'form-control db-port', 'value' => '3306']);
                        echo $this->Form->input('database_admin_user', ['label' => __('Admin User'), 'class' => 'form-control admin-user', 'value' => 'root']);
                        echo $this->Form->input('database_admin_password', ['label' => __('Admin Password'), 'class' => 'form-control admin-password', 'type' => 'password']);
                    ?>
                    <div class="section-header">Administrator Account</div>
                    <div class="clearfix">&nbsp;</div>
                    <?php
                        echo $this->Form->input('account_username', ['class' => 'form-control username', 'value' => 'admin', 'disabled' => true, 'required' => true]);
                        echo $this->Form->input('account_password', ['class' => 'form-control password', 'type' => 'password']);
                        echo $this->Form->input('retype_password', ['class' => 'form-control retype', 'type' => 'password']);
                    ?>
                    <div class="section-header">Country / Area Information</div>
                    <div class="clearfix">&nbsp;</div>
                    <?php
                        echo $this->Form->input('area_code', ['class' => 'form-control area-code', 'type' => 'text', 'maxlength' => '60', 'label' => 'Country Code']);
                        echo $this->Form->input('area_name', ['class' => 'form-control area-name', 'type' => 'text', 'maxlength' => '100', 'label' => 'Country Name']);
                    ?>
                    <div class="clearfix">&nbsp;</div>
                    <div class="">
                        <?= $this->Form->button('Next', ['type' => 'submit', 'class' => 'btn btn-default', 'onclick' => "(function(){
                            if (document.querySelector('.db-host').value === '' ||
                                document.querySelector('.db-port').value === '' ||
                                document.querySelector('.admin-user').value === '' ||
                                document.querySelector('.admin-password').value === '' ||
                                document.querySelector('.username').value === '' ||
                                document.querySelector('.password').value === '' ||
                                document.querySelector('.retype').value === '' ||
                                document.querySelector('.area-code').value === '' ||
                                document.querySelector('.area-name').value === '') {
                                    document.querySelector('.spinner-wrapper').style.display='none';
                            }
                            else {
                                document.querySelector('.spinner-wrapper').style.display='block';
                            }
                        })();"])?>
                    </div>
                    <?php
                        echo $this->Form->end();
                    ?>
                </div>
            </div>
            <?php
                elseif ($action == '3'):
            ?>
            <div class="step-pane sample-pane <?=$action == '3' ? 'active' : '' ?>" data-restrict="3" data-step="3">
                <div class="step-pane-wrapper">
                    <h1>Installation Completed</h1>
                    <p>You have successfully installed OpenSMIS. Please click Start to launch OpenSMIS.</p>
                    <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                        <div class="form-group">
                            <a href=<?=$this->Url->build(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']); ?> type="submit" class="btn btn-default" style="text-">Complete</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php
                endif;
            ?>
        </div>

        <div class="actions bottom">
        </div>
    </div>
</div>
