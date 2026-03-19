import { NgClass, SlicePipe } from '@angular/common';
import { Component, inject } from '@angular/core';
import { Router } from '@angular/router';
import { debounceTime, distinctUntilChanged, Subject, switchMap, timeout } from 'rxjs';

import { ApiService } from '../../../core/api/api.service';
import { PatientDetail, ProntuarioItem, ProntuarioPatient } from '../../../core/api/api.types';

@Component({
  selector: 'app-prontuario-page',
  imports: [NgClass, SlicePipe],
  templateUrl: './prontuario-page.component.html',
  styleUrl: './prontuario-page.component.scss',
})
export class ProntuarioPageComponent {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);
  private readonly search$ = new Subject<string>();

  protected loading = true;
  protected error = '';
  protected search = '';
  protected tab: 'receita' | 'atestado' = 'receita';
  protected patients: ProntuarioPatient[] = [];
  protected selectedPatient: PatientDetail | null = null;
  protected records: ProntuarioItem[] = [];

  constructor() {
    this.search$
      .pipe(
        debounceTime(250),
        distinctUntilChanged(),
        switchMap((term) => this.api.getPatientsWithRecords(term).pipe(timeout(12000))),
      )
      .subscribe({
        next: (patients) => {
          this.patients = patients;
          const first = patients[0]?.aluno_id;
          if (first) {
            this.selectPatient(first);
          } else {
            this.selectedPatient = null;
            this.records = [];
            this.loading = false;
          }
        },
        error: (error: unknown) => {
          this.error = this.toMessage(error);
          this.loading = false;
        },
      });

    this.search$.next('');
  }

  protected onSearch(term: string): void {
    this.search = term;
    this.loading = true;
    this.search$.next(term);
  }

  protected setTab(tab: 'receita' | 'atestado'): void {
    this.tab = tab;
  }

  protected selectPatient(id: number): void {
    this.loading = true;
    this.error = '';

    this.api
      .getPatient(id)
      .pipe(timeout(12000))
      .subscribe({
        next: (patient) => {
          this.selectedPatient = patient;
          this.api
            .getPatientRecords(id)
            .pipe(timeout(12000))
            .subscribe({
              next: (records) => {
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

  protected openAnamnese(): void {
    if (!this.selectedPatient) {
      return;
    }
    this.router.navigate(['/anamnese'], {
      queryParams: { aluno_id: this.selectedPatient.IdUsuario },
    });
  }

  private toMessage(error: unknown): string {
    const fallback = 'Nao foi possivel carregar o prontuario. Atualize a pagina ou refaca o login.';
    return error instanceof Error && error.message ? error.message : fallback;
  }
}
