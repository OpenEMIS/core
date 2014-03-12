var i18n = {
	Config : {
		InvalidUser : '<?php echo T("You have entered an invalid username or password.", true); ?>',
		validLDAP : '<?php echo T("Connected Successfully!"); ?>',
		invalidLDAP : '<?php echo T("Connection Fail."); ?>'
	},
	Education : {
		textAddProgramme : '<?php echo T("Add Programme", true); ?>',
		noMoreSubjects: '<?php echo T("All subjects have been added.", true); ?>',
		emptyProgrammeCode: '<?php echo T("Please enter a programme code.", true); ?>',
		emptyProgrammeName: '<?php echo T("Please enter a programme name.", true); ?>',
		emptyDuration : '<?php echo T("Please enter a duration."); ?>'
	},
	App : {
		confirmDeleteContent : '<?php echo T("You are able to delete this record in the database. <br><br>All related information of this record will also be deleted.<br><br>Are you sure you want to do this?", true); ?>',
		confirmClearAllContent : '<?php echo T("Do you wish to clear all records?", true); ?>',
		dlgOptErrorDialog : '<?php echo T("Unexpected Error"); ?>'
	},
	Areas : {
		initAlertOptText : '<?php echo T("Unable to add Areas.<br/>Please create Area Level before adding Areas.", true); ?>',
		AreaLevelText : '<?php echo T("(Area Levels)"); ?>'
	},
	Attachments : {
		maskDeleteAttachments : '<?php echo T("Deleting attachment...", true); ?>',
		titleDeleteAttachment : '<?php echo T("Delete Attachment", true); ?>',
		contentDeleteAttachment : '<?php echo T("Do you wish to delete this record?", true); ?>',
		textUpdatingAttachment : '<?php echo T("Updating attachment...", true); ?>',
		textDeletingAttachment : '<?php echo T("Deleting attachment...", true); ?>'
	},
	BankAccounts : {
		validateAddBranch : '<?php echo T("Bank Branch is required!", true); ?>',
		textDelete : '<?php echo T("Delete", true); ?>',
	},
	CustomTables : {
		textValue : '<?php echo T("Value", true); ?>',
		textLowerCapValue : '<?php echo T("value"); ?>'
	},
	Enrolment : {
		textDuplicateAge: '<?php echo T("This age is already exists in the list.", true); ?>',
		textUnsavedData: '<?php echo T("Unsaved Data", true); ?>',
		contentUnsavedData: '<?php echo T("Please save your data before proceed. <br><br>Do you want to save now?", true); ?>',
		textLeaving: '<?php echo T("Are you sure you want to leave?"); ?>',
		textNoGrades: '<?php echo T("There are no grades in this programme."); ?>'
	},
	Finance : {
		textNoData: '<?php echo T("No Data", true); ?>',
		textNoGNP: '<?php echo T("GNP value is required."); ?>'
	},
	InstitutionSites : {
		textArea: '<?php echo T("Area"); ?>',
		textProgrammeSelect: '<?php echo T("Please select a programme first.", true); ?>',
		textClassSelectStudent: '<?php echo T("Please select a student first."); ?>',
		textClassSelectTeacher: '<?php echo T("Please select a teacher first."); ?>',
		textClassSelectSubject: '<?php echo T("Please select a subject first."); ?>'
	},
	Training : {
		textArea: '<?php echo T("Category is required!"); ?>'
	},
	Qualifications : {
		textCertificateRequired: '<?php echo T("Certificate is required!", true); ?>',
		textCertificateNoRequired: '<?php echo T("Certificate No. is required!", true); ?>',
		textInstituteRequired: '<?php echo T("Institute is required!"); ?>'
	},
	Population : {
		textSelectCountry: '<?php echo T("Please select a country before adding new records.", true); ?>',
		textErrorOccurred: '<?php echo T("Error has occurred.", true); ?>',
		textEmptyAge: '<?php echo T("Age cannot be empty.", true); ?>',
		textAgeMoreThanZero: '<?php echo T("Age must be more then 0.", true); ?>'
	},
	Batch : {
		textExecuteSuccess: '<?php echo T("Batch executed successfully.", true); ?>',
		textRunning: '<?php echo T("Running batch..."); ?>'
	},
	Report : {
		textGenerateOlapReport: '<?php echo T("Generating OLAP Report...", true); ?>'
	},
	Search: {
		textSearching: '<?php echo T("Searching...", true); ?>',
		textSorting: '<?php echo T("Sorting...", true); ?>',
		textNoCriteria: '<?php echo T("Please enter a search criteria.", true); ?>',
		textNoResult: '<?php echo T("Your search returns no result.", true); ?>'
	},
	General : {
		textDismiss : '<?php echo T("Click to dismiss", true); ?>',
		textNo : '<?php echo T("No", true); ?>',
		textYes : '<?php echo T("Yes", true); ?>',
		textCancel : '<?php echo T("Cancel", true); ?>',
		textDialog : '<?php echo T("Dialog", true); ?>',
		textAdd : '<?php echo T("Add", true); ?>',
		textError : '<?php echo T("error", true); ?>',
		textReconnecting: '<?php echo T("Reconnecting...", true); ?>',
		textRequiredField : '<?php echo T("Required Field", true); ?>',
		textSave : '<?php echo T("Save", true); ?>',
		textSaving : '<?php echo T("Saving please wait...", true); ?>',
		textRetrieving : '<?php echo T("Retrieving...", true); ?>',
		textAddingRow : '<?php echo T("Adding row...", true); ?>',
		textAddingOption : '<?php echo T("Adding option...", true); ?>',
		textLoading : '<?php echo T("Loading...", true); ?>',
		textLoadingList : '<?php echo T("Loading list...", true); ?>',
		textValidating: '<?php echo T("Validating...", true); ?>',
		textDelete : '<?php echo T("Delete", true); ?>',
		textAdding: '<?php echo T("Adding...", true); ?>',
		textRemoving: '<?php echo T("Removing...", true); ?>',
		textLoadAreas : '<?php echo T("Loading Areas", true); ?>',
		textSelect : '<?php echo T("--Select--", true); ?>',
		textConfirm : '<?php echo T("Confirm", true); ?>',
		textConfirmation : '<?php echo T("Confirmation", true); ?>',
		textDeleteConfirmation : '<?php echo T("Delete Confirmation", true); ?>',
		textWarningConfirmation : '<?php echo T("Warning", true); ?>',
		textDeleteConfirmationMessage : '<?php echo T("Do you wish to delete this record?", true); ?>',
		textRecordUpdateSuccess : '<?php echo T("Records have been added/updated successfully.", true); ?>',
		textFileRequired : '<?php echo T("File is required!", true); ?>',
		textStatusRequired : '<?php echo T("Status is required!", true); ?>',
		textContinue : '<?php echo T("Continue", true); ?>',
		iconMoveUp : '<?php echo T("Move Up", true); ?>',
		iconMoveDown : '<?php echo T("Move Down", true); ?>',
		iconToggleField : '<?php echo T("Toggle this field active/inactive", true); ?>',
	},
	SMS: {
		confirmModifySmsMessageContent: '<?php echo T("Note: Please clear the Responses page as existing responses may no longer match the updated Messages.", true); ?>'
	},
	Training: {
		confirmActivateMessage: '<?php echo T("Do you wish to activate this record?", true); ?>',
		confirmInactivateMessage: '<?php echo T("Do you wish to inactivate this record?", true); ?>'
	},
	Wizard: {
		title: '<?php echo T("Wizard", true); ?>',
		uncomplete: '<?php echo T("Unable to proceed until wizard is completed", true); ?>'
	}
}
