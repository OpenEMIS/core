import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ITableApi, ITableColumn, KdPageBase, KdPageBaseEvent, KdTable, KdToolbarEvent } from 'openemis-styleguide-lib';
import { MINI_DASHBOARD_CONFIG, TABLE_COLUMN_LIST } from './student_attendance.config';
import { IMiniDashboardConfig, IMiniDashboardItem } from 'openemis-styleguide-lib/kd-components/kd-angular-mini-dashboard/kd-angular-mini-dashboard-interface';
import { Subscription, timer } from 'rxjs';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from '../api.service';
import { DEFAULT_TEMPLATE_THEME } from '../shared/config.default-val';

@Component({
  selector: 'app-student-attendance',
  templateUrl: './student-attendance.component.html',
  styleUrls: ['./student-attendance.component.css']
})

export class StudentAttendanceComponent extends KdPageBase implements OnInit, OnDestroy {
  @ViewChild(KdTable) child: KdTable;
  readonly TABLEID: string = "normalTable";
  readonly PAGESIZE: number = 10;
  readonly TOTALROWS: number = 50000;
  public displayLoading: boolean = false;
  public displayMiniDashboard: boolean = true;

  public breadcrumbList = {
    home: { icon: 'fa fa-home', path: '' },
    list: [{
      name: 'Institutions',
      path: '',
    },
    {
      name: 'Avory Primary School',
      path: '',
    },
    {
      name: 'Student Attendances',
      path: '',
    }]
  };

