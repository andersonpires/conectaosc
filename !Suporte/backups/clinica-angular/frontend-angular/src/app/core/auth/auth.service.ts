import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { resolveApiBase, resolveBackendOrigin, resolveProjectBasePath } from '../config/runtime-paths';

interface SessionProbe {
  success: boolean;
  data: {
    ok: boolean;
    session: boolean;
  };
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);

  checkSession(): Observable<boolean> {
    return this.http
      .get<SessionProbe>(`${resolveApiBase()}/`)
      .pipe(map((response) => Boolean(response?.data?.session)));
  }

  buildLoginRedirect(): string {
    const projectBasePath = resolveProjectBasePath();
    const backendOrigin = resolveBackendOrigin();
    const redirect = `${backendOrigin}${projectBasePath}/clinica/frontend-angular/`;
    return `${backendOrigin}${projectBasePath}/login/?redirect=${encodeURIComponent(redirect)}`;
  }
}
