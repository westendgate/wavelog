# Visitor QSO globe

Uses Wavelog's bundled Globe.GL, blue-marble texture, Qra locator conversion, published-logbook station relationships and existing confirmation preferences. The 2D map remains available with its existing table-page scope.

The new GET endpoint `/index.php/visitor/globedata/{public_slug}?period=24h|month|year|all` obeys the `public_maps` option. Periods use UTC: rolling 24 hours, current calendar month/year, or all QSOs through the current time. Repeated routes are aggregated without the export map's 5,000-QSO cap. Missing coordinates are counted separately. Payloads contain only callsigns, route coordinates, confirmation and counts.

Run from the repository root with PHP:

```sh
php tests/visitor_globe_test.php "$PWD"
php tests/visitor_globe_query_test.php "$PWD"
```

The tests use the real Maidenhead converter for precision and invalid-location cases; verify year boundaries, repeat aggregation beyond 5,000, explicit zero coordinates, public station scope, exact timestamps and absence of a query limit.

Browser checks: select all four periods, switch 3D/2D/3D, rapidly change periods, test narrow and desktop widths, and confirm the map counts match the public logbook. Invalid slugs return 404, invalid periods 400, non-GET requests 405. WebGL failure keeps the 2D fallback usable.
