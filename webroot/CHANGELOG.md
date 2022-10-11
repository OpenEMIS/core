### [3.75.31.2] - 2022-10-11
- Bug Fixed: Institutions>Students>Export: Students that are Repeated last year and Enrolled this year should show Enrolled (Repeater) in the report

### [3.75.31.1] - 2022-10-11
- Bug Fixed: Administrations > Workflow > Actions tab : Institution > Pending Student Transfer: Changes in workflow
- Bug Fixed: Institutions>Students>Student1>Academic>Assessment: It should show the current and past assessment marks even from other institutions
- Bug Fixed: Institutions > Students : Bulk Transfer for Enrolled students are showing the wrong results for "Next Grade" field

### [3.75.31] - 2022-09-29
- Bug Fixed: Reports > Institution > Class Attendance Marked Summary Report: Report is empty
- Bug Fixed: Institutions > Academic > Programmes > Delete Grades: When the system restricts the delete, the system counts Institution Students that are holding Enrolled status only

### [3.75.30] - 2022-09-27
- Bug Fixed: System Performance for queries-Assessment page that is generating thousands of database queries per click
- Bug Fixed: Ensure that Assignee field is added to all Add page that has workflows
- Bug Fixed: Develop a class profile feature for Institution>Profile
- Bug Fixed: Administration>Profile>Classes: 404 error when trying to generate this profile for previous academic period
- Bug Fixed: Institutions>Student>Transfer: Unable to add Institution Positions
- Bug Fixed: Institutions>Students>Bulk Transfer: Issues in Bulk transfer page

### [3.75.29] - 2022-09-22
- Bug Fixed: Report>Students>Subject and Book List: Education grade is not filtering by Institution
- Bug Fixed: Institution > Student > Academic: Assessments should show all past and current Assessment records
- Bug Fixed: Reports>Students>Students: Students are not reflecting correctly
- Bug Fixed: Institution > Academic > Programmes: Able to delete programmes when classes for the programmes still exist
- Bug Fixed: Institution>Survey:Surveys are not appearing
- Bug Fixed: Institutions > Staff List page > Edit : System does not trigger any workflow for editing staff
- Bug Fixed: Institutions>Students>Add: When trying to add a new student from the Institution tab, the system assigns a small integer that does not follow the OpenEMIS id logic
- Bug Fixed: Institutions>Performance>Report Cards: Create placeholders for both education subjects and institution subjects

### [3.75.28] - 2022-09-15
- Bug Fixed: Institution > Students > Transfer: Education Grade is Empty Even Though There are Students in that specific year
- Bug Fixed: Implement database foreign keys for OpenEMIS Core-Issues raised
- Bug Fixed: API: User with OpenEMIS ID 2488140537 has no academic history records, but he has marks in 7 different grades.
- Bug Fixed: Institutions>Student>Bulk Transfer: Assignee is assigned wrongly

### [3.75.27] - 2022-09-14
- Bug Fixed: Report>Institution>Wash Report: Changes to the Wash Report
- Bug Fixed: Institution > Students: User is able to create students with the same OpenEMIS ID at the same time as another user

### [3.75.26] - 2022-09-10
- Implemented: Develop a class profile feature
- Bug Fixed: Administration>Security>Roles: 404 error when creating new security user
- Bug Fixed: Administration>Meals: Area education is not filtering the institutions properly
- Bug Fixed: Institutions>Staff>Career>Positions: User should be able to edit staff shift and filter staff attendance by shift 
- Bug Fixed: Institution>Meals: Correction to Meals
- Bug Fixed: Report>Institution>Student Body Masses: Filter for Governorate is not working
- Bug Fixed: Institutions>Staff>Staff1>Transfer: Getting 404 error when clicking transfer button
- Bug Fixed: Institution > Academic > Classes: Student status does not show correctly
- Bug Fixed: Institutions>Meals>Distribution/Students: Distributed meals should be linked to the number of students who received the meal
- Bug Fixed: Institutions>Academic>Programmes: Allow users to delete because there is no more student enrolled in the grade

### [3.75.25] - 2022-09-02
- Bug Fixed: Institutions>Performance>Assessments>Reports:Getting 404 error when generating
- Bug Fixed: Report>Institution>Positions: Changes to Positions Report
- Bug Fixed: Institution>Academic>Programmes/Shift: Getting network error when downloading
- Bug Fixed: Institutions>Attendance>Students-Data shown under the date column is showing the academic year instead of the date.
- Bug Fixed: Institution > Student > Academic: Assessments are not showing fully
- Bug Fixed: Administration>System Setup>Configurations: Logout webhook is not working

### [3.75.24] - 2022-08-30
- Bug Fixed: Implement database foreign keys for OpenEMIS Core-Issues raised 2
- Bug Fixed: Directory>Student: Student is showing the wrong institution
- Bug Fixed: Institutions>Students>Transfer: Assignee option is not accurate
- Bug Fixed: Institution > General > Map: Back button should be removed
- Bug Fixed: Institutions>Students>Bulk Transfer: Students are not showing

### [3.75.23] - 2022-08-25
- Bug Fixed: Report>Students>Enrolment Summary: Changes to Enrolment Summary Report
- Bug Fixed: Institutions > Performances > Assessments > Class > Reports : Timeout Gateway error
- Bug Fixed: Report>Institution>Student: Data does not match
- Bug Fixed: Institutions>Students>Student1: Assessment tab keeps loading
- Bug Fixed: Report>Institution/Student/Staff: Report is stuck in progress(PHP 7.4)
- Bug Fixed: Institution>Performance>Report Card: Report card is stuck in progress(PHP 7.4)
- Bug Fixed: Administration>Profile: Report is stuck in progress(PHP 7.4)
- Bug Fixed: Institutions>Academic>Classes: Student is unassigned to class but he or she is still assigned to the class subjects

### [3.75.22] - 2022-08-20
- Bug Fixed: Administration>Scholarships>Details: Changes to Scholarship
- Bug Fixed: Institutions > General > Overview: Excel export is empty
- Bug Fixed: Institutions>General>Contact>People: Unable to export
- Bug Fixed: Administration>Data Management>Copy: User is unable to copy
- Bug Fixed: Administration>Archive>Connection: Getting 404 error when click test
- Bug Fixed: Report>Institution: Area education is not filtering
- Bug Fixed: Report>Institution>Student Body Masses: Data is not showing correctly
- Bug Fixed: Institutions>Statistics>Standard: Staff career does not tally with the staff leaves on the system

### [3.75.21] - 2022-08-09
- Developed: Create a migration script to insert a row in the report_queries table to truncate the system_errors table every week
- Bug Fixed: Institutions > Staff > Edit > End of Assignment : End date should be mandatory when user wants to end the assignment
- Bug Fixed: Assessment API fixes : Set assessment_grading_option_id to not mandatory, API to return all results and Update API documentation
- Bug Fixed: Institutions>Staff>Transfer: Staff is not showing under Pending transfer in after approving
- Bug Fixed: Institutions>Performance>Assessments: Unable to load PDF
- Bug Fixed: Administration>Meals: Area level should filter the area education which filters the institution
- Bug Fixed: Institution>Meals>Distribution/Students: No edit permission in permission page
- Bug Fixed: Institutions>Meals>Students: Date should show current and past dates

### [3.75.20] - 2022-08-06
- Bug Fixed: Report>Statistics>Staff Absences: Changes to Report
- Bug Fixed: Institutions>Performance>Report Cards: Report status is showing error
- Bug Fixed: Institutions>Staff>Transfer: Currently assigned to is not showing the current institution
- Bug Fixed: Institution>Infrastructure>Overview: Editing new record is getting 404 error
- Bug Fixed: Institutions>Performance>Report Cards: Report is stuck in progress

### [3.75.19] - 2022-08-04
- Implemented: Allow shift times to overlap
- Bug Fixed: Institutions>Performance>Report Cards: Report is stuck in progress
- Bug Fixed: Administration>Performance>Assessment: User should be able to add education subjects when editing
- Bug Fixed: Administration > Performance > Report Cards: Principal comments set to no but can see Principal tab
- Bug Fixed: Institutions>Performance>Report Cards: Report card is not showing correctly
- Bug Fixed: Institutions > Appointment> Positions: Identity type should show default identity type
- Bug Fixed: Report>Statistics>Student Health: Report generated in Test tab is showing students that are transferred
- Bug Fixed: Institutions>Performance>Assessments: Unable to load PDF
- Bug Fixed: Report>Institution/Staff/Student: Report are stuck in progress

### [3.75.18] - 2022-07-30
- Implemented: D. Update existing WebHooks in OpenEMIS to add all Custom Fields for integration with JoLearn (MoE Operational Plan Activity 1.1.4.b.2)
- Implemented: E. Develop OpenEMIS APIS for JoLearn - Develop teacher subjects API (MoE EMIS Operational Plan Activity 1.1.4b3.2)
- Bug Fixed: Institutions>Performance>Report Card: Student that is transferred should not appear in the list
- Bug Fixed: Reports>Training: Trainers Session Report is not displaying
- Bug Fixed: Institution>Performance>Report Cards: Error status is showing the wrong modified date and time
- Bug Fixed: Update summary table to include temporary tables and current academic periods
- Bug Fixed: Reports>Students>Students: No options when selecting Area Education
- Bug Fixed: Report>Institution>Positions: Add a new Status filter
- Bug Fixed: Report>Training>Needs: National Number should be the identity number which is the default identity
- Bug Fixed: Institutions > Examinations > Results: Results are not showing

