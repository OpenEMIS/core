### [3.63.1] - 2021-03-25
- Implemented: Reports > Staff > Staff Health report
- Bug Fixed: Reports > Students > Student Health report: Changes to report
- Bug Fixed: Institutions > Student > Pending Transfer In > Pending Approval from Receiving Institution: Error

### [3.63.0] - 2021-03-19
- Implemented: Student Profile feature
- Implemented: Changes to daily recording of Meal distribution to students
- Bug Fixed: Administration > System Setup > Attendances > Attendances tab : The period_id shuffles across attendance periods when it is reordered. This can create data inconsistencies in report
- Bug Fixed: Administration > System Setup > System Configuration > Coordinates: To allow decimal input
- Bug Fixed: Administration > System Setup > System Configuration > Profile Completeness : Ability to configure features included in profile completeness
- Bug Fixed: Institutions > Academic > Programmes > Edit: Upon editing page without checking any Subjects and saving record, 404 error encountered
- Bug Fixed: Institutions > Academic > Subjects > View: Student is having both Withdrawn and Enrolled Status in Subjects page even though student is currently having Enrolled status
- Bug Fixed: Institutions > Students > Individual student > Academic > Outcomes tab > Subjects dropdown showing unassigned subjects

### [3.62.3] - 2021-03-15
- Implemented: Feature for school activities/houses/clubs
- Implemented: Enhancement of Translations
- Implemented: Improvement of the school closing process changes
- Bug Fixed: Institutions > Surveys > Forms : Unable to save even though mandatory fields are entered
- Bug Fixed: Institutions > Academic > Programme > Add/Edit: Number of Subjects listed is not the same as number of Subjects configured in Education Structure
- Bug Fixed: Institutions > Academic > Subject: Multigrade Class was created but only 1 Subject has Subject allocated for the Grades in the Multigrade
- Bug Fixed: Institutions > Academic > Subjects > View: Student who was Withdrawn and Enrolled back in the same Institution is still having Withdrawn Status in Subjects page
- Bug Fixed: Institutions > Add page : Unable to save due to unknown error
- Bug Fixed: Institutions > Infrastructure > Overview: Unable to edit record
- Bug Fixed: Institutions > Meals/ Institutions > Infrastructures : Menu for Infrastructure is hidden in Meals
- Bug Fixed: Institutions > Survey: Data is missing upon saving Survey Form
- Bug Fixed: Profile > Staff > Career > Classes tab: Encountered 404 error
- Bug Fixed: Report > Institutions > Staff Leave Report: Changes to report
- Bug Fixed: Report > Institutions > Staff Attendance Report: Changes to report
- Bug Fixed: Administration > Security > Roles > System Roles: Re-order function is not working

### [3.62.2] - 2021-03-05
- Implemented: Generate Risks for all school at one time in Administration > System Setup > Risks
- Bug Fixed: Profile > Student > Academic > Risk: 404 error upon viewing the Risk record
- Bug Fixed: Institutions > Meals > Students > Export: Name column only has First Name of students
- Bug Fixed: Institution > Attendance > Staff: When editing the Time in and Time Out, the up and down button for the minutes should be increased or decreased by 1 instead of 15
- Bug Fixed: Reports > Institutions > Programmes: Missing Programmes column
- Bug Fixed: Administration > System Setup > Localization > Translations: Added new data for Infrastructure Needs report
- Bug Fixed: Reports > Institution > Student Absence report: Changes to the report
- Bug Fixed: Profile > Student > Academic: Academic Page is missing
- Bug Fixed: Institutions > Students > Academic > Programmes tab: Student's Start Date was edited after being Admitted to a new Institution but the new Start Date did not reflect in student's Academic page

### [3.62.1] - 2021-02-26
- Implemented: Added extra columns for abscence reasons to Attendance Report
- Implemented: Staff Profile feature
- Implemented: Enhancement of Drop-Out and Withdraw function
- Implemented: Enhancement of Student Transfer function
- Bug Fixed: Reports > Profile: Upon clicking the generate button, Profiles were not being generated
- Bug Fixed: Institutions > Academic > Classes > Add: Unable to select Homeroom Teacher is shift field has been selected
- Bug Fixed: Institutions > Students > Import > Import Student Guardians: Add Guardian National ID column
- Bug Fixed: Institutions > Attendance > Student: Multigrade Teacher unable to mark attendance
- Bug Fixed: Institutions > General > Map: Latitude Length and Longitude Length is not reflecting the length that is configured in System Configurations
- Bug Fixed: Institutions > Students > Bulk Promote: When selecting Promoted, the Next Grade field should not be mandatory
- Bug Fixed: Administration > Education Structure > Programmes > Edit: Record disappeared after edit
- Bug Fixed: Staff Payslips API: API Error

### [3.62.0] - 2021-02-19
- Implemented: Infrastructure module - Export feature
- Implemented: Enhancement of the Core survey module: survey school coverage feature
- Implemented: Staff Payslips feature
- Implemented: Changes to student and staff health vaccination
- Implemented: Import feature for importing the student marks on the assessments from Excel
- Implemented: Allow user to view the reports in browser and add export / print the report
- Implemented: Improvement of the Workbench functions
- Implemented: Import Staff Salaries function
- Bug Fixed: Institutions > Meals > Students: To add All Days page
- Bug Fixed: Institutions > Students > Meal: Add permission to view and allow students to view their own student meal page
- Bug Fixed: Reports > Workflows: Add Areas to filter institutions list
- Bug Fixed: Institutions > Student > Academic > Programmes: End Date was not update when Academic Period End Date was changed
- Bug Fixed: Daily recording of Meal distribution issue
- Bug Fixed: Institutions > Attendance > Student: Student Transferred still appears in both Institutions if Transfer is in the middle of the week
- Bug Fixed: Institutions > Staff > Career > Class: Staff is assigned as Secondary Teacher but did not appear in Class tab
- Bug Fixed: Institutions > Survey > Forms: Forms with sections that have special characters is not working
- Bug Fixed: Institutions > Staff > Salary: Data is not saved in staff_salary_transactions table

### [3.61.10] - 2021-02-09
- Bug Fixed: Institutions > Attendance > Students: Student transferred to a new Institution and then transferred back to the old Institution has double record in Attendance page
- Bug Fixed: Staff > Institution > Staff > Pending Transfer In > Approve: Encountered 404 error
- Bug Fixed: Reports > Workflows: Workflow Records - Institutions > Student Transfer > Receiving - Done report does not generate.
- Bug Fixed: Reports > Institution > Committees Report (PTA and School Board): No data generated and changes to Columns and Filter

### [3.61.9] - 2021-02-05
- Bug Fixed: Reports > Institutions > Committees: Not generating
- Bug Fixed: Reports > Institutions > Class Attendance Marked Summary: the selectable date range is incorrect
- Bug Fixed: Reports > Institutions > Student Absence report: Changes to optimise generation
- Bug Fixed: Reports > Institutions > Students > Import Extracurricular: Remove OpenEMIS ID in reference tab
- Bug Fixed: Reports > Institutions > Student Absence report: changes
- Bug Fixed: Reports > Institutions > Student Attendance Summary: Not generating
- Bug Fixed: Reports > Institutions > Income and Expenditure report: To add From Date and To Date on the report generation page
- Bug Fixed: Reports > Institutions > Infrastructure > Room: Progress bar moves but does not complete
- Bug Fixed: Profile > Student > Academic > Report card and Risks tab : System should not show record for all students
- Bug Fixed: Profile > Student > Academic > Risk tab: 404 error

### [3.61.8] - 2021-02-04
- Bug Fixed: Profile > Student > Academic > Subjects, Absence, Outcomes, Competencies tab : System should not show record for ALL students

### [3.61.7] - 2021-02-02
- Bug Fixed: Institution > Attendance > Students: Unable to view attendance and Secondary Teacher should be able to view and mark attendance

### [3.61.6] - 2021-01-30
- Implemented: Export Institutions > Visits
- Bug Fixed: Institution > Staff > Professional > Qualifications/License tab > Add: 404 error
- Bug Fixed: Profile and Directory pages: Encountered 404 error
- Bug Fixed: Administration > Training > Courses > Add: 404 error
- Bug Fixed: Reports > Institutions > Student Attendance Summary: Excel tab do not tally with the data on the Excel sheet
- Bug Fixed: Institution > Students > Pending Transfer In/Out: Student awaiting for transfer with Open status not appearing in Pending Transfer In/Out
- Bug Fixed: Institutions > Performance > Assessments : Students that are transferred out of school and get transferred back into same school status is not showing enrolled
- Bug Fixed: Institutions > Attendance > Students > Export: Export file is not reflecting data correctly
- Bug Fixed: Profiles > Students > Academic > Classes tab : System should not show record for ALL students
- Bug Fixed: Administration > Academic Periods : Setting academic period to current should copy data from previous years
- Bug Fixed: Institutions > Attendance > Students: Transferred student appeared in the Attendance list even before her Start Date
- Bug Fixed: Workbench> Survey: Survey does not appear in workbench until user access institutions survey manually
- Bug Fixed: Institution > Assessments : Missing marks for a specific class/subject
- Bug Fixed: Institutions > Classes > Add : List of Homeroom/Secondary teacher is not populated
- Bug Fixed: Institution > Attendance > Staff > Edit: Time in reverted to default time which is 7am after editing the Time In and Time Out

