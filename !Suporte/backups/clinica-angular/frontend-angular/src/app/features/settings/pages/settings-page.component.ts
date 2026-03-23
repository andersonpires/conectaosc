import { Component, inject } from '@angular/core';
import { timeout } from 'rxjs';

import { ApiService } from '../../../core/api/api.service';
import { Professional } from '../../../core/api/api.types';

@Component({
  selector: 'app-settings-page',
  imports: [],
  templateUrl: './settings-page.component.html',
  styleUrl: './settings-page.component.scss',
})
export class SettingsPageComponent {
  private readonly api = inject(ApiService);

  protected loading = true;
  protected error = '';
  protected professionals: Professional[] = [];

  constructor() {
    this.api
      .getProfessionals()
      .pipe(timeout(12000))
      .subscribe({
        next: (professionals) => {
          this.professionals = professionals;
          this.loading = false;
        },
        error: (error: unknown) => {
          this.error = this.toMessage(error);
          this.loading = false;
        },
      });
  }

  private toMessage(error: unknown): string {
    const fallback = 'Nao foi possivel carregar configuracoes. Atualize a pagina ou refaca o login.';
    return error instanceof Error && error.message ? error.message : fallback;
  }
}
