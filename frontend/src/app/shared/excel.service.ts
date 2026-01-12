import { Injectable } from "@angular/core";
import * as fileSaver from "file-saver";

import * as Excel from "exceljs";

import { logoBase64 } from "./config.default-val";

declare const ExcelJS: any;

@Injectable({
  providedIn: "root",
})
export class ExcelService {
  private workbook: any;
  private dataSheet: any;
  private referenceSheet: any;
  private referenceHeadingsAddressObject = {};
  private totalColumns: number = 0;
  private referenceNames: any;
  private font = {
    name: "calibri",
    size: 16,
    bold: true,
  };
  private alignment: any = {
    vertical: "middle",
    horizontal: "left",
  };
  private columnHeaderFill: any = {
    type: "pattern",
    pattern: "solid",
    fgColor: { argb: "6699CC" },
  };
  private columnHeaderFont = {
    name: "Calibri",
    size: 11,
    bold: true,
    color: { argb: "FFFFFF" },
  };
  private alphabets = [];

  public idAsKey = false;

  constructor() {
    this.initialiseAlphabets();
  }

  /* This function is used when reference data object has id as key and name as value. For example: {8: "0520/12 - LISTENING COMPREHENSION"},
    here 8 is id which is key of the object and "0520/12 - LISTENING COMPREHENSION" is the value of the object. This is done because
    earlier backend was sending string/value as key and id as value which is a very bad idea because 2 same strings can replace eacother
    if used as ids in obj and can can data loss.
    Later they reversed the order and hence this function is used to identify the type of reference data and maintain order in printing of
    reference data in reference sheet. Otherwise the name and id used to exchange columns with eachother due to change of order in api.

    Just call this method before init when id of reference data is coming as key and string is coming as value of obj like this
    {8: "0520/12 - LISTENING COMPREHENSION"}
  */
  setIdAsKeyInReferenceData() {
    this.idAsKey = true;
  }

  public init(
    fileName,
    title,
    dataColumnHeadings,
    referenceNames,
    referenceData,
    dataValidationHeadings,
    dataSheetData?
  ) {
    this.workbook = new ExcelJS.Workbook();
    this.dataSheet = this.workbook.addWorksheet("Data");
    // this.referenceSheet = this.workbook.addWorksheet("Reference");
    this.addOELogo([this.dataSheet]);
    this.addTitle([
      { title: title, sheet: this.dataSheet },
      // { title: "References", sheet: this.referenceSheet },
    ]);

    this.dataSheet.addRow(dataColumnHeadings);
    this.formattingDataSheet(dataColumnHeadings);
    dataSheetData ? this.addDataToDataSheet(dataSheetData) : "";
    this.setReferenceNames(referenceNames);
    this.referenceNames = referenceNames;
    this.setReferenceData(referenceData);
    this.setDataValidations(dataValidationHeadings, referenceData);

    this.writeFile(fileName);
  }

  protected formattingDataSheet(columnArray) {
    /* Formatting column headings below OE logo*/
    let headColumnRow = this.dataSheet.getRow(2);
    headColumnRow.height = 60;
    for (let i = 1; i <= columnArray.length; i++) {
      if (i > 2) this.dataSheet.getColumn(i).width = 20;
      headColumnRow.getCell(i).fill = this.columnHeaderFill;
      headColumnRow.font = this.columnHeaderFont;
      let align = JSON.parse(JSON.stringify(this.alignment));
      align["wrapText"] = true;
      headColumnRow.alignment = align;
      this.totalColumns++;
    }
  }

  protected addDataToDataSheet(dataSheetData) {
    /* Adding data to data sheet, if any*/

    let rowNum = 3;
    dataSheetData.forEach((element) => {
      let row = this.dataSheet.getRow(rowNum);
      let arrKey = 0;
      for (let col = 1; col <= this.totalColumns; col++) {
        let cell = row.getCell(col);
        cell.value = element[arrKey];
        arrKey++;
      }
      rowNum++;
    });
  }

  protected addOELogo(sheets) {
    sheets.forEach((sheet) => {
      let logo = this.workbook.addImage({
        base64: logoBase64,
        extension: "png",
      });
      sheet.addImage(logo, "A1:B1");
      sheet.mergeCells("A1:B1");
    });
  }

  protected addTitle(sheets) {
    sheets.forEach((sheet) => {
      let headerRow = sheet.sheet.getRow(1);

      /* setting height and width for OE logo.  */
      headerRow.height = 80;
      sheet.sheet.getColumn(1).width = 20;
      sheet.sheet.getColumn(2).width = 10;

      /* Setting and formatting title next to OE logo */
      let title = headerRow.getCell(3);
      title.value = sheet.title;
      title.style = { font: this.font, alignment: this.alignment };
      sheet.sheet.mergeCells("C1:E1");
    });
  }

