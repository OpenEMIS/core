<div class="input string required">
    <?php $dynamicOpenemisNoHeader = (isset($dynamicOpenemisNoHeader) && !empty($dynamicOpenemisNoHeader)) ? h($dynamicOpenemisNoHeader) : __('OpenEMIS ID'); ?>
    <label><?= $dynamicOpenemisNoHeader ?></label>
    <input ng-model="selectedUserData.openemis_no" type="string" ng-disabled="selectedUserData.openemis_no">
</div>
<div class="input string required">
    <label><?= __('First Name') ?></label>
    <input ng-model="selectedUserData.first_name" type="string" ng-disabled="selectedUserData.first_name">
</div>
<div class="input string">
    <label><?= __('Middle Name') ?></label>
    <input ng-model="selectedUserData.middle_name"
           ng-change="setName()" type="string"
    >
</div>
<div class="input string">
    <label><?= __('Third Name') ?></label>
    <input ng-model="selectedUserData.third_name"
           ng-change="setName()" type="string"
    >
</div>
<div class="input string required">
    <label><?= __('Last Name') ?></label>
    <input ng-model="selectedUserData.last_name"
           type="string"
           ng-disabled="selectedUserData.last_name">
</div>
<div class="input string">
    <label><?= __('Preferred Name') ?></label>
    <input ng-model="selectedUserData.preferred_name"
           type="string"
    >
</div>
<div class="input select required">
    <label><?= __('Gender') ?></label>
    <input ng-model="selectedUserData.gender.name"
           ng-disabled="selectedUserData.gender.name"/>
</div>
<div class="input date required">
    <label for="User_date_of_birth"><?= __('Date Of Birth') ?></label>
    <div class="input-group date "  style="">
        <!-- POCOR-8613 -->
        <input type="text" class="form-control " name="User[date_of_birth]"
               ng-model="selectedUserData.date_of_birth"
               ng-disabled="selectedUserData.date_of_birth">
    </div>
</div>
