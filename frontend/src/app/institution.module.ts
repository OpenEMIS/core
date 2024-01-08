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

export const appRoutes: Routes = [
  { path: 'Dashboard', component: WorkbenchComponent },
  { path: 'Institution/Institutions/Comments', component: CommentsComponent },
  { path: 'Institution/Institutions/Results', component: AssessmentComponent },
  { path: 'Institution/Institutions/StudentAttendances', component: StudentAttendanceComponent},
  { path: 'Institution/Institutions/StudentMeals', component: StudentMealsComponent},
];

function getBaseUrl() {
  if (document.cookie) {
    let base_url: any = document.cookie.split('; ')
      .find(row => row.startsWith(`my_base_url=`)).split('=')
    if (base_url[1]) {
      let setBaseUrl = decodeURIComponent(base_url[1]);
      console.log(setBaseUrl, "setBaseUrl");
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
      WorkbenchComponent
    ],
    imports: [
      BrowserModule,
      SharedModule,
      CommonModule,
      BrowserAnimationsModule,
      HttpClientModule,
      RouterModule.forRoot(appRoutes)
    ],
    providers: [{provide: APP_BASE_HREF, useValue: getBaseUrl()},
    ApiService],
    bootstrap: [CommentsComponent]
  })
  export class IntitutionModule { }