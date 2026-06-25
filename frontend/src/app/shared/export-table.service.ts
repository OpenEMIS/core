import { Injectable } from '@angular/core';
import * as fs from 'file-saver';
import { logoBase64 } from './config.default-val';

declare const ExcelJS: any;

@Injectable({
  providedIn: 'root'
})

export class ExportTableService {

    private workbook:any;
    private dataSheet:any;
    private columns: any;

    constructor(){}

    public init(columnHeadings, fileName: string, rowData){

        this.workbook = new ExcelJS.Workbook();
        this.dataSheet = this.workbook.addWorksheet('Data');

        this.addColumns(columnHeadings);
        this.addCellData(rowData);
        this.formatColumns();
        this.writeFile(fileName);
    }

    public addColumns(columnHeadings){
        let colArr = [];
        columnHeadings.forEach(element => {
            colArr.push(element.headerName)
        });
        this.columns = columnHeadings;
        this.dataSheet.addRow(colArr);
    }

    public addCellData(rowData){

        rowData.forEach(rowEle => {

            let rowArr = [];
            this.columns.forEach(colEle => {
                rowArr.push(rowEle[colEle['field']]);
            });
            this.dataSheet.addRow(rowArr);
        });
    }

    private formatColumns(){
        for(let i=1; i<=this.columns.length; i++){
            this.dataSheet.getColumn(i).width = 50;
        }
        this.dataSheet.getRow(1).font = {
            name: 'calibri',
            size: 12,
            bold: true
          }
    }

    protected writeFile(fileName){
        this.workbook.xlsx.writeBuffer().then((data) => {
          let blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
          fs.saveAs(blob, fileName+'.xlsx');
        }, (err) =>{
          console.log('errr', err)
        });
    }
}
