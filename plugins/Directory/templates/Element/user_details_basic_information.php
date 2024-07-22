<!-- File: src/Template/Element/user_details_basic_information.php -->
<div name="UserDetailsBasicInformation">
    <div class="row section-header header-space-lg"><?= __('Search By Basic Information') ?></div>
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
                    ng-change="changeGender()">
                <option value="">-- <?= __('Select') ?> --</option>
            </select>
        </div>
        <div ng-if="error.gender_id" class="error-message">
            <p>{{ error.gender_id }}</p>
        </div>
    </div>
    <div class="input date required">
        <label for="User_date_of_birth"><?= __('Date Of Birth') ?></label>
        <div class="input-group date " id="User_date_of_birth" style="">
            <input type="text" class="form-control "
                   name="User[date_of_birth]"
                   ng-model="selectedUserData.date_of_birth"
                   ng-change="changeDateOfBirth()">
            <span class="input-group-addon" style="color: #FFFFFF;background-color: #6699CC;"><i class="glyphicon glyphicon-calendar"></i></span>
        </div>
        <div ng-if="error.date_of_birth" class="error-message">
            <p>{{ error.date_of_birth }}</p>
        </div>
    </div>
</div>
