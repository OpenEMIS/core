import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { AppComponent } from './app.component';
import { SharedModule } from './shared/shared.module';
import { CommonModule } from '@angular/common';
import { HttpClientModule } from '@angular/common/http';
import { KdComponentsModule } from 'openemis-styleguide-lib';
import { IntitutionModule } from './institution.module';
import { ApiService } from './api.service';
import { MatDialogModule } from "@angular/material/dialog";

@NgModule({
  declarations: [
    AppComponent
  ],
  imports: [
    BrowserModule,
    SharedModule,
    CommonModule,
    KdComponentsModule,
    BrowserAnimationsModule,
    HttpClientModule,
    IntitutionModule,
    MatDialogModule
  ],
  providers: [ApiService],
  bootstrap: [AppComponent]
})
export class AppModule { }
