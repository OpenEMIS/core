import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { shareReplay } from 'rxjs/operators';
import { ApiService } from '../api.service';

/**
 * POCOR-9594: Cache service for StudentMeals dropdown options.
 * Wraps ApiService.getWithToken() with shareReplay(1) so each unique URL
 * is fetched at most once per service lifetime (i.e. per Angular app bootstrap).
 * Use clear() when the institution context changes to force a fresh fetch.
 */
@Injectable({ providedIn: 'root' })
export class StudentMealsCacheService {

  private _cache = new Map<string, Observable<any>>();

  constructor(private Rest: ApiService) {}

  /**
   * Returns a cached observable for the given API URL.
   * On first call the HTTP request is made; subsequent calls with the same
   * URL return the already-resolved observable without hitting the network.
   */
  getWithToken(url: string): Observable<any> {
    if (!this._cache.has(url)) {
      this._cache.set(url, this.Rest.getWithToken(url).pipe(shareReplay(1)));
    }
    return this._cache.get(url)!;
  }

  /** Invalidate the entire cache (e.g. on institution or period change). */
  clear(): void {
    this._cache.clear();
  }

  /** Invalidate a single cached entry by URL. */
  invalidate(url: string): void {
    this._cache.delete(url);
  }
}
