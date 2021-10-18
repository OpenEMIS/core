<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Directory.angular/directoryadd/directory.directoryadd.svc', ['block' => true]); ?>
<?= $this->Html->script('Directory.angular/directoryadd/directory.directoryadd.ctrl', ['block' => true]); ?>
<?= $this->Html->script('ControllerAction.../plugins/timepicker/js/bootstrap-timepicker.min', ['block' => true]); ?>
<?= $this->Html->css('ControllerAction.../plugins/timepicker/css/bootstrap-timepicker.min', ['block' => true]); ?>

<div ng-controller = 'DirectoryAddCtrl'>
    <div class="alert {{class}}" ng-hide="message == null">
        <a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a><?= __('{{message}}') ?>
    </div>
    <div class="wizard" data-initialize="wizard" id="wizard">
        <div class="steps-container">
            <ul class="steps" style="margin-left: 0">
                <li data-step="1" class="active" data-name="userDetails">
                    <div class="step-wrapper">
                        User Details
                        <span class="chevron"></span>
                    </div>
                </li>

                <li data-step="2" data-name="internalSearch">
                    <div class="step-wrapper">
                        Internal Search
                        <span class="chevron"></span>
                    </div>
                </li>

                <li data-step="3" data-name="externalSearch">
                    <div class="step-wrapper">
                        External Search
                        <span class="chevron"></span>
                    </div>
                </li>

                <li data-step="4" data-name="confirmation">
                    <div class="step-wrapper">
                        Confirmation
                        <span class="chevron"></span>
                    </div>
                </li>

                <li data-step="5" data-name="summary">
                    <div class="step-wrapper">
                        Summary
                        <span class="chevron"></span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="actions top">
            <button
                ng-if="(step=='user_details' || step=='internal_search')"
                type="button" class="btn btn-cancel">Cancel</button>
            <button
                ng-if="(step!=='user_details' && step!=='internal_search')"
                type="button" class="btn btn-prev">Back</button>
            <button
                ng-if="(step=='confirmation')"
                type="button" class="btn btn-default">Confirm</button>
            <button
                ng-if="(step=='summary')"
                type="button" class="btn btn-cancel">Close</button>
            <button type="button" class="btn btn-default btn-next"
                ng-if="step!=='confirmation'"
                data-last="<?= __('Save') ?>">Next</button>
        </div>
        <div class="step-content">
            <div class="step-pane sample-pane active" data-step="1" data-name="userDetails">
                <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                    <div class="input string required">
                        <label><?= __('OpenEMIS ID') ?></label>
                        <input ng-model="selectedUserData.openemis_no" type="string" ng-disabled="true">
                        <div ng-if="postResponse.error.openemis_no" class="error-message">
                            <p ng-repeat="error in postResponse.error.openemis_no">{{ error }}</p>
                        </div>
                    </div>
                    <div class="input string required">
                        <label><?= __('First Name') ?></label>
                        <input ng-model="selectedUserData.first_name" ng-change="setStaffName()" type="string" ng-init="selectedUserData.first_name='';">
                        <div ng-if="postResponse.error.first_name" class="error-message">
                            <p ng-repeat="error in postResponse.error.first_name">{{ error }}</p>
                        </div>
                    </div>
                    <div class="input string">
                        <label><?= __('Middle Name') ?></label>
                        <input ng-model="selectedUserData.middle_name" ng-change="setStaffName()" type="string">
                    </div>
                    <div class="input string">
                        <label><?= __('Third Name') ?></label>
                        <input ng-model="selectedUserData.third_name" ng-change="setStaffName()" type="string">
                    </div>
                    <div class="input string required">
                        <label><?= __('Last Name') ?></label>
                        <input ng-model="selectedUserData.last_name" ng-change="setStaffName()" type="string" ng-init="selectedUserData.last_name='';">
                        <div ng-if="postResponse.error.last_name" class="error-message">
                            <p ng-repeat="error in postResponse.error.last_name">{{ error }}</p>
                        </div>
                    </div>
                    <div class="input string">
                        <label><?= __('Preferred Name') ?></label>
                        <input ng-model="selectedUserData.preferred_name" type="string">
                    </div>
                    <div class="input select required error">
                        <label><?= __('Gender') ?></label>
                        <div class="input-select-wrapper">
                            <select name="Staff[gender_id]" id="staff-gender_id"
                                ng-options="option.id as option.name for option in genderOptions"
                                ng-model="selectedUserData.gender_id"
                                ng-change="changeGender()"
                                ng-init="selectedUserData.gender_id='';"
                                >
                                <option value="" >-- <?= __('Select') ?> --</option>
                            </select>
                        </div>
                        <div ng-if="postResponse.error.gender_id" class="error-message">
                            <p ng-repeat="error in postResponse.error.gender_id">{{ error }}</p>
                        </div>
                    </div>
                    <div class="input date required">
                        <label for="Staff_date_of_birth"><?= __('Date Of Birth') ?></label>
                        <div class="input-group date " id="Staff_date_of_birth" style="">
                            <input type="text" class="form-control " name="Staff[date_of_birth]" ng-model="selectedUserData.date_of_birth" ng-init="selectedUserData.date_of_birth='';">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                        </div>
                        <div ng-if="postResponse.error.date_of_birth" class="error-message">
                            <p ng-repeat="error in postResponse.error.date_of_birth">{{ error }}</p>
                        </div>
                    </div>
                    <div ng-class="InstitutionStaffController.Staff.nationality_class">
                        <label><?= __('Nationality') ?></label>
                        <div class="input-select-wrapper">
                            <select name="Staff[nationality_id]" id="staff-nationality_id"
                                ng-options="option.id as option.name for option in StaffNationalitiesOptions"
                                ng-model="Staff.nationality_id"
                                ng-change="changeNationality()"
                                ng-init="Staff.nationality_id='';"
                                >
                                <option value="" >-- <?= __('Select') ?> --</option>
                            </select>
                        </div>
                        <div ng-if="postResponse.error.nationalities[0].nationality_id" class="error-message">
                            <p ng-repeat="error in postResponse.error.nationalities[0].nationality_id">{{ error }}</p>
                        </div>
                    </div>
                    <div ng-class="InstitutionStaffController.Staff.identity_type_class">
                        <label><?= __('Identity Type') ?></label>
                        <div class="input-select-wrapper">
                            <select name="Staff[identities_type_id]" id="staff-identities_type_id"
                                ng-options="option.id as option.name for option in StaffIdentitiesOptions"
                                ng-model="Staff.identity_type_id"
                                ng-change="changeIdentityType()"
                                >
                                <option value="" >-- <?= __('Select') ?> --</option>
                            </select>
                        </div>
                        <div ng-if="postResponse.error.identities[0].identity_type_id" class="error-message">
                            <p ng-repeat="error in postResponse.error.identities[0].identity_type_id">{{ error }}</p>
                        </div>
                    </div>
                    <div ng-class="InstitutionStaffController.Staff.identity_class" ng-show="InstitutionStaffController.StaffIdentities != 2">
                        <label><?= __('{{InstitutionStaffController.Staff.identity_type_name}}') ?></label>
                        <input ng-model="selectedUserData.identity_number" type="string" ng-init="selectedUserData.identity_number='';">
                        <div ng-if="postResponse.error.identities[0].number" class="error-message">
                            <p ng-repeat="error in postResponse.error.identities[0].number">{{ error }}</p>
                        </div>
                    </div>

                    <div class="input string required">
                        <label><?= __('Username') ?></label>
                        <input ng-model="selectedUserData.username" type="string" ng-init="selectedUserData.username='';">
                        <div ng-if="postResponse.error.username" class="error-message">
                            <p ng-repeat="error in postResponse.error.username">{{ error }}</p>
                        </div>
                    </div>

                    <div class="input password required">
                        <label><?=
                            __('Password') . '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $tooltipMessage . '"></i>'
                        ?></label>
                        <input ng-model="selectedUserData.password" type="string" ng-init="selectedUserData.password='';">
                        <div ng-if="postResponse.error.password" class="error-message">
                            <p ng-repeat="error in postResponse.error.password">{{ error }}</p>
                        </div>
                    </div>
                </form>
            </div>
            <div class="step-pane sample-pane" data-step="2" data-name="internalSearch">
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
            <div class="step-pane sample-pane" data-step="3" data-name="externalSearch">
                <div class="table-wrapper">
                    <div>
                        <div class="scrolltabs sticky-content">
                            <div id="institution-student-table" class="table-wrapper">
                                <div ng-if="InstitutionStaffController.externalGridOptions" kd-ag-grid="InstitutionStaffController.externalGridOptions" ag-selection-type="radio" class="ag-height-fixed"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="step-pane sample-pane" data-step="4" data-name="confirmation">
            <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post">
                <div class="row section-header header-space-lg">Information</div>
                <div class="input string row-content">
                    <label><?= __('Photo Content') ?></label>
                    <div>
                        <div class="table-thumb mb-16">
                            <div class="profile-image-thumbnail">
                                <i class="kd-staff"></i>
                            </div>
                        </div>
                        <p class="font-italic mb-0">* Advisable photo dimension 90 by 115</p>
                        <p class="font-italic">* Format Supported: .jpg, .jpeg, .png, .gif</p>
                        <div class="d-flex">
                            <div class="position-relative">
                                <button class="btn btn-xs btn-default fontSize-16">
                                    <i class="fa fa-folder"></i>
                                    <span>Select File</span>
                                </button>
                                <input type="file" class="input-hidden">
                            </div>
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
                <div class="row section-header header-space-lg">Address Area</div>
                <div class="input string">
                    <label><?= __('Address Area') ?></label>
                    <div class="input-select-wrapper">
                        <select name="Staff[gender_id]" id="staff-gender_id"
                            ng-options="option.id as option.name for option in genderOptions"
                            ng-model="selectedUserData.addressArea"
                            ng-init="selectedUserData.addressArea='';"
                            >
                            <option value="" >-- <?= __('Select') ?> --</option>
                        </select>
                    </div>
                </div>
                <div class="row section-header header-space-lg">Birthplace Area</div>
                <div class="input string">
                    <label><?= __('Birthplace Area') ?></label>
                    <div class="input-select-wrapper">
                        <select name="Staff[gender_id]" id="staff-gender_id"
                            ng-options="option.id as option.name for option in genderOptions"
                            ng-model="selectedUserData.birthplaceArea"
                            ng-init="selectedUserData.birthplaceArea='';"
                            >
                            <option value="" >-- <?= __('Select') ?> --</option>
                        </select>
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
            </form>
            </div>
            <div class="step-pane sample-pane" data-step="5" data-name="summary">
                <form class="form-horizontal ng-pristine ng-valid" accept-charset="utf-8" method="post" >
                    <div class="input string">
                        <label><?= __('OpenEMIS ID') ?></label>
                        <input type="string" ng-model="InstitutionStaffController['selectedStaffData']['openemis_no']" disabled="disabled">
                    </div>
                    <div class="input string">
                        <label><?= __('Staff') ?></label>
                        <input type="string" ng-model="InstitutionStaffController['selectedStaffData']['name']" disabled="disabled">
                    </div>
                    <div class="input string">
                        <label><?= __('Currently Assigned To') ?></label>
                        <input type="string" ng-model="InstitutionStaffController['selectedStaffData']['institution_staff'][0]['institution']['code_name']" disabled="disabled">
                    </div>
                    <div class="input string">
                        <label><?= __('Requested By') ?></label>
                        <input type="string" ng-model="InstitutionStaffController.institutionName" disabled="disabled">
                    </div>
                    <div class="input string">
                        <label><?= __('Position') ?></label>
                        <input type="string" ng-model="InstitutionStaffController.institutionPositionOptions.selectedOption.name" disabled="disabled">
                    </div>
                    <div class="input string">
                        <label><?= __('Staff Type') ?></label>
                        <input type="string" ng-model="InstitutionStaffController.staffTypeId.name" disabled="disabled">
                    </div>
                    <div class="input string">
                        <label><?= __('FTE') ?></label>
                        <input type="string" ng-model="InstitutionStaffController.displayedFTE" disabled="disabled">
                    </div>
                    <div class="input string">
                        <label><?= __('Start Date') ?></label>
                        <input type="string" ng-model="InstitutionStaffController.startDate" disabled="disabled">
                    </div>
                    <div class="input text">
                        <label><?= __('Comment') ?></label>
                        <textarea ng-model="InstitutionStaffController.comment" ng-init="InstitutionStaffController.comment = ''"></textarea>
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
        var datepicker0 = $('#Staff_start_date').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true, language: '<?php echo $dateLanguage; ?>'});
        var datepicker1 = $('#Staff_end_date').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true, language: '<?php echo $dateLanguage; ?>'});
        var datepicker2 = $('#Staffs_date_of_birth').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true, language: '<?php echo $dateLanguage; ?>'});
        var datepicker3 = $('#Staff_date_of_birth').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true, language: '<?php echo $dateLanguage; ?>'});
        $( document ).on('DOMMouseScroll mousewheel scroll', function(){
            window.clearTimeout( t );
            t = window.setTimeout( function(){
                datepicker0.datepicker('place');
                datepicker1.datepicker('place');
                datepicker2.datepicker('place');
                datepicker3.datepicker('place');
            });
        });
    });
</script>

<style>
    .btn-close {
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
    @media (min-width: 800px) {
        .row-content{
            display: flex; 
            align-items: flex-start;
        }
    }

</style>
