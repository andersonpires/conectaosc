import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { map, Observable } from 'rxjs';

import { resolveApiBase } from '../config/runtime-paths';
import {
  AgendaEvent,
  AnamneseItem,
  ApiEnvelope,
  DashboardSummary,
  PatientDetail,
  PatientItem,
  Professional,
  ProntuarioPatient,
  ProntuarioItem,
} from './api.types';

@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly http = inject(HttpClient);
  private readonly apiBase = resolveApiBase();

  getPatients(search = ''): Observable<PatientItem[]> {
    const params = search ? new HttpParams().set('search', search) : undefined;
    return this.http
      .get<ApiEnvelope<{ pacientes: PatientItem[] }>>(`${this.apiBase}/pacientes`, { params })
      .pipe(map((response) => response.data.pacientes ?? []));
  }

  getPatient(id: number): Observable<PatientDetail> {
    return this.http
      .get<ApiEnvelope<{ paciente: PatientDetail }>>(`${this.apiBase}/pacientes/${id}`)
      .pipe(map((response) => response.data.paciente));
  }

  getPatientRecords(id: number): Observable<ProntuarioItem[]> {
    const params = new HttpParams().set('aluno_id', id);
    return this.http
      .get<ApiEnvelope<{ prontuarios: ProntuarioItem[] }>>(`${this.apiBase}/prontuarios`, { params })
      .pipe(map((response) => response.data.prontuarios ?? []));
  }

  getProfessionals(): Observable<Professional[]> {
    return this.http
      .get<ApiEnvelope<{ profissionais: Professional[] }>>(`${this.apiBase}/profissionais`)
      .pipe(map((response) => response.data.profissionais ?? []));
  }

  getDashboardSummary(date: string): Observable<DashboardSummary> {
    const params = new HttpParams().set('view', 'day').set('date', date);
    return this.http
      .get<ApiEnvelope<{ eventos: AgendaEvent[] }>>(`${this.apiBase}/agenda`, { params })
      .pipe(
        map((response) => {
          const appointments = response.data.eventos ?? [];
          const completedCount = appointments.filter((item) => item.status === 'concluida').length;
          const pendingConfirmation = appointments.filter(
            (item) => item.status === 'confirmacao_solicitada',
          ).length;
          const upcomingCount = appointments.length;

          return {
            date,
            appointments,
            completedCount,
            pendingConfirmation,
            upcomingCount,
            estimatedRevenue: Number((upcomingCount * 65.5).toFixed(2)),
          };
        }),
      );
  }

  getAgendaDay(date: string): Observable<AgendaEvent[]> {
    const params = new HttpParams().set('view', 'day').set('date', date);
    return this.http
      .get<ApiEnvelope<{ eventos: AgendaEvent[] }>>(`${this.apiBase}/agenda`, { params })
      .pipe(map((response) => response.data.eventos ?? []));
  }

  getPatientsWithRecords(search = ''): Observable<ProntuarioPatient[]> {
    const params = search ? new HttpParams().set('search', search) : undefined;
    return this.http
      .get<ApiEnvelope<{ pacientes: ProntuarioPatient[] }>>(`${this.apiBase}/prontuarios/pacientes`, {
        params,
      })
      .pipe(map((response) => response.data.pacientes ?? []));
  }

  getAnamnesesByPatient(alunoId: number): Observable<AnamneseItem[]> {
    const params = new HttpParams().set('aluno_id', alunoId);
    return this.http
      .get<ApiEnvelope<{ anamneses: AnamneseItem[] }>>(`${this.apiBase}/anamnese`, { params })
      .pipe(map((response) => response.data.anamneses ?? []));
  }

  createAnamnese(payload: Partial<AnamneseItem> & { consulta_id: number; aluno_id: number }): Observable<number> {
    return this.http
      .post<ApiEnvelope<{ id: number }>>(`${this.apiBase}/anamnese`, payload)
      .pipe(map((response) => response.data.id));
  }

  updateAnamnese(id: number, payload: Partial<AnamneseItem>): Observable<void> {
    return this.http
      .put<ApiEnvelope<unknown>>(`${this.apiBase}/anamnese/${id}`, payload)
      .pipe(map(() => void 0));
  }
}