### [3.61.5] - 2021-01-21
- Implemented: Improve readability of the student status in the current year
- Implemented: Improvement of the school closing process
- Implemented: Export staff extracurricular activities
- Implemented: Reports > School Profile Report
- Bug Fixed: Institutions > Students > Nationalities tab > Add/Edit: Validate button is not validating Identity Number correctly
- Bug Fixed: Reports > Student > Subject and Book List: Issues
- Bug Fixed: Institutions > Students > General > Identities tab: Issue Date and Expiry Date to remove default date
- Bug Fixed: Institutions > Performance > Report Cards: No Download PDF for bulk download
- Bug Fixed: Reports > Staff > Position Summary Report : Changes
- Bug Fixed: Reports > Students > BMI Status Report : Issues
- Bug Fixed: Reports > Students > Risk Assessment Report : Changes
- Bug Fixed: Institutions > Committees Add/Edit page
- Bug Fixed: Add User from External Source: OpenEMIS ID should be the same as from OpenEMIS Identity for the same user
- Bug Fixed: Reports > Institution > Guardians Report: Changes
- Bug Fixed: Reports > Institutions > Staff Attendance/Staff Leave: Include columns
- Bug Fixed: Reports > Students > Students with Special Needs: Changes
- Bug Fixed: Reports > Students > Not Assigned to a Class: Changes
- Bug Fixed: Reports > Students > Enrolment Summary: Changes
- Bug Fixed: Institutions > Finances > Budget/Income/Expenditure : Budget appears across all Schools and user unable to view attachments
- Bug Fixed: Institutions > Staff > Salaries : Remove additions, deductions column from staff_salaries table
- Bug Fixed: Profiles > Students > Guardian : Slow query when accessing this page
- Bug Fixed: Institutions > Students > Import: To allow other users besides System Administrator to Import Extracurricular

### [3.61.4] - 2021-01-08
- Bug Fixed: Reports > Institutions > Body Masses : Remove Type filter from report generation to enable user to generate report for all schools
- Bug Fixed: Reports > Institution > Infrastructure Report
- Bug Fixed: Institution > Appointment > Duties: 404 error
- Bug Fixed: Security > Roles > Permissions : Allow users to enabled/disable View for Assessment/Attendance Archive buttons
- Bug Fixed: Reports > Students > Student Health Reports : Changes in report
- Bug Fixed: Reports > Institution > Staff Leave: To add Custom Fields on the report
- Bug Fixed: Institution > Academic > Classes: Unable to View/Edit record

### [3.61.3] - 2021-01-06
- Bug Fixed: Profile > Student > Academic > Behaviour tab: Student is able to see all Behaviour records in the system even though the records do not belong to the student
- Bug Fixed: Directory > Add: Encountered 404 error
- Bug Fixed: Institution > Students > Nationalities: Identity is not read-through even though the identity has been linked to the Nationality selected
- Bug Fixed: Reports > Institution > WASH Report
- Bug Fixed: Reports > Institutions > Classes/Subjects Report is not generating report
- Bug Fixed: Report > Institution > Classes
- Bug Fixed: 1.04 Reports > Institution > Subject Report

### [3.61.2] - 2020-12-30
- Bug Fixed: Student Profiles > Report Cards feature
- Bug Fixed: Institutions > Students > Undo feature
- Implemented: Improve management of student nationalities

### [3.61.1] - 2020-12-24
- Implemented: Enable Changes in the Education Structure across school years
- Bug Fixed: Institution > Performance > Assessment: Only show assigned subjects
- Bug Fixed: Issues with Archive feature

### [3.61.0] - 2020-12-22
- Implemented: Education System setup
- Bug Fixed: Webhook Institution Create/Update: To include institution_area_education_id and institution_area_administrative_id
- Bug Fixed: Webhook Subject create/update: To include institution_classes_name,institution_classes_id,education_grades_id,education_subjects_id
- Bug Fixed: Webhook Class Create/Update: To include education_grade_id
- Bug Fixed: Webhook Security User delete
- Bug Fixed: Webhook Student/Staff Delete: To include institution_id
- Bug Fixed: API endpoints to return JSON response and not redirects to the sign-in page
- Bug Fixed: Institution > Report Cards > Comments: Total Mark for Assessment is not reflecting even though Total Mark is filled up in Assessment page

### [3.60.0] - 2020-12-11
- Implemented: Devlop a function to show percentage of user profile data completion
- Implemented: Develop Data Archiving feature
- Implemented: Enhancement of the OpenEMIS mapping features
- Bug Fixed: Reports > Institution > Add > WASH Report : Changes in WASH report page
- Bug Fixed: Administrations > System Setup > APIs : Unable to change User Authentication API security permission

### [3.59.1] - 2020-12-05
- Implemented: Quick Search and Advanced Search option for Institution > Students List > Withdrawn
- Implemented: Enable Guardians to access their pupil's records
- Bug Fixed: Institution > Performance > Assessments > Edit: Marks are not displaying for Assessments with more than one Assessment Period
- Bug Fixed: Reports > Survey: Data reflected incorrectly upon generating and downloading the report
- Bug Fixed: Institutions > Performance > Assessment: Edit Permission do not work for Homeroom Teacher role
- Bug Fixed: Remove the InsitutionClassSubjects shell script as it keeps running in the background
- Bug Fixed: Validate Report Cards / Outcome / Competency Comments - Comments cannot start with special characters

### [3.59.0] - 2020-12-02
- Implemented: Enhancement of the Core survey report module
- Implemneted: Reports for the Infrastructure module
- Implemented: Automation of a revised WebGIS input file and API for user authentification
- Bug Fixed: Student Attendance POST API

### [3.58.3] - 2020-11-27
- Implemented: Add Results and Comments tabs under Institution > Student > Academic Outcomes
- Implemented: Add a chart for student/staff attendance to institution dashboard
- Implemented: Reports > Institution > Finance (Income and Expenditure) Report
- Bug Fixed: Import Extracurriculars: Changes to the Import Template
- Bug Fixed: Institutions > Students > Academic > Competencies tab: Competencies filter list down all Competencies even though student is not taking all
- Bug Fixed: Institutions > Attendance > Students: Add an Education Grade field
- Bug Fixed: Institutions > Attendance > Students: Disable edit for future date attendances
- Bug Fixed: Reports > Institutions > Special Needs Facilities: Changes to the report and filters
- Bug Fixed: Reports > Students > Guardians: Add an Institution filter option and an Institution column in Guardians report
- Bug Fixed: Institution > Academic > Schedules: Schedules not found even though user has been given the Permission rights

### [3.58.2] - 2020-11-19
- Data Archiving feature in OpenEMIS Core
- Develop Reports > Directoty > User List
- Develop API Feature student assessment (write)
- Develop API Feature student assessment (read)
- Develop Staff Duties feature
- Develop Export Subject List in Institutions > Subjects Page
- Develop Export Class List Institutions > Class Page
- Develop Reports > Staff > Duties Report
- Develop Reports > Institution > Summary Report
- Bug Fix in Institution > Finance > Expenditure > Add page
- Bug Fix in Institution > Finance > Income > Add page
- Bug Fix in Institutions > Performance > Assessments page
- Bug Fix in Institution > Attendance > Students page
- Bug Fix in Institutions > Students > Academic > Extracurricular page
- Bug Fix in Institution > Students > Import > Import Extracurricular feature
- Bug Fix in student overview page

### [3.58.1] - 2020-11-03
- Implemented: Webhook Feature class (delete)
- Implemented: Webhook Feature staff (delete)
- Implemented: Webhook Feature student (delete)
- Implemented: Webhook Feature subject (delete)
- Implemented: Develop a function to view students report cards online
- Bug Fixed: Institutions > Forms > Survey : System does not save mandatory dropdown fields when user clicks on save
- Bug Fixed: Administration > Survey > Forms: Survey created with "Open" status is still resulting in Institutions having "Not Completed" status in the Survey report
- Bug Fixed: Institution > Attendance > Staff/Student : Replace BEMIS ID with OpenEMIS ID
- Bug Fixed: Administration > Survey > Forms > Forms tab: To allow Repeater questions in Surveys
- Bug Fixed: Institutions > Attendance : Set default height for table row
- Bug Fixed: Institutions > Attendance : Set default height for table row

### [3.58.0] - 2020-10-27
- Implemented: Allow students from different class to be combined into the same subjects / Allow students from same class to split across multiple subjects
- Implemented: Administration > System Setup > Attendance - Student Mark Types: reorder function
- Implemented: Webhook Feature staff (update)	
- Implemented: Webhook Feature programme (delete)
- Implemented: Webhook Feature programme (update)
- Implemented: Webhook Feature programme (create)
- Implemented: Webhook Feature institution (delete)
- Implemented: Webhook Feature institution (update)
- Implemented: Allow user to set dates to Administrations > Attendance configurations
- Implemented: Add Subject filter to Class Attendance Marked Report 
- Implemented: Add a case workflow for unmarked attendance
- Bug Fixed: Webhooks: Missing field output after Update Programme has been triggered in Core
- Bug Fixed: Webhooks: Missing field output after Create Programme has been triggered in Core
- Bug Fixed: Webhooks: Missing field output after Update Staff has been triggered in Core
- Bug Fixed: Webhooks: Missing field output after Create Staff has been triggered in Core
- Bug Fixed: Webhooks Institution Delete: To remove some Webhooks field output
- Bug Fixed: Webhooks Programme Delete: To remove some Webhooks field output
- Bug Fixed: Webhooks Update Programme: Updated Webhooks fields
- Bug Fixed: Webhooks Create Programme: Updated Webhooks fields
- Bug Fixed: Webhooks: When Class is created in Core and Subjects are automatically created, Create Subject was not triggered in Webhooks
- Bug Fixed: Webhooks: Missing field output after Create Staff has been triggered in Core
- Bug Fixed: Login: 404 Error Message encountered upon successful login
- Bug Fixed: Institution > Attendance > Students: When changing Class dropdown, the page loads and it keeps loading

### [3.57.10] - 2020-10-02
- Implemented: Webhook Feature student (update)
- Implemented: Webhook Feature student (create)
- Implemented: Webhook Feature subject (update)
- Implemented: Webhook Feature subject (create)
- Implemented: Webhook Feature staff (create)
- Bug Fixed: Institution > Academic > Schedules > Timetable > Timetable tab: Rooms field has no option
- Bug Fixed: Administration > System Configuration > Webhooks : Add id to Class Create output
- Bug Fixed: Webhooks Output for Create Class: Missing fields in Webhooks Output
- Bug Fixed: Administration > System Configuration > Webhooks : Add institution id to Institutions Create output
- Bug Fixed: Administration > System Configuration > Webhooks : Add id to Class Update output
- Bug Fixed: Institutions > Attendance > Students: View and Edit permissions
- Bug Fixed: Institutions > Students/Staff > Add : Unable to create users as OpenEMIS ID is showing 1 or 2 digits.
- Bug Fixed: Institutions > Academic > Subjects : Subject list should follow Institution > Programme subject list

