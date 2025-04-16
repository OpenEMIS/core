import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { SharedModule } from './shared/shared.module';
import { RouterModule } from '@angular/router';
import { CommonModule } from '@angular/common';
import { HttpClientModule } from '@angular/common/http';
import { APP_BASE_HREF } from '@angular/common';
import { ApiService } from './api.service';
import { Routes } from '@angular/router';
import { CommentsComponent } from './comments/comments.component';
import { AssessmentComponent } from './assessment/assessment.component';
import { StudentAttendanceComponent } from './student-attendance/student-attendance.component';
import { StudentMealsComponent } from './student-meals/student-meals.component';
import { WorkbenchComponent } from './workbench/workbench.component';
import { AddDirectoryComponent } from './add-directory/add-directory.component';
import { UserDetailsComponent } from './user-details/user-details.component';
import { InternalSearchComponent } from './internal-search/internal-search.component';
import { ExternalSearchComponent } from './external-search/external-search.component';
import { ConfirmationComponent } from './confirmation/confirmation.component';
import { SummaryComponent } from './summary/summary.component';
import { AddStudentComponent } from './add-student/add-student.component';
import { AddStaffComponent } from './add-staff/add-staff.component';
import { AddGuardiansComponent } from './add-guardians/add-guardians.component';
import { StaffAttendanceComponent } from './staff-attendance/staff-attendance.component';
import { ClassesComponent } from './classes/classes.component';
import { SubjectsComponent } from './subjects/subjects.component';
import { AssessmentReportComponent } from './assessment-report/assessment-report.component';
import { StudentAttendanceReportComponent } from './student-attendance-report/student-attendance-report.component';
import { StaffAttendanceReportComponent } from './staff-attendance-report/staff-attendance-report.component';
import { StudentMealImportComponent } from './student-meal-import/student-meal-import.component';
import { StudentMealResultComponent } from './student-meal-result/student-meal-result.component';
import { StudentTimetableComponent } from './student-timetable/student-timetable.component';
import { StudentAttendanceImportResultComponent } from './student-attendance-import-result/student-attendance-import-result.component';
import { StudentAttendanceArchiveComponent } from './student-attendance-archive/student-attendance-archive.component';
import { ScannedStudentComponent } from './scanned/scanned-student/scanned-student.component';
import { StudentListComponent } from './scanned/student-list/student-list.component';

export const appRoutes: Routes = [
  { path: 'Dashboard', component: WorkbenchComponent },
  { path: `Institution/Institutions/Comments/${setEncodedId()}`, component: CommentsComponent },
  { path: 'Institution/Institutions/Results', component: AssessmentComponent },
  { path: 'Institution/Institutions/ResultsReport', component: AssessmentReportComponent },

  { path: `Institution/Institutions/StudentAttendances/index/${setEncodedData()}`, component: StudentAttendanceComponent },
  { path: `Institution/Institutions/${setEncodedData()}/ImportStudentAttendances/add`, component: StudentAttendanceReportComponent },
  { path: `Institution/Institutions/ImportStudentAttendance/results`, component: StudentAttendanceImportResultComponent },
  { path: `Institution/Institutions/InstitutionStudentAbsencesArchived/${setEncodedData()}`, component: StudentAttendanceArchiveComponent },

  { path: `Institution/Institutions/StudentMeals/index/${setEncodedId()}`, component: StudentMealsComponent },
  { path: `Institution/Institutions/${setEncodedData()}/ImportStudentMeals/add`, component: StudentMealImportComponent },
  { path: `Institution/Institutions/ImportStudentMeals/results`, component: StudentMealResultComponent },
  { path: `Institution/Institutions/${setEncodedData()}/StudentMeals`, component: StudentMealsComponent },

  { path: 'Directory/Directories/Directories/add', component: AddDirectoryComponent },
  { path: `Institution/Institutions/${setEncodedId()}/Students/add`, component: AddStudentComponent },
  { path: `Institution/Institutions/${setEncodedId()}/Staff/add`, component: AddStaffComponent },
  { path: 'Institution/Institutions/Addguardian', component: AddGuardiansComponent },
  { path: `Institution/Institutions/${setEncodedId()}/InstitutionStaffAttendances/index`, component: StaffAttendanceComponent },
  { path: `Institution/Institutions/${setEncodedId()}/ImportStaffAttendances/add`, component: StaffAttendanceReportComponent },
  { path: 'Institution/Institutions/Classes', component: ClassesComponent },
  { path: 'Institution/Institutions/Subjects', component: SubjectsComponent },
  { path: `Institution/Institutions/ScheduleTimetable/view/${setEncodedId()}`, component: StudentTimetableComponent },
  { path: `Institution/Institutions/Scanned/index/${setEncodedId()}`, component: ScannedStudentComponent },
  { path: 'Institution/Institutions/Scanned/list', component: StudentListComponent }
];

function setEncodedId() {
  let token = localStorage.getItem('encoded_url');
  if (token) {
    localStorage.setItem("meal_url_data", token);
    return token;
  } else {
    setTimeout(() => {
      this.setEncodedId();
    }, 1000);
  }
}

function setEncodedData() {
  let token = localStorage.getItem('meal_url_data');
  if (token) {
    return token;
  } else {
    setTimeout(() => {
      this.setEncodedId();
    }, 1000);
  }
}

function getBaseUrl() {
  if (document.cookie) {
    let base_url: any = document.cookie.split('; ')
      .find(row => row.startsWith(`my_base_url=`))?.split('=')
    if (base_url && base_url[1]) {
      let setBaseUrl = decodeURIComponent(base_url[1]);
      if (setBaseUrl == '/') {
        return '/';
      }
      return `${setBaseUrl}`
    } else {
      return '/'
    }
  } else {
    return '/'
  }
}

@NgModule({
  declarations: [
    CommentsComponent,
    AssessmentComponent,
    StudentAttendanceComponent,
    StudentMealsComponent,
    WorkbenchComponent,
    AddDirectoryComponent,
    UserDetailsComponent,
    InternalSearchComponent,
    ExternalSearchComponent,
    ConfirmationComponent,
    SummaryComponent,
    AddStudentComponent,
    AddStaffComponent,
    AddGuardiansComponent,
    StaffAttendanceComponent,
    ClassesComponent,
    SubjectsComponent,
    AssessmentReportComponent,
    StudentAttendanceReportComponent,
    StaffAttendanceReportComponent,
    StudentMealImportComponent,
    StudentMealResultComponent,
    StudentTimetableComponent,
    StudentAttendanceImportResultComponent,
    StudentAttendanceArchiveComponent,
    ScannedStudentComponent,
    StudentListComponent
  ],
  imports: [
    BrowserModule,
    SharedModule,
    CommonModule,
    BrowserAnimationsModule,
    HttpClientModule,
    RouterModule.forRoot(appRoutes),
  ],
  providers: [{ provide: APP_BASE_HREF, useValue: getBaseUrl() },
    ApiService],
  bootstrap: [CommentsComponent]
})

export class IntitutionModule { }
