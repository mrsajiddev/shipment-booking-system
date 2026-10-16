import { Injectable } from '@angular/core';

/**
 * Keeps the Sanctum token in localStorage.
 * Wrapped in a service so it is easy to swap (sessionStorage, memory) and easy to mock in tests.
 */
@Injectable({ providedIn: 'root' })
export class TokenStorage {
  private readonly key = 'shipment_portal_token';

  get(): string | null {
    try {
      return localStorage.getItem(this.key);
    } catch {
      return null;
    }
  }

  set(token: string): void {
    try {
      localStorage.setItem(this.key, token);
    } catch {
      /* storage blocked (private mode) — the user just has to log in again next time */
    }
  }

  clear(): void {
    try {
      localStorage.removeItem(this.key);
    } catch {
      /* ignore */
    }
  }
}