### [3.57.9] - 2020-09-25
- Bug Fixed: Webhooks Output for Update Class: Missing fields in Webhooks Output
- Bug Fixed: Administration > Security > Groups : 404 error when creating group that is linked to an institution

### [3.57.8] - 2020-09-22
- Bug Fixed: Institutions > Academic > Subjects: Subjects are not automatically created
- Bug Fixed: System Configurations > Authentication > Other Identity Providers > SAML: Increase the input length of the below fields from 50 to 100
- Bug Fixed: Reports > Institution > Student Attendance Summary Report: Report generated is inaccurate
- Implemented: Webhook Feature institution (create)
- Implemented: Webhook Feature class (update)
- Implemented: Webhook Feature class (create)

### [3.57.7] - 2020-09-17
- Bug Fixed: System Configurations > Authentication > Other Identity Providers > SAML: Increase the input length of the fields from 50 to 100
- Bug Fixed: Reports > Institution > Student Attendance Summary Report: Report generated is inaccurate

### [3.57.6] - 2020-09-10
- Bug Fixed: Institution > Attendance > Students : Access for Secondary Teacher/ Subject Teacher
- Bug Fixed: Institutions > Academic > Subjects: System does not create subjects based on configuration in National Level

### [3.57.5] - 2020-09-07
- Bug Fixed: Institutions > Attendance > Students: Unable to view/mark attendance even though permission for All Classes and All Subjects have been given
- Bug FIxed: Institutions > Performance > Outcomes: Problems viewing all outcomes when editing

### [3.57.4] - 2020-09-01
- Bug Fixed: Report > Institutions > Student Attendance Summary report : Include gender columns
- Bug Fixed: Institutions > Academic > Subjects: Subjects are not automatically created

### [3.57.3] - 2020-08-18
- Bug Fixed: Institutions > Attendance > Students: Unable to view/mark attendance even though permission for All Classes and All Subjects have been given
- Bug FIxed: Institutions > General > Overview: Fax is not reflecting as Mobile after it has been updated in Labels
- Bug Fixed: User > Nationalities : Ordering of nationalities should be the same as ordering of nationalities in Field Options>Nationalities
- Bug Fixed: Administration > System Setup > Education Structure > Grades: Reorder function is not working

### [3.57.2] - 2020-08-12
- Bug Fixed: Institutions > Attendance > Students: Period Names are in the wrong order
- Bug Fixed: Institutions > Attendance > Students: Teachers are able to view/mark attendance of other classes and grades that they are not assigned to

### [3.57.1] - 2020-08-12
- Bug Fixed: Institutions > Academics > Classes: Number of Subjects not reflected
- Bug Fixed: Institution > Attendance > Students: Periods are not displaying accordingly
- Bug Fixed: Administrations > Performance > Assessments > Assessment Periods: Encountered 404 error when editing record
- Bug Fixed: Institutions > Dashboard: Total Number of students should only consist of students with Enrolled Status
- Bug Fixed: Translation : Reports > Institution > Subject Report : Report headers is not translated
- Bug Fixed: Directory > Student > Overview tab: Gender field unable to edit
- Bug Fixed: Institutions > Attendance > Students > All Days filter: Error Encountered
- Bug Fixed: Institutions > Student/Staff > Internal Search: Columns are not resizable

### [3.57.0] - 2020-07-28
- Implemented feature to mark student attendance by subject
- Implemented institution Finance (Income/Expenditure) feature

### [3.54.2] - 2020-07-13
- Bug Fixed: Pages where reordering of items do not save when refreshed
- Bug Fixed: Administration > Survey > Forms: Mandatory Fields are empty but can be Approved

### [3.54.1] - 2020-07-09
- Bug Fixed: Institutions > Examinations > Results > View: 404 error 
- Bug Fixed: Institution > Academics > Programmes > Edit: 404 Error encountered

### [3.54.0] - 2020-07-03
- Implemented Student Extracurricular Activities Import
- Implemented Dhivehi translations to Student Report
- Implemented Download and Export of Class list and Subject list

### [3.53.1] - 2020-6-23
- Bug Fixed: Reports > Remove abc option from format options
- Bug Fixed: API > Gender not working
- Bug Fixed: Institution > Attendance > Student > Comment box for Reason/Comment not placed accurately in the cell box
- Bug Fixed: Reports > Survey > Status in report is still showing Open when Date Disabled for the survey is changed to a past date

### [3.53.0] - 2020-6-19
- Implemented Reports > Staff Leave Report
- Implemented Reports > Staff > Training Report
- Implemented Reports > Staff > Position Summary Report
- Implemented Reports > Staff Attendance and Leave Reports

### [3.52.0] - 2020-6-10
- Implemented Reports > Students > Risk Assessment Report
- Implemented Reports > Students > Health Report
- Implemented Reports > Students > BMI Status Report
- Implemented Reports > Students > Not Assigned to a Class
- Implemented Reports > Students > Enrolment Summary Report
- Implemented Reports > Students > Special Needs Report
- Implemented Reports > Students > Absent Report
- Implemented Reports > Students > Subject and Book List Report

### [3.51.1] - 2020-6-05
- Bug Fixed: API for Staff Photo > Not able to fetch staff photo
- Bug Fixed: API for fetching of Staff List as per user authentication
- Bug Fixed: API for fetching Institutions as per user login and role
- Bug Fixed: API Staff can only see their own name on Attendance > Staff

### [3.51.0] - 2020-6-04
- Implemented Administration > Attendance: Develop feature to rename Attendance periods	
- Implemented Reports > Institution > WASH Report
- Implemented Reports > Institution > Infrastructure Report
- Implemented Reports > Institution > Special Needs Facilities
- Implemented Reports > Institution > Guardians Report
- Implemented Reports > Institution > Committees Report (PTA and School Board)
- Implemented Reports > Institution > Body Mass Report
- Implemented Reports > Institution > Subject Report
- Implemented Reports > Institution > Classes Report
- Implemented Reports > Student/Staff > Photo Report

### [3.50.3] - 2020-5-08
- Bug Fixed API: Fetch Institutions data on behalf of the user role.

### [3.50.2] - 2020-4-30
- Develop all asociated APIs to add an Institution and Student

### [3.50.1] - 2020-4-20
- Bug Fixed Institutions > Performance > Report Cards: Left Navigation Menu collapsed when selecting the last filter on the page
- Bug Fixed Institutions > Examinations > Students: 404 Error when assigning students into Examinations
- Bug Fixed Administration > Workflow > Rules tab > Student Attendance: Missing post events
- Bug Fixed Administration > Report Cards: Configuration for Template generation moved to Administration > Performance > Report Cards
- Bug Fixed Administration > System Setup > Labels: Report Cards Labels
- Bug Fixed Administration > Security> Roles: General Checkbox is not working in RTL Firefox browser
- Bug Fixed Staff Leave: Unable to add leave that spans over multiple academic periods
- Bug Fixed Textareas does not have validation for maximum number of characters resulting in error page
- Bug Fixed General > Error 404 Page: No arabic version for RTL

### [3.49.1] - 2020-4-15
- Bug Fixed Administration > Security > Users: 404 error when clicking on View and Edit function
- Bug Fixed Administration > System Setup > Localization > Translations: Added "Create New Student"
- Bug Fixed Administration > Survey > Rules: Text in Dropdown boxes get cut off
- Bug Fixed Institutions > Student > Transfer: Student not able to be transferred due to absence record
- Bug Fixed Institutions > Students/Staff: Hide mini dashboard when user selects a non-current Academic Period
- Bug Fixed Institutions > Survey > Forms: To allow at least 5 columns in one view
- Bug Fixed Institutions > Performance > Assessments: If a student is transferred mid year the student's assessments result will not be removed
- Bug Fixed Institutions > Performance > Assessments: Assessment Reports will inlcude students in all statuses
- Bug Fixed Institutions > Classes: Total number of students should not include the count for Repeated students
- Bug Fixed Institutions > Staff > Add: Academic period field removed

### [3.49.0] - 2020-03-30
- Implemented Year Acquired field in Institution > Infrastructure > Overview to be Mandatory
- Implemented the Move of Institutions > Report Cards > Report Cards Statuses to Institution > Performance and rename it to Report Cards
- Implemented a Requester field in Institution > Students > Counselling to record who requested for student counselling
- Implemented a Academic Period filter to Staff > Subjects page
- Implemented Import function for Administration > Performance > Competencies
- Implemented Shifts field on Institution > Staff > Add Staff

### [3.48.3] - 2020-03-12
- Bug Fixed Institutions > Performance > Competencies/Outcomes : Staff unable to view competencies/outcomes of assigned classes/subjects
- Bug Fixed Institutions/Directory > Add User : Unable to save when user enter identity number

### [3.48.2] - 2020-03-07
- Bug Fixed Institutions > Performance > Assessments : User is unable to view classes that have subjects assigned to them
- Bug Fixed where staff with an End of Assignment position get a 'Date from is greater than end date of staff' error when applying for leave
- Bug Fixed Administration > Workflow > Rules > Student Attendances: Missing options for post events assignments
- Bug Fixed Support date plugin for multiple languages
- Bug Fixed where Institutions > Students > Create New Student: Nationalities Order is Incorrect
- Bug Fixed Administration > Textbook > Import: Error 404 when importing records 
- Bug Fixed Institution > Cases > Student Attendance : Set All grades as Default

### [3.48.1] - 2020-03-02
- Bug Fixed for Administration > Security > Roles > System Role: Enabled Delete permission for Student Attendance
- Bug Fixed for Institution > Staff > Staff Release Configurations
- Bug Fixed for Student > Academic > Classes : System does not display student's current class in Classes tab
- Bug Fixed for Administration > Security > Users: Nationality and Identity Type field displays index instead of actual data

### [3.48.0] - 2020-02-19
- Implemented the ability for Secondary Teacher to enter comments on behalf of Homeroom Teacher

