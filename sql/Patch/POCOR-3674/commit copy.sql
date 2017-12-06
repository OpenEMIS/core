-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3674', NOW());

-- translations
CREATE TABLE `z_3674_translations_backup` LIKE `translations`;
INSERT INTO `z_3674_translations_backup` SELECT * FROM `translations`;

-- delete unused translate word.
DELETE FROM `translations` WHERE `id` = 2 And `en` = 'Welcome';
DELETE FROM `translations` WHERE `id` = 11 And `en` = 'Settings';
DELETE FROM `translations` WHERE `id` = 13 And `en` = 'Demo';
DELETE FROM `translations` WHERE `id` = 15 And `en` = 'Day';
DELETE FROM `translations` WHERE `id` = 16 And `en` = 'Month';
DELETE FROM `translations` WHERE `id` = 17 And `en` = 'Year';
DELETE FROM `translations` WHERE `id` = 20 And `en` = 'Unisex';
DELETE FROM `translations` WHERE `id` = 29 And `en` = 'Previous';
DELETE FROM `translations` WHERE `id` = 30 And `en` = 'Next';
DELETE FROM `translations` WHERE `id` = 31 And `en` = 'Statistics';
DELETE FROM `translations` WHERE `id` = 32 And `en` = 'Institutions Sites';
DELETE FROM `translations` WHERE `id` = 33 And `en` = 'Activities';
DELETE FROM `translations` WHERE `id` = 35 And `en` = 'has been edited';
DELETE FROM `translations` WHERE `id` = 36 And `en` = 'has been deleted';
DELETE FROM `translations` WHERE `id` = 37 And `en` = 'has been added to the List of';
DELETE FROM `translations` WHERE `id` = 38 And `en` = 'By';
DELETE FROM `translations` WHERE `id` = 40 And `en` = 'Note: Max upload file size is 2MB.';
DELETE FROM `translations` WHERE `id` = 41 And `en` = 'Image';
DELETE FROM `translations` WHERE `id` = 42 And `en` = 'Document';
DELETE FROM `translations` WHERE `id` = 44 And `en` = 'Powerpoint';
DELETE FROM `translations` WHERE `id` = 45 And `en` = 'File is deleted successfully.';
DELETE FROM `translations` WHERE `id` = 46 And `en` = 'File was deleted successfully.';
DELETE FROM `translations` WHERE `id` = 47 And `en` = 'Error occurred while deleting file.';
DELETE FROM `translations` WHERE `id` = 48 And `en` = 'Files have been saved successfully.';
DELETE FROM `translations` WHERE `id` = 49 And `en` = 'Some errors have been encountered while saving files.';
DELETE FROM `translations` WHERE `id` = 50 And `en` = 'Records have been added/updated successfully.';
DELETE FROM `translations` WHERE `id` = 51 And `en` = 'Records have been deleted successfully.';
DELETE FROM `translations` WHERE `id` = 52 And `en` = 'Error occurred while deleting record.';
DELETE FROM `translations` WHERE `id` = 53 And `en` = ' have been deleted successfully.';
DELETE FROM `translations` WHERE `id` = 54 And `en` = 'Searching...';
DELETE FROM `translations` WHERE `id` = 56 And `en` = 'Please enter a unique Identification No';
DELETE FROM `translations` WHERE `id` = 57 And `en` = 'Please enter a unique Code';
DELETE FROM `translations` WHERE `id` = 58 And `en` = 'List of Students';
DELETE FROM `translations` WHERE `id` = 64 And `en` = 'More';
DELETE FROM `translations` WHERE `id` = 65 And `en` = 'Additional Info';
DELETE FROM `translations` WHERE `id` = 66 And `en` = 'Student Information';
DELETE FROM `translations` WHERE `id` = 67 And `en` = 'Static fields';
DELETE FROM `translations` WHERE `id` = 69 And `en` = 'National Assessments';
DELETE FROM `translations` WHERE `id` = 71 And `en` = 'Assessment Results';
DELETE FROM `translations` WHERE `id` = 72 And `en` = 'Student Details';
DELETE FROM `translations` WHERE `id` = 73 And `en` = 'Student History';
DELETE FROM `translations` WHERE `id` = 76 And `en` = 'Student Identification No, First Name or Last Name';
DELETE FROM `translations` WHERE `id` = 77 And `en` = 'Student Identification';
DELETE FROM `translations` WHERE `id` = 78 And `en` = 'Teacher Identification';
DELETE FROM `translations` WHERE `id` = 79 And `en` = 'Staff Identification';
DELETE FROM `translations` WHERE `id` = 80 And `en` = 'Identification No.';
DELETE FROM `translations` WHERE `id` = 81 And `en` = 'Identification No';
DELETE FROM `translations` WHERE `id` = 90 And `en` = 'Uploaded On';
DELETE FROM `translations` WHERE `id` = 96 And `en` = 'Birth Place Area';
DELETE FROM `translations` WHERE `id` = 100 And `en` = 'E-mail';
DELETE FROM `translations` WHERE `id` = 105 And `en` = 'No history found.';
DELETE FROM `translations` WHERE `id` = 106 And `en` = 'List of Staff';
DELETE FROM `translations` WHERE `id` = 108 And `en` = 'Staff Information';
DELETE FROM `translations` WHERE `id` = 109 And `en` = 'Staff Details';
DELETE FROM `translations` WHERE `id` = 110 And `en` = 'No Staff found.';
DELETE FROM `translations` WHERE `id` = 111 And `en` = 'Staff History';
DELETE FROM `translations` WHERE `id` = 112 And `en` = 'List of Teachers';
DELETE FROM `translations` WHERE `id` = 113 And `en` = 'Add new Teacher';
DELETE FROM `translations` WHERE `id` = 118 And `en` = 'Teacher Details';
DELETE FROM `translations` WHERE `id` = 119 And `en` = 'Teacher History';
DELETE FROM `translations` WHERE `id` = 120 And `en` = 'Date of Issue';
DELETE FROM `translations` WHERE `id` = 121 And `en` = 'Certificate';
DELETE FROM `translations` WHERE `id` = 122 And `en` = 'Certificate No.';
DELETE FROM `translations` WHERE `id` = 123 And `en` = 'Issued By';
DELETE FROM `translations` WHERE `id` = 127 And `en` = 'No Teacher found.';
DELETE FROM `translations` WHERE `id` = 129 And `en` = 'Please enter a valid First Name';
DELETE FROM `translations` WHERE `id` = 130 And `en` = 'Please enter a valid Last Name';
DELETE FROM `translations` WHERE `id` = 131 And `en` = 'Please enter a valid Identification No';
DELETE FROM `translations` WHERE `id` = 132 And `en` = 'Please select a Gender';
DELETE FROM `translations` WHERE `id` = 135 And `en` = 'Please select a Date of Birth';
DELETE FROM `translations` WHERE `id` = 137 And `en` = 'Please enter a valid username';
DELETE FROM `translations` WHERE `id` = 138 And `en` = 'This username is already in use.';
DELETE FROM `translations` WHERE `id` = 139 And `en` = 'Password must be at least 6 characters';
DELETE FROM `translations` WHERE `id` = 140 And `en` = 'Please enter a valid password';
DELETE FROM `translations` WHERE `id` = 141 And `en` = 'You need to assign a role to the user';
DELETE FROM `translations` WHERE `id` = 143 And `en` = 'No Areas';
DELETE FROM `translations` WHERE `id` = 145 And `en` = 'Edit Permissions';
DELETE FROM `translations` WHERE `id` = 146 And `en` = 'Edit Role - Area Restricted';
DELETE FROM `translations` WHERE `id` = 147 And `en` = 'Role - Area Restricted';
DELETE FROM `translations` WHERE `id` = 148 And `en` = 'Edit Role Assignment';
DELETE FROM `translations` WHERE `id` = 149 And `en` = 'Role Assignment';
DELETE FROM `translations` WHERE `id` = 150 And `en` = 'Edit Roles';
DELETE FROM `translations` WHERE `id` = 154 And `en` = 'Edit Details';
DELETE FROM `translations` WHERE `id` = 155 And `en` = 'Edit Additional Info';
DELETE FROM `translations` WHERE `id` = 156 And `en` = 'Edit Programmes';
DELETE FROM `translations` WHERE `id` = 160 And `en` = 'Site Type';
DELETE FROM `translations` WHERE `id` = 161 And `en` = 'Site Code';
DELETE FROM `translations` WHERE `id` = 163 And `en` = 'Institution Name';
DELETE FROM `translations` WHERE `id` = 166 And `en` = 'Site Name';
DELETE FROM `translations` WHERE `id` = 170 And `en` = 'Province';
DELETE FROM `translations` WHERE `id` = 171 And `en` = 'District';
DELETE FROM `translations` WHERE `id` = 172 And `en` = 'LLG';
DELETE FROM `translations` WHERE `id` = 173 And `en` = 'Ward';
DELETE FROM `translations` WHERE `id` = 174 And `en` = 'Street';
DELETE FROM `translations` WHERE `id` = 175 And `en` = 'Block';
DELETE FROM `translations` WHERE `id` = 176 And `en` = 'Axis';
DELETE FROM `translations` WHERE `id` = 177 And `en` = 'State';
DELETE FROM `translations` WHERE `id` = 184 And `en` = 'Please enter a valid Name';
DELETE FROM `translations` WHERE `id` = 185 And `en` = 'Please enter a valid Code';
DELETE FROM `translations` WHERE `id` = 186 And `en` = 'Please select a Provider';
DELETE FROM `translations` WHERE `id` = 187 And `en` = 'Please select a Status';
DELETE FROM `translations` WHERE `id` = 188 And `en` = 'Please select the Date Opened';
DELETE FROM `translations` WHERE `id` = 190 And `en` = 'Please select an Ownership';
DELETE FROM `translations` WHERE `id` = 191 And `en` = 'Please select an Area';
DELETE FROM `translations` WHERE `id` = 192 And `en` = 'Institution Code';
DELETE FROM `translations` WHERE `id` = 193 And `en` = 'Institution Name or Code';
DELETE FROM `translations` WHERE `id` = 201 And `en` = 'Add New';
DELETE FROM `translations` WHERE `id` = 206 And `en` = 'Branch';
DELETE FROM `translations` WHERE `id` = 207 And `en` = 'Bank Details';
DELETE FROM `translations` WHERE `id` = 208 And `en` = 'National Education System';
DELETE FROM `translations` WHERE `id` = 210 And `en` = 'Seats';
DELETE FROM `translations` WHERE `id` = 215 And `en` = 'Totals';
DELETE FROM `translations` WHERE `id` = 217 And `en` = 'Source';
DELETE FROM `translations` WHERE `id` = 218 And `en` = 'Nature';
DELETE FROM `translations` WHERE `id` = 220 And `en` = 'Edit Teachers';
DELETE FROM `translations` WHERE `id` = 221 And `en` = 'Edit Training';
DELETE FROM `translations` WHERE `id` = 222 And `en` = 'Edit Bank Accounts';
DELETE FROM `translations` WHERE `id` = 223 And `en` = 'Edit Classes';
DELETE FROM `translations` WHERE `id` = 224 And `en` = 'Edit Finances';
DELETE FROM `translations` WHERE `id` = 225 And `en` = 'Edit Other Forms';
DELETE FROM `translations` WHERE `id` = 226 And `en` = 'Edit Infrastructure';
DELETE FROM `translations` WHERE `id` = 227 And `en` = 'Trained Teachers';
DELETE FROM `translations` WHERE `id` = 228 And `en` = 'No Available Programmes';
DELETE FROM `translations` WHERE `id` = 229 And `en` = 'Institution History';
DELETE FROM `translations` WHERE `id` = 231 And `en` = 'My Details';
DELETE FROM `translations` WHERE `id` = 232 And `en` = 'has been updated successfully.';
DELETE FROM `translations` WHERE `id` = 233 And `en` = 'Change Password';
DELETE FROM `translations` WHERE `id` = 234 And `en` = 'successfully updated.';
DELETE FROM `translations` WHERE `id` = 235 And `en` = 'Please try again later.';
DELETE FROM `translations` WHERE `id` = 236 And `en` = 'Please enter your current password';
DELETE FROM `translations` WHERE `id` = 237 And `en` = 'Current password does not match.';
DELETE FROM `translations` WHERE `id` = 238 And `en` = 'New password required.';
DELETE FROM `translations` WHERE `id` = 239 And `en` = 'Please enter a min of 6 alpha numeric characters.';
DELETE FROM `translations` WHERE `id` = 240 And `en` = 'Please enter alpha numeric characters.';
DELETE FROM `translations` WHERE `id` = 241 And `en` = 'Passwords do not match.';
DELETE FROM `translations` WHERE `id` = 243 And `en` = 'Support';
DELETE FROM `translations` WHERE `id` = 245 And `en` = 'Edit My Details';
DELETE FROM `translations` WHERE `id` = 255 And `en` = 'Orientation';
DELETE FROM `translations` WHERE `id` = 257 And `en` = 'Certification';
DELETE FROM `translations` WHERE `id` = 259 And `en` = 'January';
DELETE FROM `translations` WHERE `id` = 260 And `en` = 'February';
DELETE FROM `translations` WHERE `id` = 261 And `en` = 'March';
DELETE FROM `translations` WHERE `id` = 262 And `en` = 'April';
DELETE FROM `translations` WHERE `id` = 263 And `en` = 'May';
DELETE FROM `translations` WHERE `id` = 264 And `en` = 'June';
DELETE FROM `translations` WHERE `id` = 265 And `en` = 'July';
DELETE FROM `translations` WHERE `id` = 266 And `en` = 'August';
DELETE FROM `translations` WHERE `id` = 267 And `en` = 'September';
DELETE FROM `translations` WHERE `id` = 268 And `en` = 'October';
DELETE FROM `translations` WHERE `id` = 269 And `en` = 'November';
DELETE FROM `translations` WHERE `id` = 270 And `en` = 'December';
DELETE FROM `translations` WHERE `id` = 271 And `en` = 'Your session is timed out. Please login again.';
DELETE FROM `translations` WHERE `id` = 272 And `en` = 'You are not an authorized user.';
DELETE FROM `translations` WHERE `id` = 273 And `en` = 'You have encountered an unexpected error. Please contact the system administrator for assistance.';
DELETE FROM `translations` WHERE `id` = 274 And `en` = 'Host Unreachable';
DELETE FROM `translations` WHERE `id` = 275 And `en` = 'Host is unreachable, please check your internet connection.';
DELETE FROM `translations` WHERE `id` = 276 And `en` = 'Session Timed Out';
DELETE FROM `translations` WHERE `id` = 277 And `en` = 'Page not found';
DELETE FROM `translations` WHERE `id` = 278 And `en` = 'The requested page cannot be found.';
DELETE FROM `translations` WHERE `id` = 279 And `en` = 'Please contact the administrator for assistance.';
DELETE FROM `translations` WHERE `id` = 280 And `en` = 'An unexpected error has occurred.';
DELETE FROM `translations` WHERE `id` = 281 And `en` = 'JSON parse failed';
DELETE FROM `translations` WHERE `id` = 282 And `en` = 'Invalid JSON data.';
DELETE FROM `translations` WHERE `id` = 283 And `en` = 'Request Timeout';
DELETE FROM `translations` WHERE `id` = 284 And `en` = 'Request Aborted';
DELETE FROM `translations` WHERE `id` = 285 And `en` = 'Your request has been aborted.';
DELETE FROM `translations` WHERE `id` = 286 And `en` = 'Unexpected Error';
DELETE FROM `translations` WHERE `id` = 287 And `en` = 'Edit System Configurations';
DELETE FROM `translations` WHERE `id` = 288 And `en` = 'File is updated successfully.';
DELETE FROM `translations` WHERE `id` = 289 And `en` = 'Error occurred while updating file.';
DELETE FROM `translations` WHERE `id` = 290 And `en` = 'File have been updated successfully.';
DELETE FROM `translations` WHERE `id` = 291 And `en` = 'File has not been updated successfully.';
DELETE FROM `translations` WHERE `id` = 292 And `en` = 'File format not supported.';
DELETE FROM `translations` WHERE `id` = 293 And `en` = 'Image has exceeded the allow file size of';
DELETE FROM `translations` WHERE `id` = 294 And `en` = 'Please reduce file size.';
DELETE FROM `translations` WHERE `id` = 295 And `en` = 'Image resolution is too small.';
DELETE FROM `translations` WHERE `id` = 296 And `en` = 'Error';
DELETE FROM `translations` WHERE `id` = 298 And `en` = 'log does not exists';
DELETE FROM `translations` WHERE `id` = 299 And `en` = 'Add new Institution';
DELETE FROM `translations` WHERE `id` = 300 And `en` = 'Institution Details';
DELETE FROM `translations` WHERE `id` = 306 And `en` = 'Census';
DELETE FROM `translations` WHERE `id` = 307 And `en` = 'Enrolment';
DELETE FROM `translations` WHERE `id` = 313 And `en` = 'Other Forms';
DELETE FROM `translations` WHERE `id` = 320 And `en` = 'Edit Custom Fields';
DELETE FROM `translations` WHERE `id` = 321 And `en` = 'Custom Table';
DELETE FROM `translations` WHERE `id` = 322 And `en` = 'Edit Custom Table';
DELETE FROM `translations` WHERE `id` = 324 And `en` = 'Accounts and Security';
DELETE FROM `translations` WHERE `id` = 327 And `en` = 'Population';
DELETE FROM `translations` WHERE `id` = 328 And `en` = 'Data Processing';
DELETE FROM `translations` WHERE `id` = 329 And `en` = 'Generate Reports';
DELETE FROM `translations` WHERE `id` = 331 And `en` = 'Processes';
DELETE FROM `translations` WHERE `id` = 332 And `en` = 'Database';
DELETE FROM `translations` WHERE `id` = 333 And `en` = 'Backup';
DELETE FROM `translations` WHERE `id` = 334 And `en` = 'Restore';
DELETE FROM `translations` WHERE `id` = 337 And `en` = 'Graduates not required.';
DELETE FROM `translations` WHERE `id` = 339 And `en` = 'Message Not Found.';
DELETE FROM `translations` WHERE `id` = 340 And `en` = 'Good';
DELETE FROM `translations` WHERE `id` = 341 And `en` = 'Fair';
DELETE FROM `translations` WHERE `id` = 342 And `en` = 'Poor';
DELETE FROM `translations` WHERE `id` = 343 And `en` = 'Current Password';
DELETE FROM `translations` WHERE `id` = 348 And `en` = 'Please enter your current password.';
DELETE FROM `translations` WHERE `id` = 353 And `en` = 'Edit Area Levels';
DELETE FROM `translations` WHERE `id` = 354 And `en` = 'Structure';
DELETE FROM `translations` WHERE `id` = 360 And `en` = 'Order';
DELETE FROM `translations` WHERE `id` = 362 And `en` = 'ISCED Level';
DELETE FROM `translations` WHERE `id` = 366 And `en` = 'Grade Subject';
DELETE FROM `translations` WHERE `id` = 367 And `en` = 'Back to Programmes';
DELETE FROM `translations` WHERE `id` = 368 And `en` = 'Back to Grades';
DELETE FROM `translations` WHERE `id` = 369 And `en` = 'Graduates';
DELETE FROM `translations` WHERE `id` = 370 And `en` = 'Please select a programme first.';
DELETE FROM `translations` WHERE `id` = 372 And `en` = 'an option';
DELETE FROM `translations` WHERE `id` = 373 And `en` = 'School Year';
DELETE FROM `translations` WHERE `id` = 375 And `en` = 'Available';
DELETE FROM `translations` WHERE `id` = 376 And `en` = 'Categories';
DELETE FROM `translations` WHERE `id` = 378 And `en` = 'Materials';
DELETE FROM `translations` WHERE `id` = 379 And `en` = 'Resources';
DELETE FROM `translations` WHERE `id` = 380 And `en` = 'Furniture';
DELETE FROM `translations` WHERE `id` = 381 And `en` = 'Energy';
DELETE FROM `translations` WHERE `id` = 383 And `en` = 'Sanitation';
DELETE FROM `translations` WHERE `id` = 384 And `en` = 'Water';
DELETE FROM `translations` WHERE `id` = 385 And `en` = 'Banks';
DELETE FROM `translations` WHERE `id` = 386 And `en` = 'Capital Income';
DELETE FROM `translations` WHERE `id` = 387 And `en` = 'Capital Expenditure';
DELETE FROM `translations` WHERE `id` = 388 And `en` = 'Recurrent Income';
DELETE FROM `translations` WHERE `id` = 389 And `en` = 'Recurrent Expenditure';
DELETE FROM `translations` WHERE `id` = 391 And `en` = 'Instructional';
DELETE FROM `translations` WHERE `id` = 392 And `en` = 'Support Services';
DELETE FROM `translations` WHERE `id` = 393 And `en` = 'Facilities';
DELETE FROM `translations` WHERE `id` = 394 And `en` = 'Qualification Certificates';
DELETE FROM `translations` WHERE `id` = 395 And `en` = 'Qualification Categories';
DELETE FROM `translations` WHERE `id` = 396 And `en` = 'Qualification Institutions';
DELETE FROM `translations` WHERE `id` = 398 And `en` = 'Sources';
DELETE FROM `translations` WHERE `id` = 399 And `en` = 'Branches';
DELETE FROM `translations` WHERE `id` = 400 And `en` = 'Sanitations';
DELETE FROM `translations` WHERE `id` = 402 And `en` = 'Single Line Text';
DELETE FROM `translations` WHERE `id` = 403 And `en` = 'Multi Line Text';
DELETE FROM `translations` WHERE `id` = 404 And `en` = 'Dropdown List';
DELETE FROM `translations` WHERE `id` = 406 And `en` = 'Checkboxes';
DELETE FROM `translations` WHERE `id` = 407 And `en` = 'Institution Custom Fields';
DELETE FROM `translations` WHERE `id` = 410 And `en` = 'Student Custom Fields';
DELETE FROM `translations` WHERE `id` = 411 And `en` = 'Teacher Custom Fields';
DELETE FROM `translations` WHERE `id` = 412 And `en` = 'Staff Custom Fields';
DELETE FROM `translations` WHERE `id` = 414 And `en` = 'Field Label';
DELETE FROM `translations` WHERE `id` = 416 And `en` = 'Filter by';
DELETE FROM `translations` WHERE `id` = 417 And `en` = 'X Category';
DELETE FROM `translations` WHERE `id` = 418 And `en` = 'Y Category';
DELETE FROM `translations` WHERE `id` = 420 And `en` = 'Dashboard Image';
DELETE FROM `translations` WHERE `id` = 421 And `en` = 'Back to Config';
DELETE FROM `translations` WHERE `id` = 423 And `en` = 'Date Format';
DELETE FROM `translations` WHERE `id` = 424 And `en` = 'Currency';
DELETE FROM `translations` WHERE `id` = 426 And `en` = 'Back to List';
DELETE FROM `translations` WHERE `id` = 427 And `en` = 'Reconnecting...';
DELETE FROM `translations` WHERE `id` = 430 And `en` = 'Education Management Information System';
DELETE FROM `translations` WHERE `id` = 438 And `en` = 'Custom Tables';
DELETE FROM `translations` WHERE `id` = 441 And `en` = 'Modules';
DELETE FROM `translations` WHERE `id` = 444 And `en` = 'Retype Password';
DELETE FROM `translations` WHERE `id` = 445 And `en` = 'User Details';
DELETE FROM `translations` WHERE `id` = 446 And `en` = 'Full access on all modules';
DELETE FROM `translations` WHERE `id` = 447 And `en` = 'Back to Roles';
DELETE FROM `translations` WHERE `id` = 449 And `en` = 'PHP Version';
DELETE FROM `translations` WHERE `id` = 450 And `en` = 'Web Server';
DELETE FROM `translations` WHERE `id` = 451 And `en` = 'Operating System';
DELETE FROM `translations` WHERE `id` = 452 And `en` = 'Data not available.';
DELETE FROM `translations` WHERE `id` = 453 And `en` = 'The selected report is currently being processed.';
DELETE FROM `translations` WHERE `id` = 455 And `en` = 'Student Reports';
DELETE FROM `translations` WHERE `id` = 456 And `en` = 'Teacher Reports';
DELETE FROM `translations` WHERE `id` = 458 And `en` = 'Consolidated Reports';
DELETE FROM `translations` WHERE `id` = 459 And `en` = 'Indicator Reports';
DELETE FROM `translations` WHERE `id` = 460 And `en` = 'Data Quality Reports';
DELETE FROM `translations` WHERE `id` = 462 And `en` = 'Custom Reports';
DELETE FROM `translations` WHERE `id` = 463 And `en` = 'Please contact';
DELETE FROM `translations` WHERE `id` = 465 And `en` = 'for more information on Custom Reports.';
DELETE FROM `translations` WHERE `id` = 466 And `en` = 'Last Run';
DELETE FROM `translations` WHERE `id` = 469 And `en` = 'List of Institutions';
DELETE FROM `translations` WHERE `id` = 476 And `en` = 'Institution Programme Report';
DELETE FROM `translations` WHERE `id` = 477 And `en` = 'List of Institutions with programmes';
DELETE FROM `translations` WHERE `id` = 478 And `en` = 'Institution Bank Account Report';
DELETE FROM `translations` WHERE `id` = 479 And `en` = 'List of Institutions with bank accounts';
DELETE FROM `translations` WHERE `id` = 480 And `en` = 'Enrolment Report';
DELETE FROM `translations` WHERE `id` = 482 And `en` = 'Class Report';
DELETE FROM `translations` WHERE `id` = 486 And `en` = 'Teacher Report';
DELETE FROM `translations` WHERE `id` = 490 And `en` = 'Staff Report';
DELETE FROM `translations` WHERE `id` = 496 And `en` = 'Income Report';
DELETE FROM `translations` WHERE `id` = 498 And `en` = 'Expenditure Report';
DELETE FROM `translations` WHERE `id` = 504 And `en` = 'Sanitation Report';
DELETE FROM `translations` WHERE `id` = 505 And `en` = 'Summary of sanitation, gender and condition from census';
DELETE FROM `translations` WHERE `id` = 506 And `en` = 'Furniture Report';
DELETE FROM `translations` WHERE `id` = 508 And `en` = 'Resource Report';
DELETE FROM `translations` WHERE `id` = 510 And `en` = 'Energy Report';
DELETE FROM `translations` WHERE `id` = 512 And `en` = 'Water Report';
DELETE FROM `translations` WHERE `id` = 514 And `en` = 'Student Report';
DELETE FROM `translations` WHERE `id` = 515 And `en` = 'Report on student';
DELETE FROM `translations` WHERE `id` = 518 And `en` = 'Teacher List';
DELETE FROM `translations` WHERE `id` = 519 And `en` = 'Report on Teachers';
DELETE FROM `translations` WHERE `id` = 522 And `en` = 'Staff List';
DELETE FROM `translations` WHERE `id` = 523 And `en` = 'Report on Staff';
DELETE FROM `translations` WHERE `id` = 526 And `en` = 'Wheres My School Report';
DELETE FROM `translations` WHERE `id` = 527 And `en` = 'A Google Earth (KML) file containing all the location of all Institutions';
DELETE FROM `translations` WHERE `id` = 528 And `en` = 'Year Book Report';
DELETE FROM `translations` WHERE `id` = 530 And `en` = 'Return Rate';
DELETE FROM `translations` WHERE `id` = 531 And `en` = 'Census Discrepancy';
DELETE FROM `translations` WHERE `id` = 532 And `en` = 'Backup files found.';
DELETE FROM `translations` WHERE `id` = 534 And `en` = 'Consolidated';
DELETE FROM `translations` WHERE `id` = 536 And `en` = 'Summary of School\'s Census Data';
DELETE FROM `translations` WHERE `id` = 537 And `en` = 'Non-Responsive Schools Report';
DELETE FROM `translations` WHERE `id` = 539 And `en` = 'Data Discrepancy Report';
DELETE FROM `translations` WHERE `id` = 541 And `en` = 'Number of students (enrollment) by sex, age, locality and grade';
DELETE FROM `translations` WHERE `id` = 542 And `en` = 'Number of teachers by sex, locality and grade';
DELETE FROM `translations` WHERE `id` = 551 And `en` = 'Divide the number of pupils (or students) enrolled who are of the official age group for a given level of education by the population for the same age group and multiply the result by 100. This indicator has dimension values of sex, locality and grade.';
DELETE FROM `translations` WHERE `id` = 554 And `en` = 'Divide the number of children of official primary school-entrance age who enter the first grade of primary education for the first time by the population of the same age, and multiply the result by 100. This indicator has dimension values of sex and locality.';
DELETE FROM `translations` WHERE `id` = 556 And `en` = 'Divide the number of pupils (or students) enrolled in a given level of education regardless of age by the population of the age group which officially corresponds to the given level of education, and multiply the result by 100. This indicator has dimension values of sex, locality and grade.';
DELETE FROM `translations` WHERE `id` = 557 And `en` = 'Divide the number of new entrants in grade 1, irrespective of age, by the population of official school-entrance age, and multiply the result by 100. This indicator has dimension values of sex, locality and grade.';
DELETE FROM `translations` WHERE `id` = 559 And `en` = 'Divide the number of repeaters in a given grade in school year t+1 by the number of pupils from the same cohort enrolled in the same grade in the previous school year t . This indicator has dimension values of sex, locality, sector and grade.';
DELETE FROM `translations` WHERE `id` = 561 And `en` = 'Divide the number of primary graduates, irrespective of age, by the population of theoretical primary graduation, and multiply the result by 100. This indicator has dimension values of sex, locality and sector.';
DELETE FROM `translations` WHERE `id` = 563 And `en` = 'Divide the total number of pupils belonging to a school-cohort who reached each successive grade of the specified level of education by the number of pupils in the school-cohort i.e. those originally enrolled in the first grade of primary education, and multiply the result by 100. The survival rate is calculated on the basis of the reconstructed cohort method, which uses data on enrolment and repeaters for two consecutive years. This indicator has dimension values of sex, locality and sector.';
DELETE FROM `translations` WHERE `id` = 565 And `en` = 'Divide the number of new entrants in the first grade of the specified higher cycle or level of education by the number of pupils who were enrolled in the final grade of the preceding cycle or level of education in the previous school year, and multiply by 100. This indicator has dimension values of sex, locality, sector and grade.';
DELETE FROM `translations` WHERE `id` = 567 And `en` = 'Divide the total number of pupils enrolled at the specified level of education by the number of teachers at the same level. This indicator has dimension values of locality, sector and level of education.';
DELETE FROM `translations` WHERE `id` = 571 And `en` = 'Divide the number of teachers of the specified level of education who have received the minimum required teacher training by the total number of teachers at the same level of education, and multiply the result by 100. This indicator has dimension values of locality, sector and level of education.';
DELETE FROM `translations` WHERE `id` = 573 And `en` = 'Divide the number of female students by the number of male students. This indicator has dimension values of sex, locality, sector and level of education.';
DELETE FROM `translations` WHERE `id` = 575 And `en` = 'Divide the number of female tertiary students enrolled in a specified ISCED level by the total number of students (male plus female) in that level in a given academic-year, and multiply the result by 100. This indicator has dimension values of locality, sector and level of education.';
DELETE FROM `translations` WHERE `id` = 577 And `en` = 'Divide the total number of female teachers at a given level of education by the total number of teachers (male and female) at the same level in a given school year, and multiply by 100. This indicator has dimension values of locality, sector and level of education.';
DELETE FROM `translations` WHERE `id` = 579 And `en` = 'Divide the number of pupils (or students) enrolled in private educational institutions in a given level of education by total enrolment (public and private) at the same level of education, and multiply the result by 100. This indicator has dimension values of sex, locality and level of education.';
DELETE FROM `translations` WHERE `id` = 581 And `en` = 'Divide the total number of pupils enrolled at the specified level of education by the number of textbooks at the same level. This indicator has dimension values of locality, and sector.';
DELETE FROM `translations` WHERE `id` = 583 And `en` = 'Number of water sources by type. This indicator has dimension values of locality, sector and condition.';
DELETE FROM `translations` WHERE `id` = 585 And `en` = 'Number of sanitation facilities by type. This indicator has dimension values of locality, sector and condition.';
DELETE FROM `translations` WHERE `id` = 594 And `en` = 'Process';
DELETE FROM `translations` WHERE `id` = 595 And `en` = 'Abort All';
DELETE FROM `translations` WHERE `id` = 596 And `en` = 'Clear All';
DELETE FROM `translations` WHERE `id` = 597 And `en` = 'Started By';
DELETE FROM `translations` WHERE `id` = 598 And `en` = 'Started Date';
DELETE FROM `translations` WHERE `id` = 599 And `en` = 'Finished Date';
DELETE FROM `translations` WHERE `id` = 600 And `en` = 'Log';
DELETE FROM `translations` WHERE `id` = 603 And `en` = 'Aborted';
DELETE FROM `translations` WHERE `id` = 605 And `en` = 'Export To';
DELETE FROM `translations` WHERE `id` = 607 And `en` = 'No Data';
DELETE FROM `translations` WHERE `id` = 608 And `en` = 'GNP';
DELETE FROM `translations` WHERE `id` = 610 And `en` = 'No Available Finance Records';
DELETE FROM `translations` WHERE `id` = 614 And `en` = 'Security Users, Roles, and Functions';
DELETE FROM `translations` WHERE `id` = 615 And `en` = 'System Configuration Values';
DELETE FROM `translations` WHERE `id` = 618 And `en` = 'Run Reports';
DELETE FROM `translations` WHERE `id` = 619 And `en` = 'Select All';
DELETE FROM `translations` WHERE `id` = 620 And `en` = 'De-Select All';
DELETE FROM `translations` WHERE `id` = 621 And `en` = 'Generate';
DELETE FROM `translations` WHERE `id` = 622 And `en` = 'Generated Files';
DELETE FROM `translations` WHERE `id` = 625 And `en` = 'Below are the list of available backup dates, please choose a restore point.';
DELETE FROM `translations` WHERE `id` = 626 And `en` = 'Files';
DELETE FROM `translations` WHERE `id` = 627 And `en` = 'Format not support.';
DELETE FROM `translations` WHERE `id` = 628 And `en` = 'Image filesize too large.';
DELETE FROM `translations` WHERE `id` = 629 And `en` = 'Resolution too large.';
DELETE FROM `translations` WHERE `id` = 630 And `en` = 'File uploaded with success.';
DELETE FROM `translations` WHERE `id` = 631 And `en` = 'Image exceeds system max filesize.';
DELETE FROM `translations` WHERE `id` = 632 And `en` = 'Image exceeds max file size in the HTML form.';
DELETE FROM `translations` WHERE `id` = 633 And `en` = 'Image was only partially uploaded.';
DELETE FROM `translations` WHERE `id` = 634 And `en` = 'No image was uploaded.';
DELETE FROM `translations` WHERE `id` = 635 And `en` = 'Missing a temporary folder.';
DELETE FROM `translations` WHERE `id` = 636 And `en` = 'Failed to write file to disk.';
DELETE FROM `translations` WHERE `id` = 637 And `en` = 'A PHP extension stopped the file upload.';
DELETE FROM `translations` WHERE `id` = 638 And `en` = 'Max Resolution:';
DELETE FROM `translations` WHERE `id` = 639 And `en` = 'Max File Size:';
DELETE FROM `translations` WHERE `id` = 640 And `en` = 'Format Supported:';
DELETE FROM `translations` WHERE `id` = 641 And `en` = 'Profile Image';
DELETE FROM `translations` WHERE `id` = 642 And `en` = 'Please enter the code for the Area.';
DELETE FROM `translations` WHERE `id` = 643 And `en` = 'There are duplicate area code.';
DELETE FROM `translations` WHERE `id` = 644 And `en` = 'Please enter the name for the Area.';
DELETE FROM `translations` WHERE `id` = 645 And `en` = 'Please supply a valid image.';
DELETE FROM `translations` WHERE `id` = 646 And `en` = 'Please enter a name for the Field of Study.';
DELETE FROM `translations` WHERE `id` = 647 And `en` = 'This Field of Study already exists in the system.';
DELETE FROM `translations` WHERE `id` = 648 And `en` = 'Please select the programme orientation.';
DELETE FROM `translations` WHERE `id` = 649 And `en` = 'Please enter a name for the Subject.';
DELETE FROM `translations` WHERE `id` = 650 And `en` = 'This subject already exists in the system.';
DELETE FROM `translations` WHERE `id` = 651 And `en` = 'You have entered an invalid username or password.';
DELETE FROM `translations` WHERE `id` = 652 And `en` = 'Please enter a duration.';
DELETE FROM `translations` WHERE `id` = 653 And `en` = 'Add Programme';
DELETE FROM `translations` WHERE `id` = 655 And `en` = 'Deleting attachment...';
DELETE FROM `translations` WHERE `id` = 656 And `en` = 'Delete Attachment';
DELETE FROM `translations` WHERE `id` = 657 And `en` = 'Do you wish to delete this record?';
DELETE FROM `translations` WHERE `id` = 658 And `en` = 'Updating attachment...';
DELETE FROM `translations` WHERE `id` = 659 And `en` = 'Bank Branch is required!';
DELETE FROM `translations` WHERE `id` = 662 And `en` = 'Unsaved Data';
DELETE FROM `translations` WHERE `id` = 664 And `en` = 'Are you sure you want to leave?';
DELETE FROM `translations` WHERE `id` = 666 And `en` = 'Category is required!';
DELETE FROM `translations` WHERE `id` = 667 And `en` = 'Certificate is required!';
DELETE FROM `translations` WHERE `id` = 669 And `en` = 'Please select a country before adding new records.';
DELETE FROM `translations` WHERE `id` = 670 And `en` = 'Error has occurred.';
DELETE FROM `translations` WHERE `id` = 671 And `en` = 'Age cannot be empty.';
DELETE FROM `translations` WHERE `id` = 672 And `en` = 'Age must be more then 0.';
DELETE FROM `translations` WHERE `id` = 675 And `en` = 'Dialog';
DELETE FROM `translations` WHERE `id` = 676 And `en` = 'Required Field';
DELETE FROM `translations` WHERE `id` = 677 And `en` = 'Retrieving...';
DELETE FROM `translations` WHERE `id` = 678 And `en` = 'Adding row...';
DELETE FROM `translations` WHERE `id` = 679 And `en` = 'Adding option...';
DELETE FROM `translations` WHERE `id` = 681 And `en` = 'Loading list...';
DELETE FROM `translations` WHERE `id` = 682 And `en` = 'File is required!';
DELETE FROM `translations` WHERE `id` = 683 And `en` = 'Status is required!';
DELETE FROM `translations` WHERE `id` = 684 And `en` = 'Move Up';
DELETE FROM `translations` WHERE `id` = 685 And `en` = 'Move Down';
DELETE FROM `translations` WHERE `id` = 686 And `en` = 'Toggle this field active/inactive';
DELETE FROM `translations` WHERE `id` = 687 And `en` = 'Delete Confirmation';
DELETE FROM `translations` WHERE `id` = 688 And `en` = 'Click to dismiss';
DELETE FROM `translations` WHERE `id` = 689 And `en` = 'Unable to add Areas.<br/>Please create Area Level before adding Areas.';
DELETE FROM `translations` WHERE `id` = 690 And `en` = 'Saving please wait...';
DELETE FROM `translations` WHERE `id` = 691 And `en` = 'Loading Areas';
DELETE FROM `translations` WHERE `id` = 694 And `en` = 'Adding Field...';
DELETE FROM `translations` WHERE `id` = 695 And `en` = 'Please select a valid Start Date';
DELETE FROM `translations` WHERE `id` = 696 And `en` = 'Please select a valid End Date';
DELETE FROM `translations` WHERE `id` = 697 And `en` = 'Please add a programme to this institution site.';
DELETE FROM `translations` WHERE `id` = 698 And `en` = 'Missing Coordinates Report';
DELETE FROM `translations` WHERE `id` = 699 And `en` = 'List of Institutions with latitude and/or longitude values of 0 or null';
DELETE FROM `translations` WHERE `id` = 710 And `en` = 'Area Name';
DELETE FROM `translations` WHERE `id` = 711 And `en` = 'Education Programme Name';
DELETE FROM `translations` WHERE `id` = 713 And `en` = 'Bank Account Number';
DELETE FROM `translations` WHERE `id` = 714 And `en` = 'Bank Account Active';
DELETE FROM `translations` WHERE `id` = 716 And `en` = 'Academic Year';
DELETE FROM `translations` WHERE `id` = 717 And `en` = 'Education Grade Name';
DELETE FROM `translations` WHERE `id` = 719 And `en` = 'Education Subject Name';
DELETE FROM `translations` WHERE `id` = 720 And `en` = 'No Of Textbooks';
DELETE FROM `translations` WHERE `id` = 721 And `en` = 'Grid X Category';
DELETE FROM `translations` WHERE `id` = 722 And `en` = 'Grid Y Category';
DELETE FROM `translations` WHERE `id` = 724 And `en` = 'Material';
DELETE FROM `translations` WHERE `id` = 726 And `en` = 'Resource';
DELETE FROM `translations` WHERE `id` = 728 And `en` = 'Teacher Name';
DELETE FROM `translations` WHERE `id` = 729 And `en` = 'Staff Name';
DELETE FROM `translations` WHERE `id` = 732 And `en` = 'Institution Provider';
DELETE FROM `translations` WHERE `id` = 733 And `en` = 'Institution Status (حالة المدرسة)';
DELETE FROM `translations` WHERE `id` = 740 And `en` = 'Student Category';
DELETE FROM `translations` WHERE `id` = 742 And `en` = 'Yearbook';
DELETE FROM `translations` WHERE `id` = 743 And `en` = 'Organization Name';
DELETE FROM `translations` WHERE `id` = 744 And `en` = 'Publication Date';
DELETE FROM `translations` WHERE `id` = 748 And `en` = 'Page Orientation';
DELETE FROM `translations` WHERE `id` = 749 And `en` = 'Yearbook Logo';
DELETE FROM `translations` WHERE `id` = 750 And `en` = 'Maximum Student Age';
DELETE FROM `translations` WHERE `id` = 751 And `en` = 'Minimum Student Age';
DELETE FROM `translations` WHERE `id` = 752 And `en` = 'Maximum Student Number';
DELETE FROM `translations` WHERE `id` = 753 And `en` = 'Minimum Student Number';
DELETE FROM `translations` WHERE `id` = 756 And `en` = 'Previous Year';
DELETE FROM `translations` WHERE `id` = 757 And `en` = 'Previous Year Male';
DELETE FROM `translations` WHERE `id` = 758 And `en` = 'Previous Year Female';
DELETE FROM `translations` WHERE `id` = 759 And `en` = 'Data Discrepancy Reports';
DELETE FROM `translations` WHERE `id` = 762 And `en` = 'Quality - Rubrics';
DELETE FROM `translations` WHERE `id` = 770 And `en` = 'View Rubric';
DELETE FROM `translations` WHERE `id` = 772 And `en` = 'Quality';
DELETE FROM `translations` WHERE `id` = 773 And `en` = 'Rubric Details';
DELETE FROM `translations` WHERE `id` = 774 And `en` = 'Quality - Rubric Details';
DELETE FROM `translations` WHERE `id` = 775 And `en` = 'Quality - Edit Rubric Details';
DELETE FROM `translations` WHERE `id` = 776 And `en` = 'Add Heading';
DELETE FROM `translations` WHERE `id` = 777 And `en` = 'Add Criteria Row';
DELETE FROM `translations` WHERE `id` = 778 And `en` = 'Add Level Column';
DELETE FROM `translations` WHERE `id` = 779 And `en` = 'Edit Rubric Details';
DELETE FROM `translations` WHERE `id` = 782 And `en` = 'Quality - Rubric';
DELETE FROM `translations` WHERE `id` = 783 And `en` = 'Supervisor';
DELETE FROM `translations` WHERE `id` = 784 And `en` = 'Quality - Rubric Detail';
DELETE FROM `translations` WHERE `id` = 787 And `en` = 'Quality - Visit';
DELETE FROM `translations` WHERE `id` = 793 And `en` = 'Quality - Add Rubric';
DELETE FROM `translations` WHERE `id` = 794 And `en` = 'Add Rubric';
DELETE FROM `translations` WHERE `id` = 795 And `en` = 'Quality - Rubric Infomations';
DELETE FROM `translations` WHERE `id` = 796 And `en` = 'Rubric Infomations';
DELETE FROM `translations` WHERE `id` = 797 And `en` = 'Quality - Edit Rubric';
DELETE FROM `translations` WHERE `id` = 798 And `en` = 'Edit Rubric';
DELETE FROM `translations` WHERE `id` = 799 And `en` = 'Quality - Setup Rubric Criteria';
DELETE FROM `translations` WHERE `id` = 800 And `en` = 'Setup Rubric Criteria';
DELETE FROM `translations` WHERE `id` = 801 And `en` = 'Quality - Add Rubric Criteria';
DELETE FROM `translations` WHERE `id` = 802 And `en` = 'Add Rubric Criteria';
DELETE FROM `translations` WHERE `id` = 803 And `en` = 'Quality - Edit Rubric Criteria';
DELETE FROM `translations` WHERE `id` = 804 And `en` = 'Edit Rubric Criteria';
DELETE FROM `translations` WHERE `id` = 805 And `en` = 'Quality - Rubric Criteria';
DELETE FROM `translations` WHERE `id` = 806 And `en` = 'Rubric Criteria';
DELETE FROM `translations` WHERE `id` = 807 And `en` = 'Quality - Status';
DELETE FROM `translations` WHERE `id` = 808 And `en` = 'Quality - Add Status';
DELETE FROM `translations` WHERE `id` = 809 And `en` = 'Quality - Edit Status';
DELETE FROM `translations` WHERE `id` = 810 And `en` = 'Quality - Add Rubrics';
DELETE FROM `translations` WHERE `id` = 811 And `en` = 'Quality - Edit Rubrics';
DELETE FROM `translations` WHERE `id` = 812 And `en` = 'Quality - Add Visit';
DELETE FROM `translations` WHERE `id` = 813 And `en` = 'Quality - Edit Visit';
DELETE FROM `translations` WHERE `id` = 815 And `en` = 'Identity Details';
DELETE FROM `translations` WHERE `id` = 816 And `en` = 'Edit Identity Details';
DELETE FROM `translations` WHERE `id` = 818 And `en` = 'Issued';
DELETE FROM `translations` WHERE `id` = 819 And `en` = 'Expiry';
DELETE FROM `translations` WHERE `id` = 822 And `en` = 'Issue Location';
DELETE FROM `translations` WHERE `id` = 832 And `en` = 'Goal / Objectives';
DELETE FROM `translations` WHERE `id` = 833 And `en` = 'Category / Field of Study';
DELETE FROM `translations` WHERE `id` = 834 And `en` = 'Target Population';
DELETE FROM `translations` WHERE `id` = 835 And `en` = 'Add Target Population';
DELETE FROM `translations` WHERE `id` = 841 And `en` = 'Prerequisite';
DELETE FROM `translations` WHERE `id` = 842 And `en` = 'Add Prerequisite';
DELETE FROM `translations` WHERE `id` = 843 And `en` = 'Pass Result';
DELETE FROM `translations` WHERE `id` = 845 And `en` = 'Courses Details';
DELETE FROM `translations` WHERE `id` = 846 And `en` = 'Sessions Details';
DELETE FROM `translations` WHERE `id` = 847 And `en` = 'Inactivate';
DELETE FROM `translations` WHERE `id` = 848 And `en` = 'Activate';
DELETE FROM `translations` WHERE `id` = 852 And `en` = 'Results Details';
DELETE FROM `translations` WHERE `id` = 854 And `en` = 'Self-Study';
DELETE FROM `translations` WHERE `id` = 855 And `en` = 'Credit';
DELETE FROM `translations` WHERE `id` = 857 And `en` = 'Training Needs Details';
DELETE FROM `translations` WHERE `id` = 858 And `en` = 'Priority';
DELETE FROM `translations` WHERE `id` = 860 And `en` = 'Training Results Details';
DELETE FROM `translations` WHERE `id` = 861 And `en` = 'Training Self Study';
DELETE FROM `translations` WHERE `id` = 862 And `en` = 'Training Self Study Details';
DELETE FROM `translations` WHERE `id` = 864 And `en` = 'Credits';
DELETE FROM `translations` WHERE `id` = 866 And `en` = 'Your data has been saved successfully.';
DELETE FROM `translations` WHERE `id` = 867 And `en` = 'Please select a Type';
DELETE FROM `translations` WHERE `id` = 868 And `en` = 'Please enter a valid Message';
DELETE FROM `translations` WHERE `id` = 869 And `en` = 'Please enter a valid Number';
DELETE FROM `translations` WHERE `id` = 870 And `en` = 'Please enter a valid Issue Location';
DELETE FROM `translations` WHERE `id` = 871 And `en` = 'Expiry Date must be greater than Issue Date';
DELETE FROM `translations` WHERE `id` = 873 And `en` = 'Nationality Details';
DELETE FROM `translations` WHERE `id` = 874 And `en` = 'Edit Nationality Details';
DELETE FROM `translations` WHERE `id` = 875 And `en` = 'Please select a Country';
DELETE FROM `translations` WHERE `id` = 878 And `en` = 'Messages';
DELETE FROM `translations` WHERE `id` = 879 And `en` = 'Responses';
DELETE FROM `translations` WHERE `id` = 883 And `en` = 'Add Message';
DELETE FROM `translations` WHERE `id` = 884 And `en` = 'Message Details';
DELETE FROM `translations` WHERE `id` = 885 And `en` = 'Edit Message Details';
DELETE FROM `translations` WHERE `id` = 886 And `en` = 'All logs have been deleted successfully.';
DELETE FROM `translations` WHERE `id` = 887 And `en` = 'All responses have been deleted successfully.';
DELETE FROM `translations` WHERE `id` = 889 And `en` = 'Sent';
DELETE FROM `translations` WHERE `id` = 890 And `en` = 'Received';
DELETE FROM `translations` WHERE `id` = 891 And `en` = 'Warning';
DELETE FROM `translations` WHERE `id` = 892 And `en` = 'Continue';
DELETE FROM `translations` WHERE `id` = 893 And `en` = 'Note: Please clear the Responses page as existing responses may no longer match the updated Messages.';
DELETE FROM `translations` WHERE `id` = 894 And `en` = 'Do you wish to clear all records?';
DELETE FROM `translations` WHERE `id` = 895 And `en` = 'Date/Time';
DELETE FROM `translations` WHERE `id` = 896 And `en` = 'Confirmation';
DELETE FROM `translations` WHERE `id` = 898 And `en` = 'Do you wish to inactivate this record?';
DELETE FROM `translations` WHERE `id` = 899 And `en` = 'Do you wish to activate this record?';
DELETE FROM `translations` WHERE `id` = 902 And `en` = 'Mode of Deliveries';
DELETE FROM `translations` WHERE `id` = 903 And `en` = 'Priorities';
DELETE FROM `translations` WHERE `id` = 905 And `en` = 'Requirements';
DELETE FROM `translations` WHERE `id` = 907 And `en` = 'Edit Field Options';
DELETE FROM `translations` WHERE `id` = 909 And `en` = 'Identification No, First Name or Last Name';
DELETE FROM `translations` WHERE `id` = 910 And `en` = 'Pass';
DELETE FROM `translations` WHERE `id` = 913 And `en` = 'Please add an area level to this area';
DELETE FROM `translations` WHERE `id` = 914 And `en` = 'There are no assessments';
DELETE FROM `translations` WHERE `id` = 918 And `en` = 'Build';
DELETE FROM `translations` WHERE `id` = 924 And `en` = 'Quality Assurance Report';
DELETE FROM `translations` WHERE `id` = 926 And `en` = 'Time Periods';
DELETE FROM `translations` WHERE `id` = 927 And `en` = 'Advance Search';
DELETE FROM `translations` WHERE `id` = 928 And `en` = 'OpenEMIS ID, First Name or Last Name';
DELETE FROM `translations` WHERE `id` = 931 And `en` = 'Date of Death';
DELETE FROM `translations` WHERE `id` = 932 And `en` = 'No File Chosen';
DELETE FROM `translations` WHERE `id` = 933 And `en` = 'Max Resolution';
DELETE FROM `translations` WHERE `id` = 934 And `en` = 'Your request has been timed out. Please try again.';
DELETE FROM `translations` WHERE `id` = 935 And `en` = 'OVERWRITE ALL';
DELETE FROM `translations` WHERE `id` = 937 And `en` = 'Choose File';
DELETE FROM `translations` WHERE `id` = 938 And `en` = 'File have not been updated successfully.';
DELETE FROM `translations` WHERE `id` = 939 And `en` = 'Completion Rate / Gross Primary Graduation Ratio';
DELETE FROM `translations` WHERE `id` = 940 And `en` = 'Divide the number of primary graduates, irrespective of age, by the population of theoretical primary graduation, and multiply the result by 100.';
DELETE FROM `translations` WHERE `id` = 941 And `en` = 'Percentage of Trained Teachers';
DELETE FROM `translations` WHERE `id` = 943 And `en` = 'Percent of Female Students';
DELETE FROM `translations` WHERE `id` = 944 And `en` = 'Percent of Female Teachers';
DELETE FROM `translations` WHERE `id` = 945 And `en` = 'Percentage of Private Enrolment';
DELETE FROM `translations` WHERE `id` = 946 And `en` = 'Percentage of schools with improved drinking water sources';
DELETE FROM `translations` WHERE `id` = 947 And `en` = 'Percentage of schools with adequate sanitation facilities';
DELETE FROM `translations` WHERE `id` = 949 And `en` = 'Divide public current expenditure devoted to each level of education by the total public current expenditure on education, and multiply the result by 100. Divide public current expenditure on education in a given financial year by the total public expenditure on education for the same financial year and multiply the result by 100.';
DELETE FROM `translations` WHERE `id` = 964 And `en` = 'Report on conditions, number, kinds of water source of Institutions';
DELETE FROM `translations` WHERE `id` = 965 And `en` = 'Student List';
DELETE FROM `translations` WHERE `id` = 969 And `en` = 'List of institution\'s additional info';
DELETE FROM `translations` WHERE `id` = 971 And `en` = 'Class List';
DELETE FROM `translations` WHERE `id` = 972 And `en` = 'Report on classes';
DELETE FROM `translations` WHERE `id` = 981 And `en` = 'Institution List';
DELETE FROM `translations` WHERE `id` = 982 And `en` = 'Report on institutions';
DELETE FROM `translations` WHERE `id` = 990 And `en` = 'Wizard';
DELETE FROM `translations` WHERE `id` = 991 And `en` = 'Skip';
DELETE FROM `translations` WHERE `id` = 992 And `en` = 'have been deleted successfully.';
DELETE FROM `translations` WHERE `id` = 993 And `en` = 'Edit Attachments';
DELETE FROM `translations` WHERE `id` = 995 And `en` = 'Partners';
DELETE FROM `translations` WHERE `id` = 996 And `en` = 'Verifications';
DELETE FROM `translations` WHERE `id` = 1001 And `en` = 'Summary of sanitation, gender and conditions from census';
DELETE FROM `translations` WHERE `id` = 1005 And `en` = 'Add Provider';
DELETE FROM `translations` WHERE `id` = 1007 And `en` = 'Course Prerequisite';
DELETE FROM `translations` WHERE `id` = 1008 And `en` = 'Add Course Prerequisite';
DELETE FROM `translations` WHERE `id` = 1009 And `en` = 'There are no assessments.';
DELETE FROM `translations` WHERE `id` = 1014 And `en` = 'Kindergarten';
DELETE FROM `translations` WHERE `id` = 1015 And `en` = 'Number of Shifts';
DELETE FROM `translations` WHERE `id` = 1017 And `en` = 'Student Prefix';
DELETE FROM `translations` WHERE `id` = 1018 And `en` = 'Teacher Prefix';
DELETE FROM `translations` WHERE `id` = 1019 And `en` = 'Staff Prefix';
DELETE FROM `translations` WHERE `id` = 1021 And `en` = 'Institution Telephone';
DELETE FROM `translations` WHERE `id` = 1022 And `en` = 'Institution Fax';
DELETE FROM `translations` WHERE `id` = 1023 And `en` = 'Institution Postal Code';
DELETE FROM `translations` WHERE `id` = 1027 And `en` = 'Student Telephone';
DELETE FROM `translations` WHERE `id` = 1028 And `en` = 'Student Postal Code';
DELETE FROM `translations` WHERE `id` = 1029 And `en` = 'Teacher Telephone';
DELETE FROM `translations` WHERE `id` = 1030 And `en` = 'Teacher Postal Code';
DELETE FROM `translations` WHERE `id` = 1031 And `en` = 'Staff Telephone';
DELETE FROM `translations` WHERE `id` = 1032 And `en` = 'Staff Postal Code';
DELETE FROM `translations` WHERE `id` = 1034 And `en` = 'LDAP Server';
DELETE FROM `translations` WHERE `id` = 1036 And `en` = 'Base DN';
DELETE FROM `translations` WHERE `id` = 1038 And `en` = 'Where\'s My School Config';
DELETE FROM `translations` WHERE `id` = 1039 And `en` = 'Where is my School URL';
DELETE FROM `translations` WHERE `id` = 1040 And `en` = 'Starting Longitude';
DELETE FROM `translations` WHERE `id` = 1041 And `en` = 'Starting Latitude';
DELETE FROM `translations` WHERE `id` = 1042 And `en` = 'Starting Range';
DELETE FROM `translations` WHERE `id` = 1043 And `en` = 'SMS Provider URL';
DELETE FROM `translations` WHERE `id` = 1044 And `en` = 'SMS Number';
DELETE FROM `translations` WHERE `id` = 1045 And `en` = 'SMS Content';
DELETE FROM `translations` WHERE `id` = 1046 And `en` = 'SMS Retry Times';
DELETE FROM `translations` WHERE `id` = 1047 And `en` = 'SMS Retry Delay';
DELETE FROM `translations` WHERE `id` = 1048 And `en` = 'Credit Hour';
DELETE FROM `translations` WHERE `id` = 1050 And `en` = 'Default Country';
DELETE FROM `translations` WHERE `id` = 1051 And `en` = 'System Defined Roles';
DELETE FROM `translations` WHERE `id` = 1052 And `en` = 'User Defined Roles';
DELETE FROM `translations` WHERE `id` = 1057 And `en` = 'Report on all completed courses by staff, date, location and results';
DELETE FROM `translations` WHERE `id` = 1065 And `en` = 'Report of trainers by name, course and date';
DELETE FROM `translations` WHERE `id` = 1082 And `en` = 'Attendance Report';
DELETE FROM `translations` WHERE `id` = 1084 And `en` = 'Assessment Report';
DELETE FROM `translations` WHERE `id` = 1099 And `en` = 'Student Assessment Report';
DELETE FROM `translations` WHERE `id` = 1106 And `en` = 'Standard';
DELETE FROM `translations` WHERE `id` = 1108 And `en` = 'New Surveys';
DELETE FROM `translations` WHERE `id` = 1109 And `en` = 'Completed Surveys';
DELETE FROM `translations` WHERE `id` = 1111 And `en` = 'Sync';
DELETE FROM `translations` WHERE `id` = 1127 And `en` = 'Family';
DELETE FROM `translations` WHERE `id` = 1134 And `en` = 'REPORT';
DELETE FROM `translations` WHERE `id` = 1143 And `en` = 'Staff Label';
DELETE FROM `translations` WHERE `id` = 1144 And `en` = 'Check Box';
DELETE FROM `translations` WHERE `id` = 1146 And `en` = 'Hours per Week';
DELETE FROM `translations` WHERE `id` = 1147 And `en` = 'Commencement Date';
DELETE FROM `translations` WHERE `id` = 1148 And `en` = 'Document No.';
DELETE FROM `translations` WHERE `id` = 1149 And `en` = 'Qualification Title';
DELETE FROM `translations` WHERE `id` = 1151 And `en` = 'No position found';
DELETE FROM `translations` WHERE `id` = 1152 And `en` = 'Total days absent';
DELETE FROM `translations` WHERE `id` = 1153 And `en` = 'Total days attended';
DELETE FROM `translations` WHERE `id` = 1155 And `en` = 'Last day ';
DELETE FROM `translations` WHERE `id` = 1156 And `en` = 'First day';
DELETE FROM `translations` WHERE `id` = 1157 And `en` = 'Total Days';
DELETE FROM `translations` WHERE `id` = 1158 And `en` = 'List of Behaviour';
DELETE FROM `translations` WHERE `id` = 1159 And `en` = 'No record available';
DELETE FROM `translations` WHERE `id` = 1160 And `en` = 'Net';
DELETE FROM `translations` WHERE `id` = 1163 And `en` = 'Gross';
DELETE FROM `translations` WHERE `id` = 1167 And `en` = 'Ended';
DELETE FROM `translations` WHERE `id` = 1168 And `en` = 'Commenced';
DELETE FROM `translations` WHERE `id` = 1171 And `en` = 'Training Achievements';
DELETE FROM `translations` WHERE `id` = 1172 And `en` = 'Health - History';
DELETE FROM `translations` WHERE `id` = 1173 And `en` = 'Health - Family';
DELETE FROM `translations` WHERE `id` = 1174 And `en` = 'Health - Immunizations';
DELETE FROM `translations` WHERE `id` = 1175 And `en` = 'Health - Medications';
DELETE FROM `translations` WHERE `id` = 1176 And `en` = 'Health - Medication';
DELETE FROM `translations` WHERE `id` = 1177 And `en` = 'Health - Allergies';
DELETE FROM `translations` WHERE `id` = 1178 And `en` = 'Health - Tests';
DELETE FROM `translations` WHERE `id` = 1179 And `en` = 'Health - Consultations';
DELETE FROM `translations` WHERE `id` = 1180 And `en` = 'File size';
DELETE FROM `translations` WHERE `id` = 1181 And `en` = 'Please enter a valid OpenEMIS ID';
DELETE FROM `translations` WHERE `id` = 1182 And `en` = 'Dashboards';
DELETE FROM `translations` WHERE `id` = 1187 And `en` = 'Geographical Level';
DELETE FROM `translations` WHERE `id` = 1196 And `en` = 'Owned';
DELETE FROM `translations` WHERE `id` = 1197 And `en` = 'Rented';
DELETE FROM `translations` WHERE `id` = 1198 And `en` = 'Both';
DELETE FROM `translations` WHERE `id` = 1199 And `en` = 'Permanent';
DELETE FROM `translations` WHERE `id` = 1201 And `en` = 'Urban';
DELETE FROM `translations` WHERE `id` = 1202 And `en` = 'Rural';
DELETE FROM `translations` WHERE `id` = 1203 And `en` = 'Administrative';
DELETE FROM `translations` WHERE `id` = 1204 And `en` = 'Technical';
DELETE FROM `translations` WHERE `id` = 1205 And `en` = 'Download CSV';
DELETE FROM `translations` WHERE `id` = 1206 And `en` = 'Weightings';
DELETE FROM `translations` WHERE `id` = 1208 And `en` = 'Add Header';
DELETE FROM `translations` WHERE `id` = 1211 And `en` = 'Edit Criteria';
DELETE FROM `translations` WHERE `id` = 1212 And `en` = 'Criteria Details';
DELETE FROM `translations` WHERE `id` = 1213 And `en` = 'Quality Assurance Reports';
DELETE FROM `translations` WHERE `id` = 1214 And `en` = 'Quality Assurance';
DELETE FROM `translations` WHERE `id` = 1215 And `en` = 'Report generated at the school, FD and national level for each aspect (category) and domain (technical or administrative) ';
DELETE FROM `translations` WHERE `id` = 1216 And `en` = 'QA Schools Report';
DELETE FROM `translations` WHERE `id` = 1217 And `en` = 'Report generated by type, category, result (pass or fail) and a table with the average, minimum and maximum values and also compare results from one year to the next ';
DELETE FROM `translations` WHERE `id` = 1218 And `en` = 'QA Results Report';
DELETE FROM `translations` WHERE `id` = 1219 And `en` = 'Report generation for those who hasn\'t completed the rubric';
DELETE FROM `translations` WHERE `id` = 1220 And `en` = 'QA Rubric Not Completed Report';
DELETE FROM `translations` WHERE `id` = 1221 And `en` = 'Maximum 150 words per comment';
DELETE FROM `translations` WHERE `id` = 1224 And `en` = 'Target Grades';
DELETE FROM `translations` WHERE `id` = 1225 And `en` = 'Security Role';
DELETE FROM `translations` WHERE `id` = 1226 And `en` = 'Setup Criteria Column';
DELETE FROM `translations` WHERE `id` = 1227 And `en` = 'Header';
DELETE FROM `translations` WHERE `id` = 1229 And `en` = 'Descriptors';
DELETE FROM `translations` WHERE `id` = 1230 And `en` = 'New row has been added at the bottom of the rubric table.';
DELETE FROM `translations` WHERE `id` = 1231 And `en` = 'Header / Sub-Header / Title';
DELETE FROM `translations` WHERE `id` = 1232 And `en` = 'Section Header';
DELETE FROM `translations` WHERE `id` = 1233 And `en` = 'Add Section Header';
DELETE FROM `translations` WHERE `id` = 1235 And `en` = 'Add Grade';
DELETE FROM `translations` WHERE `id` = 1236 And `en` = 'Add Visit';
DELETE FROM `translations` WHERE `id` = 1237 And `en` = 'Visit';
DELETE FROM `translations` WHERE `id` = 1238 And `en` = 'Edit Rubric Headers';
DELETE FROM `translations` WHERE `id` = 1239 And `en` = 'Edit Headers';
DELETE FROM `translations` WHERE `id` = 1241 And `en` = 'Create Rubric Table';
DELETE FROM `translations` WHERE `id` = 1242 And `en` = 'Reorder Criteria';
DELETE FROM `translations` WHERE `id` = 1244 And `en` = 'Edit Status';
DELETE FROM `translations` WHERE `id` = 1245 And `en` = 'Add Status';
DELETE FROM `translations` WHERE `id` = 1246 And `en` = 'Criteria Level Description';
DELETE FROM `translations` WHERE `id` = 1247 And `en` = 'View Details';
DELETE FROM `translations` WHERE `id` = 1248 And `en` = 'Weightage';
DELETE FROM `translations` WHERE `id` = 1249 And `en` = 'Selected Grade(s)';
DELETE FROM `translations` WHERE `id` = 1250 And `en` = 'Status Details';
DELETE FROM `translations` WHERE `id` = 1251 And `en` = 'Rubric Name';
DELETE FROM `translations` WHERE `id` = 1253 And `en` = 'Pass/Fail';
DELETE FROM `translations` WHERE `id` = 1255 And `en` = 'QA Report';
DELETE FROM `translations` WHERE `id` = 1256 And `en` = 'Visit Report';
DELETE FROM `translations` WHERE `id` = 1257 And `en` = 'Reports - Quality';
DELETE FROM `translations` WHERE `id` = 1260 And `en` = 'Fail';
DELETE FROM `translations` WHERE `id` = 1261 And `en` = 'Quality Type';
DELETE FROM `translations` WHERE `id` = 1262 And `en` = 'Visit Date';
DELETE FROM `translations` WHERE `id` = 1263 And `en` = 'Evaluator Name';
DELETE FROM `translations` WHERE `id` = 1264 And `en` = 'Total Classes';
DELETE FROM `translations` WHERE `id` = 1265 And `en` = 'Maximum';
DELETE FROM `translations` WHERE `id` = 1266 And `en` = 'Minimum';
DELETE FROM `translations` WHERE `id` = 1267 And `en` = 'Average';
DELETE FROM `translations` WHERE `id` = 1268 And `en` = 'Pass/ Fail';
DELETE FROM `translations` WHERE `id` = 1269 And `en` = 'Total Questions';
DELETE FROM `translations` WHERE `id` = 1270 And `en` = 'Total Answered';
DELETE FROM `translations` WHERE `id` = 1272 And `en` = 'Goal Objective';
DELETE FROM `translations` WHERE `id` = 1273 And `en` = 'Requirement';
DELETE FROM `translations` WHERE `id` = 1274 And `en` = 'Trainer Type';
DELETE FROM `translations` WHERE `id` = 1276 And `en` = 'Target Group';
DELETE FROM `translations` WHERE `id` = 1277 And `en` = 'Total Target Group';
DELETE FROM `translations` WHERE `id` = 1278 And `en` = 'Total Trained';
DELETE FROM `translations` WHERE `id` = 1279 And `en` = 'Target Group Percentage';
DELETE FROM `translations` WHERE `id` = 1280 And `en` = 'Last Updated By';
DELETE FROM `translations` WHERE `id` = 1281 And `en` = 'Behaviour - Staff';
DELETE FROM `translations` WHERE `id` = 1282 And `en` = 'Behaviour - Students';
DELETE FROM `translations` WHERE `id` = 1283 And `en` = '*File size should not be larger than 2MB.';
DELETE FROM `translations` WHERE `id` = 1284 And `en` = '*Maximum 5 files are permitted on single upload. Each file size should not be larger than 2MB.';
DELETE FROM `translations` WHERE `id` = 1286 And `en` = 'User';
DELETE FROM `translations` WHERE `id` = 1287 And `en` = 'Add Result Type';
DELETE FROM `translations` WHERE `id` = 1288 And `en` = 'Goal / objective';
DELETE FROM `translations` WHERE `id` = 1289 And `en` = 'Internal';
DELETE FROM `translations` WHERE `id` = 1290 And `en` = 'External';
DELETE FROM `translations` WHERE `id` = 1292 And `en` = 'Overall Result';
DELETE FROM `translations` WHERE `id` = 1294 And `en` = 'Edit Results Details';
DELETE FROM `translations` WHERE `id` = 1296 And `en` = 'Upload Results';
DELETE FROM `translations` WHERE `id` = 1297 And `en` = 'Passed';
DELETE FROM `translations` WHERE `id` = 1298 And `en` = 'Failed';
DELETE FROM `translations` WHERE `id` = 1299 And `en` = 'Upload File';
DELETE FROM `translations` WHERE `id` = 1300 And `en` = 'Upload';
DELETE FROM `translations` WHERE `id` = 1301 And `en` = 'Invalid File Format';
DELETE FROM `translations` WHERE `id` = 1302 And `en` = 'Columns/Data do not match.';
DELETE FROM `translations` WHERE `id` = 1303 And `en` = 'Error encountered, record(s) could not be updated';
DELETE FROM `translations` WHERE `id` = 1309 And `en` = '(1=Pass/0=Fail)';
DELETE FROM `translations` WHERE `id` = 1310 And `en` = 'Need Type';
DELETE FROM `translations` WHERE `id` = 1312 And `en` = 'Achievement Type';
DELETE FROM `translations` WHERE `id` = 1313 And `en` = 'Course Goal / Objectives';
DELETE FROM `translations` WHERE `id` = 1314 And `en` = 'Add Training Needs';
DELETE FROM `translations` WHERE `id` = 1317 And `en` = 'Add Achievements';
DELETE FROM `translations` WHERE `id` = 1318 And `en` = 'Achievements Details';
DELETE FROM `translations` WHERE `id` = 1319 And `en` = 'Edit Achievements';
DELETE FROM `translations` WHERE `id` = 1320 And `en` = 'TrainingSessionTrainee';
DELETE FROM `translations` WHERE `id` = 1321 And `en` = 'Approval';
DELETE FROM `translations` WHERE `id` = 1322 And `en` = 'Reject';
DELETE FROM `translations` WHERE `id` = 1323 And `en` = 'Experience';
DELETE FROM `translations` WHERE `id` = 1326 And `en` = 'Months';
DELETE FROM `translations` WHERE `id` = 1327 And `en` = 'Add Experience';
DELETE FROM `translations` WHERE `id` = 1328 And `en` = 'Add Specialisation';
DELETE FROM `translations` WHERE `id` = 1329 And `en` = 'Upload Trainee';
DELETE FROM `translations` WHERE `id` = 1332 And `en` = 'Edit Sessions Details';
DELETE FROM `translations` WHERE `id` = 1333 And `en` = 'Evaluation Tools';
DELETE FROM `translations` WHERE `id` = 1337 And `en` = 'List of translations';
DELETE FROM `translations` WHERE `id` = 1338 And `en` = 'Add Translation';
DELETE FROM `translations` WHERE `id` = 1339 And `en` = 'Edit Translation';
DELETE FROM `translations` WHERE `id` = 1340 And `en` = 'Translation Details';
DELETE FROM `translations` WHERE `id` = 1341 And `en` = 'Please ensure the english translation is keyed in.';
DELETE FROM `translations` WHERE `id` = 1342 And `en` = 'Download Trainees';
DELETE FROM `translations` WHERE `id` = 1343 And `en` = 'Download Trainee Results';
DELETE FROM `translations` WHERE `id` = 1344 And `en` = 'Duplicate Session';
DELETE FROM `translations` WHERE `id` = 1353 And `en` = 'Shared Reports';
DELETE FROM `translations` WHERE `id` = 1354 And `en` = 'My Reports';
DELETE FROM `translations` WHERE `id` = 1364 And `en` = 'Select Image';
DELETE FROM `translations` WHERE `id` = 1366 And `en` = 'Change';
DELETE FROM `translations` WHERE `id` = 1367 And `en` = 'There is no data to be displayed.';
DELETE FROM `translations` WHERE `id` = 1371 And `en` = 's';
DELETE FROM `translations` WHERE `id` = 1374 And `en` = 'Period';
DELETE FROM `translations` WHERE `id` = 1376 And `en` = 'Shift Name';
DELETE FROM `translations` WHERE `id` = 1380 And `en` = 'Edit Shift';
DELETE FROM `translations` WHERE `id` = 1381 And `en` = 'Add Shift';
DELETE FROM `translations` WHERE `id` = 1382 And `en` = 'Add Attachment';
DELETE FROM `translations` WHERE `id` = 1383 And `en` = 'Please enter a File name';
DELETE FROM `translations` WHERE `id` = 1384 And `en` = 'No file was uploaded';
DELETE FROM `translations` WHERE `id` = 1385 And `en` = 'The files has been uploaded';
DELETE FROM `translations` WHERE `id` = 1386 And `en` = 'Attachment Details';
DELETE FROM `translations` WHERE `id` = 1390 And `en` = 'Position Details';
DELETE FROM `translations` WHERE `id` = 1391 And `en` = 'Edit Position';
DELETE FROM `translations` WHERE `id` = 1392 And `en` = 'All Years';
DELETE FROM `translations` WHERE `id` = 1394 And `en` = 'Number:';
DELETE FROM `translations` WHERE `id` = 1400 And `en` = 'Unit';
DELETE FROM `translations` WHERE `id` = 1402 And `en` = 'Step 1';
DELETE FROM `translations` WHERE `id` = 1403 And `en` = 'Step 2';
DELETE FROM `translations` WHERE `id` = 1404 And `en` = 'Step 3';
DELETE FROM `translations` WHERE `id` = 1405 And `en` = 'Step 4';
DELETE FROM `translations` WHERE `id` = 1406 And `en` = 'Step 5';
DELETE FROM `translations` WHERE `id` = 1407 And `en` = 'Step 6';
DELETE FROM `translations` WHERE `id` = 1408 And `en` = 'Step 7';
DELETE FROM `translations` WHERE `id` = 1409 And `en` = 'Step 8';
DELETE FROM `translations` WHERE `id` = 1411 And `en` = 'Dimension';
DELETE FROM `translations` WHERE `id` = 1413 And `en` = 'Review';
DELETE FROM `translations` WHERE `id` = 1416 And `en` = 'Area ID';
DELETE FROM `translations` WHERE `id` = 1417 And `en` = 'Table';
DELETE FROM `translations` WHERE `id` = 1418 And `en` = 'Column';
DELETE FROM `translations` WHERE `id` = 1420 And `en` = 'Bar';
DELETE FROM `translations` WHERE `id` = 1422 And `en` = 'Line';
DELETE FROM `translations` WHERE `id` = 1425 And `en` = 'Visualization';
DELETE FROM `translations` WHERE `id` = 1427 And `en` = 'List of Classes';
DELETE FROM `translations` WHERE `id` = 1429 And `en` = 'Verify';
DELETE FROM `translations` WHERE `id` = 1430 And `en` = 'Data Entry';
DELETE FROM `translations` WHERE `id` = 1431 And `en` = 'Estimate';
DELETE FROM `translations` WHERE `id` = 1432 And `en` = 'Full Time Equivalent Teachers';
DELETE FROM `translations` WHERE `id` = 1435 And `en` = 'Multi Grade Classes';
DELETE FROM `translations` WHERE `id` = 1437 And `en` = 'There are no subjects configured in the system.';
DELETE FROM `translations` WHERE `id` = 1443 And `en` = 'Add existing Student';
DELETE FROM `translations` WHERE `id` = 1444 And `en` = 'Add existing Staff';
DELETE FROM `translations` WHERE `id` = 1452 And `en` = 'مرفع';
DELETE FROM `translations` WHERE `id` = 1453 And `en` = 'متكرر';
DELETE FROM `translations` WHERE `id` = 1470 And `en` = 'About';
DELETE FROM `translations` WHERE `id` = 1471 And `en` = 'Preferences';
DELETE FROM `translations` WHERE `id` = 1476 And `en` = 'Modified User';
DELETE FROM `translations` WHERE `id` = 1477 And `en` = 'Modified';
DELETE FROM `translations` WHERE `id` = 1478 And `en` = 'Created User';
DELETE FROM `translations` WHERE `id` = 1479 And `en` = 'Created';
DELETE FROM `translations` WHERE `id` = 1485 And `en` = '(Area (Education';
DELETE FROM `translations` WHERE `id` = 1490 And `en` = 'Requester';
DELETE FROM `translations` WHERE `id` = 1492 And `en` = 'Due Date';
DELETE FROM `translations` WHERE `id` = 1493 And `en` = 'Received Date';
DELETE FROM `translations` WHERE `id` = 1494 And `en` = 'Request Title';
DELETE FROM `translations` WHERE `id` = 1495 And `en` = 'Student Transfer';
DELETE FROM `translations` WHERE `id` = 1496 And `en` = 'Transfer of student';
DELETE FROM `translations` WHERE `id` = 1499 And `en` = 'National Number';
DELETE FROM `translations` WHERE `id` = 1500 And `en` = 'Expelled';
DELETE FROM `translations` WHERE `id` = 1503 And `en` = 'Dropout';
DELETE FROM `translations` WHERE `id` = 1513 And `en` = 'Current Academic Period';
DELETE FROM `translations` WHERE `id` = 1515 And `en` = 'Openemis No';
DELETE FROM `translations` WHERE `id` = 1522 And `en` = 'Mark';
DELETE FROM `translations` WHERE `id` = 1524 And `en` = 'Area (Education)';
DELETE FROM `translations` WHERE `id` = 1526 And `en` = 'Full-Time';
DELETE FROM `translations` WHERE `id` = 1527 And `en` = 'Part-Time';
DELETE FROM `translations` WHERE `id` = 1529 And `en` = 'No configured options';
DELETE FROM `translations` WHERE `id` = 1530 And `en` = 'Select Role';
DELETE FROM `translations` WHERE `id` = 1534 And `en` = 'Infrastructure Type';
DELETE FROM `translations` WHERE `id` = 1540 And `en` = 'Country -';
DELETE FROM `translations` WHERE `id` = 1543 And `en` = 'Number Of Students By Grade';
DELETE FROM `translations` WHERE `id` = 1555 And `en` = 'There is no programme set for this institution';
DELETE FROM `translations` WHERE `id` = 1563 And `en` = 'Special Need Comment';
DELETE FROM `translations` WHERE `id` = 1567 And `en` = 'Absent - Excused';
DELETE FROM `translations` WHERE `id` = 1568 And `en` = 'Absent - Unexcused';
DELETE FROM `translations` WHERE `id` = 1569 And `en` = 'Present';
DELETE FROM `translations` WHERE `id` = 1570 And `en` = 'No Classes';
DELETE FROM `translations` WHERE `id` = 1576 And `en` = 'FTE';
DELETE FROM `translations` WHERE `id` = 1577 And `en` = 'Excused';
DELETE FROM `translations` WHERE `id` = 1578 And `en` = 'Unexcused';
DELETE FROM `translations` WHERE `id` = 1593 And `en` = 'Leave Status';
DELETE FROM `translations` WHERE `id` = 1595 And `en` = 'Leaves';
DELETE FROM `translations` WHERE `id` = 1596 And `en` = 'Qualification Institution';
DELETE FROM `translations` WHERE `id` = 1598 And `en` = 'Qualification Level';
DELETE FROM `translations` WHERE `id` = 1601 And `en` = 'Institution Country';
DELETE FROM `translations` WHERE `id` = 1602 And `en` = 'Qualification Specialisation';
DELETE FROM `translations` WHERE `id` = 1614 And `en` = '- Country';
DELETE FROM `translations` WHERE `id` = 1617 And `en` = 'Submit';
DELETE FROM `translations` WHERE `id` = 1618 And `en` = 'Save As Draft';
DELETE FROM `translations` WHERE `id` = 1625 And `en` = 'No Students Found';
DELETE FROM `translations` WHERE `id` = 1631 And `en` = 'School Principal';
DELETE FROM `translations` WHERE `id` = 1632 And `en` = 'المنطقة الإدارية - Country';
DELETE FROM `translations` WHERE `id` = 1635 And `en` = 'Default Shift';
DELETE FROM `translations` WHERE `id` = 1636 And `en` = 'Default Shift 2014/2015';
DELETE FROM `translations` WHERE `id` = 1638 And `en` = 'Select Student';
DELETE FROM `translations` WHERE `id` = 1641 And `en` = 'Student Attendance';
DELETE FROM `translations` WHERE `id` = 1646 And `en` = 'Openemisno';
DELETE FROM `translations` WHERE `id` = 1648 And `en` = 'Security User';
DELETE FROM `translations` WHERE `id` = 1649 And `en` = 'Amount (JD)';
DELETE FROM `translations` WHERE `id` = 1663 And `en` = 'Student Absence Reason';
DELETE FROM `translations` WHERE `id` = 1665 And `en` = 'Copyright © 2015 OpenEMIS. All rights reserved.';
DELETE FROM `translations` WHERE `id` = 1675 And `en` = 'Row Number';
DELETE FROM `translations` WHERE `id` = 1676 And `en` = 'The record is not added due to errors encountered';
DELETE FROM `translations` WHERE `id` = 1678 And `en` = 'This School';
DELETE FROM `translations` WHERE `id` = 1679 And `en` = 'Other School';
DELETE FROM `translations` WHERE `id` = 1703 And `en` = 'Area (Administrative) - Country';
DELETE FROM `translations` WHERE `id` = 1704 And `en` = 'Area (Administrative)';
DELETE FROM `translations` WHERE `id` = 1705 And `en` = 'land';
DELETE FROM `translations` WHERE `id` = 1707 And `en` = 'Building Inst Year';
DELETE FROM `translations` WHERE `id` = 1708 And `en` = 'Building Future Expansion';
DELETE FROM `translations` WHERE `id` = 1710 And `en` = 'Yearly Rent Cost';
DELETE FROM `translations` WHERE `id` = 1711 And `en` = 'Building WCstatus';
DELETE FROM `translations` WHERE `id` = 1713 And `en` = 'Land Area';
DELETE FROM `translations` WHERE `id` = 1714 And `en` = 'Building Land Number';
DELETE FROM `translations` WHERE `id` = 1715 And `en` = 'Building Water Availability';
DELETE FROM `translations` WHERE `id` = 1716 And `en` = 'Building Deflation Type';
DELETE FROM `translations` WHERE `id` = 1717 And `en` = 'Building Model';
DELETE FROM `translations` WHERE `id` = 1718 And `en` = 'Building Electricity Availability';
DELETE FROM `translations` WHERE `id` = 1719 And `en` = 'Building seq';
DELETE FROM `translations` WHERE `id` = 1720 And `en` = 'Building Bed Number';
DELETE FROM `translations` WHERE `id` = 1725 And `en` = 'Staff Account';
DELETE FROM `translations` WHERE `id` = 1726 And `en` = 'Staff User';
DELETE FROM `translations` WHERE `id` = 1727 And `en` = '.Are you sure you want to delete this record';
DELETE FROM `translations` WHERE `id` = 1728 And `en` = 'Delete is not allowed as students still exists in class';
DELETE FROM `translations` WHERE `id` = 1729 And `en` = 'InstitutionSections';
DELETE FROM `translations` WHERE `id` = 1730 And `en` = 'Please review the information before proceeding with the operation';
DELETE FROM `translations` WHERE `id` = 1735 And `en` = 'OpenEmis ID';
DELETE FROM `translations` WHERE `id` = 1738 And `en` = 'Current Education Grade';
DELETE FROM `translations` WHERE `id` = 1740 And `en` = 'Students have been transferred';
DELETE FROM `translations` WHERE `id` = 1741 And `en` = 'Are you sure you want to delete this record.';
DELETE FROM `translations` WHERE `id` = 1746 And `en` = 'Student User';
DELETE FROM `translations` WHERE `id` = 1748 And `en` = 'Student Surveys';
DELETE FROM `translations` WHERE `id` = 1749 And `en` = 'student surveys';
DELETE FROM `translations` WHERE `id` = 1750 And `en` = 'students surveys';
DELETE FROM `translations` WHERE `id` = 1752 And `en` = 'Area (Administrative)';
DELETE FROM `translations` WHERE `id` = 1753 And `en` = 'No Other Student Available';
DELETE FROM `translations` WHERE `id` = 1756 And `en` = 'Sunday';
DELETE FROM `translations` WHERE `id` = 1757 And `en` = 'Monday';
DELETE FROM `translations` WHERE `id` = 1758 And `en` = 'Tuesday';
DELETE FROM `translations` WHERE `id` = 1759 And `en` = 'Wednesday';
DELETE FROM `translations` WHERE `id` = 1760 And `en` = 'Thursday';
DELETE FROM `translations` WHERE `id` = 1761 And `en` = 'Friday';
DELETE FROM `translations` WHERE `id` = 1762 And `en` = 'Saturday';
DELETE FROM `translations` WHERE `id` = 1764 And `en` = 'No Available Subjects';
DELETE FROM `translations` WHERE `id` = 1765 And `en` = 'Next grade in the Education Structure is not available in this Institution.';
DELETE FROM `translations` WHERE `id` = 1771 And `en` = 'Our Shifts';
DELETE FROM `translations` WHERE `id` = 1772 And `en` = 'External Shifts';
DELETE FROM `translations` WHERE `id` = 1775 And `en` = 'Add All Students';
DELETE FROM `translations` WHERE `id` = 1779 And `en` = 'Transfer Approvals';
DELETE FROM `translations` WHERE `id` = 1780 And `en` = 'Approve';
DELETE FROM `translations` WHERE `id` = 1781 And `en` = 'Application Status';
DELETE FROM `translations` WHERE `id` = 1783 And `en` = 'Student Dropout Reason';
DELETE FROM `translations` WHERE `id` = 1787 And `en` = '<Not In School>';
DELETE FROM `translations` WHERE `id` = 1788 And `en` = 'Absence is already added for this date and time.';
DELETE FROM `translations` WHERE `id` = 1789 And `en` = 'Academic Period needs to be set as current';
DELETE FROM `translations` WHERE `id` = 1790 And `en` = 'Add Assessment Item';
DELETE FROM `translations` WHERE `id` = 1794 And `en` = 'Add Teacher';
DELETE FROM `translations` WHERE `id` = 1795 And `en` = 'Add to Section';
DELETE FROM `translations` WHERE `id` = 1796 And `en` = 'An unexpected error has been encounted. Please contact the administrator for assistance.';
DELETE FROM `translations` WHERE `id` = 1798 And `en` = 'Assessment record has been saved to draft successfully.';
DELETE FROM `translations` WHERE `id` = 1799 And `en` = 'Assessment record has been submitted successfully.';
DELETE FROM `translations` WHERE `id` = 1800 And `en` = 'Both passwords do not match';
DELETE FROM `translations` WHERE `id` = 1802 And `en` = 'Class name and Home Room Teacher should not be empty';
DELETE FROM `translations` WHERE `id` = 1803 And `en` = 'Class name should not be empty';
DELETE FROM `translations` WHERE `id` = 1806 And `en` = 'Date Closed should not be earlier than Date Opened';
DELETE FROM `translations` WHERE `id` = 1807 And `en` = 'Date of Birth cannot be future date';
DELETE FROM `translations` WHERE `id` = 1809 And `en` = 'Date To should not be earlier than Date From';
DELETE FROM `translations` WHERE `id` = 1813 And `en` = 'Dropout request has been approved successfully.';
DELETE FROM `translations` WHERE `id` = 1814 And `en` = 'Dropout request has been rejected successfully.';
DELETE FROM `translations` WHERE `id` = 1815 And `en` = 'Dropout request hsa been submitted successfully.';
DELETE FROM `translations` WHERE `id` = 1816 And `en` = 'Duplicate Code Identified';
DELETE FROM `translations` WHERE `id` = 1817 And `en` = 'Duplicate OpenEMIS ID Identified';
DELETE FROM `translations` WHERE `id` = 1818 And `en` = 'Duplicate Unique Key on the same sheet';
DELETE FROM `translations` WHERE `id` = 1820 And `en` = 'End Date should not be earlier than Start Date';
DELETE FROM `translations` WHERE `id` = 1821 And `en` = 'Error Message';
DELETE FROM `translations` WHERE `id` = 1822 And `en` = 'Event';
DELETE FROM `translations` WHERE `id` = 1823 And `en` = 'Execution Time';
DELETE FROM `translations` WHERE `id` = 1824 And `en` = 'Expiry Date Is Required';
DELETE FROM `translations` WHERE `id` = 1825 And `en` = 'External Trainer';
DELETE FROM `translations` WHERE `id` = 1826 And `en` = 'failed to import completely.';
DELETE FROM `translations` WHERE `id` = 1827 And `en` = 'failed to import.';
DELETE FROM `translations` WHERE `id` = 1828 And `en` = 'Failed to revert student records.';
DELETE FROM `translations` WHERE `id` = 1829 And `en` = 'Failed to save grades';
DELETE FROM `translations` WHERE `id` = 1830 And `en` = 'Failed Validation';
DELETE FROM `translations` WHERE `id` = 1831 And `en` = 'File format not supported';
DELETE FROM `translations` WHERE `id` = 1832 And `en` = 'File is required';
DELETE FROM `translations` WHERE `id` = 1833 And `en` = 'File records exceeds maximum rows allowed';
DELETE FROM `translations` WHERE `id` = 1834 And `en` = 'File records exceeds maximum size allowed';
DELETE FROM `translations` WHERE `id` = 1835 And `en` = 'First Day Of Absence';
DELETE FROM `translations` WHERE `id` = 1837 And `en` = 'Grading';
DELETE FROM `translations` WHERE `id` = 1838 And `en` = 'Home Room Teacher should not be empty';
DELETE FROM `translations` WHERE `id` = 1839 And `en` = 'Incorrect password.';
DELETE FROM `translations` WHERE `id` = 1840 And `en` = 'Internal Trainer';
DELETE FROM `translations` WHERE `id` = 1841 And `en` = 'Invalid Code';
DELETE FROM `translations` WHERE `id` = 1842 And `en` = 'is successfully imported.';
DELETE FROM `translations` WHERE `id` = 1843 And `en` = 'Issue Date cannot be later than Expiry Date';
DELETE FROM `translations` WHERE `id` = 1845 And `en` = 'Last Day Of Absence';
DELETE FROM `translations` WHERE `id` = 1846 And `en` = 'Level Name';
DELETE FROM `translations` WHERE `id` = 1847 And `en` = 'New staff is not added to the institutition as there are no available FTE for the selected position.';
DELETE FROM `translations` WHERE `id` = 1848 And `en` = 'New staff is not added to the institutition, due to an error';
DELETE FROM `translations` WHERE `id` = 1850 And `en` = 'No available FTE.';
DELETE FROM `translations` WHERE `id` = 1851 And `en` = 'No Available Grades';
DELETE FROM `translations` WHERE `id` = 1852 And `en` = 'No Available Grades in this Institution';
DELETE FROM `translations` WHERE `id` = 1853 And `en` = 'No Available Institutions';
DELETE FROM `translations` WHERE `id` = 1854 And `en` = 'No Available Levels';
DELETE FROM `translations` WHERE `id` = 1855 And `en` = 'No Available Periods';
DELETE FROM `translations` WHERE `id` = 1857 And `en` = 'No Available Staff';
DELETE FROM `translations` WHERE `id` = 1859 And `en` = 'No Available Surveys';
DELETE FROM `translations` WHERE `id` = 1860 And `en` = 'No Available Trainees';
DELETE FROM `translations` WHERE `id` = 1861 And `en` = 'No Education Grade was selected.';
DELETE FROM `translations` WHERE `id` = 1862 And `en` = 'No Grades';
DELETE FROM `translations` WHERE `id` = 1863 And `en` = 'No Grades Assigned';
DELETE FROM `translations` WHERE `id` = 1864 And `en` = 'No identifiable survey found';
DELETE FROM `translations` WHERE `id` = 1865 And `en` = 'No other alternative options available to convert records.';
DELETE FROM `translations` WHERE `id` = 1866 And `en` = 'No Record';
DELETE FROM `translations` WHERE `id` = 1867 And `en` = 'No Record has been selected / saved.';
DELETE FROM `translations` WHERE `id` = 1868 And `en` = 'No record were found in the file imported';
DELETE FROM `translations` WHERE `id` = 1870 And `en` = 'No Students';
DELETE FROM `translations` WHERE `id` = 1873 And `en` = 'Not able to add absence record as this student is no longer enrolled in the institution.';
DELETE FROM `translations` WHERE `id` = 1874 And `en` = 'Not available to revert.';
DELETE FROM `translations` WHERE `id` = 1875 And `en` = 'Not supported in this form.';
DELETE FROM `translations` WHERE `id` = 1876 And `en` = 'Only alphabets and numbers are allowed';
DELETE FROM `translations` WHERE `id` = 1877 And `en` = 'Password should not contain spaces';
DELETE FROM `translations` WHERE `id` = 1879 And `en` = 'Please confirm your new password';
DELETE FROM `translations` WHERE `id` = 1880 And `en` = 'Please enter a Contact Type';
DELETE FROM `translations` WHERE `id` = 1881 And `en` = 'Please enter a number between 0 and 5';
DELETE FROM `translations` WHERE `id` = 1882 And `en` = 'Please enter a unique identity number.';
DELETE FROM `translations` WHERE `id` = 1883 And `en` = 'Please enter a unique OpenEMIS ID';
DELETE FROM `translations` WHERE `id` = 1884 And `en` = 'Please enter a valid amount.';
DELETE FROM `translations` WHERE `id` = 1885 And `en` = 'Please enter a valid Award.';
DELETE FROM `translations` WHERE `id` = 1886 And `en` = 'Please enter a valid Comment';
DELETE FROM `translations` WHERE `id` = 1887 And `en` = 'Please enter a valid Date';
DELETE FROM `translations` WHERE `id` = 1888 And `en` = 'Please enter a valid Graduate Year';
DELETE FROM `translations` WHERE `id` = 1889 And `en` = 'Please enter a valid Gross Salary';
DELETE FROM `translations` WHERE `id` = 1890 And `en` = 'Please enter a valid Hours.';
DELETE FROM `translations` WHERE `id` = 1891 And `en` = 'Please enter a valid Institution';
DELETE FROM `translations` WHERE `id` = 1892 And `en` = 'Please enter a valid Issuer.';
DELETE FROM `translations` WHERE `id` = 1893 And `en` = 'Please enter a valid Latitude';
DELETE FROM `translations` WHERE `id` = 1894 And `en` = 'Please enter a valid License Number.';
DELETE FROM `translations` WHERE `id` = 1895 And `en` = 'Please enter a valid Longitude';
DELETE FROM `translations` WHERE `id` = 1896 And `en` = 'Please enter a valid Major/Specialisation';
DELETE FROM `translations` WHERE `id` = 1897 And `en` = 'Please enter a valid Membership.';
DELETE FROM `translations` WHERE `id` = 1898 And `en` = 'Please enter a valid Middle Name';
DELETE FROM `translations` WHERE `id` = 1899 And `en` = 'Please enter a valid Net Salary';
DELETE FROM `translations` WHERE `id` = 1900 And `en` = 'Please enter a valid Numeric value';
DELETE FROM `translations` WHERE `id` = 1901 And `en` = 'Please enter a valid Preferred Name';
DELETE FROM `translations` WHERE `id` = 1902 And `en` = 'Please enter a valid Qualification Level';
DELETE FROM `translations` WHERE `id` = 1903 And `en` = 'Please enter a valid Qualification Title';
DELETE FROM `translations` WHERE `id` = 1904 And `en` = 'Please enter a valid Third Name';
DELETE FROM `translations` WHERE `id` = 1905 And `en` = 'Please enter a valid Title';
DELETE FROM `translations` WHERE `id` = 1906 And `en` = 'Please enter a valid value';
DELETE FROM `translations` WHERE `id` = 1907 And `en` = 'Please enter a valid Value';
DELETE FROM `translations` WHERE `id` = 1908 And `en` = 'Please enter an Account name';
DELETE FROM `translations` WHERE `id` = 1909 And `en` = 'Please enter an Account number';
DELETE FROM `translations` WHERE `id` = 1910 And `en` = 'Please enter an alphanumeric username';
DELETE FROM `translations` WHERE `id` = 1911 And `en` = 'Please enter your new password';
DELETE FROM `translations` WHERE `id` = 1912 And `en` = 'Please review the information before proceeding with the operation.';
DELETE FROM `translations` WHERE `id` = 1913 And `en` = 'Please select a Bank';
DELETE FROM `translations` WHERE `id` = 1914 And `en` = 'Please select a Bank Branch';
DELETE FROM `translations` WHERE `id` = 1915 And `en` = 'Please select a Language';
DELETE FROM `translations` WHERE `id` = 1916 And `en` = 'Please select a preferred contact type';
DELETE FROM `translations` WHERE `id` = 1917 And `en` = 'Please select a Salary Date';
DELETE FROM `translations` WHERE `id` = 1918 And `en` = 'Please select a valid License Type.';
DELETE FROM `translations` WHERE `id` = 1919 And `en` = 'Please select a valid Special Need Type.';
DELETE FROM `translations` WHERE `id` = 1920 And `en` = 'Please select an institution location.';
DELETE FROM `translations` WHERE `id` = 1921 And `en` = 'Please upload image format files. Eg. jpg, png, gif.';
DELETE FROM `translations` WHERE `id` = 1922 And `en` = 'Remote authentication failed, please try local login.';
DELETE FROM `translations` WHERE `id` = 1923 And `en` = 'Rows Failed:';
DELETE FROM `translations` WHERE `id` = 1924 And `en` = 'Rows Imported:';
DELETE FROM `translations` WHERE `id` = 1925 And `en` = 'Rows Updated:';
DELETE FROM `translations` WHERE `id` = 1926 And `en` = 'Rubric record has been saved to draft successfully.';
DELETE FROM `translations` WHERE `id` = 1927 And `en` = 'Rubric record has been submitted successfully.';
DELETE FROM `translations` WHERE `id` = 1930 And `en` = 'Select Staff';
DELETE FROM `translations` WHERE `id` = 1931 And `en` = 'Select Teacher';
DELETE FROM `translations` WHERE `id` = 1932 And `en` = 'Staff has already been added.';
DELETE FROM `translations` WHERE `id` = 1933 And `en` = 'Start Date cannot be later than End Date';
DELETE FROM `translations` WHERE `id` = 1934 And `en` = 'Start Date should not be earlier than Institution Date Opened';
DELETE FROM `translations` WHERE `id` = 1935 And `en` = 'Student admission has been approved successfully.';
DELETE FROM `translations` WHERE `id` = 1936 And `en` = 'Student admission has been rejected successfully.';
DELETE FROM `translations` WHERE `id` = 1937 And `en` = 'Student exists in the school';
DELETE FROM `translations` WHERE `id` = 1938 And `en` = 'Student has already been added to admission list';
DELETE FROM `translations` WHERE `id` = 1939 And `en` = 'Student has already been added.';
DELETE FROM `translations` WHERE `id` = 1940 And `en` = 'Student has already been enrolled in another Institution.';
DELETE FROM `translations` WHERE `id` = 1941 And `en` = 'Student has already dropped out from the school.';
DELETE FROM `translations` WHERE `id` = 1942 And `en` = 'Student is already exists in the new school';
DELETE FROM `translations` WHERE `id` = 1943 And `en` = 'Student records have been reverted successfully.';
DELETE FROM `translations` WHERE `id` = 1945 And `en` = 'Students have been transferred.';
DELETE FROM `translations` WHERE `id` = 1947 And `en` = 'Survey code is missing from the file. Please make sure that survey code exists on sheet';
DELETE FROM `translations` WHERE `id` = 1948 And `en` = 'Survey record has been saved to draft successfully.';
DELETE FROM `translations` WHERE `id` = 1949 And `en` = 'Survey record has been submitted successfully.';
DELETE FROM `translations` WHERE `id` = 1950 And `en` = 'Survey Template';
DELETE FROM `translations` WHERE `id` = 1953 And `en` = 'The chosen academic period is not editable';
DELETE FROM `translations` WHERE `id` = 1954 And `en` = 'The file';
DELETE FROM `translations` WHERE `id` = 1955 And `en` = 'The language has been successfully compiled.';
DELETE FROM `translations` WHERE `id` = 1956 And `en` = 'The language has not been compiled due to errors encountered.';
DELETE FROM `translations` WHERE `id` = 1957 And `en` = 'The position number that you have entered already existed, please try again.';
DELETE FROM `translations` WHERE `id` = 1958 And `en` = 'The record cannot be deleted as there are still records associated with it.';
DELETE FROM `translations` WHERE `id` = 1959 And `en` = 'The record does not exist.';
DELETE FROM `translations` WHERE `id` = 1960 And `en` = 'The record exists in the system.';
DELETE FROM `translations` WHERE `id` = 1962 And `en` = 'The record has been deleted successfully.';
DELETE FROM `translations` WHERE `id` = 1963 And `en` = 'The record has been duplicated successfully.';
DELETE FROM `translations` WHERE `id` = 1964 And `en` = 'The record has been rejected successfully.';
DELETE FROM `translations` WHERE `id` = 1967 And `en` = 'The record is not deleted due to errors encountered.';
DELETE FROM `translations` WHERE `id` = 1968 And `en` = 'The record is not duplicated due to errors encountered.';
DELETE FROM `translations` WHERE `id` = 1969 And `en` = 'The record is not rejected due to errors encountered.';
DELETE FROM `translations` WHERE `id` = 1970 And `en` = 'The record is not saved due to errors encountered.';
DELETE FROM `translations` WHERE `id` = 1972 And `en` = 'The staff has already exist within the start date and end date specified.';
DELETE FROM `translations` WHERE `id` = 1973 And `en` = 'There are no available Classes';
DELETE FROM `translations` WHERE `id` = 1974 And `en` = 'There are no available Education Grade.';
DELETE FROM `translations` WHERE `id` = 1975 And `en` = 'There are no available Education Level.';
DELETE FROM `translations` WHERE `id` = 1976 And `en` = 'There are no available Education Programme.';
DELETE FROM `translations` WHERE `id` = 1977 And `en` = 'There are no available Education Subjects.';
DELETE FROM `translations` WHERE `id` = 1978 And `en` = 'There are no available FTE for this position.';
DELETE FROM `translations` WHERE `id` = 1980 And `en` = 'There are no available Students for revert Student Status.';
DELETE FROM `translations` WHERE `id` = 1981 And `en` = 'There are no available Students for Transfer.';
DELETE FROM `translations` WHERE `id` = 1982 And `en` = 'There are no position available.';
DELETE FROM `translations` WHERE `id` = 1983 And `en` = 'There are no students selected.';
DELETE FROM `translations` WHERE `id` = 1984 And `en` = 'There are no subjects in the assigned grade';
DELETE FROM `translations` WHERE `id` = 1985 And `en` = 'There is a pending dropout application for this student at the moment, please reject the dropout application before making another request.';
DELETE FROM `translations` WHERE `id` = 1986 And `en` = 'There is a pending transfer application for this student at the moment, please remove the';
DELETE FROM `translations` WHERE `id` = 1987 And `en` = 'There is no active institution';
DELETE FROM `translations` WHERE `id` = 1988 And `en` = 'There is no class under the selected academic period';
DELETE FROM `translations` WHERE `id` = 1989 And `en` = 'There is no grade selected';
DELETE FROM `translations` WHERE `id` = 1990 And `en` = 'There is no rubric section selected';
DELETE FROM `translations` WHERE `id` = 1991 And `en` = 'There is no subject selected';
DELETE FROM `translations` WHERE `id` = 1992 And `en` = 'This code already exists in the system';
DELETE FROM `translations` WHERE `id` = 1993 And `en` = 'This Education Programme already exists in the system';
DELETE FROM `translations` WHERE `id` = 1995 And `en` = 'This identity has already existed in the system.';
DELETE FROM `translations` WHERE `id` = 1996 And `en` = 'This record is not editable';
DELETE FROM `translations` WHERE `id` = 1997 And `en` = 'This rubric record is not submitted due to criteria answers is not complete.';
DELETE FROM `translations` WHERE `id` = 1998 And `en` = 'This student does not fall within the allowed age range for';
DELETE FROM `translations` WHERE `id` = 1999 And `en` = 'This student has already been enrolled in an institution.';
DELETE FROM `translations` WHERE `id` = 2000 And `en` = 'This student was removed from the institution earlier';
DELETE FROM `translations` WHERE `id` = 2001 And `en` = 'This translation is already exists';
DELETE FROM `translations` WHERE `id` = 2002 And `en` = 'Total Amount Exceeded Outstanding Amount';
DELETE FROM `translations` WHERE `id` = 2004 And `en` = 'Total Rows:';
DELETE FROM `translations` WHERE `id` = 2005 And `en` = 'Transfer request has been approved successfully.';
DELETE FROM `translations` WHERE `id` = 2006 And `en` = 'Transfer request has been rejected successfully.';
DELETE FROM `translations` WHERE `id` = 2007 And `en` = 'Transfer request has been submitted successfully.';
DELETE FROM `translations` WHERE `id` = 2011 And `en` = 'Wrong template file';
DELETE FROM `translations` WHERE `id` = 2012 And `en` = 'You cannot remove a not enrolled student from the institution.';
DELETE FROM `translations` WHERE `id` = 2013 And `en` = 'You do not have access to this Class.';
DELETE FROM `translations` WHERE `id` = 2014 And `en` = 'You do not have access to this location.';
DELETE FROM `translations` WHERE `id` = 2015 And `en` = 'You have entered an invalid date.';
DELETE FROM `translations` WHERE `id` = 2016 And `en` = 'You have entered an invalid time.';
DELETE FROM `translations` WHERE `id` = 2017 And `en` = 'You have entered an invalid url.';
DELETE FROM `translations` WHERE `id` = 2018 And `en` = 'You have not been authorised to add an institution into that area.';
DELETE FROM `translations` WHERE `id` = 2019 And `en` = 'You need to configure Academic Periods first.';
DELETE FROM `translations` WHERE `id` = 2020 And `en` = 'You need to configure Academic Periods for Promotion / Graduation.';
DELETE FROM `translations` WHERE `id` = 2021 And `en` = 'You need to configure Classes first.';
DELETE FROM `translations` WHERE `id` = 2022 And `en` = 'You need to configure Education Programmes first.';
DELETE FROM `translations` WHERE `id` = 2023 And `en` = 'You need to configure Grading Types first.';
DELETE FROM `translations` WHERE `id` = 2024 And `en` = 'You need to configure Guardian Education Level first.';
DELETE FROM `translations` WHERE `id` = 2025 And `en` = 'You need to configure Guardian Relations first.';
DELETE FROM `translations` WHERE `id` = 2026 And `en` = 'You need to configure Institution Grades first.';
DELETE FROM `translations` WHERE `id` = 2027 And `en` = 'You need to configure Institution Site Positions first.';
DELETE FROM `translations` WHERE `id` = 2028 And `en` = 'You need to configure Result Types under Training Course.';
DELETE FROM `translations` WHERE `id` = 2029 And `en` = 'You need to configure Security Roles first.';
DELETE FROM `translations` WHERE `id` = 2030 And `en` = 'You need to configure Staff Types first.';
DELETE FROM `translations` WHERE `id` = 2031 And `en` = 'You need to configure Student Statuses first.';
DELETE FROM `translations` WHERE `id` = 2032 And `en` = 'You need to configure Workflows for this form.';
DELETE FROM `translations` WHERE `id` = 2033 And `en` = 'Your account has been disabled.';
DELETE FROM `translations` WHERE `id` = 2036 And `en` = 'Trim Openemis No';
DELETE FROM `translations` WHERE `id` = 2041 And `en` = 'Absence - Excused';
DELETE FROM `translations` WHERE `id` = 2043 And `en` = 'Absence - Unexcused';
DELETE FROM `translations` WHERE `id` = 2045 And `en` = 'Late';
DELETE FROM `translations` WHERE `id` = 2056 And `en` = 'Staff Change Type';
DELETE FROM `translations` WHERE `id` = 2057 And `en` = 'Change in FTE';
DELETE FROM `translations` WHERE `id` = 2058 And `en` = 'Change in Staff Type';
DELETE FROM `translations` WHERE `id` = 2059 And `en` = 'Institution Position';
DELETE FROM `translations` WHERE `id` = 2061 And `en` = 'Currently Assigned To';
DELETE FROM `translations` WHERE `id` = 2062 And `en` = 'Requested By';
DELETE FROM `translations` WHERE `id` = 2063 And `en` = 'By clicking save, a transfer request will be sent to the institution for approval';
DELETE FROM `translations` WHERE `id` = 2066 And `en` = 'To Be Approved BY';
DELETE FROM `translations` WHERE `id` = 2068 And `en` = 'Current Institution Position';
DELETE FROM `translations` WHERE `id` = 2069 And `en` = 'Current FTE';
DELETE FROM `translations` WHERE `id` = 2070 And `en` = 'Current Staff Type';
DELETE FROM `translations` WHERE `id` = 2072 And `en` = 'Current Start Date';
DELETE FROM `translations` WHERE `id` = 2078 And `en` = 'Transfer of Staff';
DELETE FROM `translations` WHERE `id` = 2080 And `en` = 'Staff Position Profiles';
DELETE FROM `translations` WHERE `id` = 2081 And `en` = 'To Be Approved By';
DELETE FROM `translations` WHERE `id` = 2085 And `en` = 'Absence Reasons';
DELETE FROM `translations` WHERE `id` = 2089 And `en` = 'No Available Academic Periods';
DELETE FROM `translations` WHERE `id` = 2090 And `en` = 'Dropout Requests';
DELETE FROM `translations` WHERE `id` = 2091 And `en` = 'Student Dropout';
DELETE FROM `translations` WHERE `id` = 2093 And `en` = 'All Levels';
DELETE FROM `translations` WHERE `id` = 2094 And `en` = 'Current Institution';
DELETE FROM `translations` WHERE `id` = 2095 And `en` = 'Requested Institution';
DELETE FROM `translations` WHERE `id` = 2097 And `en` = 'Requested Institution Position';
DELETE FROM `translations` WHERE `id` = 2098 And `en` = 'Requested FTE';
DELETE FROM `translations` WHERE `id` = 2100 And `en` = 'Requested Staff Type';
DELETE FROM `translations` WHERE `id` = 2101 And `en` = 'Requested Start Date';
DELETE FROM `translations` WHERE `id` = 2103 And `en` = 'Please Define Default Identity Type';
DELETE FROM `translations` WHERE `id` = 2105 And `en` = 'Staff identity is mandatory';
DELETE FROM `translations` WHERE `id` = 2107 And `en` = 'Student identity is mandatory';
DELETE FROM `translations` WHERE `id` = 2111 And `en` = 'Admission';
DELETE FROM `translations` WHERE `id` = 2113 And `en` = 'Change in Staff Assignment';
DELETE FROM `translations` WHERE `id` = 2118 And `en` = 'Institution Grades';
DELETE FROM `translations` WHERE `id` = 2120 And `en` = 'Convert From';
DELETE FROM `translations` WHERE `id` = 2121 And `en` = 'Convert To';
DELETE FROM `translations` WHERE `id` = 2122 And `en` = 'No Available Options';
DELETE FROM `translations` WHERE `id` = 2123 And `en` = 'InstitutionClasses';
DELETE FROM `translations` WHERE `id` = 2126 And `en` = 'Institution Subjects';
DELETE FROM `translations` WHERE `id` = 2133 And `en` = 'Mother';
DELETE FROM `translations` WHERE `id` = 2134 And `en` = 'Father';
DELETE FROM `translations` WHERE `id` = 2151 And `en` = 'Current Week';
DELETE FROM `translations` WHERE `id` = 2153 And `en` = 'Week';
DELETE FROM `translations` WHERE `id` = 2156 And `en` = 'Institution > Positions';
DELETE FROM `translations` WHERE `id` = 2157 And `en` = 'Transfer of staff';
DELETE FROM `translations` WHERE `id` = 2159 And `en` = 'Admission of student';
DELETE FROM `translations` WHERE `id` = 2164 And `en` = 'Other Details';
DELETE FROM `translations` WHERE `id` = 2166 And `en` = 'Previous Institution';
DELETE FROM `translations` WHERE `id` = 2168 And `en` = 'No of records';
DELETE FROM `translations` WHERE `id` = 2169 And `en` = 'Apply To';
DELETE FROM `translations` WHERE `id` = 2176 And `en` = 'Hi';
DELETE FROM `translations` WHERE `id` = 2179 And `en` = 'Date of Application';
DELETE FROM `translations` WHERE `id` = 2185 And `en` = 'Institutions with No Students';
DELETE FROM `translations` WHERE `id` = 2187 And `en` = 'Institutions with No Staff';
DELETE FROM `translations` WHERE `id` = 2188 And `en` = 'All Academic Periods';
DELETE FROM `translations` WHERE `id` = 2189 And `en` = 'All Types';
DELETE FROM `translations` WHERE `id` = 2201 And `en` = 'Guardian National No';
DELETE FROM `translations` WHERE `id` = 2207 And `en` = 'End Of Assignment';
DELETE FROM `translations` WHERE `id` = 2215 And `en` = 'from';
DELETE FROM `translations` WHERE `id` = 2217 And `en` = 'to';
DELETE FROM `translations` WHERE `id` = 2219 And `en` = 'Land';
DELETE FROM `translations` WHERE `id` = 2221 And `en` = 'Floor';
DELETE FROM `translations` WHERE `id` = 2223 And `en` = 'Single Shift Owner';
DELETE FROM `translations` WHERE `id` = 2241 And `en` = 'Requested On';
DELETE FROM `translations` WHERE `id` = 2243 And `en` = 'Please set other identity type as default before deleting the current one';
DELETE FROM `translations` WHERE `id` = 2244 And `en` = 'There are no shifts configured for the selected academic period';
DELETE FROM `translations` WHERE `id` = 2252 And `en` = 'Student is already enrolled in another school';
DELETE FROM `translations` WHERE `id` = 2258 And `en` = 'Student is already enrolled in another school';
DELETE FROM `translations` WHERE `id` = 2260 And `en` = 'Student is already enrolled in another school.';
DELETE FROM `translations` WHERE `id` = 2262 And `en` = 'Institution Information';
DELETE FROM `translations` WHERE `id` = 2268 And `en` = 'Special Need Difficulty';
DELETE FROM `translations` WHERE `id` = 2272 And `en` = 'Please enter a valid format';
DELETE FROM `translations` WHERE `id` = 2274 And `en` = 'Is Academic';
DELETE FROM `translations` WHERE `id` = 2278 And `en` = 'Change Type';
DELETE FROM `translations` WHERE `id` = 2282 And `en` = 'Update Details';
DELETE FROM `translations` WHERE `id` = 2286 And `en` = 'Change in Room Type';
DELETE FROM `translations` WHERE `id` = 2288 And `en` = 'Maintenance';
DELETE FROM `translations` WHERE `id` = 2296 And `en` = 'Academic Institution';
DELETE FROM `translations` WHERE `id` = 2298 And `en` = 'Academic Institution';
DELETE FROM `translations` WHERE `id` = 2300 And `en` = '--Select Area--';
DELETE FROM `translations` WHERE `id` = 2314 And `en` = 'Student Transfer Approval';
DELETE FROM `translations` WHERE `id` = 2322 And `en` = 'Date of Birth';
DELETE FROM `translations` WHERE `id` = 2326 And `en` = 'Create New Student';
DELETE FROM `translations` WHERE `id` = 2328 And `en` = 'New Student Details';
DELETE FROM `translations` WHERE `id` = 2330 And `en` = 'Request for Change in Assignment has been submitted successfully.';
DELETE FROM `translations` WHERE `id` = 2332 And `en` = 'Change in Assignment';
DELETE FROM `translations` WHERE `id` = 2336 And `en` = 'Students status changed successfully';
DELETE FROM `translations` WHERE `id` = 2344 And `en` = 'Students without Class';
DELETE FROM `translations` WHERE `id` = 2346 And `en` = 'Training Hours';
DELETE FROM `translations` WHERE `id` = 2382 And `en` = 'Exam';
DELETE FROM `translations` WHERE `id` = 2384 And `en` = 'Practical';
DELETE FROM `translations` WHERE `id` = 2394 And `en` = 'Student has already completed the selected grade.';
DELETE FROM `translations` WHERE `id` = 2400 And `en` = 'This student is already allocated to';
DELETE FROM `translations` WHERE `id` = 2401 And `en` = 'The student is added to the Pending Admission list successfully.';
DELETE FROM `translations` WHERE `id` = 2402 And `en` = 'Complete';
DELETE FROM `translations` WHERE `id` = 2404 And `en` = 'New Room Type';
DELETE FROM `translations` WHERE `id` = 2405 And `en` = 'There is no programme set for available Academic Period on this institution';
DELETE FROM `translations` WHERE `id` = 2406 And `en` = 'There are no shifts configured for the selected academic period, will be using system configuration timing';
DELETE FROM `translations` WHERE `id` = 2432 And `en` = 'Non-Academic Institution';
DELETE FROM `translations` WHERE `id` = 2433 And `en` = 'No Class Assignment';
DELETE FROM `translations` WHERE `id` = 2440 And `en` = 'Undo Examination Registration';
DELETE FROM `translations` WHERE `id` = 2446 And `en` = 'New Examination Centre';
DELETE FROM `translations` WHERE `id` = 2448 And `en` = 'Special Need Types';
DELETE FROM `translations` WHERE `id` = 2450 And `en` = 'No Institutions Linked';
DELETE FROM `translations` WHERE `id` = 2453 And `en` = 'Select Invigilators';
DELETE FROM `translations` WHERE `id` = 2455 And `en` = 'Select Students';
DELETE FROM `translations` WHERE `id` = 2457 And `en` = 'Select Some Options';
DELETE FROM `translations` WHERE `id` = 2460 And `en` = 'Examination Centre Information';
DELETE FROM `translations` WHERE `id` = 2462 And `en` = 'Existing Institution';
DELETE FROM `translations` WHERE `id` = 2463 And `en` = 'Institution Type';
DELETE FROM `translations` WHERE `id` = 2469 And `en` = 'Students status changed successfully.';
DELETE FROM `translations` WHERE `id` = 2470 And `en` = 'Examination Centres';
DELETE FROM `translations` WHERE `id` = 2471 And `en` = 'Examination Item Results';
DELETE FROM `translations` WHERE `id` = 2472 And `en` = 'Examination Centre Students';
DELETE FROM `translations` WHERE `id` = 2477 And `en` = 'Comment Type';
DELETE FROM `translations` WHERE `id` = 2482 And `en` = 'Staff Trainings';
DELETE FROM `translations` WHERE `id` = 2497 And `en` = 'Reactivate';
DELETE FROM `translations` WHERE `id` = 2504 And `en` = 'There is a pending transfer application for this student at the moment, please remove the transfer application before making another request.';
DELETE FROM `translations` WHERE `id` = 2505 And `en` = 'Student has been transferred to';
DELETE FROM `translations` WHERE `id` = 2506 And `en` = 'after registration';
DELETE FROM `translations` WHERE `id` = 2507 And `en` = 'You need to configure Assessment Periods first';
DELETE FROM `translations` WHERE `id` = 2508 And `en` = 'No Rows To Show';
DELETE FROM `translations` WHERE `id` = 2509 And `en` = 'Edit operation is not allowed as the record already End of Usage.';
DELETE FROM `translations` WHERE `id` = 2510 And `en` = 'The selected students are pending for transfer approval.';
DELETE FROM `translations` WHERE `id` = 2511 And `en` = 'Reopen';
DELETE FROM `translations` WHERE `id` = 2512 And `en` = 'Institution Students';
DELETE FROM `translations` WHERE `id` = 2514 And `en` = 'Staff End Date';
DELETE FROM `translations` WHERE `id` = 2515 And `en` = 'Pinding For Approval';
DELETE FROM `translations` WHERE `id` = 2520 And `en` = 'Synchronisation';