  public pageheader = {
    leftBtn: [{
      type: "export",
      callback: (): void => {
        this.backToData();
      }
    },
    {
      type: "import",
      callback: (): void => {
        this.editTableFields();
      }
    },
    {
      type: "edit",
      callback: (): void => {
        this.onEditClick();
      }
    },
    {
      icon: "fa kd-null",
      path: '/'
    },
    {
      icon: "fa fa-folder",
      callback: (): void => {
        this.onEditClick();
      }
    },
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }

  public tabsList = [
    { tabName: 'Principal' },
    { tabName: 'Homeroom Teacher' }
  ]

  public miniDashboardConfig: IMiniDashboardConfig = MINI_DASHBOARD_CONFIG;
  public miniDashboardData: Array<IMiniDashboardItem>;
  public _row: Array<any>;
  public _column: Array<ITableColumn>;
  public displayEditTable: boolean = false;
  academicYear: any = [];
  academic_Period: any;
  academic_week: any = [];
  academic_period_week: any;
  start_day: any;
  end_day: any;
  academic_day: any = [];
  academic_period_day: any = '';
  academic_class: any = [];
  selected_academic_class: any;
  attendanceMarkType: any = [];


  public absenceTypeList: any = [];

  public studentAbsenceReasons: any = [];

  public _config: any;

  public _tableApi: ITableApi = {};
  private _toolbarSearchSub: Subscription;
  private _tableSub: Subscription;
  public academicPeriod: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Academic Period',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'disabled': false,
      'options': []
    }
  ]
  public week: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Week',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'disabled': false,
      'options': [],
    }
  ]
  public day: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Day',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'options': []
    }
  ]
  public class: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Class',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'options': []
    }
  ]
  public period: Array<any> = [
    {
      'key': 'radio',
      'label': 'Attendance per day:',
      'visible': true,
      'required': false,
      'controlType': 'radio',
      'type': 'radio',
      'list': [],
      'value': ''
    }
  ]
  public educationGrade: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Education Grade',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'options': [
      ]
    }
  ]

  public institutionSubject: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Subjects:',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'options': []
    }
  ]

  public filterButtons: Array<any> = [
    {
      name: '',
      class: 'd-none'
    },
    {
      name: '',
      class: 'd-none'
    }
  ]

  present: number = 0;
  absent: number = 0;
  late: number = 0;
  education_grade: any[] = [];
  selected_education_grade: any;
  instution_subject: any[] = [];
  selected_institution_subject: any;
  selected_day: any;
  oldRowData: any;
  education_grade_id: any;
  selectedInstitutionSubject: any;
  counter: any = 0;
  institution_id: number;
  themeArray = DEFAULT_TEMPLATE_THEME;
  selected_day_week: any;
  institution_name: any;

  constructor(
    private _router: Router,
    _activatedRoute: ActivatedRoute,
    public pageEvent: KdPageBaseEvent,
    private Rest: ApiService,
    private _toolbarEvent: KdToolbarEvent,

  ) {
    super({
      router: _router,
      activatedRoute: _activatedRoute,
      pageEvent: pageEvent,
    });

  }


  ngOnInit(): void {

    super.setPageTitle("", false);
    super.setToolbarMainBtns([]);
    super.enableToolbarSearch(true);

    super.updatePageHeader();
    super.updateBreadcrumb();
    this.counter = 0;
    this.institution_id = JSON.parse(localStorage.getItem("institution_id"));
    // this.institution_id = 6;
    this.institution_name = localStorage.getItem("institutionName");
    this.pageheader.pageheaderText = `${this.institution_name} - Student Attendances`


    this.loginData();
    timer(100).subscribe((): void => {
      this._column = [
        TABLE_COLUMN_LIST.openEmisId,
        TABLE_COLUMN_LIST.personName,
        TABLE_COLUMN_LIST.student_attendance_select_new,
        TABLE_COLUMN_LIST.reasonOrComment_select_new
      ];
    });
  }

  setDashboard() {
    this.present = 0;
    this.absent = 0;
    this.late = 0;
    this._row.forEach((student, index) => {
      if (student.institution_student_absences.absence_type_id == 0 || student.institution_student_absences.absence_type_id == null) {
        this.present += 1;
      } else if (student.institution_student_absences.absence_type_id == 1 || student.institution_student_absences.absence_type_id == 2) {
        this.absent += 1;
      } else if (student.institution_student_absences.absence_type_id == 3 || student.institution_student_absences.absence_type_id == 3) {
        this.late += 1;
      }
    })

    this.miniDashboardData = [
      {
        type: 'text',
        icon: 'kd-students icon',
        label: 'Total:',
        value: this._row.length
      },
      {
        type: 'text',
        label: 'Students Present',
        value: this.present
      },
      {
        type: 'text',
        label: 'Students Absent',
        value: this.absent
      },
      {
        type: 'text',
        label: 'Students Late',
        value: this.late
      },

    ]
  }

  loginData() {
    this.Rest.setSession();
    let token = localStorage.getItem("loginToken");
    if (!token) {
      let userName = sessionStorage.getItem('username');
      let password = sessionStorage.getItem('password');
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
      this.setTheme();
      this.getAPIData();
      this.absenceTypeAPI();
    }
  }

  setTheme() {
    this.Rest.getWithToken('themes').subscribe({
      next: (response: any) => {
        let selectedThemeData = '';
        if (response?.data[3].value) {
          selectedThemeData = response?.data[3].value;
          selectedThemeData = `#${selectedThemeData}`;
        } else {
          selectedThemeData = response?.data[3].default_value;
          selectedThemeData = `#${selectedThemeData}`;
        }
        this.themeArray.btnGroup[0].dropdownContent.forEach((element: any) => {
          if (element.text == selectedThemeData) {
            document.body.className = element.theme + ' fuelux';
          }
        });
      },
      error: (error: any) => {

      }
    })
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.setTheme();
          this.getAPIData();
          this.absenceTypeAPI();
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

  absenceTypeAPI() {
    this.Rest.getWithToken('absence-types').subscribe({
      next: (response: any) => {
        if (response) {
          this.absenceTypeList = response?.data?.list;
          this.absenceReasonAPI();
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

  absenceReasonAPI() {
    this.Rest.getWithToken('absence-reasons').subscribe({
      next: (response: any) => {
        if (response) {
          this.studentAbsenceReasons = response?.data?.data;
          this._config = {
            id: this.TABLEID,
            rowIdKey: "id",
            gridHeight: "auto",
            rowContentHeight: 60,
            loadType: "normal",
            externalFilter: false,
            paginationConfig: {
              pagesize: this.PAGESIZE,
              total: this.TOTALROWS,
            },
            context: {
              absenceTypes: this.absenceTypeList,
              education_grade_id: 189,
              isMarked: false,
              mode: this.displayEditTable ? 'edit' : 'view',
              period: 1,
              schoolClosed: true,
              studentAbsenceReasons: this.studentAbsenceReasons,
              subject_id: 0,
              week: 49,
              scope: {
                data: []
              }
            }
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

  getAPIData() {
    this.Rest.getWithToken('academic-periods').subscribe({
      next: (response: any) => {
        if (response) {
          response?.data?.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.start_year
            }
            this.academicYear.push(obj);
          });
          this.academic_Period = this.academicYear[0].key;
          this.academicPeriod[0].options = this.academicYear;
          this.getAcademicWeek(this.academic_Period);
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

  getAcademicWeek(id: any) {
    this.academic_Period = id;
    this.Rest.getWithToken('academic-periods/' + this.academic_Period + '/weeks').subscribe({
      next: (response: any) => {
        this.academic_week = [];
        response?.data?.list?.weeks.forEach((element: any) => {
          let obj = {
            key: element?.id,
            value: element?.name,
            start_day: element?.start_day,
            end_day: element?.end_day,
            selected: element?.selected
          }
          if (obj.selected) {
            this.academic_period_week = element.id;
            this.selected_day_week = element?.start_day;
            this.start_day = element?.start_day;
            this.end_day = element?.end_day;
            this.selected_day = element?.selected
          }
          this.academic_week.push(obj);
        });
        let academicWeekData = this.week;
        academicWeekData[0].options = this.academic_week;
        academicWeekData[0].value = this.academic_period_week;
        this.week = [...academicWeekData];

        this.getAcademicDay(this.academic_period_week);
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

  getAcademicDay(id: any) {
    this.academic_period_week = id;
    this.Rest.getWithToken('academic-periods/' + this.academic_Period + '/weeks/' + this.academic_period_week + '/days?institution_id=' + this.institution_id + '&school_closed_required=true').subscribe({
      next: (response: any) => {
        this.academic_day = [];
        this.academic_period_day = '';
        response?.data?.list.forEach((element: any, index: any) => {
          let obj = {
            key: element.date,
            value: element.name,
            current_week_number_selected: element.current_week_number_selected,
            date: element.date,
            selected: element.day_number
          }
          if (obj.selected) {
            this.academic_period_day = element.date;
          }
          this.academic_day.push(obj);
        });
        if (this.academic_period_day == '') {
          this.academic_period_day = this.academic_day[1].key;
        }
        let newDay = this.day;
        newDay[0].options = this.academic_day;
        newDay[0].value = this.academic_period_day;
        this.day = [...newDay];
        this.getClassData(this.academic_period_day);
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

  getClassData(id: any) {
    this.academic_period_day = id;
    this.Rest.getWithToken(`institutions/${this.institution_id}/classes?order=id&academic_period_id=${this.academic_Period}`).subscribe({
      next: (response: any) => {
        if (response) {
          this.academic_class = [];
          response?.data?.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            this.academic_class.push(obj);
          });
          this.selected_academic_class = this.academic_class[0]?.key;
          let classData = this.class;
          classData[0].options = this.academic_class;
          this.class = [...classData];
          this.getEducationGrade(this.selected_academic_class);
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

  getEducationGrade(id: any) {
    this.selected_academic_class = id;
    this.Rest.getWithToken(`institutions/classes/${id}/grades`).subscribe({
      next: (response: any) => {
        if (response) {
          this.education_grade = [];
          response?.data.forEach((element: any) => {
            let obj = {
              key: element.education_grades.id,
              value: element.education_grades.code,
              education_grade_id: element.education_grade_id
            }
            this.education_grade.push(obj);
          });
          this.education_grade_id = this.education_grade[0].education_grade_id;
          this.selected_education_grade = this.education_grade[0].key;
          let education_grade_data = this.educationGrade;
          education_grade_data[0].options = this.education_grade;
          this.educationGrade = [...education_grade_data];
          this.institutionSubjectClass(this.selected_education_grade);
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

  institutionSubjectClass(id: any) {
    this.selected_education_grade = id;
    this.Rest.getWithToken(`institutions/${this.institution_id}/grades/${this.selected_education_grade}/classes/${this.selected_academic_class}/subjects?academic_period_id=${this.academic_Period}`).subscribe({
      next: (response: any) => {
        if (response) {
          this.instution_subject = [];
          response?.data?.data.forEach((element: any) => {
            let obj = {
              key: element.id,
              value: element.name
            }
            this.instution_subject.push(obj);
          });
          this.selectedInstitutionSubject = this.instution_subject[0].key;
          this.selected_institution_subject = this.instution_subject[0].key;
          let insitution_subject_data = this.institutionSubject;
          insitution_subject_data[0].options = this.instution_subject;
          this.institutionSubject = [...insitution_subject_data];
          this.studentAttendanceType();
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

  studentAttendanceType() {
    this.Rest.getWithToken(`institutions/${this.institution_id}/grades/${this.selected_education_grade}/classes/${this.selected_academic_class}/student-attendance-types?academic_period_id=${this.academic_Period}&day_id=${this.selected_day_week}&week_start_day=${this.start_day}&week_end_day=${this.end_day}`).subscribe({
      next: (response: any) => {
        this.attendanceMarkType = [];
        if (response) {
          response?.data?.data.forEach((element: any) => {
            let obj = {
              'title': element.name,
              'value': element.id
            }
            this.attendanceMarkType.push(obj);
          });
          let attendance_mark = this.period;
          attendance_mark[0].list = this.attendanceMarkType;
          attendance_mark[0].value = this.attendanceMarkType[0].value;
          this.period = [...attendance_mark];
          this.studentAttendanceMarked();
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

  studentAttendanceMarked() {
    this.Rest.getWithToken(`institutions/${this.institution_id}/grades/${this.selected_education_grade}/classes/${this.selected_academic_class}/student-attendance-marked?academic_period_id=${this.academic_Period}&day_id=${this.selected_day_week}&attendance_period_id=1&subject_id=0`).subscribe({
      next: (response: any) => {
        if (response) {
          this.getStudentAttendanceData();
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

  getStudentAttendanceData() {
    // insitutions/6/grades/206/classes/591/student-attendances?academic_period_id=33&day_id=2024-02-05&attendance_period_id=1&subject_id=0&week_id=6&week_start_day=2024-02-05&week_end_day=2024-02-11

    this.Rest.getWithToken(`institutions/${this.institution_id}/grades/${this.selected_education_grade}/classes/${this.selected_academic_class}/student-attendances?academic_period_id=${this.academic_Period}&day_id=${this.academic_period_day}&attendance_period_id=1&subject_id=0&week_id=${this.academic_period_week}&week_start_day=${this.start_day}&week_end_day=${this.end_day}`).subscribe({
      next: (response: any) => {
        if (response) {
          this._row = response?.data?.list;
          this.oldRowData = JSON.parse(JSON.stringify(this._row));
          this.setDashboard();
          this.displayLoading = false;
        }

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

  public editTableFields() {
  }

  public backToData() {
    this._row.forEach((item) => {
      const indexInArray2 = this.findIndexInArray2(item?.institution_student_absences?.absence_type_id, item.user.openemis_no);
      if (indexInArray2 !== -1) {
        this.callPostAttandanceAPI(item);
      }
    });
  }

  findIndexInArray2(keyValue: any, id: any) {
    for (let i = 0; i < this.oldRowData.length; i++) {
      if (this.oldRowData[i].institution_student_absences.absence_type_id != keyValue && this.oldRowData[i].user.openemis_no == id) {
        if ((this.oldRowData[i].institution_student_absences.absence_type_id == null && keyValue == 0) ||
          (this.oldRowData[i].institution_student_absences.absence_type_id == 0 && keyValue == null)) {
          return -1;
        }
        else {
          return i;
        }
      }
    }
    return -1; // Return -1 if the value is not found
  }

  callPostAttandanceAPI(data: any) {
    let obj = {
      "student_id": data?.student_id,
      "institution_id": data?.institution_id,
      "academic_period_id": data?.academic_period_id,
      "institution_class_id": data?.institution_class_id,
      "absence_type_id": data?.institution_student_absences?.absence_type_id,
      "student_absence_reason_id": data?.institution_student_absences?.student_absence_reason_id,
      "comment": data?.institution_student_absences?.comment,
      "period": data?.institution_student_absences?.period,
      "date": data?.institution_student_absences?.date,
      "subject_id": this.selectedInstitutionSubject,
      "education_grade_id": this.education_grade_id
    }
    this.Rest.postWithToken(`institutions/students/absences`, obj).subscribe({
      next: (response: any) => {
        if (response) {
          this.oldRowData = JSON.parse(JSON.stringify(this._row));
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

  onEditClick() {
    this.displayEditTable = !this.displayEditTable

    if (this.displayEditTable) {
      this.displayMiniDashboard = false;
      this.pageheader = {
        leftBtn: [
          {
            type: "back",
            callback: (): void => {
              this.onEditClick();
              this.backToData();
            }
          }
        ],
        moreAction: [],
        moreBtn: false,
        pageheaderText: `${this.institution_name} - Student Attendances`,
        searchBtn: false,
        searchEvent: ['change', 'keyup']
      }
      let academic_Period = this.academicPeriod;
      academic_Period[0].disabled = true;
      this.academicPeriod = [...academic_Period];

      let week_data = this.week;
      week_data[0].disabled = true;
      this.week = [...week_data];

      let day_data = this.day;
      day_data[0].disabled = true;
      this.day = [...day_data];

      let class_data = this.class;
      class_data[0].disabled = true;
      this.class = [...class_data];

      let education_grade = this.educationGrade;
      education_grade[0].disabled = true;
      this.educationGrade = [...education_grade];

      let institution_Subject = this.institutionSubject;
      institution_Subject[0].disabled = true;
      this.institutionSubject = [...institution_Subject];
    } else {
      this.displayMiniDashboard = true;
      this.pageheader = {
        leftBtn: [{
          type: "export",
          callback: (): void => {
            this.backToData();
          }
        },
        {
          type: "import",
          callback: (): void => {
            this.editTableFields();
          }
        },
        {
          type: "edit",
          callback: (): void => {
            this.onEditClick();
          }
        },
        {
          icon: "fa kd-null",
          path: '/'
        },
        {
          icon: "fa fa-folder",
          callback: (): void => {
            this.onEditClick();
          }
        },
        ],
        moreAction: [],
        moreBtn: false,
        pageheaderText: `${this.institution_name} - Student Attendances`,
        searchBtn: false,
        searchEvent: ['change', 'keyup']
      }
      let academic_Period = this.academicPeriod;
      academic_Period[0].disabled = false;
      this.academicPeriod = [...academic_Period];

      let week_data = this.week;
      week_data[0].disabled = false;
      this.week = [...week_data];

      let day_data = this.day;
      day_data[0].disabled = false;
      this.day = [...day_data];

      let class_data = this.class;
      class_data[0].disabled = false;
      this.class = [...class_data];

      let education_grade = this.educationGrade;
      education_grade[0].disabled = false;
      this.educationGrade = [...education_grade];

      let institution_Subject = this.institutionSubject;
      institution_Subject[0].disabled = false;
      this.institutionSubject = [...institution_Subject];
    }

    let config = this._config
    config.context.mode = this.displayEditTable ? 'edit' : 'view',
      this._config = config;

    setTimeout(() => {
      this.child.setAttendance(this.displayEditTable, this._config);
    }, 100);

    setTimeout(() => {
      this.setDashboard();
    }, 200);
  }


  _submitEvent(event: any, type: any) {
    switch (type) {
      case 'academicPeriod':
        this.displayLoading = true;
        this._row = [];
        this.getAcademicWeek(event.target.value);
        break;

      case 'week':
        this.displayLoading = true;
        this._row = [];
        this.getAcademicDay(event.target.value);
        break;

      case 'day':
        this.displayLoading = true;
        this._row = [];
        this.getClassData(event.target.value);
        break;

      case 'class':
        this._row = [];
        this.displayLoading = true;
        this.education_grade_id = event.target.value;
        this.getEducationGrade(event.target.value);
        break;

      case 'educationGrade':
        this.displayLoading = true;
        this._row = [];
        this.selectedInstitutionSubject = event.target.value;
        this.institutionSubjectClass(event.target.value);
        break;
    }
  }

  ngOnDestroy(): void {

  }
}