### [3.47.1] - 2020-02-12
- Bug Fixed for Leave Application for Staff that has two positions
- Bug Fixed Institution > Academic > Programme: 404 error encountered after creating a Programme
- Bug Fixed Institution > Attendance > Staff: User is not able to remove start and end time
- Bug Fixed Institution > Performance > Assessment > Report: Report generated blank for student who had 0 marks
- Bug Fixed Reports > Institutions > Subjects : To remove Subject Name Column and Include Class Name Column
- Bug Fixed Adminsitration > System Setup > Label : Labels with OpenEMIS ID are not updated with the changes
- Bug Fixed Institution > Report Cards > Comments: Comments not displaying students on 2nd and 4th page
- Bug Fixed Reports > Institutions: New staff attendance report
- Bug Fixed Institution > Attendance > Staff : To develop import feature for new staff attendance

### [3.47.0] - 2020-02-01
- Implemented a new Report: Reports > Students > Guardians
- Implemented a System Configuration option to Enable/Disable the Generate All Button for Student Report Cards
- Implemented Progress indicator for Report Card generation

### [3.46.2] - 2020-01-14
- Bug Fixed Institution > Student > Academic: Withdraw Workflow not completed, but student status is changed to Withdrawn
- Bug Fixed Institution > Student > Academic: Future date Student Withdrawal status did not change when date arrives
- Bug Fixed Institution > Academics > Programmes: When adding a programme there is a 404 Error
- Bug Fixed Institution > Staff > Career > Leave: Staffs who have a Assigned and an End of Assignment Position in the current Insitution cannot apply for leave

### [3.46.1] - 2020-01-07
- Implemented Save as Draft button for Graduation process 
- Implemented Institution > Staff >Staff Release : Release To option not mandatory
- Bug fixed student status when Promoted: Institution > Students > Individual Student Promotion
- Bug fixed student status when Withdrawn: Institution > Students > Student > Withdraw
- Bug fixed spelling error for Difficulty: Institutions > Students/Staff > Special Needs > Assessments
- Bug fixed to change status of students who are wrongly assigned to Withdrawn status with a Data Patch
- Bug fixed Examinations > Register (Single and Bulk): 404 Error
- Bug fixed Reports > Institutions > Survey : To differentiate Status column for Completed/Not Completed/Open
- Bug fixed Institutions > Academic > Programmes : Unable to sort according to start date/end date
- Bug fixed Institutions > Student > Attendance (All days/Individual day): Reduce the height of each row
- Bug fixed Institution > Attendance > Student Import: Unable to import Late records

### [3.46.0] - 2019-12-13
- Implemented students with Special Needs report in Reports > Institution 
- Implemented import feature in Staff > Qualification
- Implemented ability to add/remove subjects to/from programmes in Institution > Academic > Programmes
- Implemented ability to show default identity in Institution > Classes/Subjects
- Implemented default identity in Staff > Qualification report

### [3.45.1] - 2019-12-06
- Implemented indication to identify if user is a staff/student  in Institution > Add Students/Staff
- Implemented sort button in Institution > Students > Academic > Programme
- Bug fixed on Administration > Examinations 
- Bug fixed on updating Title and Footer Administration > System Setup > System Configurations > System 
- Bug fixed on displaying custom fields in Directory > General > User Overview
- Bug fixed on Institution > Report Card > Statuses
- Bug fixed on Institution > Staff > Career > Leave 
- Bug fixed on repeating grade in transferred school
- Bug fixed on Institution > Student Promotion/Repeat process
- Bug fixed on Institution > Attendance > Staff 5 minutes interval time

### [3.45.0] - 2019-11-28
- Implemented lesson timetable feature: Institution > Academic > Schedules

### [3.44.1] - 2019-11-01
- Bug fix on Attendance: Changes made in Attendance not reflected in individual student profile.
- Bug Fix on Competencies: Not availabe in student profile > academic
- Bug Fix on Competencies: Crate permissions for users to grant access for Competencies
- Bug Fix on student status after Promotion and Repeat

### [3.44.0] - 2019-10-25
- Bug Fix on Report > Student Report slow generation
- Implemented Competencies tab to students profile
- Implemented sorting by first name in Classes and Subjects
- Implemented sorting of Subjects Alphabetically when adding subjects on Staff > Career > Subjects > Add page
- Implemented a change of the institution column to comments on Student > Academic > Absences page
- Implemented a new formatting for the Notices box to add a video

### [3.43.9] - 2019-10-22
- Performance > Outcome > Export: Blank fix
- Administration > Performance > Outcomes > Grading Types: 404 error fix
- Index identity_numbers in security_users table
- Institution > Staff > Career > Leave: Historical leave data
- Administration > Risk : Unable to delete risk criteria from existing Risk fix
- Administration > Sys Config > Staff Release: Implement another rule to only allow release outside of own provider
- Institutions > Dashboards : Discrepancies in the total number between two students graphs fix
- Translations : Labels are not translated fix
- Report Cards > Comments: Increase comment box height, Change comment input field into textarea to support multi-line comment
- Report Cards > Comments: Add "Display xx Records" to allow user to view more records in one page
- Institution > Staff > Career > Leave : new template to work with new leave creation format
- Institutions>Academic>Textbook>Import: Textbook ID should autogenerated if user leave blank in Textbook ID column

### [3.43.8] - 2019-10-08
- Administration > Examinations : Make Education Subject mandatory
- Infrastructure : Start date : Added a Tooltip
- Directory > Import: Import validation should follow settings in System Configurations
- Reports>Institutions>Student Body Masses: To have select all institutions option
- Administration > System configuration > Institution > Shifts : Allow adding of more than 4 shifts
- Attendance & Absent > Export Report > Missing Staff records
- Performance > Outcome/Competency: Increase overall comment box to fit 5 lines of text
- Institution > Students > Promote/Graduate : Notification Message 
- Institutions > Attendance > Students : Export
- Administration > Performance > Assessment > Assessment periods > 404 error when updating assessment period fix
- Performance > Assessments: Only display Enrolled/Promoted/Graduated students
- Performance > Outcomes > Export: Promoted student is not in the excel fix
- Reports > Survey: All Institutions > Only show Institutions that are covered within the Institution Type
- Institutions > Attendance > Student : Dashboard update

### [3.43.7] - 2019-10-09
- Institution > Student > Add Student. Mismatched OpenEMIS ID and username assigned to the students in the system.
- Transferred status should override Enrolled status
- Institutions > Students > Import : Incorrect error message during student import
- Administration > Examinations > Exam Centre : Fax should not be a mandatory criteria when creating an exam centre
- Institution > Students > Pending Transfer Out: Create a transfer record in sending Institution when receiving Institution initiate a transfer
- Institution > Attendance > Staff: Remove time in and time out
- Institution > Attendance > Student/Staff: Order Name by Alphabetical Order
- Institution > Attendance > Staff : export feature for Staff attendance
- Profiles> Scholarship > Add: User should not be able to apply to a closed scholarship
- Insitutions > Attendance> Staff Attendance: Attendance marking to be disabled for that user if user has a full day leave
- Administration > Survey > Questions: Tooltips

### [3.43.5] - 2019-09-18
- Bug fix on Administration > System Setup > Field Options > Staff Position Titles > Disable Force Delete when a record has other information linked
- Bug Fix on System Configurations > Student Settings: Increase Maximum Students Per Class/Subjects to 200
- Big Fix on Administration > System Setup > Localization > Translations: added the following translations for the Institutions > Student > Add page warning labels
- Bug Fix on Institutions > Students > Promote: Student should remain 'Enrolled' until Student Promoted Effective Date
- Bug Fix on Institutions > Risks : System displays Generated On/By and updates Status once done
- Bug Fix on Institutions > Staff > Add : Staff Transfer Workflow

### [3.43.4] - 2019-07-31
- Bug fix on Institutions > Performance > Assessments page loading speed
- Bug fix on Institutions > Performance > Assessments page to allow Staff that has the right access to edit 
- Bug fix to split the configurations for Special Needs Referral reasons and Special Needs Assessments inn Special Needs feature 
- Bug fix to enable viewing of side panel in smaller screens in Institutions > Attendance feature
- Bug fix on identity Type not appearing in User > Overview page

### [3.43.3] - 2019-06-21
- Bug fix on bulk users import in Directories > Import page
- Bug fix on placeholder for student's next class in Institution > Report Cards

### [3.43.2] - 2019-06-13
- Bug fix on Administration > Examinations > Exam Centres
- Bug fix on Institutions > Students > Promotions Page, to show 'next class' options for graduated students if institution offer the programmes

### [3.43.1] - 2019-04-01
- Bug fix on Institution > Students > Export

### [3.43.0] - 2018-12-20
- Developed Configuuration option for default school landing page in Administration > System configuration > Institutions
- Developed Import template to link Guardian to Students in Institution > Student > Import
- Developed feature to integrate user creation in Moodle in Administration module
- Developed feature to generate student report in PDF in Institution > Performance > Assessments
- Implemented Contacts column in Import template in Directory > Import
- Implemented placeholder to display subjects students will be taking in the next Academic period in Report Card module
- Bug fix on Student's effective withdrawal date

### [3.41.1] - 2018-11-23
- Implemented feature to display Date of Birth and Institution fields to Directory search results
- Implemented feature to calculate Overal Average & Total Marks based on Report Card Template Start/End Date
- Implemented feature for users to access Student's Institution when navigating from Directory > Students > Academic pages
- Implemented feature for users to access Guardians's Information when navigating from Institutions > Students > Guardian pages 
- Bug fix on Institutions > Performance > Assessments > Report page
- Bug fix on Administration > Security > Roles > Permissions page
- Bug fix on Scholarship > Application page
- Bug fix on Institutions > Performance > Outcomes > Import page

### [3.41.0] - 2018-11-14
- Implemented a feature to pre-populate Next Class
- Re-developed Outcomes Import template
- Re-developed Staff attendance feature
- Implemented auto-assign to Scholarship Applicant at Open Status
- Bug fix on institutions > Infrastructures
- Bug fix on Institutions > Students > Promotions
- Bug fix on Institutions > Students > Academic > Programmes

### [3.40.0] - 2018-11-02
- Redeveloped Student Attendance feature
- Implemented single day attendance record for Student attendance
- Bug fix on Search bar in Institution > Attendance > Students page

