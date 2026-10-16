import { provideHttpClient, withInterceptors } from '@angular/common/http';
import {
  ApplicationConfig,
  inject,
  provideAppInitializer,
  provideBrowserGlobalErrorListeners,
  provideZoneChangeDetection,
} from '@angular/core';
import { provideRouter, withComponentInputBinding } from '@angular/router';

import { routes } from './app.routes';
import { AuthService } from './core/auth/auth.service';
import { authInterceptor } from './core/interceptors/auth.interceptor';
import { errorInterceptor } from './core/interceptors/error.interceptor';

export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideZoneChangeDetection({ eventCoalescing: true }),

    // withComponentInputBinding: route params (like :id) arrive as component inputs.
    provideRouter(routes, withComponentInputBinding()),

    // Interceptors run in this order for requests (and in reverse for responses).
    provideHttpClient(withInterceptors([authInterceptor, errorInterceptor])),

    // Before the first page shows: if a token is saved, load the user from /auth/me.
    provideAppInitializer(() => inject(AuthService).restoreSession()),
  ],
};
