<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Directory.angular/directoryaddguardian/directory.directoryaddguardian.svc', ['block' => true]); ?>
<?= $this->Html->script('Directory.angular/directoryaddguardian/directory.directoryaddguardian.ctrl', ['block' => true]); ?>
<?= $this->Html->css('ControllerAction.../plugins/datepicker/css/bootstrap-datepicker.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/datepicker/js/bootstrap-datepicker.min', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/chosen/js/chosen.jquery.min.js', ['block' => true]); ?>

<link data-require="bootstrap@3.3.2" data-semver="3.3.2" rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" />
<script data-require="bootstrap@3.3.2" data-semver="3.3.2" src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
<script data-require="angularjs@1.4.9" data-semver="1.4.9" src="https://code.angularjs.org/1.4.9/angular.min.js"></script>
<script data-require="ui-bootstrap@*" data-semver="1.3.2" src="https://cdn.rawgit.com/angular-ui/bootstrap/gh-pages/ui-bootstrap-tpls-1.3.2.js"></script>
<!-- POCOR-7231 :: Start -->
<style>
    h2 {
    font-size: 20px;
    font-weight: 400;
}

h2, h3, h4, h5, h6 {
    font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;
    color: #333;
    margin: 10px 0;
}
    .page-header {
    display: block;
    width: 100%;
    padding: 0;
    clear: both;
    margin: 0;
    border-bottom: 1px solid #DDD;
    background-color: #FFF;
    z-index: 10;
    height: 39px;
    position: relative;
}
    .page-header h2 {
    display: inline-block;
    position: relative;
    padding: 8px 24px 8px 0;
    margin: 0;
    max-width: 350px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-left: 15px;
    }

    .bdcm{
        margin-bottom: 0px;
    }


</style>
<body>

<?= $this->element('OpenEmis.breadcrumbs') ?>
    <div class="page-header">
		<h2 id="main-header"><?php echo $UserData->first_name.' '.$UserData->last_name ?> - <?php echo __('Add Student Guardians') ?></h2>

			</div>

            </body>
<!-- POCOR-7231 :: END -->

<div class="pd-10" ng-controller = 'DirectoryaddguardianCtrl'>
    <div class="alert {{messageClass}}" ng-if="message">
        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>{{message}}
    </div>
    <div class="stepper-content-wrapper">
        <div class="steps-container">
            <ul class="steps" style="margin-left: 0">
                <li ng-class="{'active': step === 'user_details'}">
                    <div class="stepper-steps-wrapper">
                        User Details
                        <span class="chevron"></span>
                    </div>
                </li>
                <li ng-class="{'active': step === 'internal_search'}">
                    <div class="stepper-steps-wrapper">
                        Internal Search
                        <span class="chevron"></span>
                    </div>
                </li>
                <li ng-if="isExternalSearchEnable" ng-class="{'active': step === 'external_search'}">
                    <div class="stepper-steps-wrapper">
                        External Search
                        <span class="chevron"></span>
                    </div>
                </li>
                <li ng-class="{'active': step === 'confirmation'}">
                    <div class="stepper-steps-wrapper">
                        Confirmation
                        <span class="chevron"></span>
                    </div>
                </li>
                <li ng-class="{'active': step === 'summary'}">
                    <div class="stepper-steps-wrapper">
                        Summary
                        <span class="chevron"></span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="actions top">
            <button
                ng-if="(step=='user_details')"
                type="button" class="btn close-btn" ng-click="cancelProcess()" style="font-size: 12px;">Cancel</button>
            <button
                ng-if="(step!=='user_details' && step!=='summary')"
                type="button" class="btn btn-prev close-btn" ng-click="goToPrevStep()" style="font-size: 12px;">Back</button>
            <button
                ng-if="(step=='confirmation' && step!=='summary')"
                type="button" class="btn btn-default" ng-click="validateDetails()" style="font-size: 12px;">Confirm</button>
            <button
                ng-if="(step=='summary')"
                type="button" class="btn close-btn" ng-click="cancelProcess()" style="font-size: 12px;">Close</button>
            <button  ng-disabled="isNextButtonShouldDisable()" type="button" class="btn btn-default btn-next"
                ng-if="step!=='confirmation' && step!=='summary'" ng-click="goToNextStep()" style="font-size: 12px;">Next</button>
             <button
                ng-if="(step=='summary')"
                type="button" class="btn btn-default" ng-click="addGuardian()" style="font-size: 12px;">Add Multiple Guardians</button>
        </div>
        <div class="step-content">
            <div class="step-pane sample-pane" ng-show="step === 'user_details'">
                <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                    <div class="input select required error">
                        <label>Relation Type</label>
                        <div class="input-select-wrapper">
                            <select name="User[user_type_id]" id="user-user_type_id"
                                ng-options="option.id as option.name for option in relationTypeOptions"
                                ng-model="selectedUserData.relation_type_id"
                                ng-change="changeUserType()"
                                >
                                <option value="" >-- <?= __('Select') ?> --</option>
                            </select>
                        </div>
                        <div ng-if="error.relation_type_id" class="error-message">
                            <p>{{ error.relation_type_id }}</p>
                        </div>
                    </div>
                    <div class="row section-header header-space-lg">Search By Identity</div>
                    <div ng-class="nationality_class" class="input select">
                        <label><?= __('Nationality') ?></label>
                        <div class="input-select-wrapper">
                            <select name="User[nationality_id]" id="user-nationality_id"
                                ng-options="option.id as option.name for option in nationalitiesOptions"
                                ng-model="selectedUserData.nationality_id"
                                ng-change="changeNationality()"
                                >
                                <option value="" >-- <?= __('Select') ?> --</option>
                            </select>
                        </div>
                    </div>
                    <div ng-class="identity_type_class" class="input select">
                        <label><?= __('Identity Type') ?></label>
                        <div class="input-select-wrapper">
                            <select name="User[identities_type_id]" id="user-identities_type_id"
                                ng-options="option.id as option.name for option in identityTypeOptions"
                                ng-model="selectedUserData.identity_type_id"
                                ng-change="changeIdentityType()"
                                >
                                <option value="" >-- <?= __('Select') ?> --</option>
                            </select>
                        </div>
                    </div>
                    <!-- Always show Identity Number POCOR-7245 -->
                    <div ng-class="identity_class" class="input select">
                        <label><?= __('{{selectedUserData.identity_type_name ? selectedUserData.identity_type_name : "Identity Number"}}') ?></label>
                        <input ng-model="selectedUserData.identity_number" type="string">
                    </div>
                    <!-- <div ng-class="identity_class" ng-show="selectedUserData.identity_type_name" class="input select required">
                        <label><?= __('{{selectedUserData.identity_type_name ? selectedUserData.identity_type_name : "Identity Number"}}') ?></label>
                        <input ng-model="selectedUserData.identity_number" type="string">
                    </div> -->
                    <div class="row section-header header-space-lg">Search By Basic Information</div>
                    <div class="input string">
                        <label><?= __('OpenEMIS ID') ?></label>
                        <input ng-model="selectedUserData.openemis_no" type="string">
                    </div>
                    <div class="input string required">
                        <label><?= __('First Name') ?></label>
                        <input ng-model="selectedUserData.first_name" ng-change="setName()" type="string">
                        <div ng-if="error.first_name" class="error-message">
                            <p>{{ error.first_name }}</p>
                        </div>
                    </div>
                    <div class="input string">
                        <label><?= __('Middle Name') ?></label>
                        <input ng-model="selectedUserData.middle_name" ng-change="setName()" type="string">
                    </div>
                    <div class="input string">
                        <label><?= __('Third Name') ?></label>
                        <input ng-model="selectedUserData.third_name" ng-change="setName()" type="string">
                    </div>
                    <div class="input string required">
                        <label><?= __('Last Name') ?></label>
                        <input ng-model="selectedUserData.last_name" ng-change="setName()" type="string">
                        <div ng-if="error.last_name" class="error-message">
                            <p>{{ error.last_name }}</p>
                        </div>
                    </div>
                    <div class="input string">
                        <label><?= __('Preferred Name') ?></label>
                        <input ng-model="selectedUserData.preferred_name" type="string">
                    </div>
                    <div class="input select error required">
                        <label><?= __('Gender') ?></label>
                        <div class="input-select-wrapper">
                            <select name="Staff[gender_id]" id="staff-gender_id"
                                ng-options="option.id as option.name for option in genderOptions"
                                ng-model="selectedUserData.gender_id"
                                ng-change="changeGender()"
                                >
                                <option value="" >-- <?= __('Select') ?> --</option>
                            </select>
                        </div>
                        <div ng-if="error.gender_id" class="error-message">
                            <p>{{ error.gender_id }}</p>
                        </div>
                    </div>
                    <div class="input date required">
                        <label for="User_date_of_birth"><?= __('Date Of Birth') ?></label>
                        <div class="input-group date " id="User_date_of_birth" style="">
                            <input type="text" class="form-control " name="User[date_of_birth]" ng-model="selectedUserData.date_of_birth">
                            <span class="input-group-addon" style="color: #FFFFFF;background-color: #6699CC;"><i class="glyphicon glyphicon-calendar"></i></span>
                        </div>
                        <div ng-if="error.date_of_birth" class="error-message">
                            <p>{{ error.date_of_birth }}</p>
                        </div>
                    </div>

                </form>
            </div>
            <div class="step-pane sample-pane" ng-if="step === 'internal_search'">
                <div class="table-wrapper">
                    <div>
                        <div class="scrolltabs">
                            <div id="institution-student-table" class="table-wrapper">
                                <div ng-if="internalGridOptions" kd-ag-grid="internalGridOptions" ag-selection-type="radio" class="ag-height-fixed"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="step-pane sample-pane" ng-if="step === 'external_search'">
                <div class="table-wrapper">
                    <div>
                        <div class="scrolltabs sticky-content">
                            <div id="institution-student-table" class="table-wrapper">
                                <div ng-if="externalGridOptions" kd-ag-grid="externalGridOptions" ag-selection-type="radio" class="ag-height-fixed"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="step-pane sample-pane" ng-show="step === 'confirmation'">
                <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                    <div class="row section-header header-space-lg">Information</div>
                    <div class="input string row-content">
                        <label><?= __('Photo Content') ?></label>
                        <div class="fileinput fileinput-new fileinput-preview">
                            <div class="table-thumb mb-16">
                                <div class="profile-image-thumbnail">
                                    <i class="kd-staff"></i>
                                </div>
                            </div>
                            <div class="file-input-buttons">
                                <p>* Advisable photo dimension 90 by 115<br />
                                * Format Supported: .jpg, .jpeg, .png, .gif</p>
                                <span class="btn btn-default btn-file" style="font-size: 12px !important;">
                                    <span class="fileinput-new">
                                        <i class="fa fa-folder"></i>
                                        <span style="font-size: 12px;">Select File</span>
                                    </span>
                                    <input id="image-file" class="file-input" type="file" onchange="savePhoto(this)" >
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="input string required">
                        <label><?= __('OpenEMIS ID') ?></label>
                        <input ng-model="selectedUserData.openemis_no" type="string" ng-disabled="true">
                    </div>
                    <div class="input string required">
                        <label><?= __('First Name') ?></label>
                        <input ng-model="selectedUserData.first_name" type="string" ng-disabled="true">
                    </div>
                    <div class="input string">
                        <label><?= __('Middle Name') ?></label>
                        <input ng-model="selectedUserData.middle_name" ng-change="InstitutionStudentController.setStudentName()" type="string" ng-disabled="true">
                    </div>
                    <div class="input string">
                        <label><?= __('Third Name') ?></label>
                        <input ng-model="selectedUserData.third_name" ng-change="InstitutionStudentController.setStudentName()" type="string" ng-disabled="true">
                    </div>
                    <div class="input string required">
                        <label><?= __('Last Name') ?></label>
                        <input ng-model="selectedUserData.last_name" type="string" ng-disabled="true">
                    </div>
                    <div class="input string">
                        <label><?= __('Preferred Name') ?></label>
                        <input ng-model="selectedUserData.preferred_name" type="string" ng-disabled="true">
                    </div>
                    <div class="input select required">
                        <label><?= __('Gender') ?></label>
                        <input ng-model="selectedUserData.gender.name" ng-disabled="true" />
                    </div>
                    <div class="input date required">
                        <label for="Student_date_of_birth"><?= __('Date Of Birth') ?></label>
                        <div class="input-group date " id="Student_date_of_birth" style="">
                            <input type="text" class="form-control " name="Student[date_of_birth]" ng-model="selectedUserData.date_of_birth" ng-disabled="true">
                        </div>
                    </div>
                    <div class="row section-header header-space-lg">Location</div>
                    <div class="input string">
                        <label><?= __('Address') ?></label>
                        <textarea ng-model="selectedUserData.address" type="string"></textarea>
                    </div>
                    <div class="input string">
                        <label><?= __('Postal Code') ?></label>
                        <input ng-model="selectedUserData.postalCode" type="string">
                    </div>
                    <!-- Address area start -->
                    <div class="row section-header header-space-lg">Address Area</div>
                     <div class="input string" id="addressArea_textbox" style="visibility:hidden">
                        <label><?= __('Address Area') ?></label>
                        <input ng-model="selectedUserData.addressArea.name" type="string" ng-disabled="true">
                    </div>
                    <div class="input string" id="addressArea_dropdown" >
                        <label><?= __('Address Area') ?></label>
                        <div
                            class="tree-form"
                            id="address_area_id"
                            ng-controller="SgTreeCtrl as SgTree"
                            ng-init="SgTree.model='Area.AreaAdministratives'; SgTree.outputValue=addressAreaId; SgTree.userId=2; SgTree.displayCountry=0; SgTree.triggerOnChange=false;">
                            <kd-tree-dropdown-ng id="address_area_id-tree" expand-parent="SgTree.triggerLoad(refreshList)" output-model="addressAreaOutputModelText" model-type="single" text-config="textConfig"></kd-tree-dropdown-ng>
                        </div>
                    </div>
                    <!-- Address area end -->
                    <!-- Address area start -->
                    <div class="row section-header header-space-lg">Birthplace Area</div>
                    <div class="input string" id="birthplaceArea_textbox" style="visibility:hidden">
                        <label><?= __('Birthplace Area') ?></label>
                        <input ng-model="selectedUserData.birthplaceArea.name" type="string" ng-disabled="true">
                    </div>
                    <div class="input string" id="birthplaceArea_dropdown">
                        <label><?= __('Birthplace Area') ?></label>
                        <div
                            class="tree-form"
                            id="birthplace_area"
                            ng-controller="SgTreeCtrl as SgTree"
                            ng-init="SgTree.model='Area.AreaAdministratives'; SgTree.outputValue=birthplaceAreaId; SgTree.userId=2; SgTree.displayCountry=0; SgTree.triggerOnChange=false; ">
                            <kd-tree-dropdown-ng id="birthplace_area-tree" expand-parent="SgTree.triggerLoad(refreshList)" output-model="birthplaceAreaOutputModelText" model-type="single" text-config="textConfig"></kd-tree-dropdown-ng>
                        </div>
                    </div>
                    <div class="row section-header header-space-lg">Identities / Nationalities</div>
                    <div class="input string">
                        <label><?= __('Nationalities') ?></label>
                        <input ng-model="selectedUserData.nationality_name" type="string" ng-disabled="true" />
                    </div>
                    <div class="input string">
                        <label><?= __('Identity Type') ?></label>
                        <input ng-model="selectedUserData.identity_type_name" type="string" ng-disabled="true">
                    </div>
                    <div class="input string">
                        <label><?= __('Identity Number') ?></label>
                        <input ng-model="selectedUserData.identity_number" type="string" ng-disabled="true">
                    </div>
                    <div class="row section-header header-space-lg">Other Information</div>
                    <div class="input select">
                        <label><?= __('Contact Type') ?></label>
                        <div class="input-select-wrapper">
                            <select name="Staff[gender_id]" id="staff-contact_type_id"
                                ng-options="option.id as option.name for option in contactTypeOptions"
                                ng-model="selectedUserData.contact_type_id"
                                ng-change="changeContactType()"
                                >
                                <option value="" >-- <?= __('Select') ?> --</option>
                            </select>
                        </div>
                    </div>
                    <div class="input string">
                        <label><?= __('Contact Value') ?></label>
                        <input ng-model="selectedUserData.contact_value" type="string">
                    </div>
                    <div class="input string required">
                        <label><?= __('Username') ?></label>
                        <input ng-model="selectedUserData.username" type="string" ng-disabled="disableFields.username">
                        <div ng-if="error.username" class="error-message">
                            <p>{{ error.relation_type_id }}</p>
                        </div>
                    </div>
                    <div ng-if="!disableFields.password" class="input password required">
                        <label><?=
                            __('Password') . '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $tooltipMessage . '"></i>'
                        ?></label>
                        <input ng-model="selectedUserData.password" type="string" ng-disabled="disableFields.password">
                        <div ng-if="error.password" class="error-message">
                            <p>{{ error.password }}</p>
                        </div>
                    </div>
                </form>
            </div>
            <div class="step-pane sample-pane active" ng-if="step === 'summary'">
                <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post" style="margin: 0;">
                    <div class="wrapper">
                        <div class="wrapper-child">
                            <div class="panel">
                                <div class="panel-body">
                                    <div class="row section-header">Information</div>
                                    <div class="row row-content hidden"></div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Photo Content</div>
                                        <div class="form-input">
                                            <div class="table-thumb">
                                                <div class="profile-image-thumbnail">
                                                    <i class="kd-staff"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">OpenEMIS ID</div>
                                        <div class="form-input">{{selectedUserData.openemis_no}}</div>
                                    </div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">First Name</div>
                                        <div class="form-input">{{selectedUserData.first_name}}</div>
                                    </div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Middle Name</div>
                                        <div class="form-input">{{selectedUserData.middle_name}}</div>
                                    </div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Third Name</div>
                                        <div class="form-input">{{selectedUserData.third_name}}</div>
                                    </div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Last Name</div>
                                        <div class="form-input">{{selectedUserData.last_name}}</div>
                                    </div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Preferred Name</div>
                                        <div class="form-input">{{selectedUserData.preferred_name}}</div>
                                    </div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Gender</div>
                                        <div class="form-input">{{selectedUserData.gender.name}}</div>
                                    </div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Date of Birth</div>
                                        <div class="form-input">{{selectedUserData.date_of_birth}}</div>
                                    </div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Email</div>
                                        <div class="form-input">{{selectedUserData.email}}</div>
                                    </div>
                                    <div class="row section-header">Identities / Nationalities</div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Details</div>
                                        <div class="form-input" ng-if="selectedUserData.identity_type_name || selectedUserData.identity_number || selectedUserData.nationality_name">
                                            <div class="form-input table-full-width">
                                                <div class="table-wrapper">
                                                    <div class="table-in-view">
                                                        <table class="table" style="margin-bottom: 0px !important;">
                                                            <thead>
                                                                <tr>
                                                                    <th>Identity Type</th>
                                                                    <th>Identity Number</th>
                                                                    <th>Nationality</th>
                                                                    <th>Preferred</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td class="vertical-align-top">{{selectedUserData.identity_type_name}}</td>
                                                                    <td class="vertical-align-top">{{selectedUserData.identity_number}}</td>
                                                                    <td class="vertical-align-top">{{selectedUserData.nationality_name}}</td>
                                                                    <td class="vertical-align-top">No</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row section-header">Location</div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Address</div>
                                        <div class="form-input">{{selectedUserData.address}}</div>
                                    </div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Postal Code</div>
                                        <div class="form-input">{{selectedUserData.postalCode}}</div>
                                    </div>
                                    <div class="row section-header">Address Area</div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Address Area</div>
                                        <div class="form-input">{{selectedUserData.addressArea.name}}</div>
                                    </div>
                                    <div class="row section-header">Birthplace Area</div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Birthplace Area</div>
                                        <div class="form-input">{{selectedUserData.birthplaceArea.name}}</div>
                                    </div>
                                    <div class="row section-header">Other Information</div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Modified By</div>
                                        <div class="form-input">System Administrator</div>
                                    </div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Modified On</div>
                                        <div class="form-input">{{todayDate}}</div>
                                    </div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Created By</div>
                                        <div class="form-input">System Administrator</div>
                                    </div>
                                    <div class="row row-content">
                                        <div class="col-xs-6 col-md-3 form-label">Created On</div>
                                        <div class="form-input">{{todayDate}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="actions bottom">
        </div>
    </div>
</div>

<script>
$(function () {
var datepicker0 = $('#User_date_of_birth').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true, language: '<?php echo $dateLanguage; ?>'});
$( document ).on('DOMMouseScroll mousewheel scroll', function(){
    window.clearTimeout( t );
    t = window.setTimeout( function(){
        datepicker0.datepicker('place');
    });
});
});