### [3.38.0] - 2018-10-30
- Developed feature to allow user to set Longitude and Latitude to mandatory in Administration > System Setup > System Config
- Developed export feature for Institution > Performance > Outcome
- Implemented OpenEMIS ID and User's default identity in Reports > Staff/Students > Contacts Report
- Bug fix on report card generation process

### [3.37.0] - 2018-10-19
- Developed Demographics feature in Users > General > Demographics tab
- Developed Scholarship Award Disbursement Alert in Administration > Communications > Alert Rules
- Implemented ability to bulk update Student Pending Admission status in Institutions > Students > Pending Admission filter
- Implemented ability to access Guardian's information from Institutions > Students > Guardian
- Implemented comments field for Reassign action in all workflow-enabled feature
- Implemented Inclusive Education Visits feature in Institutions > Students > Visits

### [3.36.0] - 2018-09-28
- Developed Inclusive Education (Special Needs) feature in Profiles/Institutions/Directory > Special Needs
- Implemented a delete button to delete all Survey data in Institution > Survey
- Implemented Report Queue in Institutions > Report Card > Status page
- Bug fix on error 404 in Institutions > Staff > Export
- Bug fix on missing data when saving tables in Institutions > Surveys

### [3.35.0] - 2018-09-14
- Developed Scholarship deadline alert feature in Administration > Notification > Alert page
- Implemented Academic Period and Financial Assistance type filter in Reports > Scholarships > Recipient Payment Structures page
- Bug fix on Identity type in Directory > General > Overview page

### [3.34.0] - 2018-09-07
- Developed User Default Identity Report in Reports > Directory 
- Developed Staff Appraisal calculated fields in Administration > Appraisals > Scores
- Implemented additional column called Nationality in Report > Institution > Staff (Assigned) report
- Implemented default filters in Institutions > Cases
- Bug fix on Institutions > Survey Rules during xform generation for OpenEMIS Survey
- Bug fix on ability to save reords in Institutions > Performance > Outcomes 

### [3.33.1] - 2018-09-07
- Bug fix on ability to save records in Institution Survey table question type
- Bug fix on 404 error when Institution Survey rules answer was changed
- Bug fix on Institution Survey when Dependent question disappears 

### [3.33.0] - 2018-09-03
- Developed changes to Class Attendance Marked Report in Reports > Institution > Class Attendance Marked
- Developed post event rule for Secondary Teacher and Principal in Administration > Workflow > Rules
- Developed additional Financial Assistance type (Full scholarship, Partial scholarship, Grant, Distance Learning)
- Developed option to configure Scholarship Institution Choice in Administration > Scholarship > Applications > Institution Choices
- Developed feature to export Scholarships > Recipients > Payment Structures
- Introduced Annual Award Amount to Scholarships > Recipients > Payment Structures

### [3.32.3] - 2018-08-23
- Developed a feature to email all students their report card in Institutions > Report Card > Status
- Added Partial Scholarships and Grants to Scholarship module in Administration > Scholarships
- Added Scholarships Disbursement (Detailed) and Scholarship Enrollment reports in Reports module
- Developed a feature to retrieve Institution image through API

### [3.32.2] - 2018-08-13
- Bug fix on deleting rows and columns for table question type in Survey module

### [3.32.0] - 2018-08-03
- Developed School Feeder feature in Institutions Module
- Developed function to save class for next academic period in Institution > Students > Promotions page
- Developed feature to email student report card in Institution > Report Card

### [3.31.0] - 2018-07-20
- Developed student pending admission API
- Developed Potential Wrong Birthdates in Reports > Data Quality 
- Developed function to import height and weight for multiple students

### [3.30.2] - 2018-07-13
- Developed additional placeholders in Student Report Card

### [3.30.1] - 2018-07-05
- Added Started On and Completed On in Institutions > Report Cards

### [3.30.0] - 2018-07-02
- Developed audit reports via feature
- Implemented status column in Institution > Students > Import feature
- Change date format in Audit Report to yyyy-mm-dd to enable sorting

### [3.30.0] - 2018-07-02
- Developed audit reports via feature
- Implemented status column in Institution > Students > Import feature
- Change date format in Audit Report to yyyy-mm-dd to enable sorting

### [3.29.0] - 2018-06-22
- Data patch on Positions feature
- Develop Student Attendance Summary Report
- Develop Workflow Report
- Develop OpenEMIS Committees feature
- Develop ability to add multiple attachments to Students/Staff behaviour feature

### [3.28.1] - 2018-06-20
- Rename breadcrumbs and headers to the original name under Report module 

### [3.28.0] - 2018-06-14
- Rename Scholarships sub-menu in Administration > Scholarships > Details
- Added date range to Audit Login report
- Added "Forgot Password" and "Forgot Username" feature 
- Develop student capacity per class
- Develop Workflow Rules event in Administration > Workflows > Rules
- Develop Assets feature in Institutions > Assets

### [3.27.2] - 2018-06-11
- Added new labels to Administration > Translations
- Changed colours for Institution > Dashboard charts

### [3.27.1] - 2018-06-06
- Optimized the generation of Student Reports Card

### [3.27.0] - 2018-06-04
- Developed Recipients feature under Administration > Scholarships
- Removed the Title field in Staff > Career > Appraisal
- Added additional filters and columns to Institution > Cases
- Added Date of Birth to Report > Institutions > Student 
- Developed import feature for Institutions > Staff > Positions
- Developed import feature for Institutions > Positions

### [3.26.1] - 2018-05-25
- Created Deputy Principal system role in Administration > Security > Role

### [3.26.0] - 2018-05-18
- Developed Scholarship feature under Profile and Administration
- Implemented import feature for Staff Leave under Staff > Career > Leave
- Added Nationality and Identity Type fields when adding a new Guardian under Students > Guardian
- Fixed a bug where the Assignee is Unassigned upon creation of Cases under Institution > Cases

### [3.25.0] - 2018-04-20
- Added Column sorting to Administration > Education Structure > Grade Subjects
- Change height metric from Metres to Centimetres in Health > Body Mass feature
- Implemented rule for staff transfer in Administration > System configuration
- Implemented workflow for Staff Appraisal feature in Staff > Career > Appraisals
- Implemented score number in Staff Appraisals feature in Staff > Career > Appraisals
- Implemented Student limit per class and subject in Administration > System Configuration

### [3.24.3] - 2018-04-20
- Implemented "Note" in Administration > Survey > Forms

### [3.24.0] - 2018-04-20
- Input type validation has been added for Table field type under Administration > Survey > Questions
- Implemented links between Staff Position Titles and Staff Position Grades under Administration > Field Options > Staff Position Titles
- Implemented Gender for Guardian Relations under Administration > Field Options > Guardian Relations
- Health Insurances feature has been implemented under Institution > Student/Staff > Health
- Average and Total Marks columns have been added under Institutions > Report Cards > Comments
- Custom Filters field has been added under Administration > Survey > Forms

### [3.23.0] - 2018-04-06
- Implemented a non-mandatory field called 'Position' under Institutions > Students > Academic > Extracurriculars
- Updated placeholders for Competency sheets in the Report Card template
- Implemented import function in Institutions > Survey > Forms
- Developed 'Dropdown' field type under Administration > Appraisals > Criterias
- Implemented 'URL Validation' as a new Validation Rule for 'Text' field type under Administration > System Setup > Custom Field
- Replaced the map in Reports > Map from Google Maps to OpenStreetMap

### [3.22.0] - 2018-03-29
- Implemented Institutions search by Code and Name under Institutions > Advanced Search
- Implemented Placeholders for Total Marks & Average for each Academic Term in the Report Card template
- Implemented Outcome features in the Report Card template
- Added Positions with Staff report under Reports > Institutions > Positions
- Added Description field under Institutions > Survey > Forms
- Image file will now be displayed as thumbnail for "File" field type under Administration > System Setup > Custom Field

### [3.21.0] - 2018-03-16
- Developed Student Attendance Report to see classes that have not been marked
- Default attendance is shown as not marked now
- Enabled multiple contact details under Institutions > General > Contacts
- Added Reports > Institutions > Cases - Student Attendances report
- Updated the format for Institution > Performance > Outcomes - Import template

### [3.20.0] - 2018-03-02
- Implemented Staff Appraisals feature enhancements
- Implemented security management API
- Fixed a bug where pagination does not retain the selected value

### [3.19.0] - 2018-02-23
- Implemented customisable workflow for Student Transfers and Admissions
- Marks below "Pass Mark" will now be displayed in red

### [3.18.0] - 2018-02-02
- Identity Number, Identity Type, and Nationality fields have been added for Guardian and Others user types
- Fixed a bug where some courses are not displayed in Institutions > Staff > Training
- Fixed a 404 error when performing individual promotion

### [3.17.0] - 2018-01-26
- The system allows multiple classes to share the same subject now
- A new Student Attendances workflow rule (Absentee Intervention) has been added

### [3.16.0] - 2018-01-19
- Implemented customisable workflow for Student Dropouts
- Renamed Indexes to Risks

### [3.15.0] - 2018-01-05
- Implemented Learning Outcomes in Institutions > Performance

### [3.14.1] - 2017-12-18
- Bug fix on 404 errors in Administration > Training > Session
- Bug fix on SSO redirect url
- Bug fix on Institution > Add staff button greyed out
- Bug fix on approving Pending Admission for Transferred students from an Institution

### [3.14.0] - 2017-12-14
- Implemented Adaptation feature in Administration > System Setup > System configuration > Themes page
- Bug fix on 404 errors in Administration >System Setup > Field Options - Employment Types
- Bug fix on selecting a position in Institution > Add staff page

### [3.13.0] - 2017-12-08
- Implemented Institutions > Calender feature
- Implemented Institutions > WASH Feature
- Implemented Employments Feature
- Implemented ability to add more languages Administration > Localization > Langauages feature
- Added Bandwith to Utilities feature
- Added Count number of students in Institutions> Classes view page
- Bug fixes on 404 errors in Administration > Delete Users
- Bug fixes on 404 errors in Institutions > Report Cards
- Bug fix on slow load in Institutions > Competencies

### [3.12.0] - 2017-11-24
- Bug fixes on 404 errors

