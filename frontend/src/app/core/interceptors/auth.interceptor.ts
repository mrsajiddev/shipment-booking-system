import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';

import { environment } from '../../../environments/environment';
import { TokenStorage } from '../auth/token-storage.service';

/**
 * Runs for every HTTP request.
 * Adds the Sanctum token and asks for JSON — but only for OUR API, never for other websites.
 */
export const authInterceptor: HttpInterceptorFn = (req, next) => {
  if (!req.url.startsWith(environment.apiUrl)) {
    return next(req);
  }

  const token = inject(TokenStorage).get();
  const headers: Record<string, string> = { Accept: 'application/json' };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  // Requests are immutable, so we clone it with the extra headers.
  return next(req.clone({ setHeaders: headers }));
};
