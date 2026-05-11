import { Component, Injector, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import {
  KdPageBase,
  KdPageBaseEvent,
  ITableColumn,
  ITableConfig,
  ITableApi,
  KdTable,
} from "openemis-styleguide-lib";
import { ApiService } from '../api.service';
import { TABLE_COLUMN_LIST } from '../config';
import { DEFAULT_TEMPLATE_THEME } from '../shared/config.default-val';

@Component({
  selector: 'app-comments',
  templateUrl: './comments.component.html',
  styleUrls: ['./comments.component.css']
})
export class CommentsComponent extends KdPageBase implements OnInit, OnDestroy {
  // @ViewChild(KdTable) child: KdTable;
  @ViewChild('changetable') child: KdTable;
  public _state: string = 'principal';
  public _row: Array<any> = [];
  public _column: Array<ITableColumn> = [];
  public _tableApi: ITableApi = {};
  public _displayEditable: boolean = false;
  public _editTable: boolean = true;
  public displayLoading: boolean = true;
  public oldRowData: any;

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
    }
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "",
    searchBtn: false
  }

  public tabsListHtml: Array<any> = [];

  public _config: ITableConfig = {
    id: 'normalTableId',
    gridHeight: "auto",
    loadType: "normal",
    externalFilter: false,
    rowContentHeight: 60,
    paginationConfig: {
      pagesize: 10,
      total: 50000
    },
    context: {
      mode: 'view',
      commentTypes: [],
    }
  };
  counter: number = 0;
  institution_id: number;
  count: number = 0;
  education_grade_id: any;
  academic_period_id: any;
  themeArray = DEFAULT_TEMPLATE_THEME;
  report_card_id: any;
  institution_class_id: any;
  institution_name: any;
  userId: number;
  commentArray: any[];
  displayTabs: boolean = false;

  constructor(
    private _router: Router,
    _activatedRoute: ActivatedRoute,
    public pageEvent: KdPageBaseEvent,
    private Rest: ApiService,
  ) {
    super({
      router: _router,
      activatedRoute: _activatedRoute,
      pageEvent: pageEvent,
    });

  }


  ngOnInit(): void {
    // super.updateBreadcrumb();
    this.counter = 0;
    this.institution_id = JSON.parse(localStorage.getItem("institutionId"));
    // this.institution_id = 6;
    this.report_card_id = JSON.parse(localStorage.getItem("reportCardId"));
    // this.report_card_id = 9;
    this.institution_class_id = JSON.parse(localStorage.getItem("institutionClassId"));
    // this.institution_class_id = 591;
    this.institution_name = localStorage.getItem("institutionName");
    console.log(this.institution_name, "institution_name");

    this.pageheader.pageheaderText = `${this.institution_name} - Comments`

    super.setPageTitle("", false);
    super.setToolbarMainBtns([]);
    super.enableToolbarSearch(true);

    super.updatePageHeader();

    this._column = [];
    this.loginData();

    let columns: Array<any> = [];
    columns.push(TABLE_COLUMN_LIST.id);
    columns.push(TABLE_COLUMN_LIST.name);
    columns.push(TABLE_COLUMN_LIST.status);
    columns.push(TABLE_COLUMN_LIST.overall_average);
    columns.push(TABLE_COLUMN_LIST.comments);
    this._column = columns;
  }

  loginData() {
    // // this.Rest.setSession(); //POCOR-9594: CakePHP template injects real credentials via sessionStorage
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
      this.removeSession();
      this.setTheme();
      this.getReportCard();
    }
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.setTheme();
          this.getReportCard();
          this.removeSession();
        }
      },
      error: (error: any) => {

      }
    })
  }

  setTheme() {
    this.Rest.getWithToken('themes').subscribe({
      next: (response: any) => {
        console.log(response?.data[3].default_value, "response");
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
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  removeSession() {
    delete sessionStorage.username;
    delete sessionStorage.password;
  }

  getPermission() {
    this.Rest.getWithToken(`permissions`).subscribe({
      next: (response: any) => {
        if (response?.data) {
          this.userId = response?.data?.userId;
          if (response?.data?.roleIds.length > 0) {
            let roleId: any;
            if ((response?.data?.roleIds[0] == '6' && response?.data?.roleIds[1] == '5') || response?.data?.roleIds[0] == '5' && response?.data?.roleIds[1] == '6') {
              roleId = 5;
            } else {
              roleId = response?.data?.roleIds[0];
            }
            this.Rest.getWithToken(`security-roles/${roleId}`).subscribe({
              next: (res: any) => {
                if (res?.data?.code) {
                  if (res?.data?.code == "PRINCIPAL") {
                    this.tabsListHtml = [
                      { education_subject_id: 1, tabName: 'Principal', tabId: 'principal', isActive: true },
                      // { education_subject_id: 1, tabName: 'Homeroom Teacher', tabId: 'homeroom_teacher', isActive: false } 
                    ];
                    // this.getReportSubject();
                    // this.getReportCardCommentData();
                    this.getReportData('PRINCIPAL', 0, undefined);
                  } else if (res?.data?.code == "TEACHER") {
                    // this.tabsListHtml = [
                    //   { education_subject_id: 1, tabName: 'Homeroom Teacher', tabId: 'homeroom_teacher', isActive: true }
                    // ];
                    this.getReportSubject();
                    this.getReportCardCommentData();
                    // this._state = 'homeroom_teacher';
                    // this.getReportData('HOMEROOM_TEACHER', 0, undefined);
                  } else if (res?.data?.code == "HOMEROOM_TEACHER") {
                    this.tabsListHtml = [
                      { education_subject_id: 1, tabName: 'Homeroom Teacher', tabId: 'homeroom_teacher', isActive: true }
                    ];
                    this.getReportSubject();
                    this.getReportCardCommentData();
                    this._state = 'homeroom_teacher';
                    this.getReportData('HOMEROOM_TEACHER', 0, undefined);
                  }
                }
              }
            })
          } else if (response?.data?.roleIds.length == 0) {
            this.tabsListHtml = [
              { education_subject_id: 1, tabName: 'Principal', tabId: 'principal', isActive: true },
              { education_subject_id: 1, tabName: 'Homeroom Teacher', tabId: 'homeroom_teacher', isActive: false }
            ];
            this.getReportSubject();
            this.getReportCardCommentData();
            this.getReportData('PRINCIPAL', 0, undefined);
          }
        }
      },
      error: (error: any) => {

      }
    })
  }


  getReportCard() {
    this.Rest.getWithToken(`reportcards/${this.report_card_id}`).subscribe({
      next: (response: any) => {
        if (response?.data) {
          this.education_grade_id = response?.data?.education_grade_id;
          this.academic_period_id = response?.data?.academic_period_id;
          this.getPermission();
          // this.getReportSubject();
          // this.getReportCardCommentData();
          // this.getReportData('PRINCIPAL', 0, undefined);
        }
      },
      error: (error: any) => {

      }
    })
  }

  getReportSubject() {
    this.Rest.getWithToken(`institutions/classes/reportcards/subjects?report_card_id=${this.report_card_id}&institution_class_id=${this.institution_class_id}&type=0&staffType=0`).subscribe({
      next: (response: any) => {
        if (response?.data?.length > 0) {
          if (this.tabsListHtml.length > 0) {
            response?.data.forEach((element: any) => {
              let obj = {
                id: element?.id,
                education_subject_id: element?.education_subject_id,
                tabName: element?.name + ' Teacher',
                tabId: element?.name,
                isActive: false
              }
              this.tabsListHtml.push(obj);
            });
          } else {
            response?.data.forEach((element: any) => {
              let obj = {
                id: element?.id,
                education_subject_id: element?.education_subject_id,
                tabName: element?.name + ' Teacher',
                tabId: element?.name,
                isActive: false
              }
              this.tabsListHtml.push(obj);
              this._state = this.tabsListHtml[0].tabId;
              this.getReportData('TEACHER', 0, this.tabsListHtml[0].id);
            });
          }
        }
      },
      error: (error: any) => {

      }
    })
  }

  getReportCardCommentData() {
    this.Rest.getWithToken('institutions/classes/reportcards/comment/codes').subscribe({
      next: (response: any) => {
        console.log(response, "response");
        if (response.data) {
          this.commentArray = [];
          response?.data.forEach((element: any) => {
            let obj = {
              id: element?.id,
              name: element?.name
            }
            this.commentArray.push(obj);
          });
          this.commentArray.unshift({ id: null, name: '--Select--' })
          this._config.context.commentTypes = this.commentArray;
        }
      },
      error: (error: any) => {

      }
    })
  }

  getReportData(data: any, subject_id: any, institution_subject_id: any) {
    this.Rest.getWithToken(`institutions/classes/reportcards/subject/comments?academic_period_id=${this.academic_period_id}&institution_id=${this.institution_id}&institution_class_id=${this.institution_class_id}&education_grade_id=${this.education_grade_id}&report_card_id=${this.report_card_id}&type=${data}&education_subject_id=${subject_id}&institution_subject_id=${institution_subject_id}`).subscribe({
      next: (response: any) => {
        let obj = {};
        this._row = [];
        this.oldRowData = [];
        let row = [];
        if (data == 'PRINCIPAL' || data == 'HOMEROOM_TEACHER') {
          response?.data?.data?.forEach((element: any) => {
            obj = {
              id: element?._matchingData?.Users?.openemis_no,
              name: element?._matchingData?.Users?.full_name,
              status: element?.student_status?.code,
              student_id: element?.student_id,
              overall_average: element?.average_mark,
              comments: element?.comments,
            }
            row.push(obj);
            // this._row.push(obj);
          });
          this._row = row;
        } else {
          response?.data?.data?.forEach((element: any) => {
            obj = {
              id: element?._matchingData?.Users?.openemis_no,
              name: element?._matchingData?.Users?.full_name,
              status: element?.student_status?.code,
              total_mark: (Math.round(element?.total_mark * 100) / 100).toFixed(2),
              student_id: element?.student_id,
              comment_code: element.comments_code,
              comments: element?.comments,
              modified_by: element?.Staff?.first_name ? element?.Staff?.first_name + ' ' + element?.Staff?.last_name : ''
            }
            this._row.push(obj);
          });
        }
        this.displayLoading = false;
        this.displayTabs = true;
        this.oldRowData = JSON.parse(JSON.stringify(this._row));
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

  public editTableFields() {
    this.displayLoading = true;
    this._displayEditable = !this._displayEditable;
    let newEdit = this.child.toggleEdits('text');
    setTimeout(() => {
      this.child.setStudentMeal(true)
    }, 100);
    this.pageheader = {
      leftBtn: [{
        type: "back",
        callback: (): void => {
          this.backToData();
        }
      }
      ],
      moreAction: [],
      moreBtn: false,
      pageheaderText: "Avory Primary School - Comments",
      searchBtn: false
    }

    setTimeout(() => {
      this.displayLoading = false;
    }, 2000);
  }

  public backToData() {
    this.displayLoading = true;
    setTimeout(() => {
      this.child.setStudentMeal(false);
    }, 100);
    if (this._displayEditable) {
      this._displayEditable = false;
      this.child.toggleEdits('text');
      this.oldRowData.forEach(element => {
        this._row.forEach(newData => {
          if (element.id == newData.id) {
            if (element.comments != newData.comments || element.comment_code != newData.comment_code) {
              console.log(newData, "newData");
              let state = this._state == 'homeroom_teacher' ? 'homeroom' : this._state;
              if (state == 'homeroom' || state == 'principal') {
                let obj = {
                  "academic_period_id": this.academic_period_id,
                  "education_grade_id": this.education_grade_id,
                  "student_id": newData?.student_id,
                  "comment": newData?.comments,
                  "report_card_id": this.report_card_id
                }
                this.Rest.postWithToken(`institutions/${this.institution_id}/classes/${this.institution_class_id}/reportcardcomment/${state}`, obj).subscribe({
                  next: (res: any) => {
                    if (res) {
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
              } else {
                console.log(state, "state", newData);
                let educationSubjectId: number;
                this.tabsListHtml.forEach((element: any) => {
                  if (element.tabId == state) {
                    educationSubjectId = element.education_subject_id
                  }
                });
                console.log(this.userId, "this.userId");

                let obj = {
                  "academic_period_id": this.academic_period_id,
                  "education_grade_id": this.education_grade_id,
                  "student_id": newData?.student_id,
                  "staff_id": this.userId,
                  "comment": newData?.comments,
                  "education_subject_id": educationSubjectId,
                  "report_card_id": this.report_card_id,
                  "report_card_comment_code_id": newData.comment_code
                }
                console.log(obj, "obj");

                this.Rest.postWithToken(`institutions/${this.institution_id}/classes/${this.institution_class_id}/reportcardcomment`, obj).subscribe({
                  next: (res: any) => {
                    if (res) {
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

            } else {
              this.displayLoading = false;
            }

          }
        })
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
        }
        ],
        moreAction: [],
        moreBtn: false,
        pageheaderText: "Avory Primary School - Comments",
        searchBtn: false
      }
    } else {
      this.displayLoading = false;
    }
  }

  _selectTabs(event: any) {
    if (this._displayEditable) {
      this._config.context.mode = 'view';
      this.child.toggleEdits('text');
    }
    this.count++;
    if (this.count > 2) {
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
        ],
        moreAction: [],
        moreBtn: false,
        pageheaderText: "Avory Primary School - Comments",
        searchBtn: false
      }
      this._state = event;
      this._displayEditable = false;
      this._row = [];
      this.displayLoading = true;
      switch (event) {
        case "principal":
          this.setNewColumn(event);
          this.getReportData('PRINCIPAL', 0, undefined);
          break;
        case "homeroom_teacher":
          this.setNewColumn(event);
          this.getReportData('HOMEROOM_TEACHER', 0, undefined);
          break;
        default:
          this.setNewColumn(event);
          let subjectId = 0;
          let institutionSubjectId = 0;
          this.tabsListHtml.forEach((element: any) => {
            if (element.tabId == event) {
              subjectId = element?.education_subject_id;
              institutionSubjectId = element?.id;
            }
          });
          if (subjectId > 0 && institutionSubjectId > 0) {
            this.getReportData('TEACHER', subjectId, institutionSubjectId);
          }
          break;
      }
      setTimeout(() => {
        this.displayLoading = false;
      }, 3000);
    }
  }

  setNewColumn(event: any) {
    this._column = [];
    let columns: Array<any> = [];
    if (event == 'principal' || event == 'homeroom_teacher') {
      columns.push(TABLE_COLUMN_LIST.id);
      columns.push(TABLE_COLUMN_LIST.name);
      columns.push(TABLE_COLUMN_LIST.status);
      columns.push(TABLE_COLUMN_LIST.overall_average);
      columns.push(TABLE_COLUMN_LIST.comments);
      this._column = columns;
    } else {
      columns.push(TABLE_COLUMN_LIST.id);
      columns.push(TABLE_COLUMN_LIST.name);
      columns.push(TABLE_COLUMN_LIST.status);
      columns.push(TABLE_COLUMN_LIST.total_mark);
      columns.push(TABLE_COLUMN_LIST.comment_code);
      columns.push(TABLE_COLUMN_LIST.comments);
      columns.push(TABLE_COLUMN_LIST.modified_by);
      this._column = columns;
    }
  }

  ngOnDestroy(): void {
    super.destroyPageBaseSub();
  }
}
