import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StudentAttendanceReportComponent } from './student-attendance-report.component';

describe('StudentAttendanceReportComponent', () => {
  let component: StudentAttendanceReportComponent;
  let fixture: ComponentFixture<StudentAttendanceReportComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StudentAttendanceReportComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(StudentAttendanceReportComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