### [3.75.17] - 2022-07-22
- Implemented: C. Develop WebHook Token to establish linkage between OpenEMIS and MoE JoLearn (MoE Operational Plan Activity 1.1.4.b.1)
- Implemented: E. Develop OpenEMIS APIS for JoLearn - Develop a feature for logging of all WebHook calls-not just error message (MoE EMIS Operational Plan Activity 1.1.4b3.3)
- Implemented: E. Develop OpenEMIS APIS for JoLearn - Develop assessment grades API (MoE EMIS Operational Plan Activity 1.1.4b3.1)
- Bug Fixed: Reports>Training: Changes to Session Participants
- Bug Fixed: Reports>Training: Changes to Trainers
- Bug Fixed: Administration>Security>Users: Email field is greyed out
- Bug Fixed: Institutions>Performance>Assessments>Export: The system shows results from new institution marks instead of old institution marks
- Bug Fixed: Institution>Performance>Report Cards: Split the current Download/Generate permission into six separate permissions
- Bug Fixed: Institutions>Statistics>Standard: Marks Entered by Staff and Student Absences
- Bug Fixed: Administration>Profile>Institution/Student/Staff: The Report card will be generated in the background
- Bug Fixed: Institutions>Statistics>Standard: Sort report alphabetically from A to Z

### [3.75.16] - 2022-07-15
- Implemented: E. Develop OpenEMIS APIS for JoLearn - Develop assessment grades API (MoE EMIS Operational Plan Activity 1.1.4b3.1)
- Bug Fixed: Institutions>Meals>Students: Date should show current and past dates
- Bug Fixed: Institution > Statistics > Standard: Changes to report
- Bug Fixed: Institution > Students > Academic > Assessments : Marks are displayed from previous institution for transferred students
- Bug Fixed: Reports>Training: Changes to Results
- Bug Fixed: Administration>Security>Roles: Unable to add new roles
- Bug Fixed: Institutions:Add sort icons to the Type and Area columns
- Bug Fixed: Reports>Positions: Add All options under Teaching Filter and only include Active Staff
- Bug Fixed: Institutions>Performance>Report Cards: Grades are showing wrongly when using Generate All
- Bug Fixed: Administrative > System Setup > Field Options: Vaccination Type: Increase the character length from 50 to 100
- Bug Fixed: Review and run queries for migration

### [3.75.15] - 2022-07-07
- Implemented: Update OpenEMIS Core Russian Translation files
- Implemented: Add capacity column to Reports > Institution: Classes
- Bug Fixed: Reports > Staff > Staff Subjects: Changes to standard report
- Bug Fixed: Administration>System Setup>APIS>Securities: Assessment api is missing
- Bug Fixed: Institution > Statistics > Standard and Institution > Appointments > Positions: Changes to report
- Bug Fixed: Institution>General>Contacts: Unable to edit
- Bug Fixed: Guardian>Personal>Report Card: Unable to download report cards
- Bug Fixed: Institutions>Performance>Report Cards: Grades are showing wrongly

### [3.75.14] - 2022-07-01
- Bug Fixed: Administration/Institutions>Performance>Report Card: Update stuck student report card to error status
- Bug Fixed: Institutions > General > Profiles > Institutions : System should allow users to download Excel
- Bug Fixed: Institutions>Advanced Search>Shift Type:Shift type is not searching properly
- Bug Fixed: Institutions>Advanced Search>Area Administrative: Institution page is not showing any institution when searching by filter 'Area Administrative'
- Bug Fixed: Institution>Performance>Report Card>Report card template : Update assessment marks placeholder query to select class rather than institution
- Bug Fixed: Reports>Students>Students: No options when selecting Area Education
- Bug Fixed: Institution>Performance>Report Cards>Comments: Academic period and grade not filtering properly

### [3.75.13] - 2022-06-29
- Bug Fixed: Administration>Security>Roles: All comments should be created and edit permission should allow user to edit permission

### [3.75.12] - 2022-06-27
- Bug Fixed: Administration > Survey > Forms > Statuses: System does not automatically populate the instituion_surveys table with the news surveys for the new academic period
- Bug Fixed: Administrations > Profiles > Institutions : Report assessment summary in General is not appearing
- Bug Fixed: Institutions>Staff>Staff1>Career>Subjects: Duplicate subjects are showing
- Bug Fixed: Institutions > Students > Academic: Examinations: Results are not showing
- Bug Fixed: Institution > Students > Subjects: Total Mark: Total marks should tally with Performance>Assessments
- Bug Fixed: Report>Staff>Staff Position Names: Should not show staffs without institutions
- Bug Fixed: Report>Institution: Time completed shows before time started
- Bug Fixed: Institution>Performance>Report Cards>Comments: Homeroom teacher should only see classes that he or she is homeroom teacher of
- Bug Fixed: Administrations > Performances > Report Cards : Modified column does not capture the time
- Bug Fixed: Administration > Performance > Report Cards: Principal comments set to no but can see Principal tab
- Bug Fixed: Institutions>Dashboard: Dashboard items should be translated to Arabic
- Bug Fixed: Institutions>Advanced Search: Multiple education programmes are showing
- Bug Fixed: Institutions>Report Cards>Comments: User is unable to see subjects even though she is subject teacher

### [3.75.11] - 2022-06-14
- Implemented: Reports > Data Quality > Potential Students Duplicates > Add programme and enrollment status columns
- Implemented: Add OpenEMIS ID field to Reports > Staff > Qualifications Report
- Bug Fixed: Institution>Performance>Report Cards>Comments: Teachers are unable to view comments

### [3.75.10] - 2022-06-14
- Bug Fixed: Reports > Institutions> Student Absence Report: Optimise query and create summary table
- Bug Fixed: Report>Institution>Guardians: Changes to the Report
- Bug Fixed: Institution>Academic>Subjects: Duplicate students appearing
- Bug Fixed: JO-UAT : Administrations > Data Management > Transfer : Unable to Transfer data
- Bug Fixed: System should update modified column in report_card_processes whenever the report card status changed from New to In Progress
- Bug Fixed: Institution>Performance>Report Cards>Comments: Teachers are unable to view comments
- Bug Fixed: Institutions > Performances > Report Cards : Introduce Error Status as (-1)

### [3.75.9] - 2022-06-09
- Bug Fixed: Institutions>Academic>Classes: The number of total students should only count the number of enrolled students
- Bug Fixed: Institution>Report Card>Comments: Changes to Performance-Assessments
- Bug Fixed: API: Add staff page in Institution and updating of school classes for students
- Bug Fixed: Institutions > Statistics > Standard Report > Student Absences by Absence Type : Rename Columns, Absence Reason columns should not be hardcoded
- Bug Fixed: Institution>Students/Staff>General>Attachment: Attachment tab should allow students to see other tabs
- Bug Fixed: Institution > Students > Academic : Extracurricular record is not showing
- Bug Fixed: Institutions > Advanced Search : Changes on Shift filter
- Bug Fixed: Personal > Student > Academic>Competencies: Naming is confusing
- Bug Fixed: Institutions>Performance>Report Cards: Teacher does not match with the report card
- Bug Fixed: Administration>Security>Users: Edit permission is not working for homeroom teacher

### [3.75.8] - 2022-06-01
- Bug Fixed: Institutions>Attendance>Students: Teacher is unable to mark attendance for multigrade classes
- Bug Fixed: Institutions>Performance>Report Cards: All classes dropdown is showing all classes process even when it is only generate all for one class
- Bug Fixed: Personal>Student>Academic: No filter or dropdown button to allow student to view past years assessment
- Bug Fixed: Institutions>General>Overview: Error and notification message appears
- Bug Fixed: Institution > Performance > Competencies: Unable to see competencies as in view mode when editing
- Bug Fixed: Institutions > Report Card > Comments: Total Mark for a subject should be the same value as Institutions > Performance > Assessments > Subject tab > Total Mark column
- Bug Fixed: Report : Events and Procedures are not updating Summary Tables
- Bug Fixed: Administration>Meals:Cannot put the amount higher than 1000
- Bug Fixed: Administration>System Setup>Academic Period: 404 error when deleting academic period

### [3.75.7] - 2022-05-27
- Implemented: Add a Type field to Institution, Staff and Student Attachments
- Bug Fixed: Institutions>Academic>Classes: Unable to assign particular teacher
- Bug Fixed: Report>Institutions>Subjects: Atoll to be included in the report before area and Subjects which there are no assigned students to be removed from the report
- Bug Fixed: Reports>Institution>Special Needs Facilities: Report to include Atoll and Island as well.
- Bug Fixed: Administration>Localisation>Translation : Student Absences by Absence Type
- Bug Fixed: Institution>Performance>Report Cards: Report card is not showing in progress
- Bug Fixed: Institution>Meals>Students: Current week is not showing properly

### [3.75.6] - 2022-05-24
- Bug Fixed: Reports > Students > Students Out of School: Add new columns for contacts
- Bug Fixed: Administration>Security>Roles: Roles that are arranged are not working properly in Appointment>Positions
- Bug Fixed: Personal > Guardians> Academic > Extracurriculars: 404 error
- Bug Fixed: Institution>Meals>Students: The template file showing the all Meals programs even those are not relevant to that school and previous week Saturday is only shown
- Bug Fixed: Administration>Profile>Institution: Update the existing placeholder in Student Performance Summary Tab
- Bug Fixed: Institution > Statistics > Standard: Marks Entered by Staff-Report is blank
- Bug Fixed: Institution: Shift filter is not shown on the Institution page

### [3.75.5] - 2022-05-19
- Implemented: Rename Institution > Statistics > Standard > Staff Positions report to Staff Absences
- Bug Fixed: Institution>Performance>Assessments: Unable to enter marks for Enrolled(Repeated) Students
- Bug Fixed: Reports > Institutions> Student Absence Report/Student Attendance Summary Report: Stuck in progress
- Bug Fixed: Administration>System Setup>System Configuration>Authentication: GoogleSSO cannot be setup
- Bug Fixed: Institutions>Meals>Students/Distribution: Cost should be able to put decimal place and export in Meal students should show OpenEMIS ID
- Bug Fixed: Institution>Academic>Classes: Report does not show the education grade(column Y, highlighted yellow) of certain staffs
- Bug Fixed: Personal/Institution > Students > Academic > Competency tab : The system does not display the competency periods
- Bug Fixed: Report>Institution>Student Body Masses: Island should be changed to parent of district which is Atoll
- Bug Fixed: Institutions>Academic>Classes: Page is loading very slow
- Bug Fixed: Administration > Scholarship > Application: 404 error occur
- Bug Fixed: Institution>Academic>Classes: Cannot open the classes

