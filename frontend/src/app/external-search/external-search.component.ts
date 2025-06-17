import { Component, OnInit } from '@angular/core';
import { ITableApi, ITableColumn, ITableConfig } from 'openemis-styleguide-lib';
import { TABLE_COLUMN_LIST } from './external-search.config';
import { timer } from 'rxjs';
import { ApiService } from '../api.service';

@Component({
  selector: 'app-external-search',
  templateUrl: './external-search.component.html',
  styleUrls: ['./external-search.component.css']
})
export class ExternalSearchComponent implements OnInit {
  public _row: Array<any> = [];
  public _column: Array<ITableColumn> = [];
  public _tableApi: ITableApi = {};
  public _config: ITableConfig = {
    id: 'listTable',
    gridHeight: "auto",
    loadType: "oneshot",
    externalFilter: false,
    rowContentHeight: 25,
    paginationConfig: {
      pagesize: 10,
    },
  };

  constructor(
    private Rest: ApiService
  ) { }

  ngOnInit(): void {
    this.Rest.enableNextButton();

    let columns: Array<any> = [];
    columns.push(TABLE_COLUMN_LIST.name);
    columns.push(TABLE_COLUMN_LIST.gender);
    columns.push(TABLE_COLUMN_LIST.dob);
    columns.push(TABLE_COLUMN_LIST.nationality);
    columns.push(TABLE_COLUMN_LIST.identityType);
    columns.push(TABLE_COLUMN_LIST.identityNumber);
    this._column = columns;

    timer(2000).subscribe((): void => {
      this._row = [
        {
          id: 1522413076,
          name: 'Aaron Butler',
          gender: 'Male',
          dob: '2022-10-01',
          nationality: "Indian",
          identityType: "Enable",
          identityNumber: 101
        },
        {
          id: 1522413080,
          name: 'Aaron Butler 1',
          gender: 'Male',
          dob: '2022-10-04',
          nationality: "African",
          identityType: "Disable",
          identityNumber: 102
        },
        {
          id: 1522413088,
          name: 'Aaron Butler 2',
          gender: 'Male',
          dob: '2022-10-04',
          nationality: "African",
          identityType: "Disable",
          identityNumber: 103
        },
      ]
    });
  }

}
