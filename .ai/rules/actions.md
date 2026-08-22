---
paths:
  - 'app/Actions/**'
---

# Actions

## Read the session via session(), not $request->session()
Livewire::test() does not bind a session store to the Request instance handed to a component, so $request->session() throws "Session store not set on request" and $request->hasSession() is false. Anything reachable from a Livewire component must use the session() helper (container binding), which works in both real requests and tests. Cookies are fine to read off $request.

This bit ResolveGuest and ManageCookieConsent: guarding with hasSession() made the tests pass while silently returning null for every session read, so consent and guest identity looked broken only in subtle ways.
