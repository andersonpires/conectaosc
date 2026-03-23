import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { catchError, throwError } from 'rxjs';

export const credentialsInterceptor: HttpInterceptorFn = (req, next) => {
  const cloned = req.clone({ withCredentials: true });
  return next(cloned).pipe(
    catchError((error: HttpErrorResponse) => {
      const message =
        typeof error.error?.message === 'string'
          ? error.error.message
          : error.status === 401
            ? 'Sessao expirada. Faca login novamente.'
            : 'Falha ao comunicar com a API da clinica.';
      return throwError(() => new Error(message));
    }),
  );
};
