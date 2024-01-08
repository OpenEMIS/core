import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ITableApi, ITableColumn, KdPageBase, KdPageBaseEvent, KdTable, KdToolbarEvent } from 'openemis-styleguide-lib';
import { MINI_DASHBOARD_CONFIG, TABLE_COLUMN_LIST } from './student_attendance.config';
import { IMiniDashboardConfig, IMiniDashboardItem } from 'openemis-styleguide-lib/kd-components/kd-angular-mini-dashboard/kd-angular-mini-dashboard-interface';
import { Subscription, timer } from 'rxjs';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from '../api.service';

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
  public displayLoading: boolean = true;
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
    pageheaderText: "Avory Primary School - Student Attendances",
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
  public displayEditTable: boolean = false

  public absenceTypeList: any = [
    {
      "id": 0,
      "name": "Present",
      "code": "PRESENT"
    },
    {
      "id": 1,
      "name": "Absence - Excused",
      "code": "EXCUSED"
    },
    {
      "id": 2,
      "name": "Absence - Unexcused",
      "code": "UNEXCUSED"
    },
    {
      "id": 3,
      "name": "Late",
      "code": "LATE"
    }
  ]

  public studentAbsenceReasons: any = [
    { id: 1, name: 'Illness' },
    { id: 2, name: 'Emergency' },
    { id: 3, name: 'Weather' },
    { id: 4, name: 'Family matter' },
    { id: 5, name: 'Death' },
  ]

  public _config: any = {
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
    // click: {
    //     type: "router",
    //     path: "/App/Demo/CRUD/ViewNode",
    // },
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
        data: [
          {
            academic_period_id: 32,
            institution_class_id: 568,
            institution_id: 6,
            institution_student_absences: {
              absence_type_code: 'LATE',
              absence_type_id: 3,
              comment: 'late',
              date: '2023-12-04',
              period: '1',
              student_absence_reason_id: 3
            },
            is_NoClassScheduled: 0,
            rowHeight: 60,
            student_id: 1311,
            user: {
              default_identity_type: "",
              first_name: "Aaron",
              has_special_needs: true,
              id: 1311,
              last_name: "Butler",
              middle_name: null,
              name: "Aaron Butler",
              name_with_id: "1522413076 - Aaron Butler",
              name_with_id_role: "1522413076 - Aaron Butler (Student)",
              openemis_no: "1522413076",
              preferred_name: null,
              third_name: null
            }
          },
          {
            academic_period_id: 32,
            institution_class_id: 568,
            institution_id: 6,
            institution_student_absences: {
              absence_type_code: 'LATE',
              absence_type_id: 3,
              comment: "late",
              date: '2023-12-04',
              period: '1',
              student_absence_reason_id: 3
            },
            is_NoClassScheduled: 0,
            rowHeight: 60,
            student_id: 3540,
            user: {
              default_identity_type: "",
              first_name: "John",
              has_special_needs: true,
              id: 3540,
              last_name: "Smith",
              middle_name: null,
              name: "John Smith",
              name_with_id: "1522415305 - John Smith",
              name_with_id_role: "1522415305 - John Smith (Student)",
              openemis_no: "1522415305",
              preferred_name: null,
              third_name: null
            }
          }
        ]
      }
    }
  };

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
      'options': [
        {
          'key': 1,
          'value': '2023'
        }, {
          'key': 2,
          'value': '2022'
        }
      ]
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
      'options': [
        {
          'key': 1,
          'value': 'Week 1 (January 01, 2023 - January 08, 2023)'
        }, {
          'key': 2,
          'value': 'Week 2 (January 09, 2023 - January 15, 2023)'
        }
      ],
    }
  ]
  public day: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Day',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'options': [
        {
          'key': 1,
          'value': 'Monday'
        }, {
          'key': 2,
          'value': 'Tuesday'
        }, {
          'key': 3,
          'value': 'Wednesday'
        }, {
          'key': 4,
          'value': 'Thursday'
        }, {
          'key': 5,
          'value': 'Friday'
        },

      ]
    }
  ]
  public class: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Class',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'options': [
        {
          'key': 1,
          'value': 'Primary 1-A'
        }, {
          'key': 2,
          'value': 'Primary 1-B'
        },

      ]
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
        {
          'key': 1,
          'value': 'Primary 1-A'
        },

      ]
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

    timer(1000).subscribe((): void => {
      this._column = [
        TABLE_COLUMN_LIST.openEmisId,
        TABLE_COLUMN_LIST.personName,
        TABLE_COLUMN_LIST.student_attendance_select_new,
        TABLE_COLUMN_LIST.reasonOrComment_select_new
      ];
    });

    timer(2000).subscribe((): void => {
      this.displayLoading = false;
      this._row = [
        {
          id: 159,
          academic_period_id: 32,
          institution_class_id: 568,
          institution_id: 6,
          institution_student_absences: {
            absence_type_code: null,
            absence_type_id: 0,
            comment: null,
            date: '2023-12-04',
            period: '1',
            student_absence_reason_id: null
          },
          is_NoClassScheduled: 0,
          rowHeight: 100,
          student_id: 1311,
          user: {
            default_identity_type: "",
            first_name: "Aaron",
            has_special_needs: true,
            id: 1311,
            last_name: "Butler",
            middle_name: null,
            name: "Aaron Butler",
            name_with_id: "1522413076 - Aaron Butler",
            name_with_id_role: "1522413076 - Aaron Butler (Student)",
            openemis_no: "1522413076",
            preferred_name: null,
            third_name: null
          }
        },
        {
          id: 160,
          academic_period_id: 32,
          institution_class_id: 568,
          institution_id: 6,
          institution_student_absences: {
            absence_type_code: 'UNEXCUSED',
            absence_type_id: 2,
            comment: 'nill',
            date: '2023-12-04',
            period: '1',
            student_absence_reason_id: 2
          },
          is_NoClassScheduled: 0,
          rowHeight: 60,
          student_id: 3540,
          user: {
            default_identity_type: "",
            first_name: "John",
            has_special_needs: true,
            id: 3540,
            last_name: "Smith",
            middle_name: null,
            name: "John Smith",
            name_with_id: "1522415305 - John Smith",
            name_with_id_role: "1522415305 - John Smith (Student)",
            openemis_no: "1522415305",
            preferred_name: null,
            third_name: null
          }
        },
        {
          id: 161,
          academic_period_id: 32,
          institution_class_id: 568,
          institution_id: 6,
          institution_student_absences: {
            absence_type_code: 'UNEXCUSED',
            absence_type_id: 2,
            comment: 'nill',
            date: '2023-12-04',
            period: '1',
            student_absence_reason_id: 2
          },
          is_NoClassScheduled: 0,
          rowHeight: 60,
          student_id: 3540,
          user: {
            default_identity_type: "",
            first_name: "John",
            has_special_needs: true,
            id: 3540,
            last_name: "Smith",
            middle_name: null,
            name: "John Smith 2",
            name_with_id: "1522415305 - John Smith",
            name_with_id_role: "1522415305 - John Smith (Student)",
            openemis_no: "1522415306",
            preferred_name: null,
            third_name: null
          }
        }
      ]
      this.setDashboard();
    });

    // this._toolbarSearchSub = this._toolbarEvent
    //         .onSendSearchText()
    //         // .debounceTime(500)
    //         .subscribe((_text: string): void => {
    //             this._tableApi.general.searchRow(_text);
    //         });

    //     this._tableSub = this._tableEvent
    //         .onKdTableEventList(this.TABLEID)
    //         .subscribe((_event: any): void => { });

  }

  setDashboard() {
    this.present = 0;
    this.absent = 0;
    this.late = 0;
    this._row.forEach((student, index) => {
      if (student.institution_student_absences.absence_type_id == 0) {
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
        label: 'Total Students:',
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

  public editTableFields() {
  }

  public backToData() {

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
            }
          }
        ],
        moreAction: [],
        moreBtn: false,
        pageheaderText: "Avory Primary School - Student Attendances",
        searchBtn: false,
        searchEvent: ['change', 'keyup']
      }
      this.academicPeriod = [
        {
          'key': 'dropdown',
          'label': 'Academic Period',
          'visible': true,
          'required': false,
          'controlType': 'dropdown',
          'disabled': true,
          'options': [
            {
              'key': 1,
              'value': '2023'
            }, {
              'key': 2,
              'value': '2022'
            }
          ]
        }
      ]
      this.week = [
        {
          'key': 'dropdown',
          'label': 'Week',
          'visible': true,
          'required': false,
          'controlType': 'dropdown',
          'disabled': true,
          'options': [
            {
              'key': 1,
              'value': 'Week 1 (January 01, 2023 - January 08, 2023)'
            }, {
              'key': 2,
              'value': 'Week 2 (January 09, 2023 - January 15, 2023)'
            }
          ],
        }
      ]
      this.day = [
        {
          'key': 'dropdown',
          'label': 'Day',
          'visible': true,
          'required': false,
          'controlType': 'dropdown',
          'disabled': true,
          'options': [
            {
              'key': 1,
              'value': 'Monday'
            }, {
              'key': 2,
              'value': 'Tuesday'
            }, {
              'key': 3,
              'value': 'Wednesday'
            }, {
              'key': 4,
              'value': 'Thursday'
            }, {
              'key': 5,
              'value': 'Friday'
            },

          ]
        }
      ]
      this.class = [
        {
          'key': 'dropdown',
          'label': 'Class',
          'visible': true,
          'required': false,
          'controlType': 'dropdown',
          'disabled': true,
          'options': [
            {
              'key': 1,
              'value': 'Primary 1-A'
            }, {
              'key': 2,
              'value': 'Primary 1-B'
            },

          ]
        }
      ]
      this.educationGrade = [
        {
          'key': 'dropdown',
          'label': 'Education Grade',
          'visible': true,
          'required': false,
          'controlType': 'dropdown',
          'disabled': true,
          'options': [
            {
              'key': 1,
              'value': 'Primary 1-A'
            },

          ]
        }
      ]
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
        pageheaderText: "Avory Primary School - Student Attendances",
        searchBtn: false,
        searchEvent: ['change', 'keyup']
      }
      this.academicPeriod = [
        {
          'key': 'dropdown',
          'label': 'Academic Period',
          'visible': true,
          'required': false,
          'controlType': 'dropdown',
          'disabled': false,
          'options': [
            {
              'key': 1,
              'value': '2023'
            }, {
              'key': 2,
              'value': '2022'
            }
          ]
        }
      ]
      this.week = [
        {
          'key': 'dropdown',
          'label': 'Week',
          'visible': true,
          'required': false,
          'controlType': 'dropdown',
          'disabled': false,
          'options': [
            {
              'key': 1,
              'value': 'Week 1 (January 01, 2023 - January 08, 2023)'
            }, {
              'key': 2,
              'value': 'Week 2 (January 09, 2023 - January 15, 2023)'
            }
          ],
        }
      ]
      this.day = [
        {
          'key': 'dropdown',
          'label': 'Day',
          'visible': true,
          'required': false,
          'controlType': 'dropdown',
          'disabled': false,
          'options': [
            {
              'key': 1,
              'value': 'Monday'
            }, {
              'key': 2,
              'value': 'Tuesday'
            }, {
              'key': 3,
              'value': 'Wednesday'
            }, {
              'key': 4,
              'value': 'Thursday'
            }, {
              'key': 5,
              'value': 'Friday'
            },

          ]
        }
      ]
      this.class = [
        {
          'key': 'dropdown',
          'label': 'Class',
          'visible': true,
          'required': false,
          'controlType': 'dropdown',
          'disabled': false,
          'options': [
            {
              'key': 1,
              'value': 'Primary 1-A'
            }, {
              'key': 2,
              'value': 'Primary 1-B'
            },

          ]
        }
      ]
      this.educationGrade = [
        {
          'key': 'dropdown',
          'label': 'Education Grade',
          'visible': true,
          'required': false,
          'controlType': 'dropdown',
          'disabled': false,
          'options': [
            {
              'key': 1,
              'value': 'Primary 1-A'
            },

          ]
        }
      ]
    }

    let config = this._config
    config.context.mode = this.displayEditTable ? 'edit' : 'view',
      this._config = config;
    console.log(this._config, "this._config");

    setTimeout(() => {
      this.child.setAttendance(this.displayEditTable, this._config);
    }, 100);

    setTimeout(() => {
      this.setDashboard();
    }, 200);
  }


  _submitEvent(event: any, type: any) {
    console.log("I am event", event.target.value)

    switch (type) {
      case 'academicPeriod':
        alert(1);
        break;

      case 'week':
        alert(2);
        break;

      case 'day':
        alert(3);
        break;

      case 'class':
        alert(4);
        break;

      case 'educationGrade':
        alert(5);
        break;
    }
  }

  ngOnDestroy(): void {
    
  }
}
