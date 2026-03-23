import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { timeout } from 'rxjs';

import { ApiService } from '../../../core/api/api.service';
import { AnamneseItem, ProntuarioItem, ProntuarioPatient } from '../../../core/api/api.types';

@Component({
  selector: 'app-anamnese-page',
  imports: [FormsModule],
  templateUrl: './anamnese-page.component.html',
  styleUrl: './anamnese-page.component.scss',
})
export class AnamnesePageComponent {
  private readonly api = inject(ApiService);
  private readonly route = inject(ActivatedRoute);

  protected loading = true;
  protected saving = false;
  protected message = '';
  protected error = '';

  protected patients: ProntuarioPatient[] = [];
  protected selectedPatientId: number | null = null;
  protected selectedConsultaId: number | null = null;
  protected selectedPatientName = '';

  protected progresso = 65;

  protected form: Partial<AnamneseItem> = {
    crenca_religiao: '',
    qualidade_sono: '',
    uso_alcool_substancias: '',
    acompanhamento_psicologico: '',
    medicacoes_psicotropicos: '',
    internacao_psiquiatrica: '',
    sentimento_ultimos_meses: '',
    atividades_fazem_bem: '',
    dificuldades_memoria: '',
    observacoes_gerais: '',
  };

  protected currentAnamneseId: number | null = null;

  constructor() {
    this.loadPatients();
  }

  protected onPatientChange(patientIdText: string): void {
    const patientId = Number(patientIdText);
    this.selectedPatientId = Number.isFinite(patientId) && patientId > 0 ? patientId : null;
    if (!this.selectedPatientId) return;

    const patient = this.patients.find((item) => item.aluno_id === this.selectedPatientId);
    this.selectedPatientName = patient?.paciente_nome ?? '';

    this.loading = true;
    this.message = '';
    this.error = '';

    this.api
      .getPatientRecords(this.selectedPatientId)
      .pipe(timeout(12000))
      .subscribe({
        next: (records: ProntuarioItem[]) => {
          this.selectedConsultaId = records[0]?.consulta_id ?? null;
          this.loadLatestAnamnese(this.selectedPatientId!);
        },
        error: (error: unknown) => {
          this.error = this.toMessage(error);
          this.loading = false;
        },
      });
  }

  protected save(): void {
    if (!this.selectedPatientId || !this.selectedConsultaId) {
      this.error = 'Selecione um paciente com consulta vinculada para salvar a anamnese.';
      return;
    }

    this.saving = true;
    this.message = '';
    this.error = '';

    const payload: Partial<AnamneseItem> & { consulta_id: number; aluno_id: number } = {
      consulta_id: this.selectedConsultaId,
      aluno_id: this.selectedPatientId,
      crenca_religiao: this.form.crenca_religiao ?? null,
      qualidade_sono: this.form.qualidade_sono ?? null,
      uso_alcool_substancias: this.form.uso_alcool_substancias ?? null,
      acompanhamento_psicologico: this.form.acompanhamento_psicologico ?? null,
      medicacoes_psicotropicos: this.form.medicacoes_psicotropicos ?? null,
      internacao_psiquiatrica: this.form.internacao_psiquiatrica ?? null,
      sentimento_ultimos_meses: this.form.sentimento_ultimos_meses ?? null,
      atividades_fazem_bem: this.form.atividades_fazem_bem ?? null,
      dificuldades_memoria: this.form.dificuldades_memoria ?? null,
      observacoes_gerais: this.form.observacoes_gerais ?? null,
    };

    const onDone = () => {
      this.saving = false;
      this.message = 'Anamnese salva com sucesso.';
    };

    if (this.currentAnamneseId) {
      this.api
        .updateAnamnese(this.currentAnamneseId, payload)
        .pipe(timeout(12000))
        .subscribe({
          next: () => onDone(),
          error: (error: unknown) => {
            this.error = this.toMessage(error);
            this.saving = false;
          },
        });
      return;
    }

    this.api
      .createAnamnese(payload)
      .pipe(timeout(12000))
      .subscribe({
        next: (id) => {
          this.currentAnamneseId = id;
          onDone();
        },
        error: (error: unknown) => {
          this.error = this.toMessage(error);
          this.saving = false;
        },
      });
  }

  private loadPatients(): void {
    this.api
      .getPatientsWithRecords()
      .pipe(timeout(12000))
      .subscribe({
        next: (patients) => {
          this.patients = patients;

          const queryPatientId = Number(this.route.snapshot.queryParamMap.get('aluno_id'));
          const preferredId =
            Number.isFinite(queryPatientId) && queryPatientId > 0 ? queryPatientId : patients[0]?.aluno_id;

          if (!preferredId) {
            this.loading = false;
            return;
          }

          this.selectedPatientId = preferredId;
          const selected = patients.find((item) => item.aluno_id === preferredId);
          this.selectedPatientName = selected?.paciente_nome ?? '';
          this.onPatientChange(String(preferredId));
        },
        error: (error: unknown) => {
          this.error = this.toMessage(error);
          this.loading = false;
        },
      });
  }

  private loadLatestAnamnese(alunoId: number): void {
    this.api
      .getAnamnesesByPatient(alunoId)
      .pipe(timeout(12000))
      .subscribe({
        next: (anamneses) => {
          const latest = anamneses[0];
          this.currentAnamneseId = latest?.id ?? null;
          this.form = {
            crenca_religiao: latest?.crenca_religiao ?? '',
            qualidade_sono: latest?.qualidade_sono ?? '',
            uso_alcool_substancias: latest?.uso_alcool_substancias ?? '',
            acompanhamento_psicologico: latest?.acompanhamento_psicologico ?? '',
            medicacoes_psicotropicos: latest?.medicacoes_psicotropicos ?? '',
            internacao_psiquiatrica: latest?.internacao_psiquiatrica ?? '',
            sentimento_ultimos_meses: latest?.sentimento_ultimos_meses ?? '',
            atividades_fazem_bem: latest?.atividades_fazem_bem ?? '',
            dificuldades_memoria: latest?.dificuldades_memoria ?? '',
            observacoes_gerais: latest?.observacoes_gerais ?? '',
          };
          this.loading = false;
        },
        error: (error: unknown) => {
          this.error = this.toMessage(error);
          this.loading = false;
        },
      });
  }

  private toMessage(error: unknown): string {
    const fallback = 'Nao foi possivel carregar a anamnese. Atualize a pagina ou refaca o login.';
    return error instanceof Error && error.message ? error.message : fallback;
  }
}
