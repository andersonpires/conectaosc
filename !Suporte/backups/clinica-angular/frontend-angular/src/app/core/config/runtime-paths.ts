function readCookie(name: string): string {
  const source = document.cookie ?? '';
  const parts = source.split(';');
  for (const part of parts) {
    const trimmed = part.trim();
    if (trimmed.startsWith(`${name}=`)) {
      return decodeURIComponent(trimmed.slice(name.length + 1));
    }
  }
  return '';
}

export function resolveProjectBasePath(): string {
  const cookieBase = readCookie('CLINICA_PROJECT_BASE').trim();
  if (cookieBase) {
    return cookieBase === '/' ? '' : cookieBase.replace(/\/+$/, '');
  }

  const path = window.location.pathname;
  const markers = ['/clinica/frontend-angular', '/clinica'];
  for (const marker of markers) {
    const idx = path.indexOf(marker);
    if (idx >= 0) {
      const prefix = path.slice(0, idx).replace(/\/+$/, '');
      return prefix === '/' ? '' : prefix;
    }
  }

  if (window.location.hostname === 'localhost') {
    return '/conectaosc3';
  }
  return '';
}

export function resolveBackendOrigin(): string {
  if (window.location.hostname === 'localhost' && window.location.port === '4200') {
    return 'http://localhost';
  }
  return window.location.origin;
}

export function resolveApiBase(): string {
  return `${resolveProjectBasePath()}/clinica/api`;
}