-- adding translate word.
INSERT INTO `translations` (`code`, `en`, `editable`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Allocated To', 1, NULL, NULL, 1, NOW()),
(NULL, 'Textbook ID', 1, NULL, NULL, 1, NOW()),
(NULL, 'Condition', 1, NULL, NULL, 1, NOW()),
(NULL, 'Profile', 1, NULL, NULL, 1, NOW()),
(NULL, 'Profiles', 1, NULL, NULL, 1, NOW()),
(NULL, 'Height', 1, NULL, NULL, 1, NOW()),
(NULL, 'Body Mass Index', 1, NULL, NULL, 1, NOW()),
(NULL, 'Body Mass', 1, NULL, NULL, 1, NOW()),
(NULL, 'Promote From', 1, NULL, NULL, 1, NOW()),
(NULL, 'Promote To', 1, NULL, NULL, 1, NOW()),
(NULL, 'Region', 1, NULL, NULL, 1, NOW()),
(NULL, 'Zone', 1, NULL, NULL, 1, NOW()),
(NULL, 'Shared', 1, NULL, NULL, 1, NOW()),
(NULL, 'Textbook', 1, NULL, NULL, 1, NOW()),
(NULL, 'Textbook Condition', 1, NULL, NULL, 1, NOW()),
(NULL, 'Textbook Conditions', 1, NULL, NULL, 1, NOW()),
(NULL, 'Textbook Status', 1, NULL, NULL, 1, NOW()),
(NULL, 'Indexes', 1, NULL, NULL, 1, NOW()),
(NULL, 'Number Of Risk Index', 1, NULL, NULL, 1, NOW()),
(NULL, 'Total Index', 1, NULL, NULL, 1, NOW()),
(NULL, 'Indexes Criterias', 1, NULL, NULL, 1, NOW()),
(NULL, 'Operator', 1, NULL, NULL, 1, NOW()),
(NULL, 'Threshold', 1, NULL, NULL, 1, NOW()),
(NULL, 'References', 1, NULL, NULL, 1, NOW()),
(NULL, 'Generate', 1, NULL, NULL, 1, NOW()),
(NULL, 'Generated By', 1, NULL, NULL, 1, NOW()),
(NULL, 'Generated On', 1, NULL, NULL, 1, NOW()),
(NULL, 'Not Generated', 1, NULL, NULL, 1, NOW()),
(NULL, 'Total Index', 1, NULL, NULL, 1, NOW()),
(NULL, 'Counsellings', 1, NULL, NULL, 1, NOW()),
(NULL, 'Counselling', 1, NULL, NULL, 1, NOW()),
(NULL, 'Intervention', 1, NULL, NULL, 1, NOW()),
(NULL, 'Counselor', 1, NULL, NULL, 1, NOW()),
(NULL, 'Guidance Type', 1, NULL, NULL, 1, NOW()),
(NULL, 'File Name', 1, NULL, NULL, 1, NOW()),
(NULL, 'All associated information related to this record will also be removed. Are you sure you want to delete this record?', 1, NULL, NULL, 1, NOW()),
(NULL, 'This field is required.', 1, NULL, NULL, 1, NOW()),
(NULL, 'Not active homeroom teacher', 1, NULL, NULL, 1, NOW()),
(NULL, 'Not active teaching staff', 1, NULL, NULL, 1, NOW()),
(NULL, 'Number Of Student By Stage', 1, NULL, NULL, 1, NOW()),
(NULL, 'Number Of Staff By Type', 1, NULL, NULL, 1, NOW()),
(NULL, 'Number Of Staff By Year', 1, NULL, NULL, 1, NOW()),
(NULL, 'Delete operation is not allowed as there are other information linked to this record.', 1, NULL, NULL, 1, NOW()),
(NULL, 'File attachment is required', 1, NULL, NULL, 1, NOW()),
(NULL, 'Secondary Staff', 1, NULL, NULL, 1, NOW()),
(NULL, 'Multi-grade', 1, NULL, NULL, 1, NOW()),
(NULL, 'Secondary Home Room Teacher', 1, NULL, NULL, 1, NOW()),
(NULL, 'Select Textbooks', 1, NULL, NULL, 1, NOW()),
(NULL, 'Select Education Subject', 1, NULL, NULL, 1, NOW()),
(NULL, 'Select Education Grade', 1, NULL, NULL, 1, NOW()),
(NULL, 'Select Academic Term', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Genders', 1, NULL, NULL, 1, NOW()),
(NULL, 'No Room Allocated', 1, NULL, NULL, 1, NOW()),
(NULL, 'Teacher(s)', 1, NULL, NULL, 1, NOW()),
(NULL, 'Past Teachers', 1, NULL, NULL, 1, NOW()),
(NULL, 'Institution Textbooks', 1, NULL, NULL, 1, NOW()),
(NULL, 'No Textbooks', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add Textbook', 1, NULL, NULL, 1, NOW()),
(NULL, 'Pending Withdraw', 1, NULL, NULL, 1, NOW()),
(NULL, 'Withdraw', 1, NULL, NULL, 1, NOW()),
(NULL, 'Withdrawn', 1, NULL, NULL, 1, NOW()),
(NULL, 'Password should contain at least 1 uppercase character', 1, NULL, NULL, 1, NOW()),
(NULL, 'Password should contain at least 1 lowercase character', 1, NULL, NULL, 1, NOW()),
(NULL, 'Password should contain at least 1 non-alphanumeric character', 1, NULL, NULL, 1, NOW()),
(NULL, 'Both passwords do not match', 1, NULL, NULL, 1, NOW()),
(NULL, 'There must be at least one Preferred Nationality', 1, NULL, NULL, 1, NOW()),
(NULL, 'Relation', 1, NULL, NULL, 1, NOW()),
(NULL, 'Edit Profile', 1, NULL, NULL, 1, NOW()),
(NULL, 'Edit Relation', 1, NULL, NULL, 1, NOW()),
(NULL, 'Guardian User', 1, NULL, NULL, 1, NOW()),
(NULL, 'No Guardian found.', 1, NULL, NULL, 1, NOW()),
(NULL, 'No Guardian', 1, NULL, NULL, 1, NOW()),
(NULL, 'No Results', 1, NULL, NULL, 1, NOW()),
(NULL, 'No Examination Results', 1, NULL, NULL, 1, NOW()),
(NULL, 'No Student Fees', 1, NULL, NULL, 1, NOW()),
(NULL, 'Report Card', 1, NULL, NULL, 1, NOW()),
(NULL, 'Report Cards', 1, NULL, NULL, 1, NOW()),
(NULL, 'transition', 1, NULL, NULL, 1, NOW()),
(NULL, 'Last Executer', 1, NULL, NULL, 1, NOW()),
(NULL, 'Last Execution Date', 1, NULL, NULL, 1, NOW()),
(NULL, 'Behaviour Classification', 1, NULL, NULL, 1, NOW()),
(NULL, 'Classifications', 1, NULL, NULL, 1, NOW()),
(NULL, 'Submit For Verification & Authentication', 1, NULL, NULL, 1, NOW()),
(NULL, 'Rating', 1, NULL, NULL, 1, NOW()),
(NULL, 'Competency Set', 1, NULL, NULL, 1, NOW()),
(NULL, 'Competencies / Goals', 1, NULL, NULL, 1, NOW()),
(NULL, 'Competencies', 1, NULL, NULL, 1, NOW()),
(NULL, 'Competency', 1, NULL, NULL, 1, NOW()),
(NULL, 'Import Salaries', 1, NULL, NULL, 1, NOW()),
(NULL, 'Submit For Approval', 1, NULL, NULL, 1, NOW()),
(NULL, 'Training Need Category', 1, NULL, NULL, 1, NOW()),
(NULL, 'Applications', 1, NULL, NULL, 1, NOW()),
(NULL, 'Apply', 1, NULL, NULL, 1, NOW()),
(NULL, 'Number Of Months', 1, NULL, NULL, 1, NOW()),
(NULL, 'Number Of Hours', 1, NULL, NULL, 1, NOW()),
(NULL, 'Number Of Years', 1, NULL, NULL, 1, NOW()),
(NULL, 'Training Field Of Study', 1, NULL, NULL, 1, NOW()),
(NULL, 'Training Course Type', 1, NULL, NULL, 1, NOW()),
(NULL, 'Training Mode Of Delivery', 1, NULL, NULL, 1, NOW()),
(NULL, 'Field of Study', 1, NULL, NULL, 1, NOW()),
(NULL, 'Field Of Studies', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Days', 1, NULL, NULL, 1, NOW()),
(NULL, 'Behaviour Classification', 1, NULL, NULL, 1, NOW()),
(NULL, 'Linked Cases', 1, NULL, NULL, 1, NOW()),
(NULL, 'Case Number', 1, NULL, NULL, 1, NOW()),
(NULL, 'Student Competencies', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Competencies', 1, NULL, NULL, 1, NOW()),
(NULL, 'Competency Template', 1, NULL, NULL, 1, NOW()),
(NULL, 'Competency Period', 1, NULL, NULL, 1, NOW()),
(NULL, 'Competency Item', 1, NULL, NULL, 1, NOW()),
(NULL, 'Competency Assessment period', 1, NULL, NULL, 1, NOW()),
(NULL, 'Changes will be automatically saved when any value is changed', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Items', 1, NULL, NULL, 1, NOW()),
(NULL, 'Items', 1, NULL, NULL, 1, NOW()),
(NULL, 'Competency Items', 1, NULL, NULL, 1, NOW()),
(NULL, 'Competency Item', 1, NULL, NULL, 1, NOW()),
(NULL, 'Competency Grading Type', 1, NULL, NULL, 1, NOW()),
(NULL, 'No Assessments', 1, NULL, NULL, 1, NOW()),
(NULL, 'Student result will be save after the result has been entered.', 1, NULL, NULL, 1, NOW()),
(NULL, 'Examination Results', 1, NULL, NULL, 1, NOW()),
(NULL, 'Report Card Comments', 1, NULL, NULL, 1, NOW()),
(NULL, 'Report Cards', 1, NULL, NULL, 1, NOW()),
(NULL, 'Report Card', 1, NULL, NULL, 1, NOW()),
(NULL, 'Select Report Card', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Report Cards', 1, NULL, NULL, 1, NOW()),
(NULL, 'Report Card Statuses', 1, NULL, NULL, 1, NOW()),
(NULL, 'Statuses', 1, NULL, NULL, 1, NOW()),
(NULL, 'Payments', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add Payment', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add New Payment', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Land Types', 1, NULL, NULL, 1, NOW()),
(NULL, 'Institution Lands', 1, NULL, NULL, 1, NOW()),
(NULL, 'Land Type', 1, NULL, NULL, 1, NOW()),
(NULL, 'Land Status', 1, NULL, NULL, 1, NOW()),
(NULL, 'Institution Buildings', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Building Types', 1, NULL, NULL, 1, NOW()),
(NULL, 'Institution Floors', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Floor Types', 1, NULL, NULL, 1, NOW()),
(NULL, 'Floor Type', 1, NULL, NULL, 1, NOW()),
(NULL, 'Floor Status', 1, NULL, NULL, 1, NOW()),
(NULL, 'Institution Rooms', 1, NULL, NULL, 1, NOW()),
(NULL, 'Enrolment Information', 1, NULL, NULL, 1, NOW()),
(NULL, 'Cases', 1, NULL, NULL, 1, NOW()),
(NULL, 'Case Number', 1, NULL, NULL, 1, NOW()),
(NULL, 'Linked Records', 1, NULL, NULL, 1, NOW()),
(NULL, 'The password is automatically generated by the system', 1, NULL, NULL, 1, NOW()),
(NULL, 'Directories', 1, NULL, NULL, 1, NOW()),
(NULL, 'Business', 1, NULL, NULL, 1, NOW()),
(NULL, 'Probation', 1, NULL, NULL, 1, NOW()),
(NULL, 'Extension', 1, NULL, NULL, 1, NOW()),
(NULL, 'Termination', 1, NULL, NULL, 1, NOW()),
(NULL, 'Resignation', 1, NULL, NULL, 1, NOW()),
(NULL, 'Retirement', 1, NULL, NULL, 1, NOW()),
(NULL, 'Temporary', 1, NULL, NULL, 1, NOW()),
(NULL, 'Institutions with No Students', 1, NULL, NULL, 1, NOW()),
(NULL, 'Institutions with No Staff', 1, NULL, NULL, 1, NOW()),
(NULL, 'Area Education Code', 1, NULL, NULL, 1, NOW()),
(NULL, 'Area Administrative Code', 1, NULL, NULL, 1, NOW()),
(NULL, 'Year Opened', 1, NULL, NULL, 1, NOW()),
(NULL, 'Year Closed', 1, NULL, NULL, 1, NOW()),
(NULL, 'Area Code', 1, NULL, NULL, 1, NOW()),
(NULL, 'No Of Seats', 1, NULL, NULL, 1, NOW()),
(NULL, 'Preferred Nationality', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Nationalities', 1, NULL, NULL, 1, NOW()),
(NULL, 'Previous Institution Student', 1, NULL, NULL, 1, NOW()),
(NULL, 'Report Generated', 1, NULL, NULL, 1, NOW()),
(NULL, 'System Usage', 1, NULL, NULL, 1, NOW()),
(NULL, 'No previous login', 1, NULL, NULL, 1, NOW()),
(NULL, 'Logged in within the last 7 days', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Exam Centres', 1, NULL, NULL, 1, NOW()),
(NULL, 'Examination Results', 1, NULL, NULL, 1, NOW()),
(NULL, 'Registered Students by Examination Centre', 1, NULL, NULL, 1, NOW()),
(NULL, 'Not Registered Students', 1, NULL, NULL, 1, NOW()),
(NULL, 'Training Need Type', 1, NULL, NULL, 1, NOW()),
(NULL, 'Pending Review', 1, NULL, NULL, 1, NOW()),
(NULL, 'Session Participants', 1, NULL, NULL, 1, NOW()),
(NULL, 'Institution Types', 1, NULL, NULL, 1, NOW()),
(NULL, 'Synchronize', 1, NULL, NULL, 1, NOW()),
(NULL, 'Areas (Education)', 1, NULL, NULL, 1, NOW()),
(NULL, 'Area Levels (Education)', 1, NULL, NULL, 1, NOW()),
(NULL, 'Areas (Administrative)', 1, NULL, NULL, 1, NOW()),
(NULL, 'Area Levels (Administrative)', 1, NULL, NULL, 1, NOW()),
(NULL, 'Data will be synchronized from', 1, NULL, NULL, 1, NOW()),
(NULL, 'New Area', 1, NULL, NULL, 1, NOW()),
(NULL, 'Missing Area', 1, NULL, NULL, 1, NOW()),
(NULL, 'Security Group Affected', 1, NULL, NULL, 1, NOW()),
(NULL, 'Institution Affected', 1, NULL, NULL, 1, NOW()),
(NULL, 'Area Administrative Level', 1, NULL, NULL, 1, NOW()),
(NULL, 'Is Main Country', 1, NULL, NULL, 1, NOW()),
(NULL, 'Education Level Isced', 1, NULL, NULL, 1, NOW()),
(NULL, 'Cycles', 1, NULL, NULL, 1, NOW()),
(NULL, 'Education Certification', 1, NULL, NULL, 1, NOW()),
(NULL, 'Education Field Of Study', 1, NULL, NULL, 1, NOW()),
(NULL, 'Next Programmes', 1, NULL, NULL, 1, NOW()),
(NULL, 'Cycle - (Programme)', 1, NULL, NULL, 1, NOW()),
(NULL, 'Education Stage', 1, NULL, NULL, 1, NOW()),
(NULL, 'Hours Required', 1, NULL, NULL, 1, NOW()),
(NULL, 'Auto Allocation', 1, NULL, NULL, 1, NOW()),
(NULL, 'Grade Subjects', 1, NULL, NULL, 1, NOW()),
(NULL, 'If this option is set to Yes, students will be allocated automatically to this subject upon enrolment to a class', 1, NULL, NULL, 1, NOW()),
(NULL, 'Stages', 1, NULL, NULL, 1, NOW()),
(NULL, 'Education Programme Orientation', 1, NULL, NULL, 1, NOW()),
(NULL, 'Network Connectivities', 1, NULL, NULL, 1, NOW()),
(NULL, 'Localities', 1, NULL, NULL, 1, NOW()),
(NULL, 'Ownerships', 1, NULL, NULL, 1, NOW()),
(NULL, 'Sectors', 1, NULL, NULL, 1, NOW()),
(NULL, 'Shift Options', 1, NULL, NULL, 1, NOW()),
(NULL, 'Report Card Comment Codes', 1, NULL, NULL, 1, NOW()),
(NULL, 'Student Behaviour Categories', 1, NULL, NULL, 1, NOW()),
(NULL, 'Student Transfer Reasons', 1, NULL, NULL, 1, NOW()),
(NULL, 'Student Withdraw Reasons', 1, NULL, NULL, 1, NOW()),
(NULL, 'Guidance Types', 1, NULL, NULL, 1, NOW()),
(NULL, 'Staff Behaviour Categories', 1, NULL, NULL, 1, NOW()),
(NULL, 'Staff Leave Types', 1, NULL, NULL, 1, NOW()),
(NULL, 'Is Mandatory', 1, NULL, NULL, 1, NOW()),
(NULL, 'Is Unique', 1, NULL, NULL, 1, NOW()),
(NULL, 'Validation Rule', 1, NULL, NULL, 1, NOW()),
(NULL, 'Validation Rules', 1, NULL, NULL, 1, NOW()),
(NULL, 'Rules', 1, NULL, NULL, 1, NOW()),
(NULL, 'Rule', 1, NULL, NULL, 1, NOW()),
(NULL, 'Custom Module', 1, NULL, NULL, 1, NOW()),
(NULL, 'Apply To All', 1, NULL, NULL, 1, NOW()),
(NULL, 'Custom Filters', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add Field', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add Section', 1, NULL, NULL, 1, NOW()),
(NULL, 'Length', 1, NULL, NULL, 1, NOW()),
(NULL, 'Decimal Place', 1, NULL, NULL, 1, NOW()),
(NULL, 'Create Table', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add Columns', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add Rows', 1, NULL, NULL, 1, NOW()),
(NULL, 'Module Name', 1, NULL, NULL, 1, NOW()),
(NULL, 'Field Name', 1, NULL, NULL, 1, NOW()),
(NULL, 'Translations', 1, NULL, NULL, 1, NOW()),
(NULL, 'Label', 1, NULL, NULL, 1, NOW()),
(NULL, 'Default Value', 1, NULL, NULL, 1, NOW()),
(NULL, 'Prefix Value', 1, NULL, NULL, 1, NOW()),
(NULL, 'Enable', 1, NULL, NULL, 1, NOW()),
(NULL, 'Validation Pattern', 1, NULL, NULL, 1, NOW()),
(NULL, 'External Data Source', 1, NULL, NULL, 1, NOW()),
(NULL, 'Attributes', 1, NULL, NULL, 1, NOW()),
(NULL, 'Attribute Name', 1, NULL, NULL, 1, NOW()),
(NULL, 'Product Lists', 1, NULL, NULL, 1, NOW()),
(NULL, 'Student Admission Age', 1, NULL, NULL, 1, NOW()),
(NULL, 'Credentials', 1, NULL, NULL, 1, NOW()),
(NULL, 'Select Criteria', 1, NULL, NULL, 1, NOW()),
(NULL, 'Absences Excused', 1, NULL, NULL, 1, NOW()),
(NULL, 'Overage', 1, NULL, NULL, 1, NOW()),
(NULL, 'Status Repeated', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add Area', 1, NULL, NULL, 1, NOW()),
(NULL, 'Import Institutions.', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Subjects', 1, NULL, NULL, 1, NOW()),
(NULL, 'My Subjects', 1, NULL, NULL, 1, NOW()),
(NULL, 'Import Textbooks', 1, NULL, NULL, 1, NOW()),
(NULL, 'My Classes', 1, NULL, NULL, 1, NOW()),
(NULL, 'Student Profile', 1, NULL, NULL, 1, NOW()),
(NULL, 'Transfer Request', 1, NULL, NULL, 1, NOW()),
(NULL, 'Transfer Approval', 1, NULL, NULL, 1, NOW()),
(NULL, 'Withdraw Request', 1, NULL, NULL, 1, NOW()),
(NULL, 'Student Withdraw', 1, NULL, NULL, 1, NOW()),
(NULL, 'Account Username', 1, NULL, NULL, 1, NOW()),
(NULL, 'Import Student Attendances', 1, NULL, NULL, 1, NOW()),
(NULL, 'Undo Student Status', 1, NULL, NULL, 1, NOW()),
(NULL, 'Competency Results', 1, NULL, NULL, 1, NOW()),
(NULL, 'Competency Comments', 1, NULL, NULL, 1, NOW()),
(NULL, 'Staff Profile', 1, NULL, NULL, 1, NOW()),
(NULL, 'Import Staff Attendances', 1, NULL, NULL, 1, NOW()),
(NULL, 'Transfer Approvals', 1, NULL, NULL, 1, NOW()),
(NULL, 'Change in Staff Assignment', 1, NULL, NULL, 1, NOW()),
(NULL, 'Import Staff', 1, NULL, NULL, 1, NOW()),
(NULL, 'Generate/Download', 1, NULL, NULL, 1, NOW()),
(NULL, 'Publish/Unpublish', 1, NULL, NULL, 1, NOW()),
(NULL, 'Guardian Profile', 1, NULL, NULL, 1, NOW()),
(NULL, 'Student Body Mass', 1, NULL, NULL, 1, NOW()),
(NULL, 'Salary List', 1, NULL, NULL, 1, NOW()),
(NULL, 'Salary Details', 1, NULL, NULL, 1, NOW()),
(NULL, 'Import Staff Salaries', 1, NULL, NULL, 1, NOW()),
(NULL, 'Import Users', 1, NULL, NULL, 1, NOW()),
(NULL, 'Academic Period Levels', 1, NULL, NULL, 1, NOW()),
(NULL, 'Education Systems', 1, NULL, NULL, 1, NOW()),
(NULL, 'Education Levels', 1, NULL, NULL, 1, NOW()),
(NULL, 'Education Cycles', 1, NULL, NULL, 1, NOW()),
(NULL, 'Education Programmes', 1, NULL, NULL, 1, NOW()),
(NULL, 'Education Grade Subjects', 1, NULL, NULL, 1, NOW()),
(NULL, 'Webhooks', 1, NULL, NULL, 1, NOW()),
(NULL, 'Alerts', 1, NULL, NULL, 1, NOW()),
(NULL, 'Alert Rules', 1, NULL, NULL, 1, NOW()),
(NULL, 'Workflows', 1, NULL, NULL, 1, NOW()),
(NULL, 'Steps', 1, NULL, NULL, 1, NOW()),
(NULL, 'Exam Centre Rooms', 1, NULL, NULL, 1, NOW()),
(NULL, 'Exam Centre Exams', 1, NULL, NULL, 1, NOW()),
(NULL, 'Exam Centre Subjects', 1, NULL, NULL, 1, NOW()),
(NULL, 'Exam Centre Students', 1, NULL, NULL, 1, NOW()),
(NULL, 'Exam Centre Invigilators', 1, NULL, NULL, 1, NOW()),
(NULL, 'Exam Centre Linked Institutions', 1, NULL, NULL, 1, NOW()),
(NULL, 'Import Results', 1, NULL, NULL, 1, NOW()),
(NULL, 'Import Examination Rooms', 1, NULL, NULL, 1, NOW()),
(NULL, 'Competency Setup', 1, NULL, NULL, 1, NOW()),
(NULL, 'question', 1, NULL, NULL, 1, NOW()),
(NULL, 'Custom Module', 1, NULL, NULL, 1, NOW()),
(NULL, 'Survey Questions', 1, NULL, NULL, 1, NOW()),
(NULL, 'Survey Question', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Surveys', 1, NULL, NULL, 1, NOW()),
(NULL, 'Dependent Question', 1, NULL, NULL, 1, NOW()),
(NULL, 'Show Options', 1, NULL, NULL, 1, NOW()),
(NULL, 'Enabled', 1, NULL, NULL, 1, NOW()),
(NULL, 'Dropdown Question Options', 1, NULL, NULL, 1, NOW()),
(NULL, 'Select Question Options', 1, NULL, NULL, 1, NOW()),
(NULL, 'Select Some Options', 1, NULL, NULL, 1, NOW()),
(NULL, 'Dependent On', 1, NULL, NULL, 1, NOW()),
(NULL, 'Select One', 1, NULL, NULL, 1, NOW()),
(NULL, 'Save', 1, NULL, NULL, 1, NOW()),
(NULL, 'Template', 1, NULL, NULL, 1, NOW()),
(NULL, 'Weighting Type', 1, NULL, NULL, 1, NOW()),
(NULL, 'Percentage', 1, NULL, NULL, 1, NOW()),
(NULL, 'rubric', 1, NULL, NULL, 1, NOW()),
(NULL, 'Security Roles', 1, NULL, NULL, 1, NOW()),
(NULL, 'Start', 1, NULL, NULL, 1, NOW()),
(NULL, 'Stop', 1, NULL, NULL, 1, NOW()),
(NULL, 'Running', 1, NULL, NULL, 1, NOW()),
(NULL, 'License Renewal', 1, NULL, NULL, 1, NOW()),
(NULL, 'License Validity', 1, NULL, NULL, 1, NOW()),
(NULL, 'Retirement Warning', 1, NULL, NULL, 1, NOW()),
(NULL, 'Staff Employment', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Features', 1, NULL, NULL, 1, NOW()),
(NULL, 'Method', 1, NULL, NULL, 1, NOW()),
(NULL, 'Rule Setup', 1, NULL, NULL, 1, NOW()),
(NULL, 'Alert Content', 1, NULL, NULL, 1, NOW()),
(NULL, 'Keywords', 1, NULL, NULL, 1, NOW()),
(NULL, 'Processed Date', 1, NULL, NULL, 1, NOW()),
(NULL, 'Target Population Selection', 1, NULL, NULL, 1, NOW()),
(NULL, 'Import Trainees', 1, NULL, NULL, 1, NOW()),
(NULL, 'No Trainee found.', 1, NULL, NULL, 1, NOW()),
(NULL, 'No Trainer found.', 1, NULL, NULL, 1, NOW()),
(NULL, 'Submit for Registration', 1, NULL, NULL, 1, NOW()),
(NULL, 'Pending For Registration', 1, NULL, NULL, 1, NOW()),
(NULL, 'Pending For Review', 1, NULL, NULL, 1, NOW()),
(NULL, 'Pending For Evaluation', 1, NULL, NULL, 1, NOW()),
(NULL, 'Submit For Evaluation', 1, NULL, NULL, 1, NOW()),
(NULL, 'Pending For Posting', 1, NULL, NULL, 1, NOW()),
(NULL, 'Posted', 1, NULL, NULL, 1, NOW()),
(NULL, 'Excel Template', 1, NULL, NULL, 1, NOW()),
(NULL, 'Academic Term', 1, NULL, NULL, 1, NOW()),
(NULL, 'Edit Academic Term', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Templates', 1, NULL, NULL, 1, NOW()),
(NULL, 'Criteria Grading Options', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add New Examination Item', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add Examination Item', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add Examination Centre', 1, NULL, NULL, 1, NOW()),
(NULL, 'Link Examination', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add Special Need Type', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add All Special Need Type', 1, NULL, NULL, 1, NOW()),
(NULL, 'Special Need Type', 1, NULL, NULL, 1, NOW()),
(NULL, 'Select Special Need Type', 1, NULL, NULL, 1, NOW()),
(NULL, 'Special Need Accommodations', 1, NULL, NULL, 1, NOW()),
(NULL, 'Examination Date', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Rooms', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add Invigilator', 1, NULL, NULL, 1, NOW()),
(NULL, 'Linked Institution', 1, NULL, NULL, 1, NOW()),
(NULL, 'Auto Assign To Room', 1, NULL, NULL, 1, NOW()),
(NULL, 'Register for Examination', 1, NULL, NULL, 1, NOW()),
(NULL, 'Single Student Registration', 1, NULL, NULL, 1, NOW()),
(NULL, 'You need to configure Examination Items first', 1, NULL, NULL, 1, NOW()),
(NULL, 'Select Education Level', 1, NULL, NULL, 1, NOW()),
(NULL, 'Select Education Programme', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Education Subject', 1, NULL, NULL, 1, NOW()),
(NULL, 'Author', 1, NULL, NULL, 1, NOW()),
(NULL, 'Publisher', 1, NULL, NULL, 1, NOW()),
(NULL, 'Year Published', 1, NULL, NULL, 1, NOW()),
(NULL, 'Principal Comments Required', 1, NULL, NULL, 1, NOW()),
(NULL, 'Homeroom Teacher Comments Required', 1, NULL, NULL, 1, NOW()),
(NULL, 'Teacher Comments Required', 1, NULL, NULL, 1, NOW()),
(NULL, 'Filters', 1, NULL, NULL, 1, NOW()),
(NULL, 'Workflow Model', 1, NULL, NULL, 1, NOW()),
(NULL, 'Select Workflow', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Workflows', 1, NULL, NULL, 1, NOW()),
(NULL, 'Deletable', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add workflow steps', 1, NULL, NULL, 1, NOW()),
(NULL, 'Workflow Step Name', 1, NULL, NULL, 1, NOW()),
(NULL, 'Workflow Name', 1, NULL, NULL, 1, NOW()),
(NULL, 'No Available Workflow Steps', 1, NULL, NULL, 1, NOW()),
(NULL, 'Workflow Step', 1, NULL, NULL, 1, NOW()),
(NULL, 'Next Workflow Step', 1, NULL, NULL, 1, NOW()),
(NULL, 'Allow By Assignee', 1, NULL, NULL, 1, NOW()),
(NULL, 'Post Events', 1, NULL, NULL, 1, NOW()),
(NULL, 'Add Event', 1, NULL, NULL, 1, NOW()),
(NULL, 'All Models', 1, NULL, NULL, 1, NOW()),
(NULL, 'Workflow Statuses Steps Mapping', 1, NULL, NULL, 1, NOW()),
(NULL, 'Statuses Steps', 1, NULL, NULL, 1, NOW()),
(NULL, 'Changelog', 1, NULL, NULL, 1, NOW()),
(NULL, 'Date Released', 1, NULL, NULL, 1, NOW()),
(NULL, 'Date Approved', 1, NULL, NULL, 1, NOW()),
(NULL, 'Localization', 1, NULL, NULL, 1, NOW()),
(NULL, 'Approved By', 1, NULL, NULL, 1, NOW());

RENAME TABLE `translations` TO `z_3674_translations`;

-- locales
DROP TABLE IF EXISTS `locales`;
CREATE TABLE IF NOT EXISTS  `locales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `iso` varchar(6) NOT NULL,
  `name` varchar(50) NOT NULL,
  `editable` int(1) NOT NULL DEFAULT '1',
  `direction` char(3) NOT NULL DEFAULT 'lrt' COMMENT 'lrt = left to right, ltr = right to left',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- locale_content_translations
DROP TABLE IF EXISTS `locale_content_translations`;
CREATE TABLE IF NOT EXISTS `locale_content_translations` (
  `translation` TEXT NULL,
  `locale_content_id` INT(11) NOT NULL,
  `locale_id` INT(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` INT(11) NOT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`locale_content_id`, `locale_id`),
  INDEX `locale_content_id` (`locale_content_id`),
  INDEX `locale_id` (`locale_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- locale_contents
DROP TABLE IF EXISTS `locale_contents`;
CREATE TABLE IF NOT EXISTS `locale_contents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `en` TEXT NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` INT(11) NOT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `locale_contents` (`id`, `en`, `created_user_id`, `created`)
SELECT `id`, `en`, `created_user_id`, `created`
FROM `z_3674_translations`;

INSERT INTO `locales` (`id`, `iso`, `name`, `editable`, `direction`, `created_user_id`, `created`)
VALUES
(1, 'zh', 'Chinese',0,'ltr', 1, NOW()),
(2, 'ar', 'Arabic',0,'rtl', 1, NOW()),
(3, 'fr', 'French',0,'ltr', 1, NOW()),
(4, 'es', 'Spanish',0,'ltr', 1, NOW()),
(5, 'ru', 'Russian',0,'ltr', 1, NOW());

INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
SELECT `Z`.`zh`, `L`.`id`, 1, `Z`.`created_user_id`, `Z`.`created`
FROM `z_3674_translations` AS `Z`
INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
WHERE `Z`.`en` = `L`.`en`
;

INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
SELECT `Z`.`ar`, `L`.`id`, 2, `Z`.`created_user_id`, `Z`.`created`
FROM `z_3674_translations` AS `Z`
INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
WHERE `Z`.`en` = `L`.`en`
;

INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
SELECT `Z`.`fr`, `L`.`id`, 3, `Z`.`created_user_id`, `Z`.`created`
FROM `z_3674_translations` AS `Z`
INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
WHERE `Z`.`en` = `L`.`en`
;

INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
SELECT `Z`.`es`, `L`.`id`, 4, `Z`.`created_user_id`, `Z`.`created`
FROM `z_3674_translations` AS `Z`
INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
WHERE `Z`.`en` = `L`.`en`
;

INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
SELECT `Z`.`ru`, `L`.`id`, 5, `Z`.`created_user_id`, `Z`.`created`
FROM `z_3674_translations` AS `Z`
INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
WHERE `Z`.`en` = `L`.`en`
;



