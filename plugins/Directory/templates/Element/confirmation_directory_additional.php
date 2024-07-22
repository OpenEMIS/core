<div class="row section-header header-space-lg"><?= __('Identities / Nationalities') ?></div>
<div class="input string" ng-show="!isSearchResultEmpty">
    <label><?= __('Nationalities') ?></label>
    <input ng-model="selectedUserData.nationality_name" type="string" ng-disabled="true"/>
</div>
<div class="input string" ng-show="!isSearchResultEmpty">
    <label><?= __('Identity Type') ?></label>
    <input ng-model="selectedUserData.identity_type_name" type="string" ng-disabled="true">
</div>
<div ng-class="nationality_class" class="input select" ng-show="isSearchResultEmpty">
    <label><?= __('Nationality') ?></label>
    <div class="input-select-wrapper">
        <select name="User[nationality_id]" id="user-nationality_id"
                ng-options="option.id as option.name for option in nationalitiesOptions"
                ng-model="selectedUserData.nationality_id"
                ng-change="changeNationality()"
        >
            <option value="">-- <?= __('Select') ?> --</option>
        </select>
    </div>
    <div ng-if="error.nationality_id" class="error-message">
        <p>{{ error.nationality_id }}</p>
    </div>
</div>
<div ng-class="identity_type_class" class="input select" ng-show="isSearchResultEmpty">
    <label><?= __('Identity Type') ?></label>
    <div class="input-select-wrapper">
        <select name="User[identities_type_id]" id="user-identities_type_id"
                ng-options="option.id as option.name for option in identityTypeOptions"
                ng-model="selectedUserData.identity_type_id"
                ng-change="changeIdentityType()"
        >
            <option value="">-- <?= __('Select') ?> --</option>
        </select>
    </div>
    <div ng-if="error.identity_type_id" class="error-message">
        <p>{{ error.identity_type_id }}</p>
    </div>
</div>
<div class="input string">
    <label><?= __('Identity Number') ?></label>
    <input ng-model="selectedUserData.identity_number" type="string"
           ng-disabled="!isSearchResultEmpty">
    <div ng-if="error.identity_number" class="error-message">
        <p>{{ error.identity_number }}</p>
    </div>
</div>
<div class="row section-header header-space-lg"><?= __('Other Information') ?></div>
<div class="input select">
    <label><?= __('Contact Type') ?></label>
    <div class="input-select-wrapper">
        <select name="Staff[gender_id]" id="staff-contact_type_id"
                ng-options="option.id as option.name for option in contactTypeOptions"
                ng-model="selectedUserData.contact_type_id"
                ng-change="changeContactType()"
        >
            <option value="">-- <?= __('Select') ?> --</option>
        </select>
    </div>
</div>
<div class="input string">
    <label><?= __('Contact Value') ?></label>
    <input ng-model="selectedUserData.contact_value"
           type="string"
           ng-change="changeContactValue()">
</div>
