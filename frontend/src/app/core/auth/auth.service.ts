import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, catchError, finalize, map, of, tap } from 'rxjs';

import { environment } from '../../../environments/environment';
import { ApiResponse } from '../models/api.model';
import { AuthResponse, LoginPayload, RegisterPayload, User } from '../models/user.model';
import { TokenStorage } from './token-storage.service';

/**
 * One place for everything about "who is logged in".
 * State is kept in SIGNALS, so any component that reads user() or isAdmin() updates automatically.
 */
@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly storage = inject(TokenStorage);
  private readonly router = inject(Router);
  private readonly api = environment.apiUrl;

  private readonly currentUser = signal<User | null>(null);

  /** Read-only outside this service: only AuthService can change who is logged in. */
  readonly user = this.currentUser.asReadonly();
  readonly isLoggedIn = computed(() => this.currentUser() !== null);
  readonly isAdmin = computed(() => this.currentUser()?.role === 'admin');

  token(): string | null {
    return this.storage.get();
  }

  login(payload: LoginPayload): Observable<User> {
    return this.http
      .post<AuthResponse>(`${this.api}/auth/login`, { ...payload, device_name: 'angular-portal' })
      .pipe(
        tap((res) => this.startSession(res)),
        map((res) => res.user),
      );
  }

  register(payload: RegisterPayload): Observable<User> {
    return this.http.post<AuthResponse>(`${this.api}/auth/register`, payload).pipe(
      tap((res) => this.startSession(res)),
      map((res) => res.user),
    );
  }

  /**
   * Runs once when the app starts (see provideAppInitializer in app.config.ts).
   * If a token is saved, ask Laravel who it belongs to. If the token is no longer valid, forget it.
   */
  restoreSession(): Observable<unknown> {
    if (!this.token()) {
      return of(null);
    }

    return this.http.get<ApiResponse<User>>(`${this.api}/auth/me`).pipe(
      tap((res) => this.currentUser.set(res.data)),
      catchError(() => {
        this.endSession(false);
        return of(null);
      }),
    );
  }

  logout(): void {
    // Tell Laravel to delete this token, then clear the local session whatever happens.
    this.http
      .post(`${this.api}/auth/logout`, {})
      .pipe(
        catchError(() => of(null)),
        finalize(() => this.endSession()),
      )
      .subscribe();
  }

  endSession(redirect = true): void {
    this.storage.clear();
    this.currentUser.set(null);
    if (redirect) {
      this.router.navigate(['/login']);
    }
  }

  private startSession(res: AuthResponse): void {
    this.storage.set(res.token);
    this.currentUser.set(res.user);
  }
}