### [3.75.4] - 2022-05-11
- Implemented: Add the option for 100 and 200 records in list page view
- Implemented: Development of additional reports indicators and dashboards required by the Professional Development Department 7
- Implemented: Administrations > Profiles > Institutions : Development of additional institution profile placeholders for OpenEMIS+
- Implemented: Develop Staff Subjects report
- Bug Fixed: Institutions>Statistics>Standard: Develop Staff Qualifications Reports for all staff
- Bug Fixed: Report>Institution>Infrastructure/Student Body Masses: Area Name and Region Name should show the actual area name and region name
- Bug Fixed: Institutions>Students/Staff: 404 error when navigating Institutions>Students/Staff and Directory
- Bug Fixed: Institutions>Meals>Students/Distribution: Changes to Meals
- Bug Fixed: Administration>Performance>Competencies: Competency item is not showing even though it has been added to the competency Template
- Bug Fixed: Administration>Profile>Institution: Missing placeholders and renaming of table
- Bug Fixed: Personal > Academic > Competencies: User is unable to see data of results shown under Personal > Academic > Competencies
- Bug Fixed: Personal > Students > Academic > Extracurriculars: There is no filter or dropdown to view past academic periods extracurriculars
- Bug Fixed: Personal>Student>Academic>Absences: Unable to view records
- Bug Fixed: Directory>Import:Issues downloading data after importing student ID via Directory
- Bug Fixed: Institutions>Meals>Students/Distribution: Changes to Meals(Point 6)
- Bug Fixed: Institution > Statistics > Standard > Student Health: Report should not include withdrawn students

### [3.75.3] - 2022-04-29
- Implemented: Allow users to manually add cases in the Cases feature
- Implemented: Develop a Grade filter for Institution > Statistics > Custom reports
- Implemented: Develop a new report : Institutions > Statistics > Standard Report : Marks Entered by Staff
- Implemented: Develop a new report : Institutions > Statistics > Standard Report : Student Absences
- Bug Fixed: Institutions>Students>Student1>Withdraw: When they use the arabic interface to withdraw, the status for the withdraw student will still be enrolled.
- Bug Fixed: Institution > Academic > Classes > Export: Subject teacher is to be included in the report generated
- Bug Fixed: Institutions>Infrastructure>Overview: Area should change to Size

### [3.75.2] - 2022-04-22
- Implemented: Development of additional reports indicators and dashboards required by the Professional Development Department 3
- Implemented: Development of additional reports indicators and dashboards required by the Professional Development Department 9
- Implemented: Develop a new report : Institutions > Statistics > Standard Report : Student Absences by Type
- Bug Fixed: Institutions>Add: Administrative District field should be mandatory
- Bug Fixed: Administrations > Profiles > Institutions : Create placeholders for custom fields, assessments and class area
- Bug Fixed: Administrations > System Setup > System Configuration > System : Setting up timezone
- Bug Fixed: Institution>Dashboard: Data on the Dashboard does not tally with the one in the Attendance
- Bug Fixed: Institution>Students: Issue with the Dashboard data in the student module
- Bug Fixed: Institution>Academic>Classes: Institution 170130 and 170131 are getting 404 error
- Bug Fixed: Add the Profiles feature to Institutions module(Student)
- Bug Fixed: Institutions>Overview: Latitude and Longitude in edit institution should be hidden or read-only
- Bug Fixed: Reports > Students : Develop Student Health Report > Health
- Bug Fixed: Reports > Staff : Develop Detailed Staff Data Report Filters : Area Level, Area Education , Institutions
- Bug Fixed: Institutions > Profiles > Institutions/Staff/Students: Allow user to view PDF without downloading PDF file
- Bug Fixed: Institutions>Survey>Forms:User is unable to edit and limit needs to be increase
- Bug Fixed: Institutions>Performance>Assessments: Unable to import assessment mark even when it is within limit
- Bug Fixed: Personal>General>Comments: Menu on the left are collapsed instead of expanding

### [3.75.1] - 2022-04-14
- Implemented: Add Export button function - Institutions > Finances > Income
- Bug Fixed: Staff>Career>Subjects : Implement filter and sort
- Bug Fixed: Institutions > Performance > Report Cards : Subject name to be reflected instead of Education Subjects name
- Bug Fixed: Personal>General: Demographics and Comments cannot be added and Nationalities cannot be edited
- Bug Fixed: Reports>Institution/Staff/Student: Large reports get stuck in progress
- Bug Fixed: Institutions>Students: MEMIS stops loading
- Bug Fixed: Administration>Profile>Institution: Updates table column Comment on this institution_report_cards table
- Bug Fixed: Dashboard: Workbench is not working where there are no pending task shown even when they have pending task.
- Bug Fixed: Add the Profiles feature to Institutions module(Institutions)
- Bug Fixed: Add the Profiles feature to Institutions module(Staff)
- Bug Fixed: Staff Payslips API not working

### [3.75.0] - 2022-04-07
- Implemented: Add Export button function - Institutions > Infrastructures > Projects
- Implemented: Add Export button function - Institutions > Transport > Trips
- Implemented: Add the Profiles feature to Institutions module
- Implemented: Develop a new report: Reports > Performance > Assessment Missing Mark Entry
- Implemented: Development of additional reports indicators and dashboards required by the Professional Development Department 4
- Bug Fixed: Personal > Staff > Training > Achievements : Add Achievements page in Personal module
- Bug Fixed: Administration> Security >Users: Unable to load users
- Bug Fixed: Report>Institution: Student Attendance Report is stuck in progress
- Bug Fixed: Administrations > Profiles > Institutions : Rows are duplicated
- Bug Fixed: Institutions>Statistics>Standard: Develop Staff Positions for all staff
- Bug Fixed: Administrations > Meals : Issues in Add/View/Edit pages
- Bug Fixed: Institutions > Meals : Issues in Meal pages
- Bug Fixed: Institution>Statistics>Standard: Report that is run in Avory Primary School should not be seen in Khundo or other institutions
- Bug Fixed: Institution>Overview: System will set the students' statuses to withdraw, not only in the current academic period but in all academic periods
- Bug Fixed: Institutions>Academic>Classes/Subjects: Add export to include student list
- Bug Fixed: Institution>Staffs: Issue of not being able to take certain dates when doing the End of Assignment of staff
- Bug Fixed: Institutions>Performance>Assessments: Unable to import assessment mark even when it is within limit
- Bug Fixed: Institution>Students/Staff: 404 error when searching students and clicking on institution get 404 error
- Bug Fixed: Administration>Security>Users: 404 error when accessing

### [3.74.5] - 2022-03-25
- Implemented: Add Export button function - Institutions > Meals > Distributions
- Implemented: Development of additional reports indicators and dashboards required by the Professional Development Department 6
- Bug Fixed: Institution>Students: User is unable to search students through identity number
- Bug Fixed: Reports -> Institutions -> Students: the student totals are different from the student total in Institutions -> Students for all institutions when a specific programme is selected
- Bug Fixed: Jordan UAT: Reports > Institution > Positions
- Bug Fixed: Administration>Performance>Competencies: Unable to import
- Bug Fixed: Personal > Staff > Careers : Assignee field is not populated though Institution>Staff>Career is populating
- Bug Fixed: Report>Students>Student Enrolment summary: Enrolment summary report is showing the wrong number
- Bug Fixed: Institution > Academic > Classes: Students' names do not have the ID number included
- Bug Fixed: Institutions>Add: Coordinates fields should be available and mandatory when creating an Institution
- Bug Fixed: Administration>Localisation>Translation: Some words are missing Arabic translations
- Bug Fixed: Institutions>Infrastructure>Overview: Duplicated entries in Rooms and custom fields should show correctly
- Bug Fixed: Institution>Students: User is unable to delete students

### [3.74.4] - 2022-03-16
- Implemented: Add Export button function - Institutions > Behaviour > Staff
- Bug Fixed: Institutions>General>Shift: Edit Institution 404 Forbidden: Page Not Found
- Bug Fixed: Institution>Dashboard: Institution completeness is not showing correctly

### [3.74.3] - 2022-03-14
- Implemented: Add Export button function - Institutions > Infrastructures > Assets
- Implemented: Development of additional reports indicators and dashboards required by the Professional Development Department 1
- Implemented: Development of additional reports indicators and dashboards required by the Professional Development Department 5
- Implemented: Development of additional reports indicators and dashboards required by the Professional Development Department 8
- Bug Fixed: Institutions>Statistics>Standard: Develop Staff Special needs data for all staff.
- Bug Fixed: Institutions>Performance>Assessments: User should not be able to import if the assessment period is over
- Bug Fixed: Jordan UAT: Reports > Institution > Classes
- Bug Fixed: Institutions>Performance>Assessments: Enrolled students should show only in Reference Tab
- Bug Fixed: Report>Institution: Changes to the report

### [3.74.2] - 2022-03-07
- Implemented: Add Export button function - Institutions > Behaviour > Students
- Implemented: Add Export button function - Institutions > Finances > Bank Accounts
- Implemented: Develop an SQL migration file to automatically create / update data dictionary CSV file
- Implemented: Improvement of the student record export function in school
- Implemented: Development of additional reports indicators and dashboards required by the Professional Development Department 2
- Bug Fixed: API /restful/v2/Assessment-AssessmentItems.json is not working
- Bug Fixed: Institutions>Attendance>Students-Inconsistencies between the attendance marked per day and the attendance selected for All Days
- Bug Fixed: Create migration script for report_student_assessment_summary
- Bug Fixed: Administrations > System Setup > System Configuration > Webhooks: Webhook get method is working however Webhook post method is not working.
- Bug Fixed: Institutions>Infrastructure>Overview: Custom fields are inaccurate
- Bug Fixed: Reports> Survey : Some surveys are not showing up in report
- Bug Fixed: Institution>Staff: 404 error when trying to Edit the Staff profile
- Bug Fixed: Report>Institution: Student Attendance Report is stuck in progress

