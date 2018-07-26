### [1.3.8] - 2018-05-21
- Added logic to render toolbarbuttons (in index/view/add/edit/delete pages) from PageController of application  and allow to override in beforeRender of individual controller
- Fixed notice error when no attachment.

### [1.3.7] - 2018-01-21
- Fixed issue with the RTL checking logic

### [1.3.6] - 2018-01-21
- Added image compression logic to the FileUploadBehavior

### [1.3.5] - 2017-10-25
- Fixed an issue where the file is always overwritten when there is no file upload
- Fixed an issue where select option is not displaying if the value is 0
- Fixed an issue where multi select value doesn't retain when validation fails

### [1.3.4] - 2017-10-23
- Fixed an issue where visible foreign key display fields are not part of the search in Index page

### [1.3.3] - 2017-10-20
- Fixed an issue where notice error is displayed on View page if there are no values set for a multiple select control
- Fixed an issue in isJson() where _ext is retrieved using a deprecated function
- Fixed missing control type 'password' in PageHelper

### [1.3.2] - 2017-10-20
- Fixed an issue where onchange function is not accessible due to permission checks

### [1.3.1] - 2017-10-19
- Fixed an issue where value is not rendered with break lines for textarea fields

### [1.3.0] - 2017-10-19
- Added logic to multiple select control to render values as comma-separated in view/delete page
- Added logic to PageController to load elements from controller's default model on initialised
- Added new function 'clear' in PageComponent to empty the elements array

### [1.2.2] - 2017-10-17
- Changed MissingActionException checks to move from Controller actions to beforeFilter

### [1.2.1] - 2017-10-12
- Fixed a bug on the onchange that causes a 404 error when trying to fetch the dependent options [Issue #13](https://bitbucket.org/korditpteltd/kd-cakephp-page/issues/13/executing-on-change-logic-in-edit-page)

### [1.2.0] - 2017-10-12
- Added logic to render labels (in index/view/delete pages) from predefined select options automatically.
- Added new function 'is' in PageComponent to check if the current request action is in the list of provided actions.
- Added new function 'getAction' in PageComponent to get the current request action.

### [1.1.3] - 2017-10-12
- Fixed an issue where the displayFrom is not mapped to newly added field. [Issue #10](https://bitbucket.org/korditpteltd/kd-cakephp-page/issues/10/values-from-the-displayfrom-are-not-mapped)
- Fixed multiple dependent on for dropdown and page filters. [Issue #11](https://bitbucket.org/korditpteltd/kd-cakephp-page/issues/11/filter-or-select-control-type-having)

### [1.1.2] - 2017-10-11
- Fixed an issue on Edit action where value are not returning the correct format for multiple select control. [Issue #9](https://bitbucket.org/korditpteltd/kd-cakephp-page/issues/9/multiselect-dropdown-chosen-select-edit)
- Added hasAttribute function in PageElement for checking the existence of an attribute.

### [1.1.1] - 2017-10-11
- Fixed an issue with the i18nFormat. Change the formatting from dd-MM-YYYY to dd-MM-yyyy to address an issue with the year value in the final week of the year. [Issue #8](https://bitbucket.org/korditpteltd/kd-cakephp-page/issues/8/intl-date-formatter-returning-wrong-year)

### [1.1.0] - 2017-10-11
- Added support for multiple select control using Chosen plugin in PageHelper. [Issue #5](https://bitbucket.org/korditpteltd/kd-cakephp-page/issues/5/multiselect-dropdown)

### [1.0.2] - 2017-10-10
- Fixed an issue in PageHelper that still shows Action button even though there are no actions. [Issue #1](https://bitbucket.org/korditpteltd/kd-cakephp-page/issues/1/action-button-still-showing-and-adding)
- Fixed an issue in PageHelper that display html elements wrongly for binary control type in view page. [Issue #3](https://bitbucket.org/korditpteltd/kd-cakephp-page/issues/3/view-page-show-link-for-attachment-even)
- Fixed an issue which caused querystring to reset when performing search with filter option selected. [Issue #4](https://bitbucket.org/korditpteltd/kd-cakephp-page/issues/4/filter-and-search-will-reset-each-other)
- Added logic not to perform search conditions on excluded fields. [Issue #7](https://bitbucket.org/korditpteltd/kd-cakephp-page/issues/7/search-function-also-search-the-file_name)

### [1.0.1]
- Initial setup