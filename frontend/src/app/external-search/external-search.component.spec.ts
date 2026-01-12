import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ExternalSearchComponent } from './external-search.component';

describe('ExternalSearchComponent', () => {
  let component: ExternalSearchComponent;
  let fixture: ComponentFixture<ExternalSearchComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ExternalSearchComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ExternalSearchComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
