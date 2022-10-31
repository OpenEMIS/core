<?php

use Phinx\Migration\AbstractMigration;

class POCOR3674 extends AbstractMigration
{
    // commit
    public function up()
    {
        // backup translations table
        $this->execute('CREATE TABLE `z_3674_translations` LIKE `translations`');
        $this->execute('INSERT INTO `z_3674_translations` SELECT * FROM `translations`');

        // delete unused word in translations table
        $this->execute('DELETE FROM `translations` WHERE `id` = 2 And `en` = "Welcome"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 11 And `en` = "Settings"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 13 And `en` = "Demo"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 15 And `en` = "Day"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 16 And `en` = "Month"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 17 And `en` = "Year"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 20 And `en` = "Unisex"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 29 And `en` = "Previous"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 30 And `en` = "Next"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 31 And `en` = "Statistics"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 32 And `en` = "Institutions Sites"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 33 And `en` = "Activities"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 35 And `en` = "has been edited"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 36 And `en` = "has been deleted"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 37 And `en` = "has been added to the List of"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 38 And `en` = "By"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 40 And `en` = "Note: Max upload file size is 2MB."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 41 And `en` = "Image"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 42 And `en` = "Document"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 44 And `en` = "Powerpoint"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 45 And `en` = "File is deleted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 46 And `en` = "File was deleted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 47 And `en` = "Error occurred while deleting file."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 48 And `en` = "Files have been saved successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 49 And `en` = "Some errors have been encountered while saving files."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 50 And `en` = "Records have been added/updated successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 51 And `en` = "Records have been deleted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 52 And `en` = "Error occurred while deleting record."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 53 And `en` = " have been deleted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 54 And `en` = "Searching..."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 56 And `en` = "Please enter a unique Identification No"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 57 And `en` = "Please enter a unique Code"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 58 And `en` = "List of Students"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 64 And `en` = "More"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 65 And `en` = "Additional Info"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 66 And `en` = "Student Information"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 67 And `en` = "Static fields"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 69 And `en` = "National Assessments"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 71 And `en` = "Assessment Results"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 72 And `en` = "Student Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 73 And `en` = "Student History"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 76 And `en` = "Student Identification No, First Name or Last Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 77 And `en` = "Student Identification"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 78 And `en` = "Teacher Identification"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 79 And `en` = "Staff Identification"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 80 And `en` = "Identification No."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 81 And `en` = "Identification No"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 90 And `en` = "Uploaded On"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 96 And `en` = "Birth Place Area"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 100 And `en` = "E-mail"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 105 And `en` = "No history found."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 106 And `en` = "List of Staff"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 108 And `en` = "Staff Information"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 109 And `en` = "Staff Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 110 And `en` = "No Staff found."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 111 And `en` = "Staff History"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 112 And `en` = "List of Teachers"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 113 And `en` = "Add new Teacher"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 118 And `en` = "Teacher Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 119 And `en` = "Teacher History"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 120 And `en` = "Date of Issue"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 121 And `en` = "Certificate"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 122 And `en` = "Certificate No."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 123 And `en` = "Issued By"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 127 And `en` = "No Teacher found."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 129 And `en` = "Please enter a valid First Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 130 And `en` = "Please enter a valid Last Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 131 And `en` = "Please enter a valid Identification No"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 132 And `en` = "Please select a Gender"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 135 And `en` = "Please select a Date of Birth"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 137 And `en` = "Please enter a valid username"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 138 And `en` = "This username is already in use."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 139 And `en` = "Password must be at least 6 characters"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 140 And `en` = "Please enter a valid password"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 141 And `en` = "You need to assign a role to the user"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 143 And `en` = "No Areas"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 145 And `en` = "Edit Permissions"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 146 And `en` = "Edit Role - Area Restricted"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 147 And `en` = "Role - Area Restricted"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 148 And `en` = "Edit Role Assignment"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 149 And `en` = "Role Assignment"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 150 And `en` = "Edit Roles"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 154 And `en` = "Edit Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 155 And `en` = "Edit Additional Info"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 156 And `en` = "Edit Programmes"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 160 And `en` = "Site Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 161 And `en` = "Site Code"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 163 And `en` = "Institution Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 166 And `en` = "Site Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 170 And `en` = "Province"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 171 And `en` = "District"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 172 And `en` = "LLG"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 173 And `en` = "Ward"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 174 And `en` = "Street"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 175 And `en` = "Block"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 176 And `en` = "Axis"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 177 And `en` = "State"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 184 And `en` = "Please enter a valid Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 185 And `en` = "Please enter a valid Code"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 186 And `en` = "Please select a Provider"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 187 And `en` = "Please select a Status"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 188 And `en` = "Please select the Date Opened"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 190 And `en` = "Please select an Ownership"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 191 And `en` = "Please select an Area"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 192 And `en` = "Institution Code"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 193 And `en` = "Institution Name or Code"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 201 And `en` = "Add New"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 206 And `en` = "Branch"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 207 And `en` = "Bank Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 208 And `en` = "National Education System"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 210 And `en` = "Seats"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 215 And `en` = "Totals"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 217 And `en` = "Source"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 218 And `en` = "Nature"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 220 And `en` = "Edit Teachers"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 221 And `en` = "Edit Training"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 222 And `en` = "Edit Bank Accounts"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 223 And `en` = "Edit Classes"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 224 And `en` = "Edit Finances"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 225 And `en` = "Edit Other Forms"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 226 And `en` = "Edit Infrastructure"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 227 And `en` = "Trained Teachers"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 228 And `en` = "No Available Programmes"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 229 And `en` = "Institution History"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 231 And `en` = "My Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 232 And `en` = "has been updated successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 233 And `en` = "Change Password"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 234 And `en` = "successfully updated."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 235 And `en` = "Please try again later."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 236 And `en` = "Please enter your current password"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 237 And `en` = "Current password does not match."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 238 And `en` = "New password required."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 239 And `en` = "Please enter a min of 6 alpha numeric characters."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 240 And `en` = "Please enter alpha numeric characters."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 241 And `en` = "Passwords do not match."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 243 And `en` = "Support"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 245 And `en` = "Edit My Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 255 And `en` = "Orientation"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 257 And `en` = "Certification"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 259 And `en` = "January"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 260 And `en` = "February"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 261 And `en` = "March"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 262 And `en` = "April"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 263 And `en` = "May"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 264 And `en` = "June"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 265 And `en` = "July"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 266 And `en` = "August"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 267 And `en` = "September"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 268 And `en` = "October"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 269 And `en` = "November"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 270 And `en` = "December"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 271 And `en` = "Your session is timed out. Please login again."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 272 And `en` = "You are not an authorized user."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 273 And `en` = "You have encountered an unexpected error. Please contact the system administrator for assistance."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 274 And `en` = "Host Unreachable"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 275 And `en` = "Host is unreachable, please check your internet connection."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 276 And `en` = "Session Timed Out"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 277 And `en` = "Page not found"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 278 And `en` = "The requested page cannot be found."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 279 And `en` = "Please contact the administrator for assistance."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 280 And `en` = "An unexpected error has occurred."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 281 And `en` = "JSON parse failed"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 282 And `en` = "Invalid JSON data."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 283 And `en` = "Request Timeout"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 284 And `en` = "Request Aborted"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 285 And `en` = "Your request has been aborted."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 286 And `en` = "Unexpected Error"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 287 And `en` = "Edit System Configurations"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 288 And `en` = "File is updated successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 289 And `en` = "Error occurred while updating file."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 290 And `en` = "File have been updated successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 291 And `en` = "File has not been updated successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 292 And `en` = "File format not supported."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 293 And `en` = "Image has exceeded the allow file size of"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 294 And `en` = "Please reduce file size."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 295 And `en` = "Image resolution is too small."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 296 And `en` = "Error"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 298 And `en` = "log does not exists"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 299 And `en` = "Add new Institution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 300 And `en` = "Institution Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 306 And `en` = "Census"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 307 And `en` = "Enrolment"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 313 And `en` = "Other Forms"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 320 And `en` = "Edit Custom Fields"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 321 And `en` = "Custom Table"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 322 And `en` = "Edit Custom Table"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 324 And `en` = "Accounts and Security"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 327 And `en` = "Population"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 328 And `en` = "Data Processing"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 329 And `en` = "Generate Reports"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 331 And `en` = "Processes"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 332 And `en` = "Database"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 333 And `en` = "Backup"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 334 And `en` = "Restore"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 337 And `en` = "Graduates not required."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 339 And `en` = "Message Not Found."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 340 And `en` = "Good"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 341 And `en` = "Fair"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 342 And `en` = "Poor"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 348 And `en` = "Please enter your current password."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 353 And `en` = "Edit Area Levels"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 354 And `en` = "Structure"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 360 And `en` = "Order"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 362 And `en` = "ISCED Level"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 366 And `en` = "Grade Subject"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 367 And `en` = "Back to Programmes"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 368 And `en` = "Back to Grades"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 369 And `en` = "Graduates"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 370 And `en` = "Please select a programme first."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 372 And `en` = "an option"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 373 And `en` = "School Year"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 375 And `en` = "Available"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 376 And `en` = "Categories"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 378 And `en` = "Materials"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 379 And `en` = "Resources"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 380 And `en` = "Furniture"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 381 And `en` = "Energy"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 383 And `en` = "Sanitation"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 384 And `en` = "Water"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 385 And `en` = "Banks"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 386 And `en` = "Capital Income"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 387 And `en` = "Capital Expenditure"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 388 And `en` = "Recurrent Income"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 389 And `en` = "Recurrent Expenditure"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 391 And `en` = "Instructional"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 392 And `en` = "Support Services"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 393 And `en` = "Facilities"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 394 And `en` = "Qualification Certificates"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 395 And `en` = "Qualification Categories"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 396 And `en` = "Qualification Institutions"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 398 And `en` = "Sources"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 399 And `en` = "Branches"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 400 And `en` = "Sanitations"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 402 And `en` = "Single Line Text"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 403 And `en` = "Multi Line Text"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 404 And `en` = "Dropdown List"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 406 And `en` = "Checkboxes"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 407 And `en` = "Institution Custom Fields"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 410 And `en` = "Student Custom Fields"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 411 And `en` = "Teacher Custom Fields"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 412 And `en` = "Staff Custom Fields"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 414 And `en` = "Field Label"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 416 And `en` = "Filter by"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 417 And `en` = "X Category"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 418 And `en` = "Y Category"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 420 And `en` = "Dashboard Image"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 421 And `en` = "Back to Config"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 423 And `en` = "Date Format"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 424 And `en` = "Currency"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 426 And `en` = "Back to List"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 427 And `en` = "Reconnecting..."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 430 And `en` = "Education Management Information System"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 438 And `en` = "Custom Tables"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 441 And `en` = "Modules"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 444 And `en` = "Retype Password"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 445 And `en` = "User Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 446 And `en` = "Full access on all modules"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 447 And `en` = "Back to Roles"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 449 And `en` = "PHP Version"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 450 And `en` = "Web Server"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 451 And `en` = "Operating System"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 452 And `en` = "Data not available."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 453 And `en` = "The selected report is currently being processed."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 455 And `en` = "Student Reports"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 456 And `en` = "Teacher Reports"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 458 And `en` = "Consolidated Reports"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 459 And `en` = "Indicator Reports"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 460 And `en` = "Data Quality Reports"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 462 And `en` = "Custom Reports"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 463 And `en` = "Please contact"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 465 And `en` = "for more information on Custom Reports."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 466 And `en` = "Last Run"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 469 And `en` = "List of Institutions"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 476 And `en` = "Institution Programme Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 477 And `en` = "List of Institutions with programmes"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 478 And `en` = "Institution Bank Account Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 479 And `en` = "List of Institutions with bank accounts"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 480 And `en` = "Enrolment Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 482 And `en` = "Class Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 486 And `en` = "Teacher Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 490 And `en` = "Staff Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 496 And `en` = "Income Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 498 And `en` = "Expenditure Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 504 And `en` = "Sanitation Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 505 And `en` = "Summary of sanitation, gender and condition from census"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 506 And `en` = "Furniture Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 508 And `en` = "Resource Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 510 And `en` = "Energy Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 512 And `en` = "Water Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 514 And `en` = "Student Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 515 And `en` = "Report on student"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 518 And `en` = "Teacher List"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 519 And `en` = "Report on Teachers"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 522 And `en` = "Staff List"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 523 And `en` = "Report on Staff"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 526 And `en` = "Wheres My School Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 527 And `en` = "A Google Earth (KML) file containing all the location of all Institutions"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 528 And `en` = "Year Book Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 530 And `en` = "Return Rate"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 531 And `en` = "Census Discrepancy"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 532 And `en` = "Backup files found."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 534 And `en` = "Consolidated"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 536 And `en` = "Summary of School\'s Census Data"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 537 And `en` = "Non-Responsive Schools Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 539 And `en` = "Data Discrepancy Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 541 And `en` = "Number of students (enrollment) by sex, age, locality and grade"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 542 And `en` = "Number of teachers by sex, locality and grade"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 551 And `en` = "Divide the number of pupils (or students) enrolled who are of the official age group for a given level of education by the population for the same age group and multiply the result by 100. This indicator has dimension values of sex, locality and grade."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 554 And `en` = "Divide the number of children of official primary school-entrance age who enter the first grade of primary education for the first time by the population of the same age, and multiply the result by 100. This indicator has dimension values of sex and locality."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 556 And `en` = "Divide the number of pupils (or students) enrolled in a given level of education regardless of age by the population of the age group which officially corresponds to the given level of education, and multiply the result by 100. This indicator has dimension values of sex, locality and grade."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 557 And `en` = "Divide the number of new entrants in grade 1, irrespective of age, by the population of official school-entrance age, and multiply the result by 100. This indicator has dimension values of sex, locality and grade."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 559 And `en` = "Divide the number of repeaters in a given grade in school year t+1 by the number of pupils from the same cohort enrolled in the same grade in the previous school year t . This indicator has dimension values of sex, locality, sector and grade."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 561 And `en` = "Divide the number of primary graduates, irrespective of age, by the population of theoretical primary graduation, and multiply the result by 100. This indicator has dimension values of sex, locality and sector."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 563 And `en` = "Divide the total number of pupils belonging to a school-cohort who reached each successive grade of the specified level of education by the number of pupils in the school-cohort i.e. those originally enrolled in the first grade of primary education, and multiply the result by 100. The survival rate is calculated on the basis of the reconstructed cohort method, which uses data on enrolment and repeaters for two consecutive years. This indicator has dimension values of sex, locality and sector."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 565 And `en` = "Divide the number of new entrants in the first grade of the specified higher cycle or level of education by the number of pupils who were enrolled in the final grade of the preceding cycle or level of education in the previous school year, and multiply by 100. This indicator has dimension values of sex, locality, sector and grade."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 567 And `en` = "Divide the total number of pupils enrolled at the specified level of education by the number of teachers at the same level. This indicator has dimension values of locality, sector and level of education."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 571 And `en` = "Divide the number of teachers of the specified level of education who have received the minimum required teacher training by the total number of teachers at the same level of education, and multiply the result by 100. This indicator has dimension values of locality, sector and level of education."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 573 And `en` = "Divide the number of female students by the number of male students. This indicator has dimension values of sex, locality, sector and level of education."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 575 And `en` = "Divide the number of female tertiary students enrolled in a specified ISCED level by the total number of students (male plus female) in that level in a given academic-year, and multiply the result by 100. This indicator has dimension values of locality, sector and level of education."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 577 And `en` = "Divide the total number of female teachers at a given level of education by the total number of teachers (male and female) at the same level in a given school year, and multiply by 100. This indicator has dimension values of locality, sector and level of education."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 579 And `en` = "Divide the number of pupils (or students) enrolled in private educational institutions in a given level of education by total enrolment (public and private) at the same level of education, and multiply the result by 100. This indicator has dimension values of sex, locality and level of education."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 581 And `en` = "Divide the total number of pupils enrolled at the specified level of education by the number of textbooks at the same level. This indicator has dimension values of locality, and sector."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 583 And `en` = "Number of water sources by type. This indicator has dimension values of locality, sector and condition."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 585 And `en` = "Number of sanitation facilities by type. This indicator has dimension values of locality, sector and condition."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 594 And `en` = "Process"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 595 And `en` = "Abort All"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 596 And `en` = "Clear All"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 597 And `en` = "Started By"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 598 And `en` = "Started Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 599 And `en` = "Finished Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 600 And `en` = "Log"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 603 And `en` = "Aborted"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 605 And `en` = "Export To"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 607 And `en` = "No Data"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 608 And `en` = "GNP"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 610 And `en` = "No Available Finance Records"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 614 And `en` = "Security Users, Roles, and Functions"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 615 And `en` = "System Configuration Values"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 618 And `en` = "Run Reports"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 619 And `en` = "Select All"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 620 And `en` = "De-Select All"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 621 And `en` = "Generate"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 622 And `en` = "Generated Files"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 625 And `en` = "Below are the list of available backup dates, please choose a restore point."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 626 And `en` = "Files"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 627 And `en` = "Format not support."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 628 And `en` = "Image filesize too large."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 629 And `en` = "Resolution too large."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 630 And `en` = "File uploaded with success."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 631 And `en` = "Image exceeds system max filesize."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 632 And `en` = "Image exceeds max file size in the HTML form."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 633 And `en` = "Image was only partially uploaded."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 634 And `en` = "No image was uploaded."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 635 And `en` = "Missing a temporary folder."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 636 And `en` = "Failed to write file to disk."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 637 And `en` = "A PHP extension stopped the file upload."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 638 And `en` = "Max Resolution:"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 639 And `en` = "Max File Size:"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 640 And `en` = "Format Supported:"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 641 And `en` = "Profile Image"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 642 And `en` = "Please enter the code for the Area."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 643 And `en` = "There are duplicate area code."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 644 And `en` = "Please enter the name for the Area."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 645 And `en` = "Please supply a valid image."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 646 And `en` = "Please enter a name for the Field of Study."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 647 And `en` = "This Field of Study already exists in the system."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 648 And `en` = "Please select the programme orientation."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 649 And `en` = "Please enter a name for the Subject."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 650 And `en` = "This subject already exists in the system."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 651 And `en` = "You have entered an invalid username or password."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 652 And `en` = "Please enter a duration."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 653 And `en` = "Add Programme"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 655 And `en` = "Deleting attachment..."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 656 And `en` = "Delete Attachment"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 657 And `en` = "Do you wish to delete this record?"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 658 And `en` = "Updating attachment..."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 659 And `en` = "Bank Branch is required!"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 662 And `en` = "Unsaved Data"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 664 And `en` = "Are you sure you want to leave?"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 666 And `en` = "Category is required!"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 667 And `en` = "Certificate is required!"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 669 And `en` = "Please select a country before adding new records."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 670 And `en` = "Error has occurred."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 671 And `en` = "Age cannot be empty."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 672 And `en` = "Age must be more then 0."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 675 And `en` = "Dialog"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 676 And `en` = "Required Field"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 677 And `en` = "Retrieving..."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 678 And `en` = "Adding row..."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 679 And `en` = "Adding option..."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 681 And `en` = "Loading list..."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 682 And `en` = "File is required!"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 683 And `en` = "Status is required!"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 684 And `en` = "Move Up"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 685 And `en` = "Move Down"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 686 And `en` = "Toggle this field active/inactive"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 687 And `en` = "Delete Confirmation"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 688 And `en` = "Click to dismiss"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 689 And `en` = "Unable to add Areas.<br/>Please create Area Level before adding Areas."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 690 And `en` = "Saving please wait..."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 691 And `en` = "Loading Areas"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 694 And `en` = "Adding Field..."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 695 And `en` = "Please select a valid Start Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 696 And `en` = "Please select a valid End Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 697 And `en` = "Please add a programme to this institution site."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 698 And `en` = "Missing Coordinates Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 699 And `en` = "List of Institutions with latitude and/or longitude values of 0 or null"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 710 And `en` = "Area Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 711 And `en` = "Education Programme Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 713 And `en` = "Bank Account Number"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 714 And `en` = "Bank Account Active"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 716 And `en` = "Academic Year"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 717 And `en` = "Education Grade Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 719 And `en` = "Education Subject Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 720 And `en` = "No Of Textbooks"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 721 And `en` = "Grid X Category"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 722 And `en` = "Grid Y Category"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 724 And `en` = "Material"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 726 And `en` = "Resource"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 728 And `en` = "Teacher Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 729 And `en` = "Staff Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 732 And `en` = "Institution Provider"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 733 And `en` = "Institution Status (حالة المدرسة)"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 740 And `en` = "Student Category"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 742 And `en` = "Yearbook"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 743 And `en` = "Organization Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 744 And `en` = "Publication Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 748 And `en` = "Page Orientation"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 749 And `en` = "Yearbook Logo"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 750 And `en` = "Maximum Student Age"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 751 And `en` = "Minimum Student Age"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 752 And `en` = "Maximum Student Number"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 753 And `en` = "Minimum Student Number"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 756 And `en` = "Previous Year"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 757 And `en` = "Previous Year Male"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 758 And `en` = "Previous Year Female"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 759 And `en` = "Data Discrepancy Reports"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 762 And `en` = "Quality - Rubrics"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 770 And `en` = "View Rubric"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 772 And `en` = "Quality"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 773 And `en` = "Rubric Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 774 And `en` = "Quality - Rubric Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 775 And `en` = "Quality - Edit Rubric Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 776 And `en` = "Add Heading"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 777 And `en` = "Add Criteria Row"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 778 And `en` = "Add Level Column"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 779 And `en` = "Edit Rubric Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 782 And `en` = "Quality - Rubric"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 783 And `en` = "Supervisor"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 784 And `en` = "Quality - Rubric Detail"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 787 And `en` = "Quality - Visit"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 793 And `en` = "Quality - Add Rubric"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 794 And `en` = "Add Rubric"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 795 And `en` = "Quality - Rubric Infomations"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 796 And `en` = "Rubric Infomations"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 797 And `en` = "Quality - Edit Rubric"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 798 And `en` = "Edit Rubric"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 799 And `en` = "Quality - Setup Rubric Criteria"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 800 And `en` = "Setup Rubric Criteria"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 801 And `en` = "Quality - Add Rubric Criteria"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 802 And `en` = "Add Rubric Criteria"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 803 And `en` = "Quality - Edit Rubric Criteria"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 804 And `en` = "Edit Rubric Criteria"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 805 And `en` = "Quality - Rubric Criteria"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 806 And `en` = "Rubric Criteria"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 807 And `en` = "Quality - Status"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 808 And `en` = "Quality - Add Status"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 809 And `en` = "Quality - Edit Status"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 810 And `en` = "Quality - Add Rubrics"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 811 And `en` = "Quality - Edit Rubrics"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 812 And `en` = "Quality - Add Visit"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 813 And `en` = "Quality - Edit Visit"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 815 And `en` = "Identity Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 816 And `en` = "Edit Identity Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 818 And `en` = "Issued"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 819 And `en` = "Expiry"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 822 And `en` = "Issue Location"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 832 And `en` = "Goal / Objectives"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 833 And `en` = "Category / Field of Study"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 834 And `en` = "Target Population"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 835 And `en` = "Add Target Population"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 841 And `en` = "Prerequisite"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 842 And `en` = "Add Prerequisite"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 843 And `en` = "Pass Result"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 845 And `en` = "Courses Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 846 And `en` = "Sessions Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 847 And `en` = "Inactivate"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 848 And `en` = "Activate"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 852 And `en` = "Results Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 854 And `en` = "Self-Study"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 855 And `en` = "Credit"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 857 And `en` = "Training Needs Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 858 And `en` = "Priority"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 860 And `en` = "Training Results Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 861 And `en` = "Training Self Study"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 862 And `en` = "Training Self Study Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 864 And `en` = "Credits"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 866 And `en` = "Your data has been saved successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 867 And `en` = "Please select a Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 868 And `en` = "Please enter a valid Message"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 869 And `en` = "Please enter a valid Number"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 870 And `en` = "Please enter a valid Issue Location"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 871 And `en` = "Expiry Date must be greater than Issue Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 873 And `en` = "Nationality Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 874 And `en` = "Edit Nationality Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 875 And `en` = "Please select a Country"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 878 And `en` = "Messages"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 879 And `en` = "Responses"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 883 And `en` = "Add Message"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 884 And `en` = "Message Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 885 And `en` = "Edit Message Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 886 And `en` = "All logs have been deleted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 887 And `en` = "All responses have been deleted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 889 And `en` = "Sent"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 890 And `en` = "Received"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 891 And `en` = "Warning"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 892 And `en` = "Continue"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 893 And `en` = "Note: Please clear the Responses page as existing responses may no longer match the updated Messages."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 894 And `en` = "Do you wish to clear all records?"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 895 And `en` = "Date/Time"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 896 And `en` = "Confirmation"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 898 And `en` = "Do you wish to inactivate this record?"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 899 And `en` = "Do you wish to activate this record?"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 902 And `en` = "Mode of Deliveries"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 903 And `en` = "Priorities"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 905 And `en` = "Requirements"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 907 And `en` = "Edit Field Options"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 909 And `en` = "Identification No, First Name or Last Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 910 And `en` = "Pass"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 913 And `en` = "Please add an area level to this area"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 914 And `en` = "There are no assessments"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 918 And `en` = "Build"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 924 And `en` = "Quality Assurance Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 926 And `en` = "Time Periods"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 927 And `en` = "Advance Search"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 928 And `en` = "OpenEMIS ID, First Name or Last Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 931 And `en` = "Date of Death"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 932 And `en` = "No File Chosen"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 933 And `en` = "Max Resolution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 934 And `en` = "Your request has been timed out. Please try again."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 935 And `en` = "OVERWRITE ALL"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 937 And `en` = "Choose File"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 938 And `en` = "File have not been updated successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 939 And `en` = "Completion Rate / Gross Primary Graduation Ratio"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 940 And `en` = "Divide the number of primary graduates, irrespective of age, by the population of theoretical primary graduation, and multiply the result by 100."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 941 And `en` = "Percentage of Trained Teachers"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 943 And `en` = "Percent of Female Students"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 944 And `en` = "Percent of Female Teachers"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 945 And `en` = "Percentage of Private Enrolment"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 946 And `en` = "Percentage of schools with improved drinking water sources"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 947 And `en` = "Percentage of schools with adequate sanitation facilities"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 949 And `en` = "Divide public current expenditure devoted to each level of education by the total public current expenditure on education, and multiply the result by 100. Divide public current expenditure on education in a given financial year by the total public expenditure on education for the same financial year and multiply the result by 100."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 964 And `en` = "Report on conditions, number, kinds of water source of Institutions"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 965 And `en` = "Student List"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 969 And `en` = "List of institution\'s additional info"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 971 And `en` = "Class List"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 972 And `en` = "Report on classes"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 981 And `en` = "Institution List"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 982 And `en` = "Report on institutions"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 990 And `en` = "Wizard"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 991 And `en` = "Skip"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 992 And `en` = "have been deleted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 993 And `en` = "Edit Attachments"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 995 And `en` = "Partners"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 996 And `en` = "Verifications"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1001 And `en` = "Summary of sanitation, gender and conditions from census"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1005 And `en` = "Add Provider"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1007 And `en` = "Course Prerequisite"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1008 And `en` = "Add Course Prerequisite"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1009 And `en` = "There are no assessments."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1014 And `en` = "Kindergarten"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1015 And `en` = "Number of Shifts"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1017 And `en` = "Student Prefix"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1018 And `en` = "Teacher Prefix"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1019 And `en` = "Staff Prefix"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1021 And `en` = "Institution Telephone"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1022 And `en` = "Institution Fax"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1023 And `en` = "Institution Postal Code"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1027 And `en` = "Student Telephone"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1028 And `en` = "Student Postal Code"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1029 And `en` = "Teacher Telephone"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1030 And `en` = "Teacher Postal Code"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1031 And `en` = "Staff Telephone"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1032 And `en` = "Staff Postal Code"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1034 And `en` = "LDAP Server"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1036 And `en` = "Base DN"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1038 And `en` = "Where\'s My School Config"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1039 And `en` = "Where is my School URL"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1040 And `en` = "Starting Longitude"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1041 And `en` = "Starting Latitude"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1042 And `en` = "Starting Range"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1043 And `en` = "SMS Provider URL"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1044 And `en` = "SMS Number"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1045 And `en` = "SMS Content"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1046 And `en` = "SMS Retry Times"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1047 And `en` = "SMS Retry Delay"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1048 And `en` = "Credit Hour"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1050 And `en` = "Default Country"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1051 And `en` = "System Defined Roles"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1052 And `en` = "User Defined Roles"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1057 And `en` = "Report on all completed courses by staff, date, location and results"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1065 And `en` = "Report of trainers by name, course and date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1082 And `en` = "Attendance Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1084 And `en` = "Assessment Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1099 And `en` = "Student Assessment Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1106 And `en` = "Standard"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1108 And `en` = "New Surveys"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1109 And `en` = "Completed Surveys"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1111 And `en` = "Sync"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1127 And `en` = "Family"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1134 And `en` = "REPORT"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1143 And `en` = "Staff Label"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1144 And `en` = "Check Box"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1146 And `en` = "Hours per Week"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1147 And `en` = "Commencement Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1148 And `en` = "Document No."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1149 And `en` = "Qualification Title"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1151 And `en` = "No position found"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1152 And `en` = "Total days absent"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1153 And `en` = "Total days attended"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1155 And `en` = "Last day "');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1156 And `en` = "First day"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1157 And `en` = "Total Days"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1158 And `en` = "List of Behaviour"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1159 And `en` = "No record available"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1160 And `en` = "Net"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1163 And `en` = "Gross"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1167 And `en` = "Ended"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1168 And `en` = "Commenced"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1171 And `en` = "Training Achievements"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1172 And `en` = "Health - History"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1173 And `en` = "Health - Family"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1174 And `en` = "Health - Immunizations"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1175 And `en` = "Health - Medications"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1176 And `en` = "Health - Medication"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1177 And `en` = "Health - Allergies"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1178 And `en` = "Health - Tests"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1179 And `en` = "Health - Consultations"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1180 And `en` = "File size"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1181 And `en` = "Please enter a valid OpenEMIS ID"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1182 And `en` = "Dashboards"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1187 And `en` = "Geographical Level"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1196 And `en` = "Owned"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1197 And `en` = "Rented"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1198 And `en` = "Both"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1199 And `en` = "Permanent"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1201 And `en` = "Urban"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1202 And `en` = "Rural"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1203 And `en` = "Administrative"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1204 And `en` = "Technical"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1205 And `en` = "Download CSV"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1206 And `en` = "Weightings"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1208 And `en` = "Add Header"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1211 And `en` = "Edit Criteria"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1212 And `en` = "Criteria Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1213 And `en` = "Quality Assurance Reports"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1214 And `en` = "Quality Assurance"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1215 And `en` = "Report generated at the school, FD and national level for each aspect (category) and domain (technical or administrative) "');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1216 And `en` = "QA Schools Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1217 And `en` = "Report generated by type, category, result (pass or fail) and a table with the average, minimum and maximum values and also compare results from one year to the next "');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1218 And `en` = "QA Results Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1219 And `en` = "Report generation for those who hasn\'t completed the rubric"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1220 And `en` = "QA Rubric Not Completed Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1221 And `en` = "Maximum 150 words per comment"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1224 And `en` = "Target Grades"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1225 And `en` = "Security Role"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1226 And `en` = "Setup Criteria Column"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1227 And `en` = "Header"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1229 And `en` = "Descriptors"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1230 And `en` = "New row has been added at the bottom of the rubric table."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1231 And `en` = "Header / Sub-Header / Title"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1232 And `en` = "Section Header"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1233 And `en` = "Add Section Header"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1235 And `en` = "Add Grade"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1236 And `en` = "Add Visit"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1237 And `en` = "Visit"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1238 And `en` = "Edit Rubric Headers"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1239 And `en` = "Edit Headers"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1241 And `en` = "Create Rubric Table"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1242 And `en` = "Reorder Criteria"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1244 And `en` = "Edit Status"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1245 And `en` = "Add Status"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1246 And `en` = "Criteria Level Description"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1247 And `en` = "View Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1248 And `en` = "Weightage"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1249 And `en` = "Selected Grade(s)"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1250 And `en` = "Status Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1251 And `en` = "Rubric Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1253 And `en` = "Pass/Fail"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1255 And `en` = "QA Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1256 And `en` = "Visit Report"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1257 And `en` = "Reports - Quality"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1260 And `en` = "Fail"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1261 And `en` = "Quality Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1262 And `en` = "Visit Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1263 And `en` = "Evaluator Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1264 And `en` = "Total Classes"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1265 And `en` = "Maximum"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1266 And `en` = "Minimum"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1267 And `en` = "Average"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1268 And `en` = "Pass/ Fail"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1269 And `en` = "Total Questions"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1270 And `en` = "Total Answered"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1272 And `en` = "Goal Objective"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1273 And `en` = "Requirement"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1274 And `en` = "Trainer Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1276 And `en` = "Target Group"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1277 And `en` = "Total Target Group"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1278 And `en` = "Total Trained"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1279 And `en` = "Target Group Percentage"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1280 And `en` = "Last Updated By"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1281 And `en` = "Behaviour - Staff"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1282 And `en` = "Behaviour - Students"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1283 And `en` = "File size should not be larger than 2MB."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1284 And `en` = "Maximum 5 files are permitted on single upload. Each file size should not be larger than 2MB."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1286 And `en` = "User"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1287 And `en` = "Add Result Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1288 And `en` = "Goal / objective"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1289 And `en` = "Internal"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1290 And `en` = "External"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1292 And `en` = "Overall Result"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1294 And `en` = "Edit Results Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1296 And `en` = "Upload Results"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1297 And `en` = "Passed"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1298 And `en` = "Failed"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1299 And `en` = "Upload File"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1300 And `en` = "Upload"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1301 And `en` = "Invalid File Format"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1302 And `en` = "Columns/Data do not match."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1303 And `en` = "Error encountered, record(s) could not be updated"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1309 And `en` = "1=Pass/0=Fail)"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1310 And `en` = "Need Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1312 And `en` = "Achievement Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1313 And `en` = "Course Goal / Objectives"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1314 And `en` = "Add Training Needs"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1317 And `en` = "Add Achievements"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1318 And `en` = "Achievements Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1319 And `en` = "Edit Achievements"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1320 And `en` = "TrainingSessionTrainee"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1321 And `en` = "Approval"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1322 And `en` = "Reject"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1323 And `en` = "Experience"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1326 And `en` = "Months"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1327 And `en` = "Add Experience"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1328 And `en` = "Add Specialisation"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1329 And `en` = "Upload Trainee"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1332 And `en` = "Edit Sessions Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1333 And `en` = "Evaluation Tools"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1337 And `en` = "List of translations"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1338 And `en` = "Add Translation"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1339 And `en` = "Edit Translation"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1340 And `en` = "Translation Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1341 And `en` = "Please ensure the english translation is keyed in."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1342 And `en` = "Download Trainees"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1343 And `en` = "Download Trainee Results"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1344 And `en` = "Duplicate Session"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1353 And `en` = "Shared Reports"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1354 And `en` = "My Reports"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1364 And `en` = "Select Image"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1366 And `en` = "Change"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1367 And `en` = "There is no data to be displayed."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1371 And `en` = "s"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1374 And `en` = "Period"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1376 And `en` = "Shift Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1380 And `en` = "Edit Shift"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1381 And `en` = "Add Shift"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1382 And `en` = "Add Attachment"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1383 And `en` = "Please enter a File name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1384 And `en` = "No file was uploaded"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1385 And `en` = "The files has been uploaded"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1386 And `en` = "Attachment Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1390 And `en` = "Position Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1391 And `en` = "Edit Position"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1392 And `en` = "All Years"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1394 And `en` = "Number:"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1400 And `en` = "Unit"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1402 And `en` = "Step 1"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1403 And `en` = "Step 2"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1404 And `en` = "Step 3"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1405 And `en` = "Step 4"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1406 And `en` = "Step 5"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1407 And `en` = "Step 6"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1408 And `en` = "Step 7"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1409 And `en` = "Step 8"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1411 And `en` = "Dimension"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1413 And `en` = "Review"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1416 And `en` = "Area ID"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1417 And `en` = "Table"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1418 And `en` = "Column"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1420 And `en` = "Bar"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1422 And `en` = "Line"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1425 And `en` = "Visualization"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1427 And `en` = "List of Classes"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1429 And `en` = "Verify"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1430 And `en` = "Data Entry"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1431 And `en` = "Estimate"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1432 And `en` = "Full Time Equivalent Teachers"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1435 And `en` = "Multi Grade Classes"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1437 And `en` = "There are no subjects configured in the system."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1443 And `en` = "Add existing Student"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1444 And `en` = "Add existing Staff"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1452 And `en` = "مرفع"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1453 And `en` = "متكرر"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1485 And `en` = "Area (Education"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1490 And `en` = "Requester"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1492 And `en` = "Due Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1495 And `en` = "Student Transfer"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1496 And `en` = "Transfer of student"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1499 And `en` = "National Number"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1500 And `en` = "Expelled"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1503 And `en` = "Dropout"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1513 And `en` = "Current Academic Period"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1515 And `en` = "Openemis No"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1522 And `en` = "Mark"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1524 And `en` = "Area (Education)"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1526 And `en` = "Full-Time"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1527 And `en` = "Part-Time"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1529 And `en` = "No configured options"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1530 And `en` = "Select Role"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1534 And `en` = "Infrastructure Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1540 And `en` = "Country -"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1543 And `en` = "Number Of Students By Grade"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1555 And `en` = "There is no programme set for this institution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1563 And `en` = "Special Need Comment"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1567 And `en` = "Absent - Excused"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1568 And `en` = "Absent - Unexcused"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1569 And `en` = "Present"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1570 And `en` = "No Classes"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1576 And `en` = "FTE"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1577 And `en` = "Excused"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1578 And `en` = "Unexcused"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1593 And `en` = "Leave Status"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1595 And `en` = "Leaves"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1596 And `en` = "Qualification Institution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1598 And `en` = "Qualification Level"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1601 And `en` = "Institution Country"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1602 And `en` = "Qualification Specialisation"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1614 And `en` = "- Country"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1617 And `en` = "Submit"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1618 And `en` = "Save As Draft"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1625 And `en` = "No Students Found"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1631 And `en` = "School Principal"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1632 And `en` = "المنطقة الإدارية - Country"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1635 And `en` = "Default Shift"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1636 And `en` = "Default Shift 2014/2015"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1638 And `en` = "Select Student"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1641 And `en` = "Student Attendance"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1646 And `en` = "Openemisno"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1648 And `en` = "Security User"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1649 And `en` = "Amount (JD)"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1663 And `en` = "Student Absence Reason"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1665 And `en` = "Copyright © 2015 OpenEMIS. All rights reserved."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1675 And `en` = "Row Number"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1676 And `en` = "The record is not added due to errors encountered"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1678 And `en` = "This School"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1679 And `en` = "Other School"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1703 And `en` = "Area (Administrative) - Country"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1704 And `en` = "Area (Administrative)"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1705 And `en` = "land"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1707 And `en` = "Building Inst Year"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1708 And `en` = "Building Future Expansion"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1710 And `en` = "Yearly Rent Cost"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1711 And `en` = "Building WCstatus"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1713 And `en` = "Land Area"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1714 And `en` = "Building Land Number"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1715 And `en` = "Building Water Availability"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1716 And `en` = "Building Deflation Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1717 And `en` = "Building Model"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1718 And `en` = "Building Electricity Availability"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1719 And `en` = "Building seq"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1720 And `en` = "Building Bed Number"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1725 And `en` = "Staff Account"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1726 And `en` = "Staff User"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1727 And `en` = "Are you sure you want to delete this record"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1728 And `en` = "Delete is not allowed as students still exists in class"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1729 And `en` = "InstitutionSections"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1730 And `en` = "Please review the information before proceeding with the operation"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1735 And `en` = "OpenEmis ID"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1738 And `en` = "Current Education Grade"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1740 And `en` = "Students have been transferred"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1741 And `en` = "Are you sure you want to delete this record."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1746 And `en` = "Student User"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1748 And `en` = "Student Surveys"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1749 And `en` = "student surveys"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1750 And `en` = "students surveys"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1752 And `en` = "Area (Administrative)"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1753 And `en` = "No Other Student Available"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1756 And `en` = "Sunday"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1757 And `en` = "Monday"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1758 And `en` = "Tuesday"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1759 And `en` = "Wednesday"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1760 And `en` = "Thursday"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1761 And `en` = "Friday"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1762 And `en` = "Saturday"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1764 And `en` = "No Available Subjects"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1765 And `en` = "Next grade in the Education Structure is not available in this Institution."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1771 And `en` = "Our Shifts"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1772 And `en` = "External Shifts"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1775 And `en` = "Add All Students"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1779 And `en` = "Transfer Approvals"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1780 And `en` = "Approve"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1781 And `en` = "Application Status"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1783 And `en` = "Student Dropout Reason"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1787 And `en` = "Not In School>"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1788 And `en` = "Absence is already added for this date and time."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1789 And `en` = "Academic Period needs to be set as current"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1790 And `en` = "Add Assessment Item"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1794 And `en` = "Add Teacher"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1795 And `en` = "Add to Section"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1796 And `en` = "An unexpected error has been encounted. Please contact the administrator for assistance."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1798 And `en` = "Assessment record has been saved to draft successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1799 And `en` = "Assessment record has been submitted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1800 And `en` = "Both passwords do not match"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1802 And `en` = "Class name and Home Room Teacher should not be empty"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1803 And `en` = "Class name should not be empty"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1806 And `en` = "Date Closed should not be earlier than Date Opened"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1807 And `en` = "Date of Birth cannot be future date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1809 And `en` = "Date To should not be earlier than Date From"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1813 And `en` = "Dropout request has been approved successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1814 And `en` = "Dropout request has been rejected successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1815 And `en` = "Dropout request hsa been submitted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1816 And `en` = "Duplicate Code Identified"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1817 And `en` = "Duplicate OpenEMIS ID Identified"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1818 And `en` = "Duplicate Unique Key on the same sheet"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1820 And `en` = "End Date should not be earlier than Start Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1821 And `en` = "Error Message"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1822 And `en` = "Event"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1823 And `en` = "Execution Time"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1824 And `en` = "Expiry Date Is Required"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1825 And `en` = "External Trainer"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1826 And `en` = "failed to import completely."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1827 And `en` = "failed to import."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1828 And `en` = "Failed to revert student records."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1829 And `en` = "Failed to save grades"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1830 And `en` = "Failed Validation"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1831 And `en` = "File format not supported"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1832 And `en` = "File is required"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1833 And `en` = "File records exceeds maximum rows allowed"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1834 And `en` = "File records exceeds maximum size allowed"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1835 And `en` = "First Day Of Absence"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1837 And `en` = "Grading"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1838 And `en` = "Home Room Teacher should not be empty"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1839 And `en` = "Incorrect password."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1840 And `en` = "Internal Trainer"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1841 And `en` = "Invalid Code"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1842 And `en` = "is successfully imported."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1843 And `en` = "Issue Date cannot be later than Expiry Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1845 And `en` = "Last Day Of Absence"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1846 And `en` = "Level Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1847 And `en` = "New staff is not added to the institutition as there are no available FTE for the selected position."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1848 And `en` = "New staff is not added to the institutition, due to an error"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1850 And `en` = "No available FTE."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1851 And `en` = "No Available Grades"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1852 And `en` = "No Available Grades in this Institution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1853 And `en` = "No Available Institutions"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1854 And `en` = "No Available Levels"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1855 And `en` = "No Available Periods"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1857 And `en` = "No Available Staff"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1859 And `en` = "No Available Surveys"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1860 And `en` = "No Available Trainees"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1861 And `en` = "No Education Grade was selected."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1862 And `en` = "No Grades"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1863 And `en` = "No Grades Assigned"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1864 And `en` = "No identifiable survey found"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1865 And `en` = "No other alternative options available to convert records."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1866 And `en` = "No Record"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1867 And `en` = "No Record has been selected / saved."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1868 And `en` = "No record were found in the file imported"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1870 And `en` = "No Students"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1873 And `en` = "Not able to add absence record as this student is no longer enrolled in the institution."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1874 And `en` = "Not available to revert."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1875 And `en` = "Not supported in this form."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1876 And `en` = "Only alphabets and numbers are allowed"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1877 And `en` = "Password should not contain spaces"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1879 And `en` = "Please confirm your new password"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1880 And `en` = "Please enter a Contact Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1881 And `en` = "Please enter a number between 0 and 5"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1882 And `en` = "Please enter a unique identity number."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1883 And `en` = "Please enter a unique OpenEMIS ID"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1884 And `en` = "Please enter a valid amount."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1885 And `en` = "Please enter a valid Award."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1886 And `en` = "Please enter a valid Comment"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1887 And `en` = "Please enter a valid Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1888 And `en` = "Please enter a valid Graduate Year"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1889 And `en` = "Please enter a valid Gross Salary"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1890 And `en` = "Please enter a valid Hours."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1891 And `en` = "Please enter a valid Institution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1892 And `en` = "Please enter a valid Issuer."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1893 And `en` = "Please enter a valid Latitude"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1894 And `en` = "Please enter a valid License Number."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1895 And `en` = "Please enter a valid Longitude"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1896 And `en` = "Please enter a valid Major/Specialisation"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1897 And `en` = "Please enter a valid Membership."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1898 And `en` = "Please enter a valid Middle Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1899 And `en` = "Please enter a valid Net Salary"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1900 And `en` = "Please enter a valid Numeric value"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1901 And `en` = "Please enter a valid Preferred Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1902 And `en` = "Please enter a valid Qualification Level"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1903 And `en` = "Please enter a valid Qualification Title"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1904 And `en` = "Please enter a valid Third Name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1905 And `en` = "Please enter a valid Title"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1906 And `en` = "Please enter a valid value"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1907 And `en` = "Please enter a valid Value"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1908 And `en` = "Please enter an Account name"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1909 And `en` = "Please enter an Account number"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1910 And `en` = "Please enter an alphanumeric username"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1911 And `en` = "Please enter your new password"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1912 And `en` = "Please review the information before proceeding with the operation."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1913 And `en` = "Please select a Bank"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1914 And `en` = "Please select a Bank Branch"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1915 And `en` = "Please select a Language"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1916 And `en` = "Please select a preferred contact type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1917 And `en` = "Please select a Salary Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1918 And `en` = "Please select a valid License Type."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1919 And `en` = "Please select a valid Special Need Type."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1920 And `en` = "Please select an institution location."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1921 And `en` = "Please upload image format files. Eg. jpg, png, gif."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1922 And `en` = "Remote authentication failed, please try local login."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1923 And `en` = "Rows Failed:"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1924 And `en` = "Rows Imported:"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1925 And `en` = "Rows Updated:"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1926 And `en` = "Rubric record has been saved to draft successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1927 And `en` = "Rubric record has been submitted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1930 And `en` = "Select Staff"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1931 And `en` = "Select Teacher"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1932 And `en` = "Staff has already been added."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1933 And `en` = "Start Date cannot be later than End Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1934 And `en` = "Start Date should not be earlier than Institution Date Opened"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1935 And `en` = "Student admission has been approved successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1936 And `en` = "Student admission has been rejected successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1937 And `en` = "Student exists in the school"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1938 And `en` = "Student has already been added to admission list"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1939 And `en` = "Student has already been added."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1940 And `en` = "Student has already been enrolled in another Institution."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1941 And `en` = "Student has already dropped out from the school."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1942 And `en` = "Student is already exists in the new school"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1943 And `en` = "Student records have been reverted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1945 And `en` = "Students have been transferred."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1947 And `en` = "Survey code is missing from the file. Please make sure that survey code exists on sheet"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1948 And `en` = "Survey record has been saved to draft successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1949 And `en` = "Survey record has been submitted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1950 And `en` = "Survey Template"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1953 And `en` = "The chosen academic period is not editable"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1954 And `en` = "The file"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1955 And `en` = "The language has been successfully compiled."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1956 And `en` = "The language has not been compiled due to errors encountered."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1957 And `en` = "The position number that you have entered already existed, please try again."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1958 And `en` = "The record cannot be deleted as there are still records associated with it."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1959 And `en` = "The record does not exist."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1960 And `en` = "The record exists in the system."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1962 And `en` = "The record has been deleted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1963 And `en` = "The record has been duplicated successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1964 And `en` = "The record has been rejected successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1967 And `en` = "The record is not deleted due to errors encountered."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1968 And `en` = "The record is not duplicated due to errors encountered."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1969 And `en` = "The record is not rejected due to errors encountered."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1970 And `en` = "The record is not saved due to errors encountered."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1972 And `en` = "The staff has already exist within the start date and end date specified."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1973 And `en` = "There are no available Classes"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1974 And `en` = "There are no available Education Grade."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1975 And `en` = "There are no available Education Level."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1976 And `en` = "There are no available Education Programme."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1977 And `en` = "There are no available Education Subjects."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1978 And `en` = "There are no available FTE for this position."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1980 And `en` = "There are no available Students for revert Student Status."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1981 And `en` = "There are no available Students for Transfer."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1982 And `en` = "There are no position available."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1983 And `en` = "There are no students selected."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1984 And `en` = "There are no subjects in the assigned grade"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1985 And `en` = "There is a pending dropout application for this student at the moment, please reject the dropout application before making another request."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1986 And `en` = "There is a pending transfer application for this student at the moment, please remove the"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1987 And `en` = "There is no active institution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1988 And `en` = "There is no class under the selected academic period"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1989 And `en` = "There is no grade selected"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1990 And `en` = "There is no rubric section selected"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1991 And `en` = "There is no subject selected"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1992 And `en` = "This code already exists in the system"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1993 And `en` = "This Education Programme already exists in the system"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1995 And `en` = "This identity has already existed in the system."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1996 And `en` = "This record is not editable"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1997 And `en` = "This rubric record is not submitted due to criteria answers is not complete."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1998 And `en` = "This student does not fall within the allowed age range for"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 1999 And `en` = "This student has already been enrolled in an institution."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2000 And `en` = "This student was removed from the institution earlier"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2001 And `en` = "This translation is already exists"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2002 And `en` = "Total Amount Exceeded Outstanding Amount"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2004 And `en` = "Total Rows:"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2005 And `en` = "Transfer request has been approved successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2006 And `en` = "Transfer request has been rejected successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2007 And `en` = "Transfer request has been submitted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2011 And `en` = "Wrong template file"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2012 And `en` = "You cannot remove a not enrolled student from the institution."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2013 And `en` = "You do not have access to this Class."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2014 And `en` = "You do not have access to this location."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2015 And `en` = "You have entered an invalid date."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2016 And `en` = "You have entered an invalid time."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2017 And `en` = "You have entered an invalid url."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2018 And `en` = "You have not been authorised to add an institution into that area."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2019 And `en` = "You need to configure Academic Periods first."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2020 And `en` = "You need to configure Academic Periods for Promotion / Graduation."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2021 And `en` = "You need to configure Classes first."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2022 And `en` = "You need to configure Education Programmes first."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2023 And `en` = "You need to configure Grading Types first."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2024 And `en` = "You need to configure Guardian Education Level first."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2025 And `en` = "You need to configure Guardian Relations first."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2026 And `en` = "You need to configure Institution Grades first."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2027 And `en` = "You need to configure Institution Site Positions first."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2028 And `en` = "You need to configure Result Types under Training Course."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2029 And `en` = "You need to configure Security Roles first."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2030 And `en` = "You need to configure Staff Types first."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2031 And `en` = "You need to configure Student Statuses first."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2032 And `en` = "You need to configure Workflows for this form."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2033 And `en` = "Your account has been disabled."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2036 And `en` = "Trim Openemis No"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2041 And `en` = "Absence - Excused"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2043 And `en` = "Absence - Unexcused"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2045 And `en` = "Late"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2056 And `en` = "Staff Change Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2057 And `en` = "Change in FTE"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2058 And `en` = "Change in Staff Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2059 And `en` = "Institution Position"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2061 And `en` = "Currently Assigned To"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2062 And `en` = "Requested By"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2063 And `en` = "By clicking save, a transfer request will be sent to the institution for approval"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2066 And `en` = "To Be Approved BY"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2068 And `en` = "Current Institution Position"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2069 And `en` = "Current FTE"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2070 And `en` = "Current Staff Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2072 And `en` = "Current Start Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2078 And `en` = "Transfer of Staff"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2080 And `en` = "Staff Position Profiles"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2081 And `en` = "To Be Approved By"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2085 And `en` = "Absence Reasons"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2089 And `en` = "No Available Academic Periods"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2090 And `en` = "Dropout Requests"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2091 And `en` = "Student Dropout"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2093 And `en` = "All Levels"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2094 And `en` = "Current Institution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2095 And `en` = "Requested Institution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2097 And `en` = "Requested Institution Position"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2098 And `en` = "Requested FTE"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2100 And `en` = "Requested Staff Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2101 And `en` = "Requested Start Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2103 And `en` = "Please Define Default Identity Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2105 And `en` = "Staff identity is mandatory"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2107 And `en` = "Student identity is mandatory"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2111 And `en` = "Admission"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2113 And `en` = "Change in Staff Assignment"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2118 And `en` = "Institution Grades"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2120 And `en` = "Convert From"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2121 And `en` = "Convert To"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2122 And `en` = "No Available Options"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2123 And `en` = "InstitutionClasses"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2126 And `en` = "Institution Subjects"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2133 And `en` = "Mother"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2134 And `en` = "Father"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2151 And `en` = "Current Week"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2153 And `en` = "Week"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2156 And `en` = "Institution > Positions"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2157 And `en` = "Transfer of staff"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2159 And `en` = "Admission of student"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2164 And `en` = "Other Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2166 And `en` = "Previous Institution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2168 And `en` = "No of records"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2169 And `en` = "Apply To"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2176 And `en` = "Hi"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2179 And `en` = "Date of Application"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2185 And `en` = "Institutions with No Students"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2187 And `en` = "Institutions with No Staff"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2188 And `en` = "All Academic Periods"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2189 And `en` = "All Types"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2201 And `en` = "Guardian National No"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2207 And `en` = "End Of Assignment"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2215 And `en` = "from"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2217 And `en` = "to"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2219 And `en` = "Land"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2221 And `en` = "Floor"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2223 And `en` = "Single Shift Owner"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2241 And `en` = "Requested On"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2243 And `en` = "Please set other identity type as default before deleting the current one"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2244 And `en` = "There are no shifts configured for the selected academic period"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2252 And `en` = "Student is already enrolled in another school"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2258 And `en` = "Student is already enrolled in another school"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2260 And `en` = "Student is already enrolled in another school."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2262 And `en` = "Institution Information"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2268 And `en` = "Special Need Difficulty"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2272 And `en` = "Please enter a valid format"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2274 And `en` = "Is Academic"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2278 And `en` = "Change Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2282 And `en` = "Update Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2286 And `en` = "Change in Room Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2288 And `en` = "Maintenance"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2296 And `en` = "Academic Institution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2298 And `en` = "Academic Institution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2300 And `en` = "-Select Area--"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2314 And `en` = "Student Transfer Approval"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2322 And `en` = "Date of Birth"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2326 And `en` = "Create New Student"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2328 And `en` = "New Student Details"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2330 And `en` = "Request for Change in Assignment has been submitted successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2332 And `en` = "Change in Assignment"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2336 And `en` = "Students status changed successfully"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2344 And `en` = "Students without Class"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2346 And `en` = "Training Hours"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2382 And `en` = "Exam"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2384 And `en` = "Practical"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2394 And `en` = "Student has already completed the selected grade."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2400 And `en` = "This student is already allocated to"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2401 And `en` = "The student is added to the Pending Admission list successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2402 And `en` = "Complete"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2404 And `en` = "New Room Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2405 And `en` = "There is no programme set for available Academic Period on this institution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2406 And `en` = "There are no shifts configured for the selected academic period, will be using system configuration timing"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2432 And `en` = "Non-Academic Institution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2433 And `en` = "No Class Assignment"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2440 And `en` = "Undo Examination Registration"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2446 And `en` = "New Examination Centre"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2448 And `en` = "Special Need Types"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2450 And `en` = "No Institutions Linked"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2453 And `en` = "Select Invigilators"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2455 And `en` = "Select Students"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2457 And `en` = "Select Some Options"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2460 And `en` = "Examination Centre Information"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2462 And `en` = "Existing Institution"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2463 And `en` = "Institution Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2469 And `en` = "Students status changed successfully."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2470 And `en` = "Examination Centres"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2471 And `en` = "Examination Item Results"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2472 And `en` = "Examination Centre Students"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2477 And `en` = "Comment Type"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2482 And `en` = "Staff Trainings"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2497 And `en` = "Reactivate"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2504 And `en` = "There is a pending transfer application for this student at the moment, please remove the transfer application before making another request."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2505 And `en` = "Student has been transferred to"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2506 And `en` = "after registration"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2507 And `en` = "You need to configure Assessment Periods first"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2508 And `en` = "No Rows To Show"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2509 And `en` = "Edit operation is not allowed as the record already End of Usage."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2510 And `en` = "The selected students are pending for transfer approval."');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2511 And `en` = "Reopen"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2512 And `en` = "Institution Students"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2514 And `en` = "Staff End Date"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2515 And `en` = "Pinding For Approval"');
        $this->execute('DELETE FROM `translations` WHERE `id` = 2520 And `en` = "Synchronisation"');

