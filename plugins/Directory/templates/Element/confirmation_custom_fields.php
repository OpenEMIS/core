<div ng-if="selectedUserData.userType.name === 'Students'" ng-repeat="customField in customFieldsArray">
    <div class="row section-header header-space-lg">{{customField.sectionName}}</div>
    <div ng-repeat="field in customField.data">
        <div class="input string" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'TEXT' || field.field_type === 'TEXTAREA' || field.field_type === 'NOTE' || field.field_type === 'NUMBER' || field.field_type === 'DECIMAL'">
            <label>{{field.name}}</label>
            <input ng-if="field.field_type === 'TEXT'"
                   ng-model="field.answer"
                   type="text"
                   ng-required="field.is_mandatory !== 0"
            ng-change="unsetCustomError(field)"
            />
            <textarea ng-if="field.field_type === 'TEXTAREA' || field.field_type === 'NOTE'"
                      ng-model="field.answer"
                      type="text"
                      ng-required="field.is_mandatory !== 0"
                      ng-change="unsetCustomError(field)"
            >
            </textarea>
            <input ng-if="field.field_type === 'NUMBER'"
                   ng-model="field.answer"
                   type="number"
                   ng-required="field.is_mandatory !== 0"
            />
            <input ng-if="field.field_type === 'DECIMAL'"
                   ng-model="field.answer"
                   type="number"
                   step="0.01"
                   onKeyPress="if(this.value.length === 10) return false;"
                   ng-change="unsetCustomError(field); onDecimalNumberChange(field)"
                   ng-required="field.is_mandatory !== 0" />
            <div ng-if="field.errorMessage" class="error-message">
                <p>{{ field.errorMessage }}</p>
            </div>
        </div>
        <div class="input select" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'DROPDOWN'">
            <label>{{field.name}}</label>
            <div class="input-select-wrapper">
                <select name="Student[option_id]" id={{field.student_custom_field_id}}
                        ng-options="option.option_id as option.option_name for option in field.option"
                        ng-model="field.answer"
                        ng-change="unsetCustomError(field); changeOption(field,field.answer)"
                        ng-required="field.is_mandatory !== 0"
                >
                    <option value="" >-- <?= __('Select') ?> --</option>
                </select>
            </div>
            <div ng-if="field.errorMessage" class="error-message">
                <p>{{ field.errorMessage }}</p>
            </div>
        </div>
        <!-- POCOR-7874 little fix -->
        <div class="input date" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'DATE'">
            <label for={{field.student_custom_field_id}}>{{field.name}}</label>
            <div class="input-group date"
                 id={{field.student_custom_field_id}}
                 style="" datepicker=""
                 ng-model="field.answer"
                 ng-click="[field.isDatepickerOpen = !field.isDatepickerOpen]"
                 ng-init="unsetCustomError(field); field.isDatepickerOpen = false"

            >
                <input type="text" class="form-control "
                       ng-model="field.answer"
                       uib-datepicker-popup="dd-MM-yyyy"
                       is-open="field.isDatepickerOpen"
                       datepicker-options="field.datePickerOptions"
                       close-text="Close"
                       alt-input-formats="altInputFormats" style="width: calc(100% - 52px) !important"
                       ng-change="unsetCustomError(field); field.isDatepickerOpen = false"
                       ng-required="field.is_mandatory !== 0" />
                <span class="input-group-addon" style="background-color: #6699CC; color: #FFF;"><i class="glyphicon glyphicon-calendar"></i></span>
            </div>
            <div ng-if="field.errorMessage" class="error-message">
                <p>{{ field.errorMessage }}</p>
            </div>
        </div>
        <div class="input date" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'TIME'">
            <label for={{field.student_custom_field_id}}>{{field.name}}</label>
            <div class="input-group time"
                 uib-timepicker
                 ng-model="field.answer"
                 hour-step="field.hourStep"
                 minute-step="field.minuteStep"
                 show-meridian="field.isMeridian"
                 ng-change="unsetCustomError(field);"
            ></div>
            <div ng-if="field.errorMessage" class="error-message" style="margin-left: 150px;">
                <p>{{ field.errorMessage }}</p>
            </div>
        </div>
        <div class="input date" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'CHECKBOX'">
            <label for={{field.student_custom_field_id}}>{{field.name}}</label>
            <div class="input-group check_box">
                <div ng-repeat="option in field.option">
                    <input type="checkbox" id={{option.option_id}}
                           name={{option.option_name}}
                           value={{option.option_id}}
                           ng-model="option.selected"
                           ng-change="unsetCustomError(field); selectOption(field)"
                           ng-required="field.is_mandatory !== 0">
                    <label for={{option.option_id}}> {{option.option_name}}</label>
                </div>
                <div ng-if="field.errorMessage" class="error-message">
                    <p>{{ field.errorMessage }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div ng-if="selectedUserData.userType.name === 'Staff'" ng-repeat="customField in customFieldsArray">
    <div class="row section-header header-space-lg">{{customField.sectionName}}</div>
    <div ng-repeat="field in customField.data">
        <div class="input string" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'TEXT' || field.field_type === 'TEXTAREA' || field.field_type === 'NOTE' || field.field_type === 'NUMBER' || field.field_type === 'DECIMAL'">
            <label>{{field.name}}</label>
            <input ng-if="field.field_type === 'TEXT'"
                   ng-model="field.answer"
                   type="text"
                   ng-required="field.is_mandatory !== 0"
                   ng-change="unsetCustomError(field)"
            />
            <textarea ng-if="field.field_type === 'TEXTAREA' || field.field_type === 'NOTE'"
                      ng-model="field.answer"
                      type="text"
                      ng-required="field.is_mandatory !== 0"
                      ng-change="unsetCustomError(field)"
            >
            </textarea>
            <input ng-if="field.field_type === 'NUMBER'"
                   ng-model="field.answer"
                   type="number"
                   ng-required="field.is_mandatory !== 0"
            />
            <input ng-if="field.field_type === 'DECIMAL'"
                   ng-model="field.answer"
                   type="number"
                   step="0.01"
                   onKeyPress="if(this.value.length === 10) return false;"
                   ng-change="unsetCustomError(field); onDecimalNumberChange(field)"
                   ng-required="field.is_mandatory !== 0" />
            <div ng-if="field.errorMessage" class="error-message">
                <p>{{ field.errorMessage }}</p>
            </div>
        </div>
        <div class="input select" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'DROPDOWN'">
            <label>{{field.name}}</label>

            <div class="input-select-wrapper">
                <select name="Staff[option_id]" id={{field.staff_custom_field_id}}
                        ng-options="option.option_id as option.option_name for option in field.option"
                        ng-model="field.answer"
                        ng-change="unsetCustomError(field); changeOption(field,field.answer)"
                        ng-required="field.is_mandatory !== 0"
                >
                    <option value="" >-- <?= __('Select') ?> --</option>
                </select>
            </div>
            <div ng-if="field.errorMessage" class="error-message">
                <p>{{ field.errorMessage }}</p>
            </div>
        </div>
        <!-- POCOR-7874 little fix -->
        <div class="input date" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'DATE'">
            <label for={{field.staff_custom_field_id}}>{{field.name}}</label>
            <div class="input-group date"
                 id={{field.staff_custom_field_id}}
                 style="" datepicker=""
                 ng-model="field.answer"
                 ng-click="[field.isDatepickerOpen = !field.isDatepickerOpen]"
                 ng-init="unsetCustomError(field); field.isDatepickerOpen = false"

            >
                <input type="text" class="form-control "
                       ng-model="field.answer"
                       uib-datepicker-popup="dd-MM-yyyy"
                       is-open="field.isDatepickerOpen"
                       datepicker-options="field.datePickerOptions"
                       close-text="Close"
                       alt-input-formats="altInputFormats" style="width: calc(100% - 52px) !important"
                       ng-change="unsetCustomError(field); field.isDatepickerOpen = false"
                       ng-required="field.is_mandatory !== 0" />
                <span class="input-group-addon" style="background-color: #6699CC; color: #FFF;"><i class="glyphicon glyphicon-calendar"></i></span>
            </div>
            <div ng-if="field.errorMessage" class="error-message">
                <p>{{ field.errorMessage }}</p>
            </div>
        </div>
        <div class="input date" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'TIME'">
            <label for={{field.staff_custom_field_id}}>{{field.name}}</label>
            <div class="input-group time"
                 uib-timepicker
                 ng-model="field.answer"
                 hour-step="field.hourStep"
                 minute-step="field.minuteStep"
                 show-meridian="field.isMeridian"
                 ng-change="unsetCustomError(field);"
            ></div>
            <div ng-if="field.errorMessage" class="error-message" style="margin-left: 150px;">
                <p>{{ field.errorMessage }}</p>
            </div>
        </div>
        <div class="input date" ng-class="{'required': field.is_mandatory !== 0}" ng-if="field.field_type === 'CHECKBOX'">
            <label for={{field.staff_custom_field_id}}>{{field.name}}</label>
            <div class="input-group check_box">
                <div ng-repeat="option in field.option">
                    <input type="checkbox" id={{option.option_id}}
                           name={{option.option_name}}
                           value={{option.option_id}}
                           ng-model="option.selected"
                           ng-change="unsetCustomError(field); selectOption(field)"
                           ng-required="field.is_mandatory !== 0">
                    <label for={{option.option_id}}> {{option.option_name}}</label>
                </div>
                <div ng-if="field.errorMessage" class="error-message">
                    <p>{{ field.errorMessage }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