### [3.11.0] - 2017-11-16
- Implemented customisable workflow for Staff Transfer.
- Staff Transfer can now be triggered by both receiving and sending Schools.
- System now allows editing on Staff start dates. 
- Bug fixes (Student Report Card, Competency, Health - Body Mass)

### [3.10.15] - 2017-10-27
- User can now generate report of students from all programmes in Reports > Institution > Students
- Implemented export feature in Institution > Positions page

### [3.10.14] - 2017-10-23
- Implemented Transport feature in Institution module

### [3.10.13] - 2017-10-13
- Implemented Projects feature in Institutions > Infrastructure module 
- Implemented Needs feature in Institutions > Infrastructure module
- Implemented WASH feature in Institutions module
- Implemented Utilities feature in Institutions module 
- Implemented Area to Administration > Training > Sessions	

### [3.10.12] - 2017-10-06
- Implemented export feature in Institution > Positions page

### [3.10.11.3] - 2017-09-29
- Enhance the system performance during loading of Institution/Directory pages

### [3.10.11] - 2017-09-15
- Custom reports will generate based on User's area access
- System automatically enrols student if User that has permission to approve pending enrolments add those students into the school
- Implemented Textbook import function in Administration > Textbooks

### [3.10.10] - 2017-08-28
- System allows users to record student's Height and Weight in Students > Health > Body Mass page
- Added Comments field to Students Competencies in Institutions > Competencies > Items page
- System allows adding of Institution logo to Institutions > Overview page
- System allows a Secondary Home Room Teacher to be assigned to a class in Institution > Academic > Classes page

### [3.10.9] - 2017-08-16
- Allow for both specialisations and subjects in Staff > Professional Development > Qualifications page

### [3.10.8] - 2017-07-27
- Implemented Counselor Feature in Institution > Students > Counselling
- Upon generating a report in the Report module, system now includes filter selection in report name

### [3.10.7] - 2017-07-14
- Implemented configuration item to toggle whether the system should automatically add students into all subjects
- Implemented Staff Transfer report in Reports > Institutions > Staff Transfer

### [3.10.6] - 2017-07-07
- Implemented validation for Programme Code in Administration > Education Structure > Programmes page
- In Institutions > Attendance page, system will now count user as present when they are flagged as late
- Added "Field of Study" to Staff > Qualifications page

### [3.10.5] - 2017-06-30
- Implemented new reports module
- Implemented the ability for user to login using different IDP
- Added Report for Training Applications

### [3.10.4] - 2017-06-23
- Allows tabbing to next cell when entering Institution > Assessment results
- System now allows user to easily access their own Profile from left navigation menu

### [3.10.3] - 2017-06-20
- System Setup > Education Structure > Grade Subjects : Credit Hours can be entered in decimal form
- Username/Password is now mandatory when adding new user
- Included Area Education Name/Code and Area Administrative Name/Code in all Reports
- Implement tree hierarchy for Area Administrative/Education dropdown lists

### [3.10.2] - 2017-06-13
- Administration > Training : System allow users to import Trainees using their identity
- Add additional columns to Institution Staff Report (Sector, Provider, Position Title, All Identities)
- Add additional fields to Staff > Training Needs (Competency, Standard, Sub Standard and Reason)
- Ability to filter list of Students by Grade, Status and Gender in Institution > Classes
- Implemented Education Stages for Education Grades that requires grouping in Administration > Education Structure

### [3.10.1] - 2017-06-06
- Implemented Student Report card feature

### [3.9.14.1] - 2017-06-05
- System now displays the total mark for each Academic Term
- In Institution > Assessments, System will notify users when student's marks are not saved successfully
- Added an Academic Term filter to Institution > Assessments feature
- In Institution > Assessments, System locks the columns that contain students information for easier data entry.

### [3.9.14] - 2017-05-30
- Added an Export function to Institution > Assessments to only show marks based on user's permission
- Added Report for Training Applications
- User is now able to Import/Export Staff Salaries in Staff > Finance > Salaries
- In Institution > Add Staff page, System displays Position Grades in the Position list
- Added Nationality column in Administration> Examinations> Students> Registered/Not Registered
- System now allows user to sort list of students/staff by name

### [3.9.13] - 2017-05-17
- Implemented freezing of header and column panes in Institution > Competencies edit page for easier transaction

### [3.9.12] - 2017-05-08
- Implemented decimal type for custom fields
- Redevelop add multiple students to classes and subjects page

### [3.9.11] - 2017-04-25
- Implemented alerts for Training Credits Hours that does not fulfil required amount in Staff > Training > Courses tab
- Added a year filter for Reports > Institution with no Students
- Added Multi-grade column in Institution > Academic > Classes page
- Allows user to link specialisations to subjects in Staff > Professional Development > Qualifications page

### [3.9.9] - 2017-04-12
- Implemented Case management feature
- New Behaviour records will have to go through a workflow
- Implemented Staff Behaviour Report
- Implemented page to configure alert threshold for Staff leave, Staff licenses and Staff employment

### [3.9.8] - 2017-03-31
- Implemented Staff Professional Development Report

### [3.9.7] - 2017-03-24
- System now allows user to attach files to Staff Employment records
- Implemented Staff Salary Report in Reports> Staff> Salaries
- Implemented Staff Employment report in Reports > Staff > Employment
- System now allows user to set category(Major,Minor) to each Behaviour record
- Implemented a Staff Leave report in Reports > Institutions > Staff Leave
- System will send out email alert once user is Assigned to a workflow
- Implemented Staff License report in Reports > Staff > License
- Implemented security in User> General> Attachment tab

### [3.9.6] - 2017-03-20
- System will only allow linking of Classrooms to a Subject
- Included more information for Transfer/Dropout Student Reports
- System allows user to configure validation on Areas when creating/editing an Institution
- Students Nationality is included in Reports > Institution > Student Reports
- System usage reports for Staff is implemented in Reports > Staff > System Usage Reports

### [3.9.5] - 2017-03-10
- Applying Licenses for Staff has to go through a workflow
- Additional fields added for Staff Licenses

### [3.9.4] - 2017-03-01
- Implemented Out of School Children feature
- Added link to view changelog in Updates page
- Users can view allocated textbooks from the Students profile

### [3.9.3] - 2017-02-17
- System now restricts users to only view Reports that is generated by themselves
- System replaces the term "Dropout" with "Withdraw"
- System now allows user to view Student Examination Results from Institution module
- System now allows user to view Student Examination Results from Student's profile

### [3.9.2] - 2017-02-15
- Implemented Alerts feature

### [3.9.1] - 2017-02-07
- Implemented Student Competency Based Performance module

### [3.8.8] - 2017-02-02
- System now displays users who generated the report
- System now sorts report by date
- Institutions > Subjects : Users can filter subject list by classes based on their given permission

### [3.8.7] - 2017-01-20
- Institutions > Position : System now correctly displays list of current and past staffs

### [3.8.6] - 2017-01-16
- There is a wizard now when user tries to add Staff to an Institution

### [3.8.5] - 2017-01-06
- System allows user to search for Textbook name
- Implemented Institution and Grade filter in Students > Academic > Subjects page

### [3.8.4] - 2017-01-04
- System allows user to add Exam items manually
- System allows user to link an Exam items to an existing Subject
- Implemented version release management and approval feature

### [3.8.3] - 2016-12-23
- Implemented feature to allow user to create customised report in excel format in Institution > Student Results module

### [3.8.2] - 2016-12-16
- Added Education Grade column in Institution Staff Reports
- Implemented Import for Examination rooms
- Added Institution Code, Grade and Area in Institution Class Reports
- System no longer requires classes and teachers to be mandatory for School Visits

### [3.8.1] - 2016-12-05
- Added Textbook Import feature
- Added Textbook Report feature
- Implmented a new option called "Time" for Grading Type in Assessment module
- Implemented Textbook management module

### [3.7.5] - 2016-11-25
- System now allows additional products to be added to the application switcher list

### [3.7.4] - 2016-11-21
- Users can add classrooms to exam centers
- Users can assign invigilators to exam centers
- User is able to enter the student exam registration number or allow system to auto generate it
- System allows user to manage student's examination results

### [3.7.2] - 2016-11-10
- Directory page will show Advanced Search by default
- System allows user to search for all users regardless of user's permission
- System allows Institutions to request visits
- Staff > Professional Development > Appraisal Tab : Introduced an Appraisal feature
- System allows user to apply for training courses
- Admin is able to manage training course applicants

### [3.7.1.1] - 2016-10-26
- Introduced individual student promote/repeat feature that allows promote/repeat within the same year
- Institution > Studentlist will show enrolled students by default
- User is now able to perform Undo action on dropout and transferred students

### [3.7.1] - 2016-10-25
- There is a wizard now when user tries to add student workflow
- Adding students to an Institution will trigger Pending Admission workflow
- Student graphs are removed in Dashboard for Non-Academic Institution
- Staff Type (Full Time/Part Time) column added in Staff > Position Page
- System now allows Institution to withdraw Transfer Requests
- System only allows transferring of students between two institutions
- Introduced individual student promote/repeat feature that allows promote/repeat within the same year
- There is a new field called Assignee when user tries to submit workflow items
- System allows user to connect to an external database to add students to an Institution

### [3.6.7] - 2016-10-14
- The date and time in reports are fixed
- System allows user to search for Institution by Area in Student Transfer page

### [3.6.6] - 2016-10-07
- System splits the permission to view Salary list page and Salary Details page
- System now allows user to search accounts from different Identity types in Advanced Search option
- System now allows user to search Institutions that offers selected Education Grades in Advanced Search option

### [3.6.5] - 2016-10-03
- Institution contacts section is now moved from Overview page to Contacts page
- System now allows users to create Non-Academic Institutions

### [3.6.4] - 2016-09-23
- Introduced Examinations feature
- Institution Sector is now linked to Institution Provider

### [3.6.3] - 2016-09-13
- Users can now switch in between different OpenEMIS products while still logged in
- Users should be able to see the default identity number on Student/Staff overview page
- Out of School Student Report will include the student's default identity
- Users can update Areas via API