        // words will be added to translation table
        $words = [
            'Allocated To',
            'Textbook ID',
            'Condition',
            'Profile',
            'Profiles',
            'Height',
            'Body Mass Index',
            'Body Mass',
            'Promote From',
            'Promote To',
            'Region',
            'Zone',
            'Shared',
            'Textbook',
            'Textbook Condition',
            'Textbook Conditions',
            'Textbook Status',
            'Indexes',
            'Number Of Risk Index',
            'Indexes Criterias',
            'Institution Student Indexes',
            'Operator',
            'Less than or equal to',
            'Threshold',
            'References',
            'Generate',
            'Generated By',
            'Generated On',
            'Not Generated',
            'Total Index',
            'Counsellings',
            'Counselling',
            'Intervention',
            'Counselor',
            'Guidance Type',
            'File Name',
            'All associated information related to this record will also be removed. Are you sure you want to delete this record?',
            'This field is required.',
            'Not active homeroom teacher',
            'Not active teaching staff',
            'Number Of Student By Stage',
            'Number Of Staff By Type',
            'Number Of Staff By Year',
            'Delete operation is not allowed as there are other information linked to this record.',
            'File attachment is required',
            'Secondary Staff',
            'Secondary Teacher',
            'Multi-grade',
            'Secondary Home Room Teacher',
            'Select Textbooks',
            'Select Education Subject',
            'Select Education Grade',
            'Select Academic Term',
            'All Genders',
            'No Room Allocated',
            'Teacher(s)',
            'Past Teachers',
            'Institution Textbooks',
            'No Textbooks',
            'Add Textbook',
            'Pending Withdraw',
            'Withdraw',
            'Withdrawn',
            'The current password was not matched.',
            'Password should contain at least 1 uppercase character',
            'Password should contain at least 1 number',
            'Password should contain at least 1 non-alphanumeric character',
            'Both passwords do not match',
            'There must be at least one Preferred Nationality',
            'Relation',
            'Edit Profile',
            'Edit Relation',
            'Guardian User',
            'No Guardian found.',
            'No Guardian',
            'No Results',
            'No Examination Results',
            'No Student Fees',
            'Report Card',
            'Report Cards',
            'transition',
            'Last Executer',
            'Last Execution Date',
            'Behaviour Classification',
            'Classifications',
            'Submit For Verification & Authentication',
            'Rating',
            'Competency Set',
            'Competencies / Goals',
            'Competencies',
            'Competency',
            'Import Salaries',
            'Submit For Approval',
            'Training Need Category',
            'Applications',
            'Apply',
            'Number Of Months',
            'Number Of Hours',
            'Number Of Years',
            'Training Field Of Study',
            'Training Course Type',
            'Training Mode Of Delivery',
            'Field of Study',
            'Field Of Studies',
            'All Days',
            'Behaviour Classification',
            'Linked Cases',
            'Case Number',
            'Student Competencies',
            'All Competencies',
            'Competency Template',
            'Competency Period',
            'Competency Item',
            'Competency Assessment period',
            'Changes will be automatically saved when any value is changed',
            'All Items',
            'Items',
            'Competency Items',
            'Competency Item',
            'Competency Grading Type',
            'No Assessments',
            'Student result will be save after the result has been entered.',
            'Examination Results',
            'Report Card Comments',
            'Report Cards',
            'Report Card',
            'Select Report Card',
            'All Report Cards',
            'Report Card Statuses',
            'Statuses',
            'Payments',
            'Add Payment',
            'Add New Payment',
            'All Land Types',
            'Institution Lands',
            'Land Type',
            'Land Status',
            'Institution Buildings',
            'All Building Types',
            'Institution Floors',
            'All Floor Types',
            'Floor Type',
            'Floor Status',
            'Institution Rooms',
            'Enrolment Information',
            'Cases',
            'Case Number',
            'Linked Records',
            'The password is automatically generated by the system',
            'Directories',
            'Business',
            'Probation',
            'Extension',
            'Termination',
            'Resignation',
            'Retirement',
            'Temporary',
            'Institutions with No Students',
            'Institutions with No Staff',
            'Area Education Code',
            'Area Administrative Code',
            'Year Opened',
            'Year Closed',
            'Area Code',
            'No Of Seats',
            'Preferred Nationality',
            'All Nationalities',
            'Previous Institution Student',
            'Report Generated',
            'System Usage',
            'No previous login',
            'Logged in within the last 7 days',
            'All Exam Centres',
            'Examination Results',
            'Registered Students by Examination Centre',
            'Not Registered Students',
            'Training Need Type',
            'Pending Review',
            'Session Participants',
            'Institution Types',
            'Synchronize',
            'Areas (Education)',
            'Area Levels (Education)',
            'Areas (Administrative)',
            'Area Levels (Administrative)',
            'Data will be synchronized from',
            'New Area',
            'Missing Area',
            'Security Group Affected',
            'Institution Affected',
            'Area Administrative Level',
            'Is Main Country',
            'Education Level Isced',
            'Cycles',
            'Education Certification',
            'Education Field Of Study',
            'Next Programmes',
            'Cycle - (Programme)',
            'Education Stage',
            'Hours Required',
            'Auto Allocation',
            'Grade Subjects',
            'If this option is set to Yes, students will be allocated automatically to this subject upon enrolment to a class',
            'Stages',
            'Education Programme Orientation',
            'Network Connectivities',
            'Localities',
            'Ownerships',
            'Sectors',
            'Shift Options',
            'Report Card Comment Codes',
            'Student Behaviour Categories',
            'Student Transfer Reasons',
            'Student Withdraw Reasons',
            'Guidance Types',
            'Staff Behaviour Categories',
            'Staff Leave Types',
            'Is Mandatory',
            'Is Unique',
            'Validation Rule',
            'Validation Rules',
            'Rules',
            'Rule',
            'Custom Module',
            'Apply To All',
            'Custom Filters',
            'Add Field',
            'Add Section',
            'Length',
            'Decimal Place',
            'Create Table',
            'Add Columns',
            'Add Rows',
            'Module Name',
            'Field Name',
            'Translations',
            'Label',
            'Default Value',
            'Prefix Value',
            'Enable',
            'Validation Pattern',
            'External Data Source',
            'Attributes',
            'Attribute Name',
            'Product Lists',
            'Student Admission Age',
            'Credentials',
            'Select Criteria',
            'Absences Excused',
            'Overage',
            'Status Repeated',
            'Add Area',
            'Import Institutions.',
            'All Subjects',
            'My Subjects',
            'Import Textbooks',
            'My Classes',
            'Student Profile',
            'Transfer Request',
            'Transfer Approval',
            'Withdraw Request',
            'Student Withdraw',
            'Account Username',
            'Import Student Attendances',
            'Undo Student Status',
            'Competency Results',
            'Competency Comments',
            'Staff Profile',
            'Import Staff Attendances',
            'Transfer Approvals',
            'Change in Staff Assignment',
            'Import Staff',
            'Generate/Download',
            'Publish/Unpublish',
            'Guardian Profile',
            'Student Body Mass',
            'Salary List',
            'Salary Details',
            'Import Staff Salaries',
            'Import Users',
            'Academic Period Levels',
            'Education Systems',
            'Education Levels',
            'Education Cycles',
            'Education Programmes',
            'Education Grade Subjects',
            'Webhooks',
            'Alerts',
            'Alert Rules',
            'Workflows',
            'Steps',
            'Exam Centre Rooms',
            'Exam Centre Exams',
            'Exam Centre Subjects',
            'Exam Centre Students',
            'Exam Centre Invigilators',
            'Exam Centre Linked Institutions',
            'Import Results',
            'Import Examination Rooms',
            'Competency Setup',
            'question',
            'Custom Module',
            'Survey Questions',
            'Survey Question',
            'All Surveys',
            'Dependent Question',
            'Show Options',
            'Enabled',
            'Dropdown Question Options',
            'Select Question Options',
            'Select Some Options',
            'Dependent On',
            'Select One',
            'Save',
            'Template',
            'Weighting Type',
            'Percentage',
            'rubric',
            'Security Roles',
            'Start',
            'Stop',
            'Running',
            'License Renewal',
            'License Validity',
            'Retirement Warning',
            'Staff Employment',
            'All Features',
            'Method',
            'Rule Setup',
            'Alert Content',
            'Keywords',
            'Processed Date',
            'Target Population Selection',
            'Import Trainees',
            'No Trainee found.',
            'No Trainer found.',
            'Submit for Registration',
            'Pending For Registration',
            'Pending For Review',
            'Pending For Evaluation',
            'Submit For Evaluation',
            'Pending For Posting',
            'Posted',
            'Excel Template',
            'Academic Term',
            'Edit Academic Term',
            'All Templates',
            'Criteria Grading Options',
            'Add New Examination Item',
            'Add Examination Item',
            'Add Examination Centre',
            'Link Examination',
            'Add Special Need Type',
            'Add All Special Need Type',
            'Special Need Type',
            'Select Special Need Type',
            'Special Need Accommodations',
            'Examination Date',
            'All Rooms',
            'Add Invigilator',
            'Linked Institution',
            'Auto Assign To Room',
            'Register for Examination',
            'Single Student Registration',
            'You need to configure Examination Items first',
            'Select Education Level',
            'Select Education Programme',
            'All Education Subject',
            'Author',
            'Publisher',
            'Year Published',
            'Principal Comments Required',
            'Homeroom Teacher Comments Required',
            'Teacher Comments Required',
            'Filters',
            'Workflow Model',
            'Select Workflow',
            'All Workflows',
            'Deletable',
            'Add workflow steps',
            'Workflow Step Name',
            'Workflow Name',
            'No Available Workflow Steps',
            'Workflow Step',
            'Next Workflow Step',
            'Allow By Assignee',
            'Post Events',
            'Add Event',
            'All Models',
            'Workflow Statuses Steps Mapping',
            'Statuses Steps',
            'Changelog',
            'Date Released',
            'Date Approved',
            'Localization',
            'Approved By',
            'Training Session Results',
            'Staff Training Needs',
            'Language ISO code should be 2 letters'
        ];

