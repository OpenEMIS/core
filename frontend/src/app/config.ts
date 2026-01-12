interface TableColumns {
  id?: any;
  name?: any;
  status?: any;
  overall_average?: any;
  comments?: any;
  total_mark?: any;
  comment_code?: any;
  modified_by?: any;
}

const COLUMN_ID: any = {
  headerName: "OpenEMIS ID",
  field: "id",
  sortable: false,
  filterable: false,
  filterValue: ['Equals', 'Not Equals', 'Starts with', ' Ends with', 'Contains', 'Not contains'],
  visible: true,
  width: 20,
  pinned: true
};

const COLUMN_NAME: any = {
  headerName: "Name",
  field: "name",
  sortable: false,
  filterable: false,
  visible: true,
  width: 30,
  filterValue: ['Equals', 'Not Equals', 'Starts with', ' Ends with', 'Contains', 'Not contains'],
  class: "ag-name",
  // type: 'normal',
  enableValue: false,
  enablePivot: true,
  enableRowGroup: true,
  rowDrag: false,
};

const COLUMN_STATUS: any = {
  headerName: "Status",
  field: "status",
  sortable: false,
  filterable: false,
  width: 20,
  filterValue: ['Equals', 'Not Equals', 'Starts with', ' Ends with', 'Contains', 'Not contains'],
  visible: true,
  enableValue: false,
  enablePivot: true,
  enableRowGroup: true,
};

const OVERALL_AVERAGE: any = {
  headerName: "Overall Average",
  field: "overall_average",
  sortable: false,
  filterable: false,
  filterValue: ['Equals', 'Not Equals', 'Less Than', 'Less Than or Equal', 'Greater Than', 'Greater Than or Equal', 'In Range'],
  visible: true,
  width: 20,
  enableValue: false,
  enablePivot: true,
  enableRowGroup: true
};

const TOTAL_MARK: any = {
  headerName: "Total Mark",
  field: "total_mark",
  sortable: false,
  filterable: false,
  visible: true,
  width: 20,
  enableValue: false,
  enablePivot: true,
  enableRowGroup: false
}

const COMMENT_CODE: any = {
  headerName: "Comment Code",
  field: "comment_code",
  sortable: false,
  filterable: false,
  visible: true,
  width: 250,
  enableValue: false,
  enablePivot: true,
  enableRowGroup: false,
  suppressSorting: true,
  cellRenderer: (params) => {
    console.log(params, "params");
    let context = params.context;
    let commentTypes = context.commentTypes;
    let mode = params.context.mode;
    let data = params.data;
    if (mode == 'edit') {
      return getEditCommentElement(data, commentTypes);
    } else {
      return getViewCommentElement(data, commentTypes);
    }
  }
}

const MODIFIED_BY: any = {
  headerName: "Modified By",
  field: "modified_by",
  sortable: false,
  filterable: false,
  visible: true,
  width: 20,
  enableValue: false,
  enablePivot: true,
  enableRowGroup: false
}

const COMMENTS: any = {
  headerName: "Comments",
  field: "comments",
  sortable: false,
  filterable: false,
  visible: true,
  width: 250,
  enableValue: false,
  enablePivot: true,
  enableRowGroup: true,
  canEdit: true
};


export const TABLE_COLUMN_LIST: TableColumns = {
  id: COLUMN_ID,
  name: COLUMN_NAME,
  status: COLUMN_STATUS,
  overall_average: OVERALL_AVERAGE,
  comments: COMMENTS,
  total_mark: TOTAL_MARK,
  comment_code: COMMENT_CODE,
  modified_by: MODIFIED_BY
};

function getEditCommentElement(data: any, commentTypes: any) {
  console.log(data,"data");
  
  let eCell = document.createElement('div');
  eCell.setAttribute("class", "oe-select-wrapper input-select-wrapper");
  eCell.setAttribute("id", "edit_comment");
  let eSelect = document.createElement("select");
  eSelect.setAttribute('style', 'width: 309px;');
  commentTypes.forEach((obj: any) => {
    let eOption = document.createElement("option");
    let labelText = obj.name;
    eOption.setAttribute("value", obj.id);
    if(obj.id == data.comment_code) {
    eOption.setAttribute("selected", obj.id);
    }
    eOption.innerHTML = labelText;
    eSelect.appendChild(eOption);
  });
  eSelect.addEventListener('change', () => {
    let oldValue = data.comment_code;
    let newValue = eSelect.value;
    console.log(commentTypes, newValue, "commentTypes");

    let commentTypeObj = commentTypes.find(obj => obj.id == newValue);
    console.log(commentTypeObj, "commentTypeObj");
    console.log(data, "data");
    data.comment_code = commentTypeObj?.id;
    if (newValue != oldValue) {
      let oldParams = {
        comment_code: oldValue
      };
      data.comment_code = newValue;
    }

    //   switch (mealTypeObj.name) {
    //     case 'None':
    //       data.institution_student_meal.meal_received_id = 3;
    //       break;

    //     case 'Not Received':
    //       data.institution_student_meal.meal_received_id = 2;
    //       break;

    //     case 'Received':
    //       data.institution_student_meal.meal_received_id = newValue;
    //       break;
    //   }
    //   oldValue = newValue;
    //   // data.institution_student_meal.meal_received_id = newValue;
    //   let refreshParams = {
    //     columns: ['institution_student_meal.meal_benefit'],
    //     force: true
    //   }

    //   api.refreshCells(refreshParams);
    // }

  })
  eCell.appendChild(eSelect);
  return eCell;


}

function getViewCommentElement(data: any, commentType: any) {
  if (data.hasOwnProperty('comment_code')) {

    let html = ''
    let newIndex = commentType.findIndex(obj => obj?.id == data?.comment_code)
    if(newIndex){
      html = commentType[newIndex]?.name;
    } else {
      html = '';
    }
    return html;
  }
}
