import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ITableColumn, ITableConfig, KdPageBase, KdPageBaseEvent, KdSplitterEvent } from 'openemis-styleguide-lib';
import { timer } from 'rxjs';
import { IMiniDashboardConfig, IMiniDashboardItem, MINI_DASHBOARD_CONFIG, MINI_DASHBOARD_DATA } from './component.mini-dashboard.config';
import { ApiService } from '../api.service';
import { WORKBENCHTABLECOLUMN } from './config';

@Component({
  selector: 'app-workbench',
  providers: [KdSplitterEvent],
  templateUrl: './workbench.component.html',
  styleUrls: ['./workbench.component.css']
})
export class WorkbenchComponent extends KdPageBase implements OnInit {
  readonly PAGESIZE: number = 10;
  readonly TOTALROWS: number = 500;
  public showFullWidth: boolean = true;
  public _column: Array<ITableColumn>;
  public _row: Array<any>;
  public displayLoading: boolean = true;
  public _config: ITableConfig = {
    id: 'normalTable',
    rowIdKey: "id",
    gridHeight: "auto",
    rowContentHeight: 30,
    loadType: "infinite",
    externalFilter: false,
    paginationConfig: {
      pagesize: this.PAGESIZE,
      total: this.TOTALROWS,
    }
  };

  public counter: number = 0;

  public miniDashboardConfig: IMiniDashboardConfig = MINI_DASHBOARD_CONFIG;
  public miniDashboardData: Array<IMiniDashboardItem> = MINI_DASHBOARD_DATA;
  public workBenchAray: any = [];
  noticeTotal: any;
  noticeData: any;
  constructor(
    _pageEvent: KdPageBaseEvent,
    _router: Router,
    _activatedRoute: ActivatedRoute,
    private _kdSplitterEvent: KdSplitterEvent,
    private Rest: ApiService
  ) {
    super({
      router: _router,
      activatedRoute: _activatedRoute,
      pageEvent: _pageEvent
    });
  }

  ngOnInit(): void {
    this.counter = 0;
    super.setPageTitle('Content with Sub Splitter', false);
    super.setToolbarMainBtns([]);

    super.updatePageHeader();
    super.updateBreadcrumb();

    this.loginData();

    timer(1000).subscribe((): void => {
      this._column = [
        WORKBENCHTABLECOLUMN.Name,
        WORKBENCHTABLECOLUMN.Title,
        WORKBENCHTABLECOLUMN.Institution,
        WORKBENCHTABLECOLUMN.ReceivedDate
      ];
    });
  }