        $wordsData = [];

        foreach ($words as $text) {
            $wordsData[] = [
                'en' => $text,
                'editable' => 1,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ];
        }

        $this->insert('translations', $wordsData);

        // locales table
        $table = $this->table('locales', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the locale languages'
            ]);
        $table
            ->addColumn('iso', 'string', [
                'default' => null,
                'limit' => 2,
                'null' => false
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false
            ])
            ->addColumn('editable', 'integer', [
                'default' => 1,
                'limit' => 1,
                'null' => false
            ])
            ->addColumn('direction', 'char', [
                'default' => 'ltr',
                'limit' => 3,
                'null' => false,
                'comment' => 'lrt = left to right, rtl = right to left'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save()
        ;

        // insert data to locales table
        $localesData = [
            [
                'id' => 1,
                'iso' => 'ar',
                'name' => 'العربية',
                'editable' => 0,
                'direction' => 'rtl',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'iso' => 'zh',
                'name' => '中文',
                'editable' => 0,
                'direction' => 'ltr',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 3,
                'iso' => 'en',
                'name' => 'English',
                'editable' => 0,
                'direction' => 'rtl',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' =>4,
                'iso' => 'fr',
                'name' => 'Français',
                'editable' => 0,
                'direction' => 'ltr',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 5,
                'iso' => 'ru',
                'name' => 'русский',
                'editable' => 0,
                'direction' => 'ltr',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 6,
                'iso' => 'es',
                'name' => 'español',
                'editable' => 0,
                'direction' => 'ltr',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
        ];

        $this->insert('locales', $localesData);
        // end insert data to locales table
        // end locales

        // locale_contents
        // $table = $this->table('locale_contents', [
        //         'collation' => 'utf8mb4_unicode_ci',
        //         'comment' => 'This table contains the words in english'
        //     ]);
        // $table
        //     ->addColumn('en', 'text', [
        //         'default' => null,
        //         'null' => false
        //     ])
        //     ->addColumn('modified_user_id', 'integer', [
        //         'default' => null,
        //         'limit' => 11,
        //         'null' => true
        //     ])
        //     ->addColumn('modified', 'datetime', [
        //         'default' => null,
        //         'null' => true
        //     ])
        //     ->addColumn('created_user_id', 'integer', [
        //         'default' => null,
        //         'limit' => 11,
        //         'null' => false
        //     ])
        //     ->addColumn('created', 'datetime', [
        //         'default' => null,
        //         'null' => false
        //     ])
        //     ->addIndex('modified_user_id')
        //     ->addIndex('created_user_id')
        //     ->save()
        // ;
        // insert data to locale_contents
        $this->execute('
            INSERT INTO `locale_contents` (`id`, `en`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            SELECT `id`, `en`, 2 , NOW(), `created_user_id`, `created`
            FROM `translations`;
        ');
        // end insert data to locale_contents
        // end locale_contents

        // locale_content_translations
        $table = $this->table('locale_content_translations', [
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => 'This table contains the translation of english word'
            ]);
        $table
            ->addColumn('translation', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('locale_content_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to locale_contents.id'
            ])
            ->addColumn('locale_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to locale.id'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('locale_content_id')
            ->addIndex('locale_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save()
        ;
        // insert data to locale_content_translations
        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            SELECT `Z`.`ar`, `L`.`id`, 1, `Z`.`created_user_id`, `Z`.`created`
            FROM `translations` AS `Z`
            INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
            WHERE `Z`.`en` = `L`.`en`
            ;
        '); // ar = arabic

        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            SELECT `Z`.`zh`, `L`.`id`, 2, `Z`.`created_user_id`, `Z`.`created`
            FROM `translations` AS `Z`
            INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
            WHERE `Z`.`en` = `L`.`en`
            ;
        '); // zh = chinese

        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            SELECT `Z`.`fr`, `L`.`id`, 4, `Z`.`created_user_id`, `Z`.`created`
            FROM `translations` AS `Z`
            INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
            WHERE `Z`.`en` = `L`.`en`
            ;
        '); // fr = french

        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            SELECT `Z`.`ru`, `L`.`id`, 5, `Z`.`created_user_id`, `Z`.`created`
            FROM `translations` AS `Z`
            INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
            WHERE `Z`.`en` = `L`.`en`
            ;
        '); // ru = russian

        $this->execute('
            INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`)
            SELECT `Z`.`es`, `L`.`id`, 6, `Z`.`created_user_id`, `Z`.`created`
            FROM `translations` AS `Z`
            INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
            WHERE `Z`.`en` = `L`.`en`
            ;
        '); // es = espanyol
        // end insert data to locale_content_translations
        // end locale_content_translations

        // delete translations table
        $this->execute('DROP TABLE translations');

        $this->execute("UPDATE `security_functions` SET controller = 'LocaleContents', category = 'Localization',  _add = NULL, _delete = NULL WHERE `id` = 5019");

        $data = [
            'id' => '5080',
            'name' => 'Languages',
            'controller' => 'Locales',
            'module' => 'Administration',
            'category' => 'Localization',
            'parent_id' => 5000,
            '_view' => 'index|view',
            '_edit' => 'edit',
            '_add' => 'add',
            '_delete' => 'delete',
            '_execute' => NULL,
            'order' => 171,
            'visible' => 1,
            'description' => NULL,
            'modified_user_id' => NULL,
            'modified' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ];

        $this->insert('security_functions', $data);
    }

    // rollback
    public function down()
    {
        $this->execute("UPDATE `security_functions` SET controller = 'Translations', category = 'Translations',  _add = 'add', _delete = 'remove' WHERE `id` = 5019");
        $this->execute("DELETE FROM `security_functions` WHERE `id` = 5080");

        $this->execute('DROP TABLE `locales`');
        $this->execute('DROP TABLE `locale_contents`');
        $this->execute('DROP TABLE `locale_content_translations`');

        $this->execute('DROP TABLE IF EXISTS `translations`');
        $this->execute('RENAME TABLE `z_3674_translations` TO `translations`');
    }
}