### [3.6.2] - 2016-08-31
- Occupier schools only have view access on Infrastructure of the Owner's School
- Infrastructure custom fields is now linked to Infrastructure Types instead of Levels
- You can now track changes done to Infrastructure Rooms at a Year level
- Assessment periods is now configurable in Assessment Periods tab
- Able to assign different grading type to an Education subject for an Assessment Period
- Assessment Reports is available on School and Class level
- Deleting an Academic Period will now delete all related information linked to that academic period

### [3.6.1] - 2016-08-24
- Added Shift type in Institution Overview page and is searchable in Advanced Search
- Rename This Institution and External Institution to Owner and Occupier
- Shift name changed to Shift option and is configurable in Field Options
- When adding student absence record, the start date and end date must be within the academic period
- System will only show available positions in Add staff page
- Student punctuality is based on classes shifts start time
- Staff punctuality is based on Institution's shifts start time
- Added a maximum limit of 1000 to Maximum Credits for Training Courses
- Fixed a bug on Survey Rules where checkboxes are not shown properly
- Fixed 404 error on certain pages

### [3.5.12] - 2016-08-03
- Fixed a bug that caused some Field Options not able to be sorted

### [3.5.11.2] - 2016-07-25
- Student out of school has been moved from Institution Reports to Student Reports
- All Institution Reports are now generated based on Area access of the user

### [3.5.11] - 2016-07-21
- Student and Staff Reports now include Institution Types
- Fixed a bug that prevented users from deleting Infrastructure records
- Fixed a bug that caused 404 error page when adding Staff Behaviour
- Fixed a bug that shows incorrect message to the user after deleting an institution
- Fixed a bug that caused the system to add un-selected subjects when adding new subjects
- Class field in Student Transfer Approval page is now an optional field
- Fixed a bug that removes Group Administrator when editing User Groups
- Added new field called Type in Comments feature
- Fixed a bug that creates 2 Enrolled records when performing Student Transfers
- Added new workflow event to delete the record when Cancel is clicked during Open status
- The number of credit hours in Training Courses should fetch the correct value from System Configuration now
- Security role names are now unique in the system

### [3.5.10] - 2016-07-12
- User must enter a valid end date when creating a new academic period now
- Fixed a bug that prevented user from viewing guardian profile
- Fixed a bug that caused 404 error page when accessing Students -> Fees after the student record was deleted
- Dropdown field type can now be set as mandatory in Survey setup
- Institution can only be deleted if there are no associated records linked to it (eg. students, classes, programmes)

### [3.5.9.3] - 2016-06-30
- Added new permissions for creating Student or Staff Profile

### [3.5.9] - 2016-06-22
- Fixed a bug that caused 404 error page when deleting a Specialisation in Field Options
- Fixed a bug that caused 404 error page when removing a user from a security group
- You can now select from a list of year options as graduate year when adding a staff qualification
- Fixed a bug that caused user not able to select the last day of the academic period when editing student academic information

### [3.5.8] - 2016-06-17
- Institutions Programmes cannot be deleted if there are students associated with it
- Users can now import staff into schools via Excel
- Fixed a bug that caused user not able to access Add Guardian Page when permission was given
- Fixed a bug that caused Homeroom teacher name not appearing in Institution -> Classes
- When generating a report, the Format is set to Excel as default
- Fixed a bug that caused workflow items not showing correctly in workbench

### [3.5.7] - 2016-06-13
- Fixed a bug that caused 404 error page in Staff Training page after the training course was deleted
- You can now transfer students from one grade to another grade
- Fixed a bug that caused the system to allow user to add classes to grades that are no longer active
- Fixed a bug that caused Students not able to be added to Class via Pending Admission workflow
- Fixed a bug that caused Training Session -> External Trainer not to be saved properly
- New Report - Staff Qualifications
- Users can now see the summary of student results in Student -> Academic -> Results
- New Report - Student Absences
- Students can only be added to schools with Academic Periods of Year level
- You can now find Institutions easily when performing Student Transfer
- In Institutions -> Students, only Homeroom teachers are allowed to edit their own students
- Fixed a bug that caused 404 error page when deleting an Area

### [3.5.6] - 2016-06-01
- Zero value can now be saved properly in Survey Forms
- Removing students from Classes now delete Results, Attendance, Behaviour data linked to that student properly
- New Feature - You can now configure Rules to hide/show survey questions
- Fixed a bug that caused 'Select All' checkboxes affecting disabled checkboxes
- Usernames should be at least 6 characters now
- Administrative Boundaries and Nationalities now use two different list instead of the same one
- Classes and Subjects can now be linked to Staff via Careers
- Fixed a bug that caused user created roles not able to access Permissions
- Fixed a bug that caused Arabic translation not working properly in Attendance report
- Staff FTE effective start date is now set as the End Date of the old position instead of the Start Date of the new position
- Fixed a bug that caused 404 error page when users access Directories
- Fixed a bug that caused date format to be different between Successful Records and Failed Records in Import
- Fixed a bug that caused users not able to select Academic Periods in Institutions -> Visits
- Fixed a bug that caused validation to happen on End-date while adding Memberships or Licenses
- Fixed a bug that caused users not able to save training needs
- Fixed a bug that caused Infrastructure code to be generated incorrectly
- Fixed a bug that caused 404 error when saving a workflow

### [3.5.5.2] - 2016-05-20
- Survey Reports will now be generated based on the user's security access
- New Feature - You can now configure Rules to hide/show survey questions
- New Feature - You can now configure survey questions to be repeated
- Fixed a bug that caused Students in Subjects to be duplicated
- Fixed a bug that caused Sunday not showing up in Attendance
- Staff Status are now translated properly
- Institution list is now ordered by code in Student Transfer
- Fixed a bug that caused 404 error page when user accessing Classes -> Edit
- Changing of position title on Institution -> Positions is no longer available after the position is created
- Fixed a bug that caused user to experience 502 bad gateway after login

### [3.5.5.1] - 2016-05-18
- Fixed a bug that caused the system to calculate using a wrong admission age when adding a student to institution

### [3.5.5] - 2016-05-17
- Added Difficulties to Special Needs
- Position Number cannot contain spaces now
- Fixed a bug that prevent the system from perform Student Repeat
- OAuth and SAML authentication via mobile can now be supported
- Deleting Institutions now deletes Staff Profile change requests properly
- Fixed 504 bad gateway issue when login by certain users
- Fixed a bug that caused Coordinate tooltip not to display properly
- Optimisation on database queries
- Fixed a bug that caused specific users not able to access newly created schools
- Fixed a bug that caused 404 error page when accessing Rubrics
- Added xform validations to work with mobile app
- Fixed a bug that caused Programmes not able to save during Edit
- Class List now shows the Total number of Students
- Fixed a bug that caused 404 error page when user edits Institutions -> Subjects

### [3.5.4] - 2016-05-09
- Students can only be added to schools with academic period of Year level now
- Fixed a bug that caused Area Levels not displaying correct names
- Fixed a bug that caused 404 error page when access Workflow Statuses
- Fixed a bug that caused 404 error page when trying to add a new workflow
- Add attachments now retain the original file name
- Fixed a bug that caused additional 's' character in breadcrumb when accessing in Arabic
- Added a new Survey question type - Coordinates
- Added new Survey question type - File

### [3.5.3] - 2016-05-05
- Fixed a bug that caused 404 error when import a survey with date/time questions
- Fixed a bug that caused 404 error page when Change in FTE is selected in Staff Profile
- Added a class filter to retrieve student list by class in Promote/Transfer/Undo pages
- Fixed a bug that cause 404 error when deleting students from institutions
- Fixed a bug that allows Students to be enrolled in two different school at the same time
- Fixed a bug that cause 404 error when saving students in class
- Fixed a bug that cause 404 error page when approving a Transfer request when a Promoted/Graduate student

### [3.5.2] - 2016-04-25
- Added Weights to Assessment Items and Assessment Periods for calculating results
- Improved UI on Results data entry
- Position workflow items will now appear on workbench
- Fixed a bug that caused surveys not showing in workbench for school principal
- Fixed a bug that caused 404 error in Classes -> Add when an academic period with no available grades is selected
- Fixed a bug that prevent user from creating new students in institution

### [3.5.1] - 2016-04-18
- Attachment is now an optional field in Staff Qualifications
- Added Position Type to Staff reports
- Fixed a bug that caused System Configurations not showing all items in arabic language
- Displaying content in Arabic language now uses a larger font size for easier readability
- Implemented Staff Change in Assignment Workflow and Staff Transfer Workflow

### [3.4.18] - 2016-03-31
- Fixed a bug on Import that caused the system not able to import students properly
- Institution Positions can now be identified as a Homeroom position. Only Staff with Homeroom position will be displayed during creation of Class
- Fixed a bug where workflow actions not appearing for the configured workflow roles
- Fixed a bug that caused Nationality and Identity not saved properly when creating a new Student record
- In Institutions -> Staff list, the position filter now displays the position titles instead of all positions in the institution
- Fixed a bug that caused 404 error page when adding an Absence record for Staff in an Institution
- The system will generate codes automatically when adding new infrastructure
- Fixed translations on Attendance
- Name column in Institution -> Staff list can now be sorted
- Added Class as mandatory field in Transfer Approval page
- Fixed a bug that caused user created roles in user groups not displaying

### [3.4.17.2] - 2016-03-28
- Compilation of Translation files is now done automatically by the system whenever translations are added or modified
- Fixed a bug that caused user able to link a position title to a security role that is not set as visible
- Fixed a bug that caused edit and delete not functioning properly on Institution list page
- Subject code will now be shown together with subject names in Qualification Specialisation
- Fixed a bug that caused 404 error page when performing search while navigating on other list page
- Fixed a bug where '-- Select --' option appearing unnecessarily in multiple options select box
- Username can only be modified by Administrator now
- The system now displays only available positions when adding staff to schools
- The name of the Role will now be displayed as part of the header while editing permission for a specific role
- Fixed a bug that caused users in user groups not able to create institutions in their designated areas

