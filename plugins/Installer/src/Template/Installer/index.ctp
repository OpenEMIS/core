<div style="width: 50%; margin: 20px auto">
    <div class="wizard" data-initialize="wizard" id="wizard">

        <div class="steps-container">
            <ul class="steps" style="margin-left: 0">
                <li data-step="1" class="active">
                    <div class="step-wrapper">
                        <span><i class="fa fa-lg fa-hand-o-up"></i></span>
                        Step 1: License
                        <span class="chevron"></span>
                    </div>
                </li>
                <li data-step="2" class="">
                    <div class="step-wrapper">
                        <span><i class="fa fa-lg fa-arrows-h"></i></span>
                        Step 2: Connect Database
                        <span class="chevron"></span>
                    </div>
                </li>
                <li data-step="3" class="">
                    <div class="step-wrapper">
                        <span><i class="fa fa-lg fa-cube"></i></span>
                        Step 3: Create Account
                        <span class="chevron"></span>
                    </div>
                </li>
                <li data-step="4" class="">
                    <div class="step-wrapper">
                        <span><i class="fa fa-lg fa-map-marker"></i></span>
                        Step 4: Launch Application
                        <span class="chevron"></span>
                    </div>
                </li>
            </ul>
        </div>

        <div class="actions top">
            <button type="button" class="btn btn-default btn-prev" disabled="disabled">Previous</button>
            <button type="button" class="btn btn-default btn-next" data-last="Complete">Next</button>
        </div>

        <div class="step-content">
            <div class="step-pane sample-pane active" data-step="1">
                <div style="text-align: center; padding: 0 150px">
                    <h1>Welcome to OpenEMIS School</h1>
                    <h2 style="margin-top: 20px">OPENEMIS SCHOOL LICENSE LAST UPDATED ON 2014-01-30</h2>
                    <h3 style="margin-top: 20px">OpenEMIS SCHOOL</h3>
                    <h3>Open School Management Information System</h3>

                    <p style="margin-top: 30px">
                    Copyright © 2017 KORD IT. This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or any later version. This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program. If not, see GNU. For more information please wire to contact@openemis.org.

                    By clicking Next, you agree to the terms stated in the OpenEmis School License Agreement above.
                    </p>
                </div>
            </div>

            <div class="step-pane sample-pane" data-step="2">
                <div style="text-align: center; padding: 0 150px">
                    <h1>Setting Environment</h1>
                    <p>All fields are required and case sensitive.</p>
                    <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                        <div class="input string">
                            <label>Database Server Host</label>
                            <input type="text" value="localhost">
                        </div>
                        <div class="input string">
                            <label>Database Server Port</label>
                            <input type="text" value="3306">
                        </div>
                        <div class="input string">
                            <label>Admin Username</label>
                            <input type="text" value="root">
                        </div>
                        <div class="input string">
                            <label>Admin Password</label>
                            <input type="text">
                        </div>
                    </form>
                </div>
            </div>

            <div class="step-pane sample-pane" data-step="3">
                <div style="text-align: center; padding: 0 150px">
                    <h1>Account setup</h1>
                    <p>In order to access OpenEMIS School application, you will need to create an user account.</p>

                    <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                        <div class="input string">
                            <label>Account Username</label>
                            <input type="text" value="admin" disabled="disabled">
                        </div>
                        <div class="input string">
                            <label>Account Password</label>
                            <input type="password">
                        </div>
                        <div class="input string">
                            <label>Retype Password</label>
                            <input type="password">
                        </div>
                    </form>
                </div>
            </div>

            <div class="step-pane sample-pane" data-step="4">
                <div style="text-align: center; padding: 0 150px">
                    <h1>Installation Completed</h1>
                    <p>You have successfully installed OpenEMIS School. Please click Start to launch OpenEMIS School.</p>
                    <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                        <div class="form-buttons">
                            <a href="" type="submit" class="btn btn-default" style="text-">Start</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="actions bottom">
            <button type="button" class="btn btn-default btn-prev" disabled="disabled">Previous</button>
            <button type="button" class="btn btn-default btn-next">Next</button>
        </div>
    </div>
</div>
