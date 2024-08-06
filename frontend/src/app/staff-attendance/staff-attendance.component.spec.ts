import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StaffAttendanceComponent } from './staff-attendance.component';

describe('StaffAttendanceComponent', () => {
  let component: StaffAttendanceComponent;
  let fixture: ComponentFixture<StaffAttendanceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StaffAttendanceComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(StaffAttendanceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
