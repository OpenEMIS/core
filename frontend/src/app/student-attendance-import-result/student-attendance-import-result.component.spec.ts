import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StudentAttendanceImportResultComponent } from './student-attendance-import-result.component';

describe('StudentAttendanceImportResultComponent', () => {
  let component: StudentAttendanceImportResultComponent;
  let fixture: ComponentFixture<StudentAttendanceImportResultComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StudentAttendanceImportResultComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(StudentAttendanceImportResultComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
