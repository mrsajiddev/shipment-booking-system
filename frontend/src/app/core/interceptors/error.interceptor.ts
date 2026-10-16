import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { catchError, throwError } from 'rxjs';

import { AuthService } from '../auth/auth.service';
import { ToastService } from '../services/toast.service';

/**
 * Handles errors in ONE place instead of in every component.
 * 422 (validation) is NOT shown here — it is passed back so the form can show
 * each message under the right field.
 */
export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  const toast = inject(ToastService);

  return next(req).pipe(
    catchError((error: unknown) => {
      if (error instanceof HttpErrorResponse) {
        switch (error.status) {
          case 0:
            toast.error('Cannot reach the server. Is the Laravel API running on port 8000?');
            break;
          case 401:
            if (auth.token()) {
              auth.endSession();
              toast.info('Your session has ended. Please log in again.');
            }
            break;
          case 403:
            toast.error(error.error?.message || 'You are not allowed to do that.');
            break;
          case 429:
            toast.error('Too many attempts. Please wait a minute and try again.');
            break;
          default:
            if (error.status >= 500) {
              toast.error('Something went wrong on the server. Please try again.');
            }
        }
      }

      return throwError(() => error); // let the caller decide what else to do
    }),
  );
};
