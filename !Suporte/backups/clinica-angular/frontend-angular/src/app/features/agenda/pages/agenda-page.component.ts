import { NgClass, SlicePipe } from '@angular/common';
import { Component, inject } from '@angular/core';
import { timeout } from 'rxjs';

import { ApiService } from '../../../core/api/api.service';
import { AgendaEvent } from '../../../core/api/api.types';

interface AgendaDay {
  date: string;
  label: string;
  dayNumber: number;
  isToday: boolean;
}

@Component({
  selector: 'app-agenda-page',
  imports: [NgClass, SlicePipe],
  templateUrl: './agenda-page.component.html',
  styleUrl: './agenda-page.component.scss',
})
export class AgendaPageComponent {
  private readonly api = inject(ApiService);

  protected loading = true;
  protected error = '';
  protected events: AgendaEvent[] = [];
  protected selectedDate = new Date().toISOString().slice(0, 10);
  protected weekDays: AgendaDay[] = [];

  constructor() {
    this.weekDays = this.buildWeek(this.selectedDate);
    this.loadAgenda();
  }

  protected selectDate(date: string): void {
    this.selectedDate = date;
    this.weekDays = this.buildWeek(date);
    this.loadAgenda();
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

  protected statusClass(status: string): string {
    if (status === 'concluida') return 'done';
    if (status === 'em_atendimento') return 'live';
    if (status === 'cancelada') return 'canceled';
    return 'scheduled';
  }

  protected currentTimeLabel(): string {
    const now = new Date();
    return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
  }

  private loadAgenda(): void {
    this.loading = true;
    this.error = '';
    this.api
      .getAgendaDay(this.selectedDate)
      .pipe(timeout(12000))
      .subscribe({
        next: (events) => {
          this.events = events;
          this.loading = false;
        },
        error: (error: unknown) => {
          this.error = this.toMessage(error);
          this.loading = false;
        },
      });
  }

  private toMessage(error: unknown): string {
    const fallback = 'Nao foi possivel carregar a agenda. Atualize a pagina ou refaca o login.';
    return error instanceof Error && error.message ? error.message : fallback;
  }

  private buildWeek(date: string): AgendaDay[] {
    const base = new Date(`${date}T12:00:00`);
    const day = base.getDay();
    const mondayOffset = day === 0 ? -6 : 1 - day;
    const monday = new Date(base);
    monday.setDate(base.getDate() + mondayOffset);

    return Array.from({ length: 5 }).map((_, index) => {
      const current = new Date(monday);
      current.setDate(monday.getDate() + index);
      const iso = current.toISOString().slice(0, 10);
      const labels = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex'];
      return {
        date: iso,
        label: labels[index],
        dayNumber: current.getDate(),
        isToday: iso === new Date().toISOString().slice(0, 10),
      };
    });
  }
}
