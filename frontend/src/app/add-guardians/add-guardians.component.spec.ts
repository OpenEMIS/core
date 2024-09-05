import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AddGuardiansComponent } from './add-guardians.component';

describe('AddGuardiansComponent', () => {
  let component: AddGuardiansComponent;
  let fixture: ComponentFixture<AddGuardiansComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AddGuardiansComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(AddGuardiansComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
