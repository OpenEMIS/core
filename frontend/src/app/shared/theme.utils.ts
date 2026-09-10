/**
 * Resolve theme colour from /themes API and apply it to Angular UI.
 * POCOR-9801: find Colour by name (not data[3]), and always apply the
 * actual hex so custom colours (e.g. #07AEBE) work even when they are
 * not in the predefined openemis-* palette.
 */

const DYNAMIC_THEME_STYLE_ID = 'openemis-dynamic-theme-colour';

export function getThemeColourHex(themes: any[]): string | null {
  if (!Array.isArray(themes)) {
    return null;
  }

  // Prefer first Colour/Color row (Core is typically lowest id)
  const colourTheme = themes.find(
    (theme) => theme?.name === 'Colour' || theme?.name === 'Color'
  );
  if (!colourTheme) {
    return null;
  }

  const hex = colourTheme.value || colourTheme.default_value;
  if (!hex) {
    return null;
  }

  return `#${String(hex).replace(/^#/, '').toUpperCase()}`;
}

/** Darken a #RRGGBB colour (same idea as AppController::darkenColour). */
export function darkenHex(hex: string, darker: number = 2): string {
  const raw = String(hex || '').replace(/^#/, '');
  if (!/^[0-9A-Fa-f]{6}$/.test(raw)) {
    return '#000000';
  }

  const factor = darker > 1 ? darker : 1;
  const parts = raw.match(/.{2}/g) || [];
  const darkened = parts
    .map((part) =>
      Math.floor(parseInt(part, 16) / factor)
        .toString(16)
        .padStart(2, '0')
        .toUpperCase()
    )
    .join('');

  return `#${darkened}`;
}

/**
 * Inject CSS so Angular controls (datepicker .btn / .btn-input, etc.)
 * use the configured product colour, including custom hex values.
 */
export function applyProductColour(hex: string): void {
  if (!hex || typeof document === 'undefined') {
    return;
  }

  const secondary = darkenHex(hex);
  let styleEl = document.getElementById(
    DYNAMIC_THEME_STYLE_ID
  ) as HTMLStyleElement | null;

  if (!styleEl) {
    styleEl = document.createElement('style');
    styleEl.id = DYNAMIC_THEME_STYLE_ID;
    document.head.appendChild(styleEl);
  }

  styleEl.textContent = `
    body .btn,
    body .btn-color,
    body .btn-input,
    body .kdx-datepicker .btn-input,
    body .kdx-datepicker-wrapper .btn,
    body .dropdown-menu .btn,
    body .content-area .btn-text,
    body .toolbar .btn-icon:before,
    body .progressbar-wrapper .progress-bar {
      background-color: ${hex} !important;
      border-color: ${hex} !important;
      color: #FFF !important;
    }
    body .btn:hover,
    body .btn:focus,
    body .btn-input:hover,
    body .btn-input:focus,
    body .btn-color:hover,
    body .kdx-datepicker .btn-input:hover {
      background-color: ${secondary} !important;
      border-color: ${secondary} !important;
      color: #FFF !important;
    }
  `;
}

export function applyThemeFromResponse(response: any, themePalette: any[]): void {
  const themes = Array.isArray(response?.data)
    ? response.data
    : response?.data?.data;

  const selectedThemeData = getThemeColourHex(themes);
  if (!selectedThemeData) {
    return;
  }

  // Keep palette body class when hex is a known preset (other theme hooks)
  if (Array.isArray(themePalette)) {
    const match = themePalette.find(
      (element: any) =>
        String(element?.text || '').toUpperCase() === selectedThemeData
    );
    if (match?.theme) {
      document.body.className = `${match.theme} fuelux`;
    }
  }

  // Always apply the real hex — required for custom colours like #07AEBE
  applyProductColour(selectedThemeData);
}
