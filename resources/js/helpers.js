export function route(name, params = {}) {
  const routes = {
    'login': '/login',
    'logout': '/logout',
    'accounts.index': '/accounts',
    'accounts.create': '/accounts/create',
    'accounts.edit': (id) => `/accounts/${id}/edit`,
    'accounts.show': (id) => `/accounts/${id}`,
    'journal-entries.index': '/journal-entries',
    'journal-entries.create': '/journal-entries/create',
    'journal-entries.edit': (id) => `/journal-entries/${id}/edit`,
    'journal-entries.show': (id) => `/journal-entries/${id}`,
    'journal-entries.post': (id) => `/journal-entries/${id}/post`,
    'journal-entries.reverse': (id) => `/journal-entries/${id}/reverse`,
    'reports.ledger': '/reports/ledger',
    'reports.trial-balance': '/reports/trial-balance',
    'reports.income-statement': '/reports/income-statement',
    'reports.balance-sheet': '/reports/balance-sheet',
    'reports.cashflow': '/reports/cashflow',
  };

  const route = routes[name];
  
  if (typeof route === 'function') {
    if (typeof params === 'number' || params?.id) {
      return route(typeof params === 'number' ? params : params.id);
    }
    return route(params);
  }
  
  return route || name;
}
