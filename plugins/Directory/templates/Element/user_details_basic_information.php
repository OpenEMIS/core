<!-- File: src/Template/Element/user_details_basic_information.php -->
<div name="UserDetailsBasicInformation">
    <div class="row section-header header-space-lg"><?= __('Search By Basic Information') ?></div>
    <div class="input string" ng-class="{'required': basicFieldsRequired}">
        <label><?= __('First Name') ?></label>
        <input ng-model="selectedUserData.first_name" ng-change="setName()" type="text" ng-required="basicFieldsRequired">
        <div ng-if="error.first_name && basicFieldsRequired" class="error-message">
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
    <div class="input string" ng-class="{'required': basicFieldsRequired}">
        <label><?= __('Last Name') ?></label>
        <input ng-model="selectedUserData.last_name" ng-change="setName()" type="text" ng-required="basicFieldsRequired">
        <div ng-if="error.last_name && basicFieldsRequired" class="error-message">
            <p>{{ error.last_name }}</p>
        </div>
    </div>
    <div class="input string">
        <label><?= __('Preferred Name') ?></label>
        <input ng-model="selectedUserData.preferred_name" type="string">
    </div>
    <div class="input select error" ng-class="{'required': basicFieldsRequired}">
        <label><?= __('Gender') ?></label>
        <div class="input-select-wrapper">
            <select name="{{ addNewStudentConfig ? 'Student[gender_id]' : (addNewStaffConfig ? 'Staff[gender_id]' : 'User[gender_id]') }}"
                    ng-attr-id="{{ addNewStudentConfig ? 'student-gender_id' : (addNewStaffConfig ? 'staff-gender_id' : 'user-gender_id') }}"
                    ng-options="option.id as option.name for option in genderOptions"
                    ng-model="selectedUserData.gender_id"
                    ng-change="changeGender()"
                    ng-required="basicFieldsRequired">
                <option value="">-- <?= __('Select') ?> --</option>
            </select>
        </div>
        <div ng-if="error.gender_id && basicFieldsRequired" class="error-message">
            <p>{{ error.gender_id }}</p>
        </div>
    </div>
    <div class="input date" ng-class="{'required': basicFieldsRequired}">
        <label ng-attr-for="{{ addNewStudentConfig ? 'Student_date_of_birth' : (addNewStaffConfig ? 'Staff_date_of_birth' : 'User_date_of_birth') }}">
            <?= __('Date Of Birth') ?>
        </label>
        <div class="input-group date" id="User_date_of_birth">
            <input type="text" class="form-control"
                   name="User[date_of_birth]"
                   ng-model="selectedUserData.date_of_birth"
                   ng-change="changeDateOfBirth()"
                   ng-required="basicFieldsRequired">
            <span class="input-group-addon" style="color: #FFFFFF;background-color: #6699CC;">
            <i class="glyphicon glyphicon-calendar"></i>
        </span>
        </div>
        <div ng-if="error.date_of_birth && basicFieldsRequired" class="error-message">
            <p>{{ error.date_of_birth }}</p>
        </div>
    </div>
</div>
<!-- POCOR-8613 start -->
<script>
    $(function () {

        var datepicker2 = $('#User_date_of_birth').datepicker({
            format: '<?= $datepickerFormat ?>',
            todayBtn: 'linked',
            orientation: 'auto',
            autoclose: true,
            language: '<?= $dateLanguage ?>'
        });

        $(document).on('DOMMouseScroll mousewheel scroll', function(){
            window.clearTimeout(t);
            t = window.setTimeout(function(){
                datepicker2.datepicker('place');
            });
        });
    });
</script>

<!-- POCOR-8613 end -->
