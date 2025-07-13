import { Component, EventEmitter, Input, OnDestroy, OnInit, Output } from '@angular/core';
import { ITableApi, ITableColumn, ITableConfig, ITableDatasourceParams, KdPageBase, KdPageBaseEvent, KdTableDatasourceEvent, KdTableEvent, KdTableSelectionUpdateEvent } from 'openemis-styleguide-lib';
import { TABLE_COLUMN_LIST } from './internal-search.config';
import { timer } from 'rxjs';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-internal-search',
  templateUrl: './internal-search.component.html',
  styleUrls: ['./internal-search.component.css']
})
export class InternalSearchComponent extends KdPageBase implements OnInit, OnDestroy {
  @Input() internalSearchData: any;
  @Output() internalSearchSelectedValue: EventEmitter<any> = new EventEmitter();
  public _row: Array<any> = [];
  public _column: Array<ITableColumn> = [];
  public _tableApi: ITableApi = {};
  public _config: ITableConfig = {
    id: 'listTable',
    gridHeight: 'auto',
    loadType: 'server',
    externalFilter: false,
    rowContentHeight: 25,
    selection: {
      type: 'single',
      returnKey: ['id', 'name']
    },
    paginationConfig: {
      pagesize: 10
    }
  };


  displayTable: boolean = false;
  public _tableEventSubscription: any;

  constructor(
    _router: Router,
    _activatedRoute: ActivatedRoute,
    _pageEvent: KdPageBaseEvent,
    private _kdTableEvent: KdTableEvent
  ) {
    super({
      router: _router,
      activatedRoute: _activatedRoute,
      pageEvent: _pageEvent
    });
  }

  ngOnInit(): void {
    console.log(this.internalSearchData, "internalSearchData");

    let columns: Array<any> = [];
    columns.push(TABLE_COLUMN_LIST.id);
    columns.push(TABLE_COLUMN_LIST.name);
    columns.push(TABLE_COLUMN_LIST.gender);
    columns.push(TABLE_COLUMN_LIST.dob);
    columns.push(TABLE_COLUMN_LIST.nationality);
    columns.push(TABLE_COLUMN_LIST.identityType);
    columns.push(TABLE_COLUMN_LIST.identityNumber);
    columns.push(TABLE_COLUMN_LIST.accountType);
    this._column = columns;

    timer(1000).subscribe((): void => {
      if (this.internalSearchData?.data?.data.length > 0) {
        this.internalSearchData?.data?.data.forEach((element: any) => {
          let obj = {
            id: element?.openemis_no,
            name: element?.name,
            gender: element?.gender,
            dob: element?.date_of_birth,
            nationality: element?.nationality,
            identityType: element?.identity_type,
            identityNumber: element?.identity_number,
            accountType: element?.account_type
          }
          this._row.push(obj);
        });
        this.displayTable = true;
      } else {
        this._row = [];
        this.displayTable = true;
      }
      this._tableEventSubscription = this._kdTableEvent.onKdTableEventList('listTable').subscribe((_event: any): void => {
        if (_event instanceof KdTableDatasourceEvent) {
          console.log('KdTableDatasourceEvent', _event);

          timer(2000).subscribe((): void => {
            let datasourceParams: ITableDatasourceParams = {
              rows: this._row,
              total: this._row.length
            };

            _event.subscriber.next(datasourceParams);
            _event.subscriber.complete();
          });
        }
        else if (_event instanceof KdTableSelectionUpdateEvent) {
          console.log('KdTableSelectionUpdateEvent', _event);
          if (_event?.triggerNode) {
            this.internalSearchSelectedValue.emit(_event.triggerNode);
          }
        }
      });
    });
  }

  ngOnDestroy(): void {
    super.destroyPageBaseSub();

    if (typeof this._tableEventSubscription !== 'undefined') {
      this._tableEventSubscription.unsubscribe();
    }
  }

}