### [3.74.1] - 2022-02-25
- Implemented: Develop an API for Security Group Users
- Bug Fixed: Administration > Meals : Change fields to multi select
- Bug Fixed: Report>Survey: Open surveys are not included
- Bug Fixed: Directory > Guardian: 404 error in BS environments when trying to access the guardians' page from Directory
- Bug Fixed: Institutions>Performance>Assessments>Report: Ungrouped subjects has wrong total marks

### [3.74.0] - 2022-02-23
- Implemented: Reports > Institutions > Positions : To add Staff Name, OpenEMIS ID, Default Identity Type, Identity Number
- Implemented: Reports > Institutions > Classes : To add an Education Grade Filter
- Implemented: Reports > Institutions > Students : To Add Education Level Filter before Education Programme Filter
- Bug Fixed: Institutions>Academic>Subjects: Programme with one subject selected are showing multiple subjects when class is created
- Bug Fixed: Institution>Students: Birth Certificate value should be empty if student does have any Birth Certificate Identity type
- Bug Fixed: Institution > Statistics > Standard > Student Health: Develop Student Health report
- Bug Fixed: Institutions>Performance>Assessments: Able to import higher marks than max marks
- Bug Fixed: Reports > Institutions > Staff : Rename Header to default identity type and only display education grades from selected academic period
- Bug Fixed: Institutions > Academic > Associations : Default filter should be from current academic Period
- Bug Fixed: Institutions > Students > Transfer : If student is Graduated and Transferred out, system will change status to Promoted
- Bug Fixed: Institutions>Statistics>Standard: Training > Needs, Applications, Results, and Courses for all staff
- Bug Fixed: Institutions>Attendance>Staff: Unable to reset Staff Attendance back to default 0:00
- Bug Fixed: Institution>Students: 404 error when undo promotion for Grade 6 students in 112616
- Bug Fixed: Administration>Training>Results: 404 Error when there are more than one Result Type for one course
- Bug Fixed: Institutions>Performance>Assessments: Marks are not grouped correctly
- Bug Fixed: Institutions>General>Shift: Owner field should disappear when this institution field is selected
- Bug Fixed: Institution>Students: Unable to approve in workflow

### [3.73.12] - 2022-02-15
- Bug Fixed: Institution > Statistics > Standard > Student Special Needs: Develop Student Special Needs report
- Bug Fixed: Institution>Staff>Add: Created positions are not available when assigning the staff
- Bug Fixed: Institutions>Staff>Staff1>Career>Staff Leave: Unable to create leave even when attendance is deleted
- Bug Fixed: Institutions > Staff > Add External Staff : System shows validation message on Shifts field when adding External Staff

### [3.73.11] - 2022-02-11
- Bug Fixed: Institution>Students: Students that were previously enrolled in a grade cannot be enrolled to the same grade.
- Bug Fixed: Attendance > Students > Export: 404 error when exporting for all days
- Bug Fixed: Institution/Directory>Staff/Student: 404 Error
- Bug Fixed: Institutions > Students > Repeat feature : System assigns students to the previous year's education_grade_id

### [3.73.10] - 2022-02-11
- Bug Fixed: Institution>Academic>Subjects: Negative numbers are showing up under student numbers
- Bug Fixed: Institutions > Statistics > Standard : Develop Student Overview report
- Bug Fixed: Institutions: 404 error when deleting an institution
- Bug Fixed: Institutions > Performance > Assessments : System does not save new mark when user updates the mark
- Bug Fixed: Institutions > Students/Staff > Nationalities tab : Deleting a Preferred nationality should not show a [Message not found] error

### [3.73.9] - 2022-02-09
- Bug Fixed: Institutions>Student:Withdraw or Transfer gets 404 error
- Bug Fixed: Institutions > Staff > Add Staff : There are no validation message when user submits Add staff without selecting values in mandatory fields
- Bug Fixed: Institutions > Students > Export : Classes are not showing for some students
- Bug Fixed: Institutions > Performances > Assessments : Assessment marks should not be editable if he is no longer in the same class

### [3.73.8] - 2022-02-05
- Implemented: Reports > Institutions > Institutions With No Staff : Add institution_status column in the report
- Bug Fixed: Institutions>Infrastructure>Overview: Custom fields are inaccurate and results are duplicated
- Bug Fixed: Institutions>Students: Enrolled(Repeated) logic change
- Bug Fixed: Institutions>Students>Bulk Transfer: Programme is still reflected even though it is invisible
- Bug Fixed: Institution>Performance>Assessments: Marks are not saved and only Only first Assessment Period marks can be displayed from the interface
- Bug Fixed: Institutions>Students: The report does not show the classes for some students even though they are assigned to classes

### [3.73.7] - 2022-01-31
- Bug Fixed: Directory > General > Identities: 404 Error when viewing and editing identities
- Bug Fixed: Personal > Student > Absences/Behaviours/Outcome/Extracurricular/Textbooks/Risks tab: 404 error when accessing

### [3.73.6] - 2022-01-27
- Bug Fixed: Institutions>Report Cards>Comments: Homeroom Teachers are able to comment as Homeroom Teachers under other persons Homeroom classes
- Bug Fixed: Directory > Import: Getting 404 Error
- Bug Fixed: Institutions>Students>Export: Issues with export
- Bug Fixed: Institution>Students>Add: Adds error message when not adding start date

### [3.73.5] - 2022-01-21
- Bug Fixed: Institutions>Staff>Career>Attendances:Able to record staff time-in and time-out even if there is staff leave on the specified dates
- Bug Fixed: Institutions>Students>Transfer: Validation for next education grade not to show not visible programmes and grades in education structure
- Bug Fixed: Institutions>Students>Promote/Repeat: Individual promotion and group promotion is not working
- Bug Fixed: Institutions>Performance>Assessments: Students of duplicate same subject are not appearing according to Academic>Subjects
- Bug Fixed: Institutions > Staff > Add Staff : Start Date Date picker should not be restricted

### [3.73.4] - 2022-01-19
- Implemented: Add Export button function - Institutions > Examinations > Students
- Implemented: Add Export button function - Institutions > Committees
- Bug Fixed: Institution>Student/Staff: Identity in External Search is not saved after staff/student is added
- Bug Fixed: Institution > Statistics > View page : System shows 404 error page
- Bug Fixed: Updating security_group_users table for student transactions
- Bug Fixed: Personal > Staff > Careers : Edit and add button page should be the same as Institution>Staff
- Bug Fixed: Institutions>Students>Export: Issues with export

### [3.73.3] - 2022-01-14
- Implemented: Add Export button function - Institutions > Finances > Institution Fees
- Implemented: Add Export button function - Institutions > Transport > Providers
- Bug Fixed: Institution > Attendance > Staff : System still allows user to set time in/out when school is closed
- Bug Fixed: Reports > Institution > WASH Reports : System does not validate when there are mandatory fields
- Bug Fixed: Improvement of School Shift functions-Edit function for School Shift(Owner-Other Institution)
- Bug Fixed: Institutions>General>Calendar: Unable to add calendar
- Bug Fixed: Personal > Staff > Careers : System should allow user to Edit, Add and Delete Leave
- Bug Fixed: Report>Custom: institution_id filter is not populating
- Bug Fixed: Reports> Student: Enrollment summary support is not showing for all institutions
- Bug Fixed: Institutions>Undo: Students are not showing out in the withdrawn list
- Bug Fixed: Administration>System Setup>Localisation>Translation: Words not reflected and existed
- Bug Fixed: Personal > Guardians : Remove Guardians sub-menu under Personal Menu
- Bug Fixed: Institutions>Report Cards>Comments:Overall average in report card>comment which is showing 0 even though there is data and overall average/total marks does not tally
- Bug Fixed: Institutions: No record found in Institution

### [3.73.2] - 2022-01-12
- Bug Fixed: Institutions>Academic>Classes: Students that are assigned to a different grade(Primary 1 to Primary 2) will get a change in grade

### [3.73.1] - 2022-01-12
- Bug Fixed: Institutions>Performance>Assessments: Assessments are showing empty when the student's status is not enrolled status
- Bug Fixed: Institutions > Performance > Assessments: Export should show only enrolled student and individual school filter should show same grades as all institution filter

### [3.73.0] - 2022-01-07
- Implemented: Add Export button function - Institutions > Visits (include all tabs)
- Bug Fixed: Institution>Students: Enrolled(Repeated) students are not able to be found in classes and there are duplicate students
- Bug Fixed: Institution>Performance>Report Cards: Subject Teachers Names are not reflected on the Report Card for some users
- Bug Fixed: Development of Institution Profile placeholder: Contains Non-Teaching Staff placeholder in Extra Details tab
- Bug Fixed: Institution>Overview: Institution Overview Page should contain custom fields
- Bug Fixed: Institution>Infrastructure>Utilities: Report should include all the utilities(Electricity,Internet and Telephone)
- Bug Fixed: Institutions>Performance>Outcomes: Homeroom teacher is unable to see UKG Kindness even though he is the homeroom teacher
- Bug Fixed: Institutions>Academic>Classes: Changes to export button where student information are removed
- Bug Fixed: Institutions>Academic>Subject: Changes to export button where teacher information is removed
- Bug Fixed: Institutions>Staff>Overview>Export: Extra custom fields are showing even though one of them is removed

### [3.72.5] - 2021-12-30
- Bug Fixed: Institutions: Move "Shifts" out from "Academic" and place it under "General"
- Bug Fixed: Institution>Performance>Outcome: Students that are transferred are showing in Performance>Outcome>Students
- Bug Fixed: Institutions>Performance>Assessments: Subject teachers can view their classes twice from Performance > Assessment
- Bug Fixed: Administration > Security > Roles > Permissions > Administration: No permission for Administration > Meals
- Bug Fixed: Institution>Appointment>Duties: Report should include institution name
- Bug Fixed: Institutions>Performance>Assessments: Able to import higher than max marks
- Bug Fixed: Institutions>Students: Enrolled (Repeater) status is not displayed in the Export Report

