import { NgClass } from '@angular/common';
import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { timeout } from 'rxjs';

import { ApiService } from '../../../core/api/api.service';
import { PatientItem } from '../../../core/api/api.types';

@Component({
  selector: 'app-patients-list-page',
  imports: [FormsModule, NgClass],
  templateUrl: './patients-list-page.component.html',
  styleUrl: './patients-list-page.component.scss',
})
export class PatientsListPageComponent {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);

  protected loading = true;
  protected error = '';
  protected search = '';
  protected filter: 'todos' | 'recentes' | 'retornos' | 'criticos' = 'todos';
  protected patients: PatientItem[] = [];

  constructor() {
    this.load();
  }

  protected setFilter(filter: 'todos' | 'recentes' | 'retornos' | 'criticos'): void {
    this.filter = filter;
  }

  protected onSearch(): void {
    this.load();
  }

  protected openPatient(patientId: number): void {
    this.router.navigate(['/paciente', patientId]);
  }

  private load(): void {
    this.loading = true;
    this.error = '';
    this.api
      .getPatients(this.search)
      .pipe(timeout(12000))
      .subscribe({
        next: (patients) => {
          this.patients = patients;
          this.loading = false;
        },
        error: (error: unknown) => {
          this.error = this.toMessage(error);
          this.loading = false;
        },
      });
  }

  private toMessage(error: unknown): string {
    const fallback = 'Nao foi possivel carregar pacientes. Atualize a pagina ou refaca o login.';
    return error instanceof Error && error.message ? error.message : fallback;
  }
}
