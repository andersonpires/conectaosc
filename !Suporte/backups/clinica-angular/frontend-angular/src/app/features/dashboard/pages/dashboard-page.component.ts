import { DecimalPipe, NgClass, SlicePipe } from '@angular/common';
import { ChangeDetectorRef, Component, inject } from '@angular/core';
import { Subscription, timeout } from 'rxjs';

import { ApiService } from '../../../core/api/api.service';
import { DashboardSummary } from '../../../core/api/api.types';

@Component({
  selector: 'app-dashboard-page',
  imports: [DecimalPipe, NgClass, SlicePipe],
  templateUrl: './dashboard-page.component.html',
  styleUrl: './dashboard-page.component.scss',
})
export class DashboardPageComponent {
  private readonly api = inject(ApiService);
  private readonly cdr = inject(ChangeDetectorRef);

  protected loading = true;
  protected error = '';
  protected summary: DashboardSummary | null = null;

  protected readonly appointmentIcons = ['psychology', 'fitness_center', 'radiology'];
  protected readonly appointmentIconColors = ['teal', 'orange', 'purple'];

  private readonly fallbackMessage =
    'Nao foi possivel carregar o dashboard. Atualize a pagina ou refaca o login.';

  constructor() {
    const today = new Date().toISOString().slice(0, 10);
    let finished = false;
    let watchdog: ReturnType<typeof setTimeout> | null = null;
    let requestSub: Subscription | null = null;

    const finish = (errorMessage = ''): void => {
      if (finished) return;
      finished = true;
      if (watchdog) {
        clearTimeout(watchdog);
      }
      requestSub?.unsubscribe();
      this.error = errorMessage;
      this.loading = false;
      this.cdr.detectChanges();
    };

    watchdog = setTimeout(() => {
      finish(this.fallbackMessage);
    }, 15000);

    requestSub = this.api
      .getDashboardSummary(today)
      .pipe(timeout(12000))
      .subscribe({
        next: (summary) => {
          this.summary = summary;
          finish('');
        },
        error: (error: unknown) => {
          const message = error instanceof Error && error.message ? error.message : this.fallbackMessage;
          finish(message);
        },
      });
  }

  protected monthLabel(date: string): string {
    const [year, month] = date.split('-');
    const months = [
      'Janeiro',
      'Fevereiro',
      'Marco',
      'Abril',
      'Maio',
      'Junho',
      'Julho',
      'Agosto',
      'Setembro',
      'Outubro',
      'Novembro',
      'Dezembro',
    ];
    return `${months[Math.max(0, Number(month) - 1)]} ${year}`;
  }
}
