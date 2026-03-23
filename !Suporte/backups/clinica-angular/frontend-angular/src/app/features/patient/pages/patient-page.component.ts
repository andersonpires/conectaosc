import { NgClass, SlicePipe } from '@angular/common';
import { Component, inject } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { forkJoin, timeout } from 'rxjs';

import { ApiService } from '../../../core/api/api.service';
import { PatientDetail, PatientItem, ProntuarioItem } from '../../../core/api/api.types';

@Component({
  selector: 'app-patient-page',
  imports: [NgClass, SlicePipe],
  templateUrl: './patient-page.component.html',
  styleUrl: './patient-page.component.scss',
})
export class PatientPageComponent {
  private readonly api = inject(ApiService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  protected loading = true;
  protected error = '';
  protected patients: PatientItem[] = [];
  protected currentPatient: PatientDetail | null = null;
  protected records: ProntuarioItem[] = [];

  constructor() {
    this.route.paramMap.subscribe((params) => {
      const id = Number(params.get('id'));
      this.bootstrap(Number.isFinite(id) && id > 0 ? id : null);
    });
  }

  protected selectPatient(id: number): void {
    this.router.navigate(['/paciente', id]);
  }

  protected ageFromBirth(date?: string): number | null {
    if (!date) return null;
    const birthDate = new Date(date);
    if (Number.isNaN(birthDate.getTime())) return null;
    const now = new Date();
    let age = now.getFullYear() - birthDate.getFullYear();
    const monthDelta = now.getMonth() - birthDate.getMonth();
    if (monthDelta < 0 || (monthDelta === 0 && now.getDate() < birthDate.getDate())) age -= 1;
    return age;
  }

  private bootstrap(id: number | null): void {
    this.loading = true;
    this.error = '';

    this.api
      .getPatients()
      .pipe(timeout(12000))
      .subscribe({
        next: (patients) => {
          this.patients = patients;
          const selectedId = id ?? patients[0]?.IdUsuario;
          if (!selectedId) {
            this.currentPatient = null;
            this.records = [];
            this.loading = false;
            return;
          }

          forkJoin({
            patient: this.api.getPatient(selectedId).pipe(timeout(12000)),
            records: this.api.getPatientRecords(selectedId).pipe(timeout(12000)),
          }).subscribe({
            next: ({ patient, records }) => {
              this.currentPatient = patient;
              this.records = records;
              this.loading = false;
            },
            error: (error: unknown) => {
              this.error = this.toMessage(error);
              this.loading = false;
            },
          });
        },
        error: (error: unknown) => {
          this.error = this.toMessage(error);
          this.loading = false;
        },
      });
  }

  private toMessage(error: unknown): string {
    const fallback = 'Nao foi possivel carregar o paciente. Atualize a pagina ou refaca o login.';
    return error instanceof Error && error.message ? error.message : fallback;
  }
}
