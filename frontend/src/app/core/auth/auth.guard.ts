import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

import { AuthService } from './auth.service';

/**
 * Functional route guards.
 * Remember: guards only improve the user experience. Real security is in Laravel
 * (auth:sanctum, policies, role middleware).
 */

/** Only logged-in users. Others go to /login and come back afterwards (returnUrl). */
export const authGuard: CanActivateFn = (_route, state) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  return auth.isLoggedIn()
    ? true
    : router.createUrlTree(['/login'], { queryParams: { returnUrl: state.url } });
};

/** Only guests (login/register pages). Logged-in users go to the dashboard. */
export const guestGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  return auth.isLoggedIn() ? router.createUrlTree(['/dashboard']) : true;
};
