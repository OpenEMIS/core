/**
 * Resolve theme colour from /themes API payload and apply matching body class.
 * Finds Colour by name (not a hardcoded array index) so multi-product /
 * reordered theme rows still work across environments (POCOR-9801).
 */

export function getThemeColourHex(themes: any[]): string | null {
  if (!Array.isArray(themes)) {
    return null;
  }

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

export function applyThemeFromResponse(response: any, themePalette: any[]): void {
  const themes = Array.isArray(response?.data)
    ? response.data
    : response?.data?.data;

  const selectedThemeData = getThemeColourHex(themes);
  if (!selectedThemeData || !Array.isArray(themePalette)) {
    return;
  }

  const match = themePalette.find(
    (element: any) =>
      String(element?.text || '').toUpperCase() === selectedThemeData
  );

  if (match?.theme) {
    document.body.className = `${match.theme} fuelux`;
  }
}