  loginData() {
    this.Rest.setSession();
    let token = localStorage.getItem("loginToken");
    if (!token) {
      let userName = sessionStorage.getItem('username');
      let password = sessionStorage.getItem('password');
      console.log(userName, "userName", password, "password");

      if (userName == null && password == null) {
        setTimeout(() => {
          this.counter = this.counter + 1;
          if (this.counter <= 5) {
            this.loginData();
          } else {
            alert('Please login again')
          }
        }, 1500);
      } else {
        var decodedPassword = atob(password);
        if (userName && decodedPassword) {
          this.loginApi(userName, decodedPassword);
        } else {
          this.removeSession();
        }
      }
    } else {
      this.getAPIlist();
    }
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        console.log(response, "response");
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.getAPIlist();
          this.removeSession();
        }
      },
      error: (error: any) => {

      }
    })
  }

  removeSession() {
    delete sessionStorage.username;
    delete sessionStorage.password;
  }

  getAPIlist() {
    this.studentWithdraw(1);

    this.studentTransferOut(1);

    this.studentTransferIn(1);

    this.studentAdmission(1);

    this.studentBehaviour(1);

    this.staffLeave(1);

    this.surveyForms(1);

    this.staffAppraisal(1);

    this.staffRelease(1);

    this.staffTransferOut(1);

    this.staffTransferIn(1);

    this.staffChangeInAssignment(1);

    this.staffTraining(1);

    this.staffLicenses(1);

    this.admissionTrainingCourses(1);

    this.admissionTrainingSessions(1);

    this.admissionTrainingResults(1);

    this.visitRequests(1);

    this.trainingApplications(1);

    this.scholarshipApplications(1);

    this.institutionsCases(1);

    this.institutionsPositions(1);

    this.Rest.getWithToken('notices?limit=10&page=1').subscribe({
      next: (res: any) => {
        if (res)
          this.noticeTotal = res?.data?.total;
        this.noticeData = res?.data?.data
      },
      error: (error) => {

      }
    })

    this.Rest.getWithToken('minidashboard?limit=1').subscribe({
      next: (res: any) => {
        this.miniDashboardData = [{
          type: 'text',
          icon: 'kd-staff icon',
          label: 'Percentage Complete:',
          value: res?.data?.percentage + '%'
        }]
        this.displayLoading = false;
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
        this.displayLoading = false;
      }
    })
  }

  setRow(data: any) {
    let rowData = [];
    data?.data.forEach((element: any) => {
      let obj = {
        status: element.status,
        request_title: element.request_title,
        institution: element.institution,
        received_date: element.received_date
      }
      rowData.push(obj);
    });
    this._row = rowData;
  }

  studentWithdraw(limit: any) {
    this.Rest.getWithToken('institutions/students/withdraw?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Student Withdraw",
              "key": "student_withdraw"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  studentTransferOut(limit: any) {
    this.Rest.getWithToken('institutions/students/transferout?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Student Transfer Out",
              "key": "student_transfer_out"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  studentTransferIn(limit: any) {
    this.Rest.getWithToken('institutions/students/transferin?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Student Transfer In",
              "key": "student_transfer_in"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  studentAdmission(limit: any) {
    this.Rest.getWithToken('institutions/students/admission?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Student Admission",
              "key": "student_admission"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  studentBehaviour(limit: any) {
    this.Rest.getWithToken('institutions/behaviour/students?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Student Behaviour",
              "key": "student_behaviour"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  staffLeave(limit: any) {
    this.Rest.getWithToken('staff/career/leave?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Staff leave",
              "key": "staff_leave"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  surveyForms(limit: any) {
    this.Rest.getWithToken('institutions/survey/forms?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Survey Forms",
              "key": "survey_forms"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  staffAppraisal(limit: any) {
    this.Rest.getWithToken('staff/career/appraisals?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Staff Appraisals",
              "key": "staff_appraisals"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  staffRelease(limit: any) {
    this.Rest.getWithToken('institutions/staff/release?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Staff Release",
              "key": "staff_release"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  staffTransferOut(limit: any) {
    this.Rest.getWithToken('institutions/staff/transferout?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Staff Transfer Out",
              "key": "staff_transfer_out"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  staffTransferIn(limit: any) {
    this.Rest.getWithToken('institutions/staff/transferin?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Staff Transfer In",
              "key": "staff_transfer_in"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  staffChangeInAssignment(limit: any) {
    this.Rest.getWithToken('institutions/staff/changeinassignment?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Staff Change In Assignment",
              "key": "staff_change_in_assignment"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  staffTraining(limit: any) {
    this.Rest.getWithToken('staff/training/needs?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Staff Training",
              "key": "staff_training"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  staffLicenses(limit: any) {
    this.Rest.getWithToken('staff/professionaldevelopment/licenses?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Staff Licenses",
              "key": "staff_licenses"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  admissionTrainingCourses(limit: any) {
    this.Rest.getWithToken('administration/training/courses?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Administration Training Courses",
              "key": "administration_training_courses"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  admissionTrainingSessions(limit: any) {
    this.Rest.getWithToken('administration/training/sessions?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Administration Training Sessions",
              "key": "administration_training_sessions"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  admissionTrainingResults(limit: any) {
    this.Rest.getWithToken('administration/training/results?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Administration Training Results",
              "key": "administration_training_results"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  visitRequests(limit: any) {
    this.Rest.getWithToken('institutions/visits/requests?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Institutions Visits Request",
              "key": "institutions_visits_request"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  trainingApplications(limit: any) {
    this.Rest.getWithToken('administration/training/applications?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Administration Training Applications",
              "key": "administration_training_applications"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  scholarshipApplications(limit: any) {
    this.Rest.getWithToken('administration/scholarships/applications?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Administration Scholarships Applications",
              "key": "administration_scholarships_applications"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  institutionsCases(limit: any) {
    this.Rest.getWithToken('institutions/cases?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Institutions Cases",
              "key": "institutions_cases"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  institutionsPositions(limit: any) {
    this.Rest.getWithToken('institutions/positions?limit=' + limit).subscribe({
      next: (res: any) => {
        if (limit == 1) {
          if (res && (res?.data?.total > 0)) {
            let obj = {
              "total_value": res?.data?.total,
              "value": "Institutions Positions",
              "key": "institutions_positions"
            }
            this.workBenchAray.push(obj);
          }
        } else {
          if (res?.data) {
            this.setRow(res?.data);
          }
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  showSubContent(data: any): void {
    this._row = [];
    this._kdSplitterEvent.toggleSubPane(true);
    this.showFullWidth = false;
    switch (data?.key) {
      case 'student_withdraw':
        this.studentWithdraw(100)
        break;

      case 'student_transfer_out':
        this.studentTransferOut(100);
        break;

      case 'student_transfer_in':
        this.studentTransferIn(100);
        break;

      case 'student_admission':
        this.studentAdmission(100);
        break;

      case 'student_behaviour':
        this.studentBehaviour(100);
        break;

      case 'staff_leave':
        this.staffLeave(100);
        break;

      case 'survey_forms':
        this.surveyForms(100);
        break;

      case 'staff_appraisals':
        this.staffAppraisal(100);
        break;

      case 'staff_release':
        this.staffRelease(100);
        break;

      case 'staff_transfer_out':
        this.staffTransferOut(100);
        break;

      case 'staff_transfer_in':
        this.staffTransferIn(100);
        break;

      case 'staff_change_in_assignment':
        this.staffChangeInAssignment(100);
        break;

      case 'staff_training':
        this.staffTraining(100);
        break;

      case 'staff_licenses':
        this.staffLicenses(100);
        break;

      case 'administration_training_courses':
        this.admissionTrainingCourses(100);
        break;

      case 'administration_training_sessions':
        this.admissionTrainingSessions(100);
        break;

      case 'administration_training_results':
        this.admissionTrainingResults(100);
        break;

      case 'institutions_visits_request':
        this.visitRequests(100);
        break;

      case 'administration_training_applications':
        this.trainingApplications(100);
        break;

      case 'administration_scholarships_applications':
        this.scholarshipApplications(100);
        break;

      case 'institutions_cases':
        this.institutionsCases(100);
        break;

      case 'institutions_positions':
        this.institutionsPositions(100);
        break;
    }
  }

  hideSubContent(): void {
    this._kdSplitterEvent.toggleSubPane(false);
    this.showFullWidth = true;
  }

  ngOnDestroy(): void {
    super.destroyPageBaseSub();
  }

}
