import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StudentMealResultComponent } from './student-meal-result.component';

describe('StudentMealResultComponent', () => {
  let component: StudentMealResultComponent;
  let fixture: ComponentFixture<StudentMealResultComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StudentMealResultComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(StudentMealResultComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
