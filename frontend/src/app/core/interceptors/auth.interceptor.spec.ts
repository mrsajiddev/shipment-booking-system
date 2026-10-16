import { HttpClient, provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';

import { environment } from '../../../environments/environment';
import { TokenStorage } from '../auth/token-storage.service';
import { authInterceptor } from './auth.interceptor';

describe('authInterceptor', () => {
  let http: HttpClient;
  let httpMock: HttpTestingController;
  let storage: TokenStorage;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(withInterceptors([authInterceptor])), provideHttpClientTesting()],
    });
    http = TestBed.inject(HttpClient);
    httpMock = TestBed.inject(HttpTestingController);
    storage = TestBed.inject(TokenStorage);
  });

  afterEach(() => {
    httpMock.verify();
    storage.clear();
  });

  it('adds the Bearer token to API requests', () => {
    storage.set('abc123');

    http.get(`${environment.apiUrl}/shipments`).subscribe();

    const req = httpMock.expectOne(`${environment.apiUrl}/shipments`);
    expect(req.request.headers.get('Authorization')).toBe('Bearer abc123');
    expect(req.request.headers.get('Accept')).toBe('application/json');
    req.flush({});
  });

  it('never sends the token to other websites', () => {
    storage.set('abc123');

    http.get('https://example.com/data').subscribe();

    const req = httpMock.expectOne('https://example.com/data');
    expect(req.request.headers.has('Authorization')).toBe(false);
    req.flush({});
  });
});