  protected setReferenceNames(referenceNames) {
    /* Adding Headings of references in the sheet below OE logo */
    let row = this.dataSheet.addRow([]);
    let i = 1;
    referenceNames.forEach((referenceName) => {
      let cell = row.getCell(i);
      cell.value = referenceName.Name;
      this.dataSheet.getColumn(cell.fullAddress.col).width = 30;
      // this.dataSheet.getColumn(cell.fullAddress.col + 1).width = 10;
      cell.alignment = this.alignment;
      cell.font = {
        name: "calibri",
        size: 11,
        bold: false,
      };
      cell.border = {
        top: { style: "thin" },
        left: { style: "thin" },
        bottom: { style: "thin" },
        right: { style: "thin" },
      };
      // let colName1 =
      //   i > 26 ? "A" + this.alphabets[i - (1 + 26)] : this.alphabets[i - 1];
      // let colName2 = i > 26 ? "A" + this.alphabets[i - 26] : this.alphabets[i];
      // this.dataSheet.mergeCells(colName1 + "2:" + colName2 + "2");
      // i = i + 2;
    });
  }

  /* sets reference data with name and id. */
  protected setReferenceData(referenceData) {
    let row = this.dataSheet.addRow([]);
    row.alignment = this.alignment;

    let col = 1;
    this.referenceNames.forEach((referenceDataArr) => {
      let name;
      let id;
      if (this.idAsKey) {
        name = row.getCell(col + 1);
        // id = row.getCell(col);
      } else {
        name = row.getCell(col);
        // id = row.getCell(col + 1);
      }
      name.value = referenceData[referenceDataArr].header[0];
      // id.value = referenceData[referenceDataArr].header[1];
      // if (["Candidate"].includes(referenceDataArr)) {
      //   this.referenceHeadingsAddressObject[referenceDataArr] = col;
      // } else {
      //   this.referenceHeadingsAddressObject[referenceDataArr] = col + 1;
      // }
      // name.font = this.columnHeaderFont;
      // id.font = this.columnHeaderFont;
      // name.fill = this.columnHeaderFill;
      // id.fill = this.columnHeaderFill;

      // j is the number of row where we will begin to add references after adding logo, subtitle and col heading. hence j=4.
      let j = 3;
      let refData = referenceData[referenceDataArr].data;

      for (let x in refData) {
        // this.dataSheet.getCell(j, col).value = this.idAsKey
        //   ? refData[x].Name
        //   : x;
        for (let key in refData[x]) {
          if (key == 'Name') {
            this.dataSheet.getCell(j, col).value = this.idAsKey
              ? x
              : refData[x].Name;
          } else {
            this.dataSheet.getCell(j, col).value = this.idAsKey
            ? x
            : refData[x];
          }
        }
        this.dataSheet.getRow(j).alignment = this.alignment;
        j++;
      }
      col = col + 1;
    });
  }

  protected setDataValidations(dataValidationHeadings, referenceData) {
    this.dataSheet.getRow(2).eachCell((cell, colNumber) => {
      // finding the headings in datasheet by looping over the headings row.
      if (Object.keys(dataValidationHeadings).indexOf(cell.value) > -1) {
        // looping over cells in the column, starting from row(x) 3.
        for (let x = 3; x <= 100; x++) {
          this.dataSheet.getCell(x, colNumber).dataValidation = {
            type: "list",
            formulae: [
              "Reference!"
              // this.getDataValidationRange(
              //   referenceData,
              //   dataValidationHeadings,
              //   cell.value
              // )
            ],
            // formulae: ['Reference!B4:B5'],
            allowBlank: true,
          };
        }
      }
    });
  }

  protected getDataValidationRange(referenceData, headings, currentValue) {
    // ['"One,Two,Three,Four"']
    let colNumber = this.referenceHeadingsAddressObject[headings[currentValue]];
    let startRow = 4;

    let endRow =
      Object.keys(referenceData[headings[currentValue]].data).length + 3;
    let range =
      this.dataSheet.getCell(startRow, colNumber).fullAddress.address +
      ":" +
      this.dataSheet.getCell(endRow, colNumber).fullAddress.address;
    return range;
  }

  protected initialiseAlphabets() {
    for (let i = 65; i <= 90; i++) {
      this.alphabets.push(String.fromCharCode(i));
    }
  }

  protected writeFile(fileName) {
    this.workbook.xlsx.writeBuffer().then(
      (data) => {
        let blob = new Blob([data], {
          type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        fileSaver.saveAs(blob, fileName + ".xlsx");
      },
      (err) => {
        console.log("errr", err);
      }
    );
  }
}
