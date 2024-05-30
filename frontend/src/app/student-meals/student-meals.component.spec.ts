import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StudentMealsComponent } from './student-meals.component';

describe('StudentMealsComponent', () => {
  let component: StudentMealsComponent;
  let fixture: ComponentFixture<StudentMealsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StudentMealsComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(StudentMealsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