//]]>
</script>

<style>
    .pd-10 {
        padding: 10px;
    }
    .close-btn {
        border: 1px solid #000;
    }
    .header-space-lg{
        margin-bottom: 20px !important
    }
    .mb-16{
        margin-bottom: 16px;
    }
    .font-italic{
        font-style: italic;
    }
    .mb-0{
        margin-bottom: 0;
    }
    .d-flex{
        display: flex;
    }
    .position-relative{
        position: relative;
    }
    .fontSize-16{
        font-size: 16px !important;
    }
    .input-hidden{
        opacity: 0;
        position: absolute;
        width: 100% !important;
        height: 100% !important;
        left: 0;
        top: 0;
    }
    .row-content{
        margin-bottom: 16px;
    }
    .vertical-align-top {
        vertical-align: top !important;
    }
    /* stepper container wrapper */
    .stepper-content-wrapper {
        border: 1px solid #d4d4d4;
        border-radius: 4px;
        box-shadow: 0 1px 4px rgb(0 0 0 / 7%);
        background-color: #f9f9f9;
        position: relative;
        min-height: 610px;
    }
    .stepper-content-wrapper{
        -webkit-box-shadow: none!important;
        box-shadow: none!important;
        border: 0;
        background-color: #FFF;
        margin-bottom: 10px;
    }
    .stepper-content-wrapper:before,
    .stepper-content-wrapper:after {
        display: table;
        content: "";
        line-height: 0;
    }
    /* steps container */
    .stepper-content-wrapper .steps-container {
        border-radius: 4px 4px 0 0;
        overflow: hidden;
        border: 1px solid #DDD;
        height: 48px;
        border-bottom: none;
    }
    /* steps */

    .stepper-content-wrapper > ul.steps li,
    .stepper-content-wrapper > .steps-container > ul.steps li {
        float: left;
        margin: 0;
        padding: 0 20px 0 30px;
        height: 46px;
        line-height: 46px;
        position: relative;
        background: #ededed;
        color: #999999;
        font-size: 16px;
        cursor: not-allowed;
    }
    .stepper-content-wrapper > ul.steps li,
    .stepper-content-wrapper > .steps-container > ul.steps li {
        float: left;
        margin: 0;
        padding: 8px 20px 0 30px;
        height: 48px;
        line-height: 32px;
        position: relative;
        background: #ededed;
        color: #999;
        font-size: 12px;
        cursor: not-allowed;
        border-bottom: none;
    }
    .stepper-content-wrapper > ul.steps li.active,
    .stepper-content-wrapper > .steps-container > ul.steps li.active {
        background: #f1f6fc;
        color: #3a87ad;
        cursor: default;
    }
    .stepper-content-wrapper > ul.steps li:first-child,
    .stepper-content-wrapper > .steps-container > ul.steps li:first-child {
        border-radius: 4px 0 0 4px;
        padding-left: 20px;
    }
    .stepper-content-wrapper > ul.steps li:first-child,
    .stepper-content-wrapper > .steps-container > ul.steps li:first-child {
        -webkit-border-radius: 4px 0 0 0;
        border-radius: 4px 0 0 0;
    }
    .stepper-content-wrapper > ul.steps li.active,
    .stepper-content-wrapper > .steps-container > ul.steps li.active {
        line-height: 30px;
    }
    .stepper-content-wrapper > ul.steps li.active,
    .stepper-content-wrapper > .steps-container > ul.steps li.active {
        background-color: #999;
        color: #FFF;
        cursor: default;
        border-bottom: none;
    }
    .stepper-content-wrapper > ul.steps li.active,
    .stepper-content-wrapper > .steps-container > ul.steps li.active {
        background-color: #69C;
    }
    .stepper-content-wrapper > ul.steps li.active,
    .stepper-content-wrapper > .steps-container > ul.steps li.active {
        background-color: #6699CC;
    }
    .stepper-content-wrapper .steps {
        margin-left: 0!important;
    }
    .stepper-content-wrapper > ul.steps,
    .stepper-content-wrapper > .steps-container > ul.steps {
        list-style: none outside none;
        padding: 0;
        margin: 0;
        width: 999999px;
    }
    /* step wrapper */
    .stepper-steps-wrapper {
        overflow: hidden;
        width: 100%;
        text-overflow: ellipsis;
        white-space: nowrap;
        text-align: center;
    }
    /* chevron */
    .stepper-content-wrapper > ul.steps li .chevron,
    .stepper-content-wrapper > .steps-container > ul.steps li .chevron {
        border: 24px solid transparent;
        border-left: 14px solid #d4d4d4;
        border-right: 0;
        display: block;
        position: absolute;
        right: -14px;
        top: 0;
        z-index: 1;
    }
    .stepper-content-wrapper > ul.steps li .chevron,
    .stepper-content-wrapper > .steps-container > ul.steps li .chevron {
        border: 24px solid transparent;
        border-left: 14px solid #d4d4d4;
        border-right: 0;
        display: block;
        position: absolute;
        right: -15px;
        top: -5px;
        z-index: 1;
        margin-top: 5px;
        transform: scale(1.1,1.1);
        -ms-transform: scale(1.1,1.1);
        -webkit-transform: scale(1.1,1.1);
    }
    /* chevron before */
    .stepper-content-wrapper > ul.steps li .chevron:before,
    .stepper-content-wrapper > .steps-container > ul.steps li .chevron:before {
        border: 24px solid transparent;
        border-left: 14px solid #ededed;
        border-right: 0;
        content: "";
        display: block;
        position: absolute;
        right: 1px;
        top: -24px;
    }
    .stepper-content-wrapper > ul.steps li .chevron:before,
    .stepper-content-wrapper > .steps-container > ul.steps li .chevron:before {
        border: 24px solid transparent;
        border-left: 14px solid #ededed;
        border-right: 0;
        content: "";
        display: block;
        position: absolute;
        right: 1px;
        top: -24px;
    }
    .stepper-content-wrapper > ul.steps li.active .chevron:before,
    .stepper-content-wrapper > .steps-container > ul.steps li.active .chevron:before {
        border-left: 14px solid #f1f6fc;
    }
    .stepper-content-wrapper > ul.steps li.active .chevron:before,
    .stepper-content-wrapper > .steps-container > ul.steps li.active .chevron:before {
        border-left: 14px solid #999;
    }
    .stepper-content-wrapper > ul.steps li.active .chevron::before,
    .stepper-content-wrapper > .steps-container > ul.steps li.active .chevron::before {
        border-left-color: #69C;
    }
    .stepper-content-wrapper > ul.steps li.active .chevron::before,
    .stepper-content-wrapper > .steps-container > ul.steps li.active .chevron::before {
        border-left-color: #6699CC;
    }
    /* action buttons */
    .stepper-content-wrapper > .actions {
        z-index: 1000;
        position: absolute;
        right: 0;
        top: 0;
        line-height: 46px;
        float: right;
        padding-left: 15px;
        padding-right: 15px;
        vertical-align: middle;
        background-color: #e5e5e5;
        border-left: 1px solid #d4d4d4;
        border-radius: 0 4px 0 0;
    }
    .stepper-content-wrapper > .actions {
        background-color: #f9f9f9;
        border-left: 1px solid #d4d4d4;
        border-radius: 0 4px 0 0;
        float: right;
        line-height: 47px;
        padding-left: 15px;
        padding-right: 15px;
        position: absolute;
        right: 1px;
        top: 1px;
        vertical-align: middle;
        z-index: 500;
    }
    /* step content */
    .stepper-content-wrapper .step-content {
        border-top: 1px solid #D4D4D4;
        padding: 10px;
        float: left;
        width: 100%;
    }
    .stepper-content-wrapper .step-content {
        border-top: 1px solid #DDD;
        border-right: 1px solid #DDD;
        border-left: 1px solid #DDD;
        border-bottom: 1px solid #DDD;
        border-radius: 0 0 4px 4px;
        float: left;
        width: 100%;
    }
    @media (min-width: 800px) {
        .row-content{
            display: flex;
            align-items: flex-start;
        }
    }
    @media only screen and (max-width: 800px){
        .stepper-content-wrapper .actions.top {
        position: fixed!important;
        bottom: 35px!important;
        top: auto!important;
        height: 50px;
        left: 0!important;
        right: 0!important;
        border-top: 1px solid #DDD;
        border-left-color: transparent;
        -webkit-border-radius: 0!important;
        border-radius: 0!important;
        }
        .stepper-content-wrapper .btn-prev,
        .stepper-content-wrapper .btn-next {
            position: relative;
        }
        .stepper-content-wrapper .btn-next {
            text-align: left;
            padding-right: 20px;
        }
        .stepper-content-wrapper .actions.top .btn-next,
        .stepper-content-wrapper .actions.top .btn-prev {
            position: absolute;
            top: 10px;
        }
        .stepper-content-wrapper .actions.top .btn-next {
            right: 10px;
        }
    }

    .uib-title {
        background-color: #fff !important;
        color: #333 !important;
        margin-top: -22px;
        border-color: #fff !important;
    }

    .uib-title:hover {
        background-color: #eee !important;
        color: #000 !important;
    }

    .uib-left, .uib-right {
        background-color: #fff !important;
        color: #333 !important;
        border-color: #fff !important;
    }

    .uib-left:hover {
        background-color: #eee !important;
        color: #000 !important;
    }

    .uib-right:hover {
        background-color: #eee !important;
        color: #000 !important;
    }

    .uib-day .btn-sm {
        background-color: #fff !important;
        color: #333 !important;
        border-color: #fff !important;
    }

    .uib-day .btn-sm:hover {
        background-color: #eee !important;
        color: #000 !important;
    }

    .uib-month .btn-default {
        background-color: #fff !important;
        color: #333 !important;
        border-color: #fff !important;
    }

    .uib-month .btn-default:hover {
        background-color: #eee !important;
        color: #000 !important;
    }

    .uib-years .btn-default {
        background-color: #fff !important;
        color: #333 !important;
        border-color: #fff !important;
    }

    .uib-years .btn-default:hover {
        background-color: #eee !important;
        color: #000 !important;
    }

    .uib-datepicker-popup {
        padding: 5px 10px;
    }

    .uib-close {
        display: none !important;
    }

    .uib-datepicker-current {
        width: 230% !important;
        border-radius: 3px !important;
        border-color: #fff !important;
        color: #000 !important;
        background: #fff !important;
    }

    .uib-datepicker-current:hover {
        background: #eee !important;
    }

    .uib-clear {
        border-color: #fff !important;
        border-radius: 3px !important;
        color: #000 !important;
        background: #fff !important;
    }

    .uib-clear:hover {
        background: #eee !important;
    }

    .time {
        margin-top: -15px !important;
        margin-bottom: 5px !important;
    }

    .hours {
        vertical-align: middle !important;
    }

    .minutes {
        vertical-align: middle !important;
    }

    .check_box {
        margin-top: 2px !important;
        display: inline-flex !important;
    }

    .file-input {
        width: 200% !important;
        border: 0px !important;
        font-size: 14px !important;
        height: 40px !important;
        padding-left: 0px !important;
    }

    .alert {
        color: #FFF !important;
        padding: 10px !important;
        margin: 0 0 10px !important;
    }

    .alert-success {
        background-color: #77B576 !important;
        border: 1px solid #77B576 !important;
        color:
    }

    .alert_warn{
    color: #FFF !important;
    border-color: #faebcc !important;
    background-color: #E6BA64 !important;
    border: 1px solid #E6BA64 !important;
}
</style>
