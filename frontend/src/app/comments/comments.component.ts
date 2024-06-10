import { Component, Injector, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import {
  KdPageBase,
  KdPageBaseEvent,
  ITableColumn,
  ITableConfig,
  ITableApi,
  KdTableEvent,
  KdToolbarEvent,
  IPageheaderApi,
  IPageheaderConfig,
  KdTable,
} from "openemis-styleguide-lib";
import { ApiService } from '../api.service';
import { Subscription, timer } from 'rxjs';
import { TABLE_COLUMN_LIST } from '../config';
import { DataService } from '../shared/data.service';

@Component({
  selector: 'app-comments',
  templateUrl: './comments.component.html',
  styleUrls: ['./comments.component.css']
})
export class CommentsComponent extends KdPageBase implements OnInit, OnDestroy {
  @ViewChild(KdTable) child: KdTable;
  public _state: string = 'principal';
  public _row: Array<any> = [];
  public _column: Array<ITableColumn> = [];
  public _tableApi: ITableApi = {};
  public _displayEditable: boolean = false;
  public _editTable: boolean = true;
  public displayLoading: boolean = true;
  public oldRowData: any;

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
    }
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "Avory Primary School - Comments",
    searchBtn: false
  }

  public tabsList = [
    { tabName: 'Principal', tabId: 'principal' },
    { tabName: 'Homeroom Teacher', tabId: 'homeroom_teacher' }
  ]

  public tabsListHtml: Array<any> = [];

  public _config: ITableConfig = {
    id: 'listTable',
    gridHeight: "auto",
    loadType: "infinite",
    externalFilter: false,
    rowContentHeight: 35,
    paginationConfig: {
      pagesize: 10,
    },
  };

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

    super.setPageTitle("", false);
    super.setToolbarMainBtns([]);
    super.enableToolbarSearch(true);

    super.updatePageHeader();
    super.updateBreadcrumb();
    this.tabsList.forEach((element: any, index: any) => {
      let obj = {}
      if (index == 0) {
        obj = {
          education_subject_id: 1,
          tabName: element.tabName,
          tabId: element.tabId,
          isActive: true
        }
      } else {
        obj ={
          education_subject_id: 2,
          tabName: element.tabName,
          tabId: element.tabId
        }
      }
      this.tabsListHtml.push(obj);
    });
    this._column = [];
    this.loginData();

    let columns: Array<any> = [];
    columns.push(TABLE_COLUMN_LIST.id);
    columns.push(TABLE_COLUMN_LIST.name);
    columns.push(TABLE_COLUMN_LIST.status);
    columns.push(TABLE_COLUMN_LIST.overall_average);
    columns.push(TABLE_COLUMN_LIST.comments);
    this._column = columns;

    timer(2000).subscribe((): void => {
      this._row = [
        {
          id: 1522413076,
          name: 'Aaron Butler',
          status: 'Enrolled',
          overall_average: 66.00,
          comments: "",
        },
        {
          id: 1522415305,
          name: 'Aaron Clark',
          status: 'Enrolled',
          overall_average: 70.00,
          comments: ""
        },
        {
          id: 1522272226,
          name: 'Aaron Endicott',
          status: 'Enrolled',
          overall_average: 85.00,
          comments: ""
        },
        {
          id: 1548405785,
          name: 'Jamil',
          status: 'Enrolled',
          overall_average: 62.00,
          comments: ""
        }
      ]
      this.displayLoading = false;
      this.oldRowData = [
        {
          id: 1522413076,
          name: 'Aaron Butler',
          status: 'Enrolled',
          overall_average: 66.00,
          comments: "",
        },
        {
          id: 1522415305,
          name: 'Aaron Clark',
          status: 'Enrolled',
          overall_average: 70.00,
          comments: ""
        },
        {
          id: 1522272226,
          name: 'Aaron Endicott',
          status: 'Enrolled',
          overall_average: 85.00,
          comments: ""
        },
        {
          id: 1548405785,
          name: 'Jamil',
          status: 'Enrolled',
          overall_average: 62.00,
          comments: ""
        }
      ];
    });
  }

  loginData() {
    this.Rest.login().subscribe({
      next: (res: any) => {
        if (res) {
          localStorage.setItem("loginToken", res.data.token);
        }
      },
      error: (error: any) => {
        console.log(error, "error");

      }
    })
  }

  public editTableFields() {
    this.displayLoading = true;
    this._displayEditable = !this._displayEditable;
    // let newEdit = this.child.toggleEdits('text');
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
    console.log(this.oldRowData, "this.oldRowData");
    console.log(this._state, "this._row");

    this.displayLoading = true;
    if (this._displayEditable) {
      this._displayEditable = false;
      // this.child.toggleEdits('text');
      this.oldRowData.forEach(element => {
        this._row.forEach(newData => {
          if (element.id == newData.id) {
            if (element.comments != newData.comments) {
              console.log(newData, "newData");
              let obj = {
                "academic_period_id": 32,
                "education_grade_id": 189,
                "student_id": 3540,
                "comment": newData.comments,
                "report_card_id": 6
              }
              let state = this._state == 'homeroom_teacher' ? 'homeroom' : this._state;
              this.Rest.postWithToken(`institutions/6/classes/568/reportcardcomment/${state}`, obj).subscribe({
                next: (res: any) => {
                  if (res) {
                    this.displayLoading = false;
                  }
                },
                error: (error: any) => {
                  console.log(error, "error Data 1");
                  this.displayLoading = false;
                }
              })

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
    }
  }

  _selectTabs(event: any) {
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
        this._row = [
          {
            id: 1522413076,
            name: 'Aaron Butler',
            status: 'Enrolled',
            overall_average: 66.00,
            comments: "",
          },
          {
            id: 1522415305,
            name: 'Aaron Clark',
            status: 'Enrolled',
            overall_average: 70.00,
            comments: ""
          },
          {
            id: 1522272226,
            name: 'Aaron Endicott',
            status: 'Enrolled',
            overall_average: 85.00,
            comments: ""
          },
          {
            id: 1548405785,
            name: 'Jamil',
            status: 'Enrolled',
            overall_average: 62.00,
            comments: ""
          }
        ];
        break;
      case "homeroom_teacher":
        this._row = [
          {
            id: 15224130760000000,
            name: 'Aaron Butler',
            status: 'Enrolled',
            overall_average: 50.00,
            comments: "",
          },
          {
            id: 1522415305,
            name: 'Aaron Clark',
            status: 'Enrolled',
            overall_average: 40.00,
            comments: ""
          },
          {
            id: 1522272226,
            name: 'Aaron Endicott',
            status: 'Enrolled',
            overall_average: 59.00,
            comments: ""
          },
          {
            id: 1548405785,
            name: 'Jamil',
            status: 'Enrolled',
            overall_average: 69.00,
            comments: ""
          },
          {
            id: 1548405785,
            name: 'Jamil',
            status: 'Enrolled',
            overall_average: 69.00,
            comments: ""
          },
          {
            id: 1548405785,
            name: 'Jamil',
            status: 'Enrolled',
            overall_average: 69.00,
            comments: ""
          }
        ];
        break;
    }
    setTimeout(() => {
      this.displayLoading = false;
    }, 3000);
  }

  ngOnDestroy(): void {
    super.destroyPageBaseSub();
  }
}
