import { Observable, Subscriber, timer } from 'rxjs';
import { IEnterpriseGetRowsRequest, RowNode, ExcelExportParams } from 'ag-grid/main';

interface Column {
  headerName?: string;
  field?: string;
  type?: 'input' | 'normal' | 'image';
  sortable?: boolean;
  filterable?: boolean;
  visible?: boolean;
  config?: any;
  class?: string;
  filterValue?: Array<string>;
}

interface ListColumn {
  DateofBirth?: Column;
  IdentityType?: Column;
  Examination?: Column;
  country?: Column;
  year?: Column;
  CandidateID?: Column;
  OpenEMISID?: Column;
  FirstName?: Column;
  LastName?: Column;
  Gender?: Column;
  Nationality?: Column;
}

export const TABLECOLUMN: ListColumn = {
  CandidateID: {
    headerName: 'Candidate Number',
    field: 'candidate_id',
    sortable: true,
    filterable: true,
    filterValue: []
  },
  OpenEMISID: {
    headerName: 'OpenEMIS ID',
    field: 'openemis_no',
    sortable: true,
    filterable: true,
    filterValue: []
  },
  FirstName: {
    headerName: 'First Name',
    field: 'first_name',
    sortable: true,
    filterable: true,
    filterValue: []
  },
  LastName: {
    headerName: 'Last Name',
    field: 'last_name',
    sortable: true,
    filterable: true,
    filterValue: []
  },
  Gender: {
    headerName: 'Gender',
    field: 'gender_id',
    sortable: true,
    filterable: true,
    filterValue: []
  },
  Nationality: {
    headerName: 'Nationality',
    field: 'nationality',
    sortable: true,
    filterable: false,
    filterValue: []
  },
  DateofBirth: {
    headerName: 'Date of Birth',
    field: 'date_of_birth',
    sortable: true,
    filterable: true,
    filterValue: []
  },
  Examination: {
    headerName: 'Examination',
    field: 'examination',
    sortable: true,
    filterable: true,
    filterValue: []
  },
  IdentityType: {
    headerName: 'Identity Type',
    field: 'identity_type',
    sortable: true,
    filterable: false
  }
};

export const CREATE_ROW: ( datalist?: Array<any>) => Array<any> = (
  datalist?: Array<any>
): Array<any> => {
  let row: Array<any> = [];
  for (let i = 0; i < datalist.length; i++) {
    let oneRow: any = {
      candidate_id: datalist[i].candidate_id,
      openemis_no: datalist[i].openemis_no,
      first_name: datalist[i].first_name,
      last_name: datalist[i].last_name,
      date_of_birth: datalist[i].date_of_birth,
      nationality: datalist[i].nationality,
      gender: datalist[i].gender,
      identity_number: datalist[i].schoolRandom,
      identity_type: datalist[i].identity_type,
      objectitem: {
        level: {
          default: i % 2,
          original: 3
        },
        another: [i, i + 1, i + 2, i + 3]
      },
      inputColumnVal: {
        inputFormVal: i.toString()
      }
    };

    row.push(oneRow);
  }

  return row;
};

export const CREATE_TABLE_CONFIG: (_id: string, _pagesize: number, _total: number) => any = (
  _id: string,
  _pagesize: number,
  _total: number
): any => {
  return {
    id: _id,
    loadType: 'server',
    gridHeight: 'auto',
    externalFilter: false,
    paginationConfig: {
      pagesize: _pagesize,
      total: _total
    },
    action: {
      enabled: true,
      list: [
        {
          //type: 'view',
          name: 'view',
          // path: '/registration/view',
          custom: true,
          callback: (_rowNode, _tableApi): void => {
            console.log('ListNode: Demo callback used in when clicking the rowNode.', _rowNode, _tableApi);
          }
        }
      ]
    },
    click: {
      type: 'router',
      // pathMap: 'view',
      path: '/registration/view',
      callback: (_rowNode: RowNode, _tableApi: ITableActionApi): void => {
        console.log('ListNode: Demo callback used in when clicking the rowNode.', _rowNode, _tableApi);
      }
    }
  };
};

// export const DUMMY_API_CALL: (_params: {
//   startRow: number;
//   endRow: number;
//   filterModel: any;
//   sortModel: any;
//   pagesize: number;
//   dataList?: Array<any>;
// }) => Observable<any> = (_params: {
//   startRow: number;
//   endRow: number;
//   filterModel: any;
//   sortModel: any;
//   pagesize: number;
//   dataList?: Array<any>;
// }): Observable<any> => {
//   return new Observable((_observer: Subscriber<any>): void => {
//     timer(1000).subscribe((): void => {
//       _observer.next(CREATE_ROW(_params.pagesize, _params.startRow, _params.dataList));
//       _observer.complete();
//     });
//   });
// };

export const FILTER_INPUTS: Array<any> = [
  {
    key: 'Dropdown1',
    visible: true,
    required: true,
    order: 1,
    controlType: 'dropdown',
    options: [
      {
        key: '',
        value: '-- Select Options 1 --'
      },
      {
        key: 1,
        value: 'Option 1'
      },
      {
        key: 2,
        value: 'Option 2'
      }
    ],
    events: true
  },
  {
    key: 'Dropdown2',
    visible: true,
    required: true,
    order: 1,
    controlType: 'dropdown',
    options: [
      {
        key: '',
        value: '-- Select --'
      }
    ],
    events: true
  },
  {
    key: 'Dropdown3',
    visible: true,
    required: true,
    order: 1,
    controlType: 'dropdown',
    options: [
      {
        key: '',
        value: '-- Select --'
      }
    ],
    events: true
  }
];
export interface ITableActionApi {
  deleteThisRow?: () => void;
}