### [3.72.4] - 2021-12-29
- Bug Fixed: Institutions > Performance > Assessments : Assessment Marks Display

### [3.72.3] - 2021-12-22
- Implemented: Develop Attendance > Student > No Scheduled Class function
- Implemented: Development of Institution Profile placeholder
- Implemented: Create a batch / background process to generate reports
- Bug Fixed: Institutions > Committee : Permissions are enabled but user is unable to view in menu
- Bug Fixed: Personal > Students > Academic > Extracurriculars: Extracurriculars tab is missing

### [3.72.2] - 2021-12-21
- Bug Fixed: Institution>Performance>Report Cards: The Report Card Reflects Subjects that the Student is not Assigned to
- Bug Fixed: Institutions>Students>Student1>Academic>Extracurriculars: View and edit showing the oldest record instead of the current one
- Bug Fixed: Institutions > Attendances > Students : Student is no longer enrolled in school however he is showing up in this page

### [3.72.1] - 2021-12-16
- Implemented: Add Export button function - Institutions > Risks
- Implemented: Add Export button function - Institutions > Examinations > Results
- Implemented: Add "Area Level", "From Date" and "To Date" fields and "All Institutions" option to Reports > Workflows > Institution > Student Transfers > Sending / Receiving
- Implemented: Develop institution statistics feature
- Bug Fixed: Administration>System Setup>System Configuration>Webhooks:Add Role Names column from security_roles table to Student/Staff Create and Update webhooks
- Bug Fixed: Institutions > Student > Academic > Absences Tab : Absence record is not showing
- Bug Fixed: Institutions>Academic>Subjects-Unable to add same subject with a different name
- Bug Fixed: Institutions>Staff: Able to repeatedly add same position of same shift
- Bug Fixed: Institutions>Attendance>Students-attendance for withdrawn students is allowed to be added
- Bug Fixed: Institution>Performance>Assessments: Total Marks is showing the total marks for both class(Student1 is moved from Class 1A to Class 1B)
- Bug Fixed: Institution>Performance>Assessments: Export(button) report is showing total marks and marks for previous class(Student 1 is moved from Class 1A to Class 1B)

### [3.72.0] - 2021-12-10
- Implemented: Institutions > Staff > Career > Positions tab: To add Shift column on the list page
- Bug Fixed: Institutions>Transport>Buses: 404 Error
- Bug Fixed: Administration > Security > Roles > Permissions > Institutions: Meal component not available in Institution
- Bug Fixed: Institutions>Students>Student1>Academic>Extracurriculars:Unable to save record when added

### [3.71.4] - 2021-12-08
- Bug Fixed: Institutions>Dashboard: Modifying or adding absence, it is not reflected on the dashboard
- Bug Fixed: Personal>Account: Account permission should allow user to edit username
- Bug Fixed: Institutions>Academic>Subjects-User remains on the same page after adding new subjects.
- Bug Fixed: Special Needs : Set Default Academic Period filter to All Academic Periods
- Bug Fixed: Institutions > Students > Export : Students should not be appearing more then once in report

### [3.71.3] - 2021-12-03
- Bug Fixed: Institutions>Staff>Staff1>Career>Staff Leave: Unable to create leave even when attendance is deleted
- Bug Fixed: Institutions>Staff>Staff1> Career >Subjects: Discrepancies in the number of students displayed in the Academic > Subjects and Staff > Career > Subjects pages
- Bug Fixed: Administrations > Profiles > Students : Incorrect message upon hover
- Bug Fixed: Institutions > Attendances > Students : System does not fully populate the list of subjects
- Bug Fixed: Institutions > Performance > Report Cards : Unable to generate report cards
- Bug Fixed: Institutions > Students > Transfer : Next grade should only show one grade and not all grades
- Bug Fixed: Institutions > Students > Undo : System does not display list of students that are not assigned to any class
- Bug Fixed: Institution > Infrastructure > Export : System returns 404 error
- Bug Fixed: Reports> Student: Enrollment summary support is not showing correct record of students
- Bug Fixed: Institutions>Staff>Staff1>Finance: Unable to add payslips
- Bug Fixed: Institutions>Report Cards>Comments:Total Mark and the Overall Average are not showing

### [3.71.2] - 2021-11-26
- Implemented: Add Export button function - Institutions > Infrastructures > Needs
- Bug Fixed: Institutions>Performance>Assessments: Unable to import assessment item result
- Bug Fixed: Institutions>Staff>Staff1> Career >Subjects: Start date is changed when students are added or removed from subjects
- Bug Fixed: Administration>Meals: Active Institutions should be displayed and Institution should be filtered by the area education
- Bug Fixed: Institutions>Students>Transfer: Education grade is repeated and next education grade is showing all grades
- Bug Fixed: Institution>Overview: Institution Overview Page should contain export information for Map, Contacts and Shifts
- Bug Fixed: Institution>Staff: Report does not include custom fields and contact
- Bug Fixed: Institution>Infrastructure>Wash: Report should include all Wash modules and include institution name, code and field directorate name
- Bug Fixed: Add Export button function - Institutions > Students > Student1-Remove External Reference
- Bug Fixed: Institutions>Infrastructure>Overview: Execute Permission not working for Export
- Bug Fixed: Administrations > Training > Results : Duplicate result types
- Bug Fixed: Institutions>Performance>Assessments: Only System admin can see the classes

### [3.71.1] - 2021-11-18
- Implemented: Add Export button function - Institutions > Students > Student1 (include grades classes assessment item results and absences)
- Implemented: Implement placeholder in assessment report template for area administrative name
- Bug Fixed: Administration>Security>Roles: Permission for student transition is missing under Directory Tab
- Bug Fixed: Institutions>Academic>Subjects:Subjects with duplicated names can be added
- Bug Fixed: Administrations > Training > Results : System should display the Result types
- Bug Fixed: Institutions > Staff > Training > Results: 404 Error is given when trying to access Institutions -> Staff -> Training -> Results.
- Bug Fixed: Staff>Home Page: User Completeness is not reflecting correctly
- Bug Fixed: Institutions>Students: Students that are repeated should show repeated not promoted
- Bug Fixed: Report > Institutions > Students > Export : Custom fields (dropdown/checkboxes) results are not showing in export file
- Bug Fixed: Institutions > Performance > Assessments > Results : To provide queries for subject teachers and principal when accessing assessment > results page

### [3.71.0] - 2021-11-13
- Implemented: Improvement of the "Undo" operations linked to the basic student workflow procedures
- Implemented: Add Export button function - Institutions > General > Calendar
- Implemented: Update Export button function - Institutions > Staff (include custom fields phone number identity types)
- Bug Fixed: Institutions>Infrastructure>Overview: Add the available custom fields to the report
- Bug Fixed: Administration > Meals: When adding a programme, add fields for the beneficiary Field Directorates and the beneficiary institutions.
- Bug Fixed: Institution > Meals > Students: Ask the user to choose if the meal is received or not
- Bug Fixed: Institutions > Students > Export : Custom fields (dropdown/checkboxes) results are not showing in export file
- Bug Fixed: Report>Institution>Infrastructure: Custom fields not showing properly
- Bug Fixed: Institutions/ Directory > User 1 > Identities : Clicking Add Identities will return a 404 error
- Bug Fixed: Institution/Directory > User 1 > Nationalities: Getting 404 Error

### [3.70.5] - 2021-11-05
- Implemented: Add Export button function - Institutions > Students > Student1 > Special Needs (include all tabs)
- Implemented: Add Export button function - Institutions > Staff > Staff1 > Training (include all tabs)
- Implemented: Add Export button function - Institutions > Infrastructures > WASH > Sanitation
- Implemented: Add Export button function - Institutions > Infrastructures > WASH > Waste
- Implemented: Add Export button function - Institutions > Infrastructures > WASH > Sewage
- Implemented: Add Export button function - Institutions > Cases
- Bug Fixed: Institutions>Academic>Subjects/Classes: Exported records are showing incorrectly when student is transferred from School A to School B and back to School A
- Bug Fixed: Institutions>Students>Import: 404 error when trying to download the Student Import template.
- Bug Fixed: Institutions>Students>Transfer: Wrong academic period when promoted and not visible in Bulk Transfer
- Bug Fixed: Institutions > Staff > Pending Change in Assignment : Query is taking too long to process - To optimize query
- Bug Fixed: Administration > System Setup > System Configuration > Coordinates: Changes limits of latitude and longitude
- Bug Fixed: Directory>Staff>Careers:Career is missing for Staffs with Guardian role
- Bug Fixed: Reports>Maps: Inactive Institutions should not be show

### [3.70.4] - 2021-10-30
- Implemented: Update Export button function - Institutions > Academic > Subjects (include grade class subject teacher room and number of students by gender)
- Implemented: Add Export button function - Institutions > Staff > Staff1 > Special Needs (include all tabs)
- Bug Fixed: Administration > Profiles > Institutions: Placeholders are not working across all environments
- Bug Fixed: Institutions>General>Calendar: 404 Error
- Bug Fixed: Institutions>Students>Student1>Promotion/Repeat:Grades that are both above and lower appears for Promotion/Repeat
- Bug Fixed: Institutions > Performance > Assessments : Allow user to generate report by Student status
- Bug Fixed: Institutions > Students > Undo : Education Grades should be filtered by the Academic Period selected above

### [3.70.3] - 2021-10-22
- Bug Fixed: Institution > Student > Add: There is no validation if a new student added has the same Identity Number as an existing student
- Bug Fixed: Institutions > Students > Export : Issues with Student export file

