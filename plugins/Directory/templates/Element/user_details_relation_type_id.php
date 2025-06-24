<div name="user_details_relation_type_id">
    <div class="input select required error">
        <label> <?= __('Relation Type') ?></label>
        <div class="input-select-wrapper">
            <select name="User[relation_type_id]" id="user-relation_type_id"
                    ng-options="option.id as option.name for option in relationTypeOptions"
                    ng-model="selectedUserData.relation_type_id"
                    ng-change="changeRelationType()">
                <option value="">-- <?= __('Select') ?> --</option>
            </select>
        </div>
        <div ng-if="error.relation_type_id" class="error-message">
            <p>{{ error.relation_type_id }}</p>
        </div>
    </div>
</div>
