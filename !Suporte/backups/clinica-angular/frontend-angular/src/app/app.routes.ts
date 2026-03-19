import { Routes } from '@angular/router';
import { DashboardPageComponent } from './features/dashboard/pages/dashboard-page.component';
import { AgendaPageComponent } from './features/agenda/pages/agenda-page.component';
import { AnamnesePageComponent } from './features/anamnese/pages/anamnese-page.component';
import { PatientPageComponent } from './features/patient/pages/patient-page.component';
import { PatientsListPageComponent } from './features/patients-list/pages/patients-list-page.component';
import { ProntuarioPageComponent } from './features/prontuario/pages/prontuario-page.component';
import { SettingsPageComponent } from './features/settings/pages/settings-page.component';
import { authGuard } from './core/auth/auth.guard';

export const routes: Routes = [
  {
    path: '',
    canActivate: [authGuard],
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
      { path: 'dashboard', component: DashboardPageComponent },
      { path: 'agenda', component: AgendaPageComponent },
      { path: 'pacientes', component: PatientsListPageComponent },
      { path: 'paciente', pathMatch: 'full', redirectTo: 'pacientes' },
      { path: 'paciente/:id', component: PatientPageComponent },
      { path: 'prontuario', component: ProntuarioPageComponent },
      { path: 'atendimento', component: AnamnesePageComponent },
      { path: 'anamnese', component: AnamnesePageComponent },
      { path: 'configuracoes', component: SettingsPageComponent },
      { path: '**', redirectTo: 'dashboard' },
    ],
  },
];