### [3.70.2] - 2021-10-15
- Implemented: Improvement of School Shift functions
- Implemented: Update Export button function - Institutions > Students (include custom fields phone number identity types and name of the guardian)
- Implemented: Add Export button function - Institutions > Students > Student1 > Health (include all tabs)
- Implemented: Add Export button function - Institutions > Transport > Buses
- Implemented: Copy data from one academic period to another
- Bug Fixed: Institutions > Performance > Outcomes: Outcome template is empty even when template is created in the Administration > Performance > Outcomes > Templates and is linked correctly in the Institutions > Performance > Outcomes
- Bug Fixed: Report>Institutions>Subject: Grade column is missing and Remove Subjects that are not linked to Classes
- Bug Fixed: Reports > Institution > Student Body Masses: Academic Period filter is not functioning
- Bug Fixed: Report>Institution>Subjects: Subjects should filter according to the Institutions that are selected under Institution
- Bug Fixed: Institution>Academic>Classes: Staffs are assigned to subjects even though class is already deleted
- Bug Fixed: Personal>General: Edit Permission working only for Account tab and add permission not working except for Contact
- Bug Fixed: Students > Academic > Programmes > Transition: Student can transition to a Education Grade that does not exist in current institution
- Bug Fixed: Institutions>Advanced Search>Shift Type:Shift type is empty even though there is data in the database
- Bug Fixed: Institutions>Students: System shows more than one row for students who has more than one identity number

### [3.70.1] - 2021-10-08
- Implemented: OpenEMIS Core: Webhook Education Structure System - Create
- Implemented: OpenEMIS Core: Webhook Academic Period - Delete
- Implemented: OpenEMIS Core: Webhook Role - Update
- Implemented: Add Export button function - Institutions > Staff > Staff1 (include classes subjects absences)
- Implemented: Change text in the Updates function
- Implemented: Add Session Start Date, Session End Date and Credit Hours to Professional Development Results List Page
- Bug Fixed: Administrations > Profiles > Students : System should display student's latest record based on the filter selected
- Bug Fixed: Institutions > Performance > Report Cards: Reports are stuck in Progress
- Bug Fixed: Administration > Profiles > Institutions: Adding of new Placeholders
- Bug Fixed: Directory>Student: Error when viewing user who is both guardian and student
- Bug Fixed: Reports > Institution > Infrastructure: Infrastructure Level filter does not have an "All" option" and Academic Period filter is not functioning
- Bug Fixed: Personal>Student>Academic>Absences: 404 Error
- Bug Fixed: Institution>Attendance>Students:404 error when trying to access the Archive page for Attendance

### [3.70.0] - 2021-10-01
- Implemented: OpenEMIS Core: Webhook Role - Create
- Implemented: OpenEMIS Core: Webhook Role - Delete
- Implemented: Update Export button function - Institutions > Academic > Classes (include homeroom teacher and number of students per classes by gender)
- Implemented: Add Export button function - Institutions > Infrastructures > WASH > Water
- Bug Fixed: Institution > Academic > Students/Staff: Student or Staff Search 404 Error
- Bug Fixed: Institutions>Students>Attendance: Imported Student Attendance does not reflect Actual Absence Type
- Bug Fixed: Reports>Textbooks/Survey/Rubric/Workflow: Add Academic Period, Area Level, Area Name and Institution Name filters to all reports
- Bug Fixed: Reports-Institutions:Not able to view Reports data
- Bug Fixed: Administration>Profile>Student: institutions which are currently inactive in MEMIS are shown in the Institution filter
- Bug Fixed: Reports: Reports that are in progress should be able to be deleted
- Bug Fixed: Report>Institution: No data generated when Area Level and Area Education not selected and There is no link between the Area Level and Area Education and the Institution filters.

### [3.69.4] - 2021-09-23
- Implemented: OpenEMIS Core: Webhook Education Structure System - Delete
- Bug Fixed: Administrations > Security > Roles > Permission: To grant access for Import Extracurricular to users
- Bug Fixed: Institutions>Report Cards>Comments:Earliest Assignment End Date get pushed
- Bug Fixed: Administration > Profiles > Students: Add Area Level Filter before Area Filter
- Bug Fixed: Institution > Academic > Classes: Students' names do not have the ID number included
- Bug Fixed: Institution>Students>Student1>Academic>Assessment: Assessment Marks are missing

### [3.69.3] - 2021-09-21
- Implemented: Develop a function to import Outcomes
- Bug Fixed: System allows user to view JSON when access is not granted in API Module
- Bug Fixed: Administrations > Workflow > Rules > Student Unmarked Attendances : Many users get generic emails from unmarked classes rule
- Bug Fixed: Institution > Staff > Transfer: Cannot transfer the staff from one Institution to another
- Bug Fixed: Guardians Module : Ability to configure permissions for Guardians to view Students information
- Bug Fixed: Institution > Performance > Outcome: Number of male and female students showing wrongly when there is multigrade class of two classes
- Bug Fixed: Institutions > Staff > Staff1 > Health(Insurance)-404 Error when adding insurance
- Bug Fixed: Institutions>Students: Graduated student who are not assigned to any class will not be linked to the selected class for the next academic period
- Bug Fixed: Administration>Security>Roles: Personal Tab(Professional)-Qualifications,Awards,Extracurricular,Memberships,Licenses,Import Staff Qualifications
- Bug Fixed: Directory>Student1>Academic>Absences: User with AbsenceCases role is unable to view the student absences and absences in Directory raised to him or her
- Bug Fixed: Personal>Students>Academic>Assessment: Assessment Marks are missing
- Bug Fixed: CLONE - Optimise the migration script created in POCOR-5947 and Writing an SQL statement to count the records
- Bug Fixed: Institutions>Students: Promoted and repeated student who are not assigned to any class will not be linked to the selected class for the next academic period
- Bug Fixed: Institutions>Academic>Programmes: 404 Error when deleting a programme
- Bug Fixed: Personal: 404 Error when added as a Guardian going to Personal/General/Account or History Tab and Training/Training Results
- Bug Fixed: Institutions > Performance > Report Cards: Reports are stuck in Progress and OpenEMIS number is not displayed when we are trying to generate the report card from this module

### [3.69.2] - 2021-09-10
- Implemented: Add Export button function - Institutions > Academic > Programs (include number of grades and classes)
- Implemented: Add Export button function - Institutions > Staff > Staff1 > Professional > Qualifications
- Implemented: Add Export button function - Institutions > Appointments > Duties
- Implemented: Add default identity number to the student and staff list pages in institution and directory
- Bug Fixed: Students > Academic > Classes,Subjects,Absences,Outcomes,Report Cards,Risks: Receive 404 error when logging in from a Absencerole and ESQID profile
- Bug Fixed: Administration>System Setup>System Configuration>Webhooks: Education_level_id and education_cycle_id point to the same column for Cycle Create/Update webhooks
- Bug Fixed: Administrations > Profiles > Templates : Remove the validation for Generate Start and End date against Academic Period
- Bug Fixed: Institutions>Students>General: Individual Promotion/Repeat cannot promote Students
- Bug Fixed: Reports>Students:Add Academic Period, Area Level, Area Name and Institution Name filters to all reports
- Bug Fixed: Reports>Staff:Add Academic Period, Area Level, Area Name and Institution Name filters to all reports
- Bug Fixed: Migration Script : Update Migration Script 20190919164802_POCOR5009.php with latest SQL QUERY
- Bug Fixed: Administration>Survey>Forms:Unable to set default value for DropDown
- Bug Fixed: Institutions>Students: Imported Student assigned to wrong Grade and cannot assign to class
- Bug Fixed: Institutions > Staff > Staff1 > Health(Body Mass/Insurance)-Insurance tab not active and additional file type and file content for Body Mass and Insurance

### [3.69.1] - 2021-09-03
- Implemented: Add Export button function - Institutions > Academic > Shifts
- Implemented: Add Export button function - Institutions > Staff > Staff1 > Health (include all tabs)
- Implemented: Add Export button function - Institutions > Infrastructures > Utilities > Electricity
- Implemented: Develop a function view and clear the student report card queue
- Bug Fixed: Administration>Security>Groups: 404 error when adding an user group
- Bug Fixed: Institutions>Cases: User with AbsenceCases role is unable to view the student absences and absences raised to him or her
- Bug Fixed: Institutions>Students>Health: Attachment field was not added to the Body Mass and Insurance tabs
- Bug Fixed: Administration > Training > Sessions/Results:Filter records by FDs and training dates
- Bug Fixed: Report>Institution: Add Academic Period, Area Level, Area Name and Institution Name filters to all reports

### [3.69.0] - 2021-08-26
- Implemented: OpenEMIS Core: Webhook Education Area - Update
- Implemented: Add Export button function - Institutions > Infrastructures > Utilities > Internet
- Bug Fixed: Institutions>Students: No Classes Available for Graduation in the new year

### [3.68.2] - 2021-08-20
- Implemented: Improvement of the Professional Development Training Module interface
- Implemented: Development of School Report Cards
- Bug Fixed: Reports > Institution > Infrastructure > View: Data is not reflecting correctly on the view page
- Bug Fixed: Institution > Performance > Outcome > Export: Export file is empty when exporting Outcome for past Academic Period
- Implemented: Add Export button function - Institutions > General > Contacts > Institution
- Implemented: Add Export button function - Institutions > Examinations > Exams
- Bug Fixed: Institution > Performance > Outcome: Outcomes not listing the correct Grade
- Bug Fixed: Administration>System Setup>Field Options: Delete Training Achievement Types
- Bug Fixed: Institutions>Academic>Programmes: Extra Subjects Assigned to Each Grade
- Bug Fixed: Administration > System Setup > Field Options: Increase Character Limit for Student Behavior and Character Classification
- Bug Fixed: Institutions>Infrastructure>Overview: Data should be related to the academic year chosen on the main page when exporting.
- Bug Fixed: Institutions>Infrastructure>Overview: The infrastructure Area should be listed in the report (Land Area, Building Area, Floor Area, and Room Area)
- Bug Fixed: Institution > Staff: Email field in Staff is greyed out and cannot be edited
- Bug Fixed: Institutions>Students: Class dropdown is not populated when selecting class

