import { Component, OnInit } from '@angular/core';
import { MatDialogRef } from '@angular/material/dialog';
import { ApiService } from '../api.service';

@Component({
  selector: 'app-dialog-open',
  templateUrl: './dialog-open.component.html',
  styleUrls: ['./dialog-open.component.css']
})
export class DialogOpenComponent implements OnInit {
  public toggleCurriculum: boolean = true;
  public displayDialog: boolean = false;
  selectLesson: any = '';
  addNewLesson: any = [];

  public nonCurriculum = {
    type: 'nonCurriculum',
    name: '',
    roomId: undefined,
    room: ''
  }

  public curriculum = {
    type: 'curriculum',
    typeId: undefined,
    subjectId: undefined,
    subject: '',
    roomId: undefined,
    room: ''
  }
  institutionRoomData: any[] = [];
  institutionSubject: any = [];
  lessonType: any = [];
  showDropdownErrorMsg: boolean = false;
  showTextErrorMsg: boolean = false;

  constructor(
    private Rest: ApiService,
    public dialogRef: MatDialogRef<DialogOpenComponent>
  ) {

  }

  ngOnInit(): void {
    // this.getInstitutionRooms();
    this.getLessonType();
    this.displayDialog = true;
  }

  getInstitutionRooms() {
    this.Rest.getWithToken('institutions/6/academicperiods/32/rooms').subscribe({
      next: (response: any) => {
        if (response) {
          this.institutionRoomData = [];
          response?.data.forEach((element: any) => {
            let obj = {
              id: element.id,
              name: element.name
            }
            this.institutionRoomData.push(obj);
            // this.curriculum.room = this.institutionRoomData[0].name;
            // this.curriculum.roomId = this.institutionRoomData[0].id;
            // this.nonCurriculum.room = this.institutionRoomData[0].name;
            // this.nonCurriculum.roomId = this.institutionRoomData[0].id;
          });
          this.institutionRoomData.unshift({ id: '', name: 'Select room' })
          console.log(this.institutionRoomData, "institutionRoomData");
          this.getInstitutionSubject();
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
          }
        }
      }
    })
  }

  getInstitutionSubject() {
    this.Rest.getWithToken(`institutions/classes/568/subjects`).subscribe({
      next: (response: any) => {
        if (response) {
          response?.data.forEach((element: any) => {
            let obj = {
              id: element?.institution_subject?.id,
              name: element?.institution_subject?.name
            }
            this.institutionSubject.push(obj);
            // this.curriculum.subject = this.institutionSubject[0].name;
            // this.curriculum.subjectId = this.institutionSubject[0].id;
          });
          this.institutionSubject.unshift({id: '', name: 'Select Subject'});
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
          }
        }
      }
    })
  }

  getLessonType() {
    this.Rest.getWithToken('schedules/lessons/types').subscribe({
      next: (response: any) => {
        response?.data.forEach(element => {
          if (element.id) {
            let obj = {
              id: element.id,
              name: element.name
            }
            this.lessonType.push(obj);
          }
        });
        this.lessonType.unshift({ id: '', name: '--Select--'});
        // this.curriculum.type = this.lessonType[0].name;
        // this.curriculum.typeId = this.lessonType[0].id;
        console.log(this.lessonType, "lessonType");
        this.displayDialog = true;
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
          }
        }
      }
    })
  }

  curriculumChanged(curriculum: HTMLSelectElement) {
    this.toggleCurriculum = !this.toggleCurriculum
  }

  onNonCurriculumInput(nonCurriculumName: HTMLInputElement) {
    this.nonCurriculum.name = nonCurriculumName.value;
  }

  onRoomSelect(roomSelect: HTMLSelectElement, index: any) {
    // let indexData = this.institutionRoomData.findIndex(obj => obj.id == roomSelect.value);
    // if (this.toggleCurriculum) {
    //   this.curriculum.room = this.institutionRoomData[indexData].name;
    //   this.curriculum.roomId = this.institutionRoomData[indexData].id;
    // } else {
    //   this.nonCurriculum.room = this.institutionRoomData[indexData].name;
    //   this.nonCurriculum.roomId = this.institutionRoomData[indexData].id;
    // }
    this.addNewLesson[index].room = roomSelect.value;
  }

  onCloseClick() {
    this.dialogRef.close()
  }

  onSubjectChanged(pickSubject: HTMLSelectElement, index: any) {
    // let indexData = this.institutionSubject.findIndex(obj => obj.id == pickSubject.value);
    // this.curriculum.subject = this.institutionSubject[indexData].name;
    // this.curriculum.subjectId = this.institutionSubject[indexData].id;
    this.addNewLesson[index].subject = pickSubject.value;
    this.showDropdownErrorMsg = false;
  }

  onAddClick(index: any, status: any) {
    // if (this.toggleCurriculum) {
    //   this.dialogRef.close(this.curriculum);
    // } else {
    //   this.nonCurriculum.name = this.nonCurriculum.name.length ? this.nonCurriculum.name : 'Activity'
    //   this.dialogRef.close(this.nonCurriculum);
    // }

    if(status == 'curriculum' && (this.addNewLesson[index].subject == '' || this.addNewLesson[index].subject == undefined)){
      this.showDropdownErrorMsg = true;
    } else if (status == 'nonCurriculum' && (this.addNewLesson[index].subject == '' || this.addNewLesson[index].subject == undefined)) {
      this.showTextErrorMsg = true;
    }
    else {
      this.dialogRef.close(this.addNewLesson[index]);
    }
  }

  addLesson() {
    if (this.selectLesson == 2) {
      this.addNewLesson.push({ 'type': 'nonCurriculum', 'subject': '', 'room': '' });
      this.getInstitutionRooms();
    } else if (this.selectLesson == 1) {
      this.addNewLesson.push({ 'type': 'curriculum', 'subject': '', 'room': '' });
      this.getInstitutionRooms();
    }
    console.log(this.addNewLesson, "this.addNewLesson");
  }

  removeData(index: any) {
    this.addNewLesson.splice(index, 1);
  }

}