### [3.4.17] - 2016-03-17
- Fixed a bug that caused the user not able to upload image properly
- Student Admission age calculation now uses the year value from academic period instead of the current year
- Gender is now properly translated
- Accessing Institutions features will now check for permissions of the correct role that is assigned to that user for that selected institution
- Added Advanced Search for Student and Staff in Institutions
- Fixed a bug that caused the user not able to upload image properly
- Survey reports now include "Not Completed" surveys correctly
- Fixed a bug that caused positions not showing up when accessed by users in user groups

### [3.4.16] - 2016-03-10
- Fixed a bug that caused field options default value not selected, also added a '-- Select --' option if no default value is set
- Added Class names to Student Report in Institutions
- Added description field for Workflow Actions
- Add new Absence Type - Late
- Fixed a bug that caused 404 error page when access Maps
- Area Administrative and Area Education is now properly translated
- Fixed a bug on Institutions -> Students -> Add that caused incorrect calculation of admission age
- Fixed a bug that caused out of memory issue when accessing classes without a valid programme set in the school

### [3.4.15] - 2016-03-02
- Survey Table type questions will now be displayed in Reports
- Fixed a bug that caused loading animation to appear on the wrong position
- Fixed a bug that display wrong validation message when importing students to schools
- Fixed a bug that caused 404 error page when approving Dropout requests
- New API to query students by Identity type and number

### [3.4.14] - 2016-02-22
- Users can now import trainees to Training Sessions using excel spreadsheet
- Fixed a bug that caused Staff Career tabs not navigating to the correct page
- When accessing Attendance, date will always default to current week and day now
- Fixed a bug that caused search not working properly in Attendance
- Added hyperlinks to OpenEMIS ID/Name field in various pages to link to user profile
- Fixed a bug that caused student import to class successfully when using a class code that is not in the same academic period
- Implemented the link between Qualification Specializations and Education Subjects
- Fixed a bug that caused sorting to refresh advanced search results
- Fixed a bug that caused 404 error page when access Administration -> Administrative Boundaries

### [3.4.13] - 2016-02-15
- Class column in Institutions -> Students can be sortable now
- The system does not allow users to create shifts with overlapped timing now
- Improved on Shifts list page to display shifts used by other schools
- Reports -> Maps permission is now available
- Positions are searchable by Position titles now, titles and grades are sortable now
- Fixed a bug that caused principal accounts not able to see the areas in Institution -> edit mode
- Added SAML2 support for Single Sign On
- Fixed a bug that caused the breadcrumb home icon not working properly Actions
- Implemented customised workflow for Positions
- The system does not allow new student profile to be created if the student age does not fall within the allowed age range
- Infrastructures can be added as hierarchy now

### [3.4.12] - 2016-02-05
- Fixed a bug that caused student data not removed completely when deleting a student from a school
- Fixed a bug that caused school name and status not displaying the correct value in Directories
- Fixed a bug that caused Add buttons in Salaries to behave like Save button (only happens in Chrome browser)
- Fixed a permission issue that caused user not able to access Directories -> Classes/Subjects
- Fixed a bug that caused 404 error page when trying to access Field Options -> Position Titles
- Position Types (Teaching/Non-Teaching) is now linked to Position Titles instead of Institution Positions
- Improved on the validation error messages when importing data via excel spreadsheet
- Added Google Authentication support
- The system will not allow delete operations on Class if students still exists in the class
- Fixed a number of css issues

### [3.4.11] - 2016-01-29
- Fixed a bug that caused removing last student from class not to be deleted
- Fixed a bug that caused custom fields not appearing in Institutions
- Fixed a bug that caused Roles with All Classes and All Subjects permissions not able to see students in Results module
- Fixed a bug that caused Roles with Promote/Graduate permissions not able to see Promote/Graduate buttons
- Added Dropout and Transfer Reasons will be displayed when accessing Institutions -> Students -> Academic -> Programmes
- Added names, contacts, identities, positions to Advanced Search
- The system will auto generate position numbers based on the school code + a set of random numbers

### [3.4.10] - 2016-01-20
- Fixed a bug that caused 404 error page when Promoting students but no students are selected
- Added Search in Student Admission Page (Institutions -> Students -> <Select Pending Admission>)
- Deleting an institution will now remove all students records properly
- When adding student to school, the student age is calculated by year only (previously it is determined by month and year)
- Fixed other UI related issues
- Added Student Out of School Report
- Fixed a bug that caused records not appearing after added by users with specific roles
- Improved UI of System Setup -> Assessments
- Transfer and Dropout button now appears in Student -> Overview (Only for Enrolled students)

### [3.4.9] - 2016-01-15
- Fixed bugs related to date showing in arabic language
- Fixed a bug that prompt user to repair excel worksheet after downloading
- Fixed a bug that caused student report progress bar to always stay at 0%
- Fixed a bug that caused 404 error page when importing students to school
- Added Staff absence report
- Added validation rule to prevent user from adding spaces when changing passwords
- Added Student Teacher ratio report and Student Class ratio report under Reports -> Institutions
- Improved UI when entering data for Institutions -> Visits
- Improved styles/formats for import templates, merged all references excel sheets into one sheet called References

### [3.4.8] - 2016-01-14
- Added new feature to Undo a student status. This feature can be accessible in Institutions -> Students -> Undo
- Fixed a bug that caused classes and subjects information not removed when the student is removed from the grade in a school
- Fixed a bug that caused 404 error page when accessing Student Behaviors

### [3.4.7] - 2016-01-11
- Enabled the Health module for Users
- Administrative Boundaries can now be deleted and reassociate with another record
- Fixed a bug that caused permissions (All Classes, My Classes) not working correctly
- Added Area codes to Institution Reports
- Added Map under Reports to show the location of all Institutions with a valid longitude and latitude
- Fixed a bug that caused a repair popup prompt to appear when downloading reports
- During promotion/graduation, user will be able to review the changes in a confirmation page before proceeding with the actual operation

### [3.4.6] - 2016-01-07
- Fixed a bug that caused Institution column in Directory for Staff not displaying any information
- Fixed a bug that caused Institution Listing to display same school in every page for a non-admin user
- Mini dashboards are now reflecting the correct values in charts when user searches or selected options from the filters
- Staff and Student reports are showing complete information now
- After importing records, there is a 'Download Successful Records' button available for the users

### [3.4.5] - 2016-01-05
- Fixed a bug that caused 404 error page when saving area administrative in institutions
- Fixed a bug that caused user not able to see the roles when adding users into groups
- When performing bulk transfer on students, students with Enrolled status will not be included in the list
- Fixed a bug that caused assessments not showing newly added subjects in grades
- Modified the shifts UI to hide Location field when This school is selected
- Reports will now show the correct language

### [3.4.4] - 2015-12-31
- Added new feature to import students in classes
- Fixed a bug that caused duplicate records when editing Training Courses
- Fixed a bug that displays multiple records when user is creating multi-grade classes

### [3.4.3] - 2015-12-30
- Fixed a bug that caused 404 page when adding users to groups
- Start Date of Student Transfer is defaulted to current date now
- Added Assessment report in Institutions

### [3.4.2] - 2015-12-24
- Fixed a bug that caused an error while deleting staff records from institutions
- Added new feature to allow user to import students into institutions
- Fixed a bug that caused security roles not ordered correctly
- Fixed a bug that caused 404 error page when importing institutions
- Added new field called Network Connectivity in Institutions
- Fixed a bug that prevent user from entering 0 as value on Student admission age plus/minus
- Added new permissions for Import features
- Added two new reports in Institutions for generating Students and Staff records

### [3.4.1] - 2015-12-24
- Reorganised navigations to move existing pages into tabbed view
- Added new navigation called Directory to access user's information

### [3.3.10] - 2015-12-22
- Fixed a bug that caused different grades's admission age not to be updated based on configured value

### [3.3.9] - 2015-12-16
- Fixed a bug that caused rubrics not able to save or submit
- Adding Identities now checks for unique of the same identity type
- Added new report, Potential Duplicates
- Added new feature to import Attendance data

### [3.3.8] - 2015-12-15
- Fixed permissions on Attendance and Survey Reports
- Added delete function for Workflows

### [3.3.7] - 2015-12-08
- Added new feature Survey Import to allow users to use excel spreadsheet to import survey answers
- Fixed a bug that caused user not able to save Education Subjects in Education Grades
- Enabled Staff -> Needs and Staff -> Achievements
- Institutions add/edit page will only show the main country's areas in the Area Administrative field now

### [3.3.6] - 2015-12-02
- Fixed a couple of bugs related to Accounts

### [3.3.5] - 2015-11-27
- Fixed a bug that caused dates to save as 01-01-1970 when language is set to arabic
- Staff leave days are now auto-calculated based on start and end date
- Security -> Users will show usernames now
- Fixed a bug that caused next programmes not to be saved
- Fixed a bug that shows 404 error when user click on Subjects link in Students -> Subjects

### [3.3.4] - 2015-11-20
- Added Audit report to show all users and their last login
- Added Reports to show institutions with no student and institutions with no staffs
- Added new feature to allow users to import attendance data via excel

### [3.3.3] - 2015-11-17
- Fixed minor UI issues

### [3.3.2] - 2015-11-12
- Fixed a bug that caused 404 error page in Students -> Results
- Fixed warning messages on rubrics and student guardians when debug is turned on
- Fixed a bug that staff custom fields not working properly

### [3.3.1] - 2015-11-09
- Profession Development Tracking is now available under Administrations -> Trainings
- Fixed a bug on Survey type - Student List
- Fixed a bug on Staff Attendance edit page

### [3.2.10] - 2015-11-05
- Fixed a bug that caused survey template not able to be downloaded on mobile
- Improved performance when loading students/staff list
- Survey Report status is now configurable via Administration -> Workflows -> Statuses

### [3.2.9] - 2015-11-02
- Preference link is now working properly, user can edit their personal information
- Fixed a bug that caused other users to modify another user's information

### [3.2.8] - 2015-10-29
- Added Institution name and ID in Student report
- Fixed a bug that caused wrong absence date to be saved in arabic language

### [3.2.7] - 2015-10-26
- Fixed Rubrics Report
- Fixed some minor errors that appears when debug is turned on

### [3.2.6] - 2015-10-19
- Added workflows to institution's survey
- Added workflow statuses mapping so that customised statuses of survey can be mapped to workflow's steps for reporting purposes