### [3.68.1] - 2021-08-16
- Implemented: Add additional filters and columns to the Absence list view
- Bug Fixed: Institution > Student > Academic > Outcome: Subjects listed for student do not tally with the Subjects student is taking
- Bug Fixed: Institutions>Students: No Available Grades for Graduation in the new year
- Bug Fixed: Optimise the migration script created in POCOR-5947 and Writing an SQL statement to count the records

### [3.68.0] - 2021-08-11
- Implemented: Develop a function to delete behaviours
- Bug Fixed: Administration > System Setup > Labels: To create navigations for the word "Requester"
- Implemented: Add Export button function - Institutions > Infrastructures > WASH > Hygiene
- Implemented: Add a function to delete reports
- Bug Fixed: Administration>Security>Roles: Not able to edit Permission
- Bug Fixed: Administration > System Setup > System Configuration > Webhooks: Academic Period webhook does not include the parent academic period id
- Bug Fixed: Migration Script:Update the migration script for POCOR3804 to cater for special characters.
- Bug Fixed: Institutions>Academic>Programmes: 404 Error when deleting a programme
- Bug Fixed: Administration > Profiles > Students: Other grades from other institutions added to the queue and cannot generate profiles for last year students

### [3.67.3] - 2021-08-03
- Bug Fixed: Administration > System Setup > Localization > Translations: Remove Nursery from Translation
- Bug Fixed: Administration> Examinations > Centres > Add: Exam Centre was not created when selecting All Institutions
- Bug Fixed: Institution > Performance > Assessment: Student from a Multigrade class is appearing in both grades in Assessment page
- Bug Fixed: Personal>Student>Academic>Outcomes tab: User cannot see all subjects
- Bug Fixed: Institutions>Meals>Students: Display the Meals Received section wrongly,Displays "None" instead of "Meal Received"
- Bug Fixed: Institution > Academic > Classes: Students who are promoted without selecting next class will not be found in class
- Bug Fixed: Institutions > Staff > Edit : Issues with editing staff's employment
- Bug Fixed: Administration > System Setup > Field Options: 404 error when he is trying to delete a Student Absence Reason
- Bug Fixed: Institutions>Academic>Subjects-Cannot remove extra subjects

### [3.67.2] - 2021-07-23
- Bug Fixed: Institution > Academic > Associations: Unable to add new record
- Implemented: Add Export button function - Institutions > Staff > Staff1 > Career > Positions (include institutions position type start end and status)
- Implemented: Add Export button function - Institutions > Finances > Budget
- Implemented: Add Export button function - Institutions > Finances > Expenditure
- Bug Fixed: Institution > Survey > Forms: Date Enabled was set to a future date, but user is still able to view Survey Form
- Bug Fixed: Institution > Staff > Career: Staff End of Assignment: Assignee section combines different users in one group
- Bug Fixed: Reports>Surveys: Academic Period should be before Survey Form
- Bug Fixed: Institution:404 error when creating a new institution when there are no institutions and deleting it
- Bug Fixed: Administration>Security>Roles: Page is stuck after trying to grant permission

### [3.67.1] - 2021-07-09
- Implemented: Add default identity, staff position and add date range filter to Reports > Institution > Staff leave
- Bug Fixed: Institution > Attendance > Students: Student has Enrolled status but is not appearing on Attendance page
- Implemented: OpenEMIS Core: Webhook Education Structure System - Update
- Implemented: Add Export button function - Institutions > General > Contacts > People

### [3.67.0] - 2021-07-02
- Bug Fixed: Administration > Survey > Forms > Questions tab > Edit > Delete: Options from Questions was deleted but was not removed
- Bug Fixed: Administration > System Setup > Education Structure: Encountered 404 error when copying data on Systems tab

### [3.66.5] - 2021-06-29
- Bug Fixed: Administration > Performance > Assessment > Assessment Period > Add: Encountered 404 error upon saving
- Bug Fixed: Administration > Security > Permission > Personal tab: To enable Execute function for Report Cards

### [3.66.4] - 2021-06-23
- Implemented: Add a function to attach multiple files under the student and staff health feature
- Implemented: Change password reset request email subject to application name
- Implemented: Add Export button function - Institutions > Staff > Appointments > Positions
- Bug Fixed: Institutions > School > Students > Students Transfer Out: Auto Assignee in student transfer showing as unassigned
- Bug Fixed: Workflow Rule was not configured but it is processing
- Bug Fixed: Assessment Mark Placeholders in Report Cards
- Bug Fixed: Administration > Security > Roles > Permission: Unable to save record
- Bug Fixed: Institution > Performance > Outcomes: User is able to mark Outcomes for student who is not taking the Subject selected
- Bug Fixed: Administration > Security > Groups > Edit: Encountered 404 error when adding a new use

### [3.66.3] - 2021-06-18
- Bug Fixed: Institution > Students > Promote: There is no option to select for Next Class field
- Bug Fixed: Directory: User who is a Student and a Staff has only 1 record in Directory page
- Bug Fixed: Institution> Academic > Feeders > Outgoing: User is given security permission to delete record but is unable to do so
- Implemented: Update Export button function - Institutions > General > Overview (include custom fields contacts and shifts)
- Bug Fixed: Administration > Security > Roles > Permission: Permission missing for Finance > Income, Budget and Expenditure
- Bug Fixed: Administration > System Setup > Academic Period: Start Date changes when student is successfully Transferred to a new Institution
- Bug Fixed: Institution > Bulk Transfer: To allow user to select the same Grade that student is currently in for the Next Institution
- Bug Fixed: Administration > System Setup > Localization > Translations: Word/ phrases not created
- Bug Fixed: Institutions > General > Map > Export
- Bug Fixed: Administration > System Setup > Administrative Boundaries > Areas (Administrative) tab: Encountered 404 error
- Bug Fixed: Institution > Staff > Transfer: To make Position End Date and Start Date fields mandatory fields

### [3.66.2] - 2021-06-12
- Bug Fixed: Reports>Institutions:Progress Loading bar image is different between RTL and LTR
- Bug Fixed: Administration > Security Groups : Allow the adding of Multiple Roles for the same user in User Groups
- Bug Fixed: Administration > System Setup > Custom Field: Mandatory field is missing for Checkbox
- Bug Fixed: Institution > Staff > Pending Change in Assignment: Search function under Staff Pending Change in Assignment is not working
- Bug Fixed: Institutions > Academic > Programmes: Education Subjects on List page are not reflected
- Bug Fixed: Administration > Training > Results: Search field not working
- Bug Fixed: Reports > Students Report : Add Institutions information in existing report
- Bug Fixed: Institutions > Attendances > Students > Import: Unable to Import even though all fills were filled up
- Bug Fixed: Administrations > System Setup > Attendances > Status: No error validation when duplicate records are created
- Bug Fixed: Administration > Security > Roles > Add/Edit page : Include Security Role code field in page
- Bug Fixed: Reports > Directory > User Default Identity report: To add Username column in the report
- Bug Fixed: Reports > Students > Student Health Report : Page Loading Issue
- Bug Fixed: Administrations > System Setup > Localizations > Translations: Missing Translations
- Bug Fixed: Reports > Students > Student Health Report > Overview: Transferred student still appear in the Institution which student was Transferred Out from
- Implemented: OpenEMIS Core: Webhook Education Area - Create
- Implemented: OpenEMIS Core: Webhook Education Area - Delete
- Implemented: OpenEMIS Core: Webhook Education Structure Level - Create
- Implemented: OpenEMIS Core: Webhook Education Structure Level - Update
- Implemented: OpenEMIS Core: Webhook Education Structure Level - Delete
- Implemented: OpenEMIS Core: Webhook Education Structure Subject - Update
- Implemented: OpenEMIS Core: Webhook Education Structure Subject - Delete
- Implemented: OpenEMIS Core: Webhook Education Structure Grade Subject - Create
- Implemented: OpenEMIS Core: Webhook Education Structure Grade Subject - Update
- Implemented: OpenEMIS Core: Webhook Education Structure Grade Subject - Delete
- Bug Fixed: Report > Institution > Student Attendance Summary report: Education Grade listed not correct
- Bug Fixed: Administration > System Setup > Academic Period > Delete: Encountered 404 error upon deleting the record
- Bug Fixed: Institution > Appointment: To add "s" at the end of Appointment
- Implemented: Add Export button function - Institutions > General > Map

### [3.66.1] - 2021-06-04
- Bug Fixed: Profiles > Contacts tab : Allow user to add contact information
- Bug Fixed: Institution > Infrastructure : Unable to delete infrastructure
- Bug Fixed: Reports > Institution > Students/Staff: To add Sector and Locality columns in the reports
- Bug Fixed: Institutions > Staff > Professional > License > Edit : Allow user to edit the License Type dropdown
- Bug Fixed: Update Reports > Students > Contacts
- Bug Fixed: Rename Profile module to Personal
- Bug Fixed: Administration > System Setup > Education Structure > Programmes: Next Programme was not copied when user copied from the current Academic Period to next Academic Period
- Bug Fixed: Reports > Students > Student Health Report > Overview: Student without Middle and Third Name has a big gap in between the First and Last Name
- Implemented: OpenEMIS Core: Webhook Education Structure Cycle - Create
- Implemented: OpenEMIS Core: Webhook Education Structure Cycle - Update
- Implemented: OpenEMIS Core: Webhook Education Structure Cycle - Delete
- Implemented: OpenEMIS Core: Webhook Education Structure Programme - Create
- Implemented: OpenEMIS Core: Webhook Education Structure Programme - Update
- Implemented: OpenEMIS Core: Webhook Education Structure Programme - Delete
- Implemented: Bug Fixed: OpenEMIS Core: Webhook Education Structure Grade - Create
- Implemented: OpenEMIS Core: Webhook Education Structure Grade - Update
- Implemented: OpenEMIS Core: Webhook Education Structure Grade - Delete
- Implemented: OpenEMIS Core: Webhook Education Structure Subject - Create
- Implemented: OpenEMIS Core: Webhook Academic Period - Create
- Implemented: OpenEMIS Core: Webhook Academic Period - Update
- Bug Fixed: Institution > Performance > Assessment > Import: Add Education Subject field on the Import Summary page
- Implemented: Institutions > Infrastructure > Overview > Rooms : Add a new field called area

