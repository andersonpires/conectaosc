import { CanActivateFn } from '@angular/router';
import { catchError, map, of } from 'rxjs';
import { inject } from '@angular/core';

import { AuthService } from './auth.service';

export const authGuard: CanActivateFn = () => {
  const authService = inject(AuthService);

  return authService.checkSession().pipe(
    map((isLogged) => {
      if (isLogged) return true;
      window.location.href = authService.buildLoginRedirect();
      return false;
    }),
    catchError(() => {
      window.location.href = authService.buildLoginRedirect();
      return of(false);
    }),
  );
};

