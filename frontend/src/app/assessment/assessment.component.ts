import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ITableApi, ITableColumn, ITableConfig, KdPageBase, KdPageBaseEvent, KdTable, KdTableEvent, KdToolbarEvent } from 'openemis-styleguide-lib';
import { ApiService } from '../api.service';
import { TABLE_COLUMN_LIST } from './assessment_config';
import { timer } from 'rxjs';

@Component({
  selector: 'app-assessment',
  templateUrl: './assessment.component.html',
  styleUrls: ['./assessment.component.css']
})

export class AssessmentComponent extends KdPageBase implements OnInit, OnDestroy {
  @ViewChild(KdTable) child: KdTable;
  public _state: string = 'social_studies';
  public _row: Array<any> = [];
  public _column: Array<ITableColumn> = [];
  public _tableApi: ITableApi = {};
  public _displayEditable: boolean = false;
  public _editTable: boolean = true;
  public displayLoading: boolean = true;

  public pageHeaderTitle: any = ""

  public breadcrumbList = {
    home: { icon: 'fa fa-home', path: '' },
    list: [{
      name: 'Institutions',
      path: '',
    },
    {
      name: 'Avory Primary School',
      path: '',
    }]
  };

  public pageheader = {
    leftBtn: [{
      type: "back",
      callback: (): void => {
        this.backToData();
      }
    },
    {
      type: "edit",
      callback: (): void => {
        this.editTableFields();
      }
    },
    {
      icon: "fa kd-header-row",
      path: '/'
    },
    {
      icon: "fa fa-file-pdf-o",
      path: '/'
    },
    {
      type: "export",
      path: '/'
    }
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: this.pageHeaderTitle,
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }

  public tabsListHtml: Array<any> = [];

  public _config: ITableConfig = {
    id: 'listTable',
    gridHeight: "auto",
    loadType: "infinite",
    externalFilter: false,
    rowContentHeight: 25,
    paginationConfig: {
      pagesize: 10,
    },
  };
  academicTerm: any;
  oldData: any = [];

  constructor(
    private _router: Router,
    _activatedRoute: ActivatedRoute,
    public pageEvent: KdPageBaseEvent,
    private _tableEvent: KdTableEvent,
    private _toolbarEvent: KdToolbarEvent,
    private Rest: ApiService,
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
    this.loginData();
    this._column = [];

    let columns: Array<any> = [];
    columns.push(TABLE_COLUMN_LIST.id);
    columns.push(TABLE_COLUMN_LIST.name);
    columns.push(TABLE_COLUMN_LIST.status);
    columns.push(TABLE_COLUMN_LIST.period1);
    columns.push(TABLE_COLUMN_LIST.period2);
    columns.push(TABLE_COLUMN_LIST.totalMark);
    this._column = columns;
  }

  loginData() {
    this.Rest.login().subscribe({
      next: (res: any) => {
        if (res) {
          localStorage.setItem("loginToken", res.data.token);
          this.getAPIlist();
        }
      },
      error: (error: any) => {
        console.log(error, "error");

      }
    })
  }

  getAPIlist() {
    this.assessmentPeriods();
    this.assessment();
    this.assessmentItems();
  }

  assessmentPeriods() {
    this.Rest.getWithToken('assessments/32/assessmentperiods').subscribe({
      next: (res: any) => {
        if (res) {
          this.academicTerm = res?.data?.data
        }
      },
      error: (error: any) => {
        console.log(error, "error Data 1");

      }
    })
  }

  assessment() {
    this.Rest.getWithToken('assessments/32').subscribe({
      next: (res: any) => {
        this.pageHeaderTitle = res?.data?.code_name;
        this.pageheader.pageheaderText = res?.data?.code_name;
      },
      error: (error: any) => {
        console.log(error, "error Data 1");

      }
    })
  }

  assessmentItems() {
    this.Rest.getWithToken('assessments/32/assessmentitems?class_id=568&academic_period_id=32&institution_id=6').subscribe({
      next: (res: any) => {
        if (res) {
          res?.data?.data.forEach((element: any, index: any) => {
            let obj = {};
            if (index == 0) {
              obj = {
                education_subject_id: element.InstitutionSubjects.education_subject_id,
                tabId: element.InstitutionSubjects.id,
                tabName: element.InstitutionSubjects.name,
                isActive: true
              }
            } else {
              obj = {
                education_subject_id: element.InstitutionSubjects.education_subject_id,
                tabId: element.InstitutionSubjects.id,
                tabName: element.InstitutionSubjects.name
              }
            }
            this.tabsListHtml.push(obj);
          });
          this.institutionsSubject(this.tabsListHtml[0].tabId);
        }
      },
      error: (error: any) => {
        console.log(error, "error Data 1");

      }
    })
  }

  institutionsSubject(institution_subject_id) {
    this.Rest.getWithToken(`institutions/subject/student?institution_id=6&institution_class_id=568&assessment_id=32&academic_period_id=32&institution_subject_id=${institution_subject_id}&education_grade_id=189`).subscribe({
      next: (res: any) => {
        console.log(res, "res 111");
        if (res) {
          let objData = [];
          res?.data?.data.forEach((element: any, index: any) => {
            if (element) {
              let obj = {
                id: element.the_student_code + index,
                name: element.first_name + ' ' + element.last_name,
                status: element.the_student_status,
                period1: element.assessment_id,
                period2: element.assessment_id,
                totalMark: element.total_mark
              }
              objData.push(obj);
              this.oldData.push(obj);
            }
          });
          timer(2000).subscribe((): void => {
            this._row = objData;
            this.displayLoading = false;
            console.log(this._row, "row data");
          });

        }
      },
      error: (error: any) => {
        console.log(error, "error Data 1");

      }
    })
  }

  public editTableFields() {
    this._displayEditable = !this._displayEditable;
    // this.child.toggleEdits('number');
    this.pageheader = {
      leftBtn: [{
        type: "back",
        callback: (): void => {
          this.backToData();
        }
      },
      {
        icon: "fa kd-header-row",
        path: '/'
      },
      {
        icon: "fa fa-file-pdf-o",
        path: '/'
      },
      {
        type: "export",
        path: '/'
      }
      ],
      moreAction: [],
      moreBtn: false,
      pageheaderText: this.pageHeaderTitle,
      searchBtn: false,
      searchEvent: ['change', 'keyup']
    }
  }

  public backToData() {
    if (this._displayEditable) {
      this._displayEditable = false;
    // this.child.toggleEdits('number');
      console.log(this._row, "_row _row");
      this.oldData.forEach(element => {
        this._row.forEach(newData => {
          if (element.id == newData.id) {
            if (element.period1 != newData.period1) {
              this.Rest.postWithToken
                (`institutions/students/assessment-item-results?student_id=1153&assessment_id=17&education_subject_id=6&education_grade_id=194&academic_period_id=32&assessment_period_id=3&institution_id=6&institution_classes_id=582&marks=${newData.period1}&action_type=default`, {}).subscribe({
                  next: (res: any) => {
                    if (res) {
                      console.log(res, "res");

                    }
                  },
                  error: (error: any) => {
                    console.log(error, "error Data 1");

                  }
                })
            }
          }
        });
      });
      this.pageheader = {
        leftBtn: [{
          type: "back",
          callback: (): void => {
            this.backToData();
          }
        },
        {
          type: "edit",
          callback: (): void => {
            this.editTableFields();
          }
        },
        {
          icon: "fa kd-header-row",
          path: '/'
        },
        {
          icon: "fa fa-file-pdf-o",
          path: '/'
        },
        {
          type: "export",
          path: '/'
        }
        ],
        moreAction: [],
        moreBtn: false,
        pageheaderText: this.pageHeaderTitle,
        searchBtn: false,
        searchEvent: ['change', 'keyup']
      }
    }
  }

  _selectTabs(event: any) {
    console.log(event, "event");

    this.pageheader = {
      leftBtn: [{
        type: "back",
        callback: (): void => {
          this.backToData();
        }
      },
      {
        type: "edit",
        callback: (): void => {
          this.editTableFields();
        }
      },
      {
        icon: "fa kd-header-row",
        path: '/'
      },
      {
        icon: "fa fa-file-pdf-o",
        path: '/'
      },
      {
        type: "export",
        path: '/'
      }
      ],
      moreAction: [],
      moreBtn: false,
      pageheaderText: this.pageHeaderTitle,
      searchBtn: false,
      searchEvent: ['change', 'keyup']
    }
    this._state = event;
    this._displayEditable = false;
    this._row = [];
    this.displayLoading = true;
    this.institutionsSubject(event);
  }

  ngOnDestroy(): void {
    super.destroyPageBaseSub();
  }
}