### [3.66.0] - 2021-05-28
- Bug Fixed: Institutions > Performance > Report Cards: Report Cards remained In-Progress
- Bug Fixed: Institution > Staff > Edit: Remove Default Start Date for Staff Change of Start Date
- Bug Fixed: Meals Modules: Add 's' to Distribution and Meal Programme
- Bug Fixed: Reports> Institutions > Subjects: Data not reflected correctly on the report
- Bug Fixed: Institution > Appointment > Duties > Add: Unable to add Head of Department
- Bug Fixed: Institution > Attendance > Student: All Week page is not reflecting the attendance

### [3.65.4] - 2021-05-24
- Bug Fixed: Institution > Student > Transfer: Start Date and End Date is reflected incorrectly
- Implemented: Changes to Data Archiving feature in OpenEMIS Core - Part II
- Implemented: Remove default date for student / staff actions

### [3.65.3] - 2021-05-21
- Bug Fixed: Institution > Performance > Assessment: Student who had Withdrawn/Transferred Status in 1 class and Enrolled Status in another class from the same Institution has Enrolled Status for both class in Assessment page
- Bug Fixed: Administration > System Setup > Education Structure > Programme > Add: Upon adding a new record, the record is not reflected on the List page
- Bug Fixed: Report > Institution > Student Attendance Summary report: Education Grade and Report End Date are not correct
- Bug Fixed: Reports > Institution > Infrastructure Needs: Changes to reports columns
- Bug Fixed: Institution > Performance > Assessment > Import: Issues with Import
- Bug Fixed: Institution > Student > Pending Transfer Out: Bulk Transfer page missing
- Bug Fixed: Institution > Attendance > Student: Attendance marked on Fridays disappeared
- Bug Fixed: Directory > Add: Encountered error while adding users with Users Type
- Bug Fixed: Administration > System Setup > Risk: 504 error encountered when generating risk for all Institutions	
- Implemented: Add area filter to the staff and student profile feature
- Bug Fixed: Institution > Attendance > Students: Unable to view students Attendance even though the students are assigned to a class
- Bug Fixed: Institution > Survey > Forms: Repeater Question is not saving the Date of Birth correctly
- Bug Fixed: Reports > Institutions > Student Body Mass report: Changes to the report
- Bug Fixed: Institution > Student > Meals: To remove "Total Paid" on the list page
- Bug Fixed: Institution > Infrastructure > Overview > Export: Encountered 404 error
- Bug Fixed: Institution > Student > Add: Education Grade field is listing Education Grade from all Academic Period
- Bug Fixed: Institution > Performance > Assessments: Sys Admin unable to edit Assessment. Edit button is missing.

### [3.65.2] - 2021-05-12
- Bug Fixed: Installer creates database without tables
- Bug Fixed: Institution > Student > Transfer: Student's Start Date and End Date upon approval did no match with the Academic Period
- Implemented: Report > Students > Student Out of School Report: add / remove columns

### [3.65.1] - 2021-05-07
- Bug Fixed: Institution > Survey > Forms: Edited record disappeared
- Bug Fixed: Institution > Student > Pending Transfer In: After successful Bulk Student Transfer In process, notification is incorrect
- Bug Fixed: Directory > Search: Error encountered when click on a user without selecting User Type
- Bug Fixed: Institution > Performance > Assessment: Homeroom Teacher is able to edit marks even though the Homeroom Teacher is not assigned to any Subjects
- Bug Fixed: Institutions > Finance > Budget/Income/Expenditure : Remove the word PM
- Bug Fixed: Institution > Student > Academic > Programmes tab: End Date is showing incorrect date for students with Withdrawn status
- Bug Fixed: Institution > Staff > Career > Leave tab: Leave filter not able to filter Staff Leave Type accordingly### [3.65.1] - 2021-05-07

### [3.65.0] - 2021-04-30
- Implemented: Changes to Data Archiving feature in OpenEMIS Core
- Implemented: Improvement of the Workflow module
- Bug Fixed: Institution > Attendance > Student > All Day > Export Page
- Bug Fixed: Institution > Meals > Students Dashboard Statistics
- Bug Fixed: Directory > Student > Academic > Risk page
- Bug Fixed: Profile > Guardian > Student > Academic page

### [3.64.4] - 2021-04-22
- Implemented: Add an option to modifying the student grade and specialty
- Bug Fixed: Institutions > Staff > Finance > Payslip > Edit: Unable to edit record
- Bug Fixed: Institutions > Staff > Career > Associations : Issues for staff
- Bug Fixed: Institutions > Attendance > Student: Secondary Teacher unable to mark attendance for the Class he is assigned to
- Bug Fixed: Institutions > Performance > Report Cards: Unable to generate Report Card
- Bug Fixed: Institutions > Student > Promote: Education Grade is not reflecting based on the Academic Period selected
- Bug Fixed: Institutions > Report Cards > Comments: Unable to view All Subjects comments even though security permission is given
- Bug Fixed: Institutions > Cases > Student Attendance : Set All for Class filter dropdown as Default
- Bug Fixed: Administration > System Setup > System Configuration > Automated Student Withdrawal: Encountered 404 error
- Bug Fixed: Administration > System Setup > Field Options > Meal Implementers: Unable to add new record in other language
- Bug Fixed: Administration > Security > Roles > Permission: Delete functions for Timetables are disabled
- Bug Fixed: Profile > Workbench > Cases: There are Open Cases in the assignee workbench even though the Cases have been closed
- Bug Fixed: Ensure that system shows education structure from the correct academic period

### [3.64.3] - 2021-04-16
- Bug Fixed: Institutions > Report Cards > Comments: Student transferred to another school, but report card record is still available in the previous school
- Bug Fixed: Institutions > Academic > Classes: When a Student gets transferred back into the same school in the same year, student is not showing in the list of Unassigned students
- Bug Fixed: Institutions > Attendance > Student: Withdrawn student still appear on Attendance page
- Bug Fixed: Institutions > Staff > Finance > Payslip > Add: Unable to add record
- Bug Fixed: Institutions > Report Cards > Comment: Homeroom Teacher unable to input/edit comment
- Bug Fixed: Institutions > Students > Health > Vaccinations > Delete: Delete Prompt header is reflecting Immunizations
- Bug Fixed: Institutions > Performance > Competencies > Import: There is no Option to select Class
- Bug Fixed: Reports > Institution > Infrastructure: Changes to reports field
- Bug Fixed: Reports > Institutions > Institutions > View: View format is incorrect
- Bug Fixed: Administration > Workflow: Editable and Deletable check/uncheck function is not working
- Bug Fixed: API: The list of Absence -Excused Reasons are not listed in the same order as in Student Attendance page
- Bug Fixed: Directory > Search user: When selecting User Type as Students, the selection listed belongs to Staff
- Bug Fixed: Profile: User Account is only showing First and Last Name

### [3.64.2] - 2021-04-14
- Implemented: Improvement to the school closing process
- Implemented: Improvement to the student overview page
- Implemented: Changes to reports for the Infrastructure module
- Implemented: Improvement of readability of the student status in the current year
- Bug Fixed: Reports > Institution > Students: Withdrawn: Data not reflected on the View page after report has been generated
- Bug Fixed: Reports > Workflow report: Report is remain in In-Progress status
- Bug Fixed: Reports > Institution > Infrastructure report: Add 'All' option for some of the fields
- Bug Fixed: Users > Identities tab > Edit Page : Make Nationality mandatory
- Bug Fixed: Institution > Attendance > Students: If a Multigrade Class period is configured as Subjects, the list of Subjects listed are Subjects from All Education Grade that are assigned to the Multi Grade class

### [3.64.1] - 2021-04-09
- Bug Fixed: Administration > System Setup > Education Structure > Programme: When selecting a past Academic Period filter and then selecting Levels filter, the Academic Period filter reverted to current Academic Period
- Bug Fixed: Administration > System Setup > Academic Period > Edit: An error encountered when editing End Date for a non-current Academic Period
- Bug Fixed: Administration > Profile > Institution and Staff > Add/Edit: Error message shows [Message Not Found]
- Bug Fixed: Administration > Examinations > Centres > Add: 404 error encountered for Existing Institution with 1 Institution
- Bug Fixed: Administration > System Setup > Education Structure > Programmes/Grades : Unable to save if there is an existing Programmes/Grades with the same code
- Bug Fixed: Institutions > Academic > Schedules > Timetable > Timetable tab: Overview button is missing
- Bug Fixed: Institutions > Academic > Schedules > Timetables Edit Page : System should allow user to add lessons without selecting a room
- Bug Fixed: Institutions > Students> Withdrawn : Search is not working
- Bug Fixed: Institutions > Survey > Forms: Checkbox question did not appear in the Repeater Survey Form
- Bug Fixed: Reports > Trainings > Results: Unable to generate report and To add "All Training Courses" selection for Training Course field
- Bug Fixed: Reports > Workflow Records: Institutions > Student Transfer > Receiving: Upon selecting the Institution, the Institution selected was removed from the field
- Bug Fixed: Reports > Directory > User List Report: Add username column
- Bug Fixed: Profile > Guardian > Students > Academic: Guardian is unable to view student Academic page
- Bug Fixed: Workbench> Survey: Survey does not appear in workbench until user access institutions survey manually

### [3.64.0] - 2021-04-06
- Implemented: Installation Wizard

### [3.63.2] - 2021-04-01
- Bug Fixed: Number appearing when editing Institutions > Survey > Forms page
- Bug Fixed: Issues when viewing Students data in Guardian Login > Profiles > Students page

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
