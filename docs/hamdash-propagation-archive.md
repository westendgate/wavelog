# Ham-Dash propagation archive in Wavelog

The dashboard now offers **Ham-Dash** and **Propagation Archive**. Inside either page, **Live Map** and **Propagation Archive** switch between the two views. German translations are included. The archive opens automatically in the existing same-origin iframe; closing its dialog returns to the live dashboard inside that frame.

## Architecture and prerequisites

This integration extends the Ham-Dash installation in the custom `update/wavelog-3.2.4` branch. It does not duplicate reception reports into Wavelog's QSO tables or import reception reports as QSOs.

- `map/propagation` remains the live entry point.
- `map/propagation_archive` opens `ham-dash/?view=archive`.
- Both routes use the existing `Map` controller constructor and its `authorize(2)` check. The shared page renderer is private and cannot be called as a controller route.
- The iframe URL is fixed by the application; no user-supplied upstream URL or credentials are accepted.
- The existing Ham-Dash reverse proxy must forward the whole application mount, including its `/api/archive/days`, `/api/archive/stats`, and `/api/archive/spots` routes. Ham-Dash resolves archive requests relative to its mount, including a Wavelog subdirectory. Its canonical app URL must end with `/`.
- The archive requires the matching Ham-Dash archive backend and frontend from https://github.com/westendgate/Ham-Dash/pull/6. Updating this Wavelog wrapper alone does not create the archive or update the running Go service.
- A Ham-Dash database belongs to one configured transmitting callsign. Changing the active Wavelog logbook does not switch that transmitting callsign. Existing deployment-level access controls for the Ham-Dash mount must remain in place; these PHP page routes do not replace reverse-proxy protection of direct Ham-Dash URLs.

No Wavelog database migration, schema change, or new API key is needed. Ham-Dash's SQLite migrations and retention are documented in its README. Imported PSK data does not create Wavelog QSOs or historical connection-coverage intervals.

## Coordinated update

1. Back up the currently deployed Wavelog files, Go executable, static overlays/proxy configuration, and the existing Ham-Dash SQLite database using its documented backup procedure. Stop Ham-Dash cleanly before migrating/importing.
2. Update the Go service and its frontend to the same archive revision. If Apache serves `index.html`, `app.js` or other files from an overlay directory, update that overlay too and ensure `archive.js` and `archive.css` are available under the same mount. Preserve local production customizations when comparing overlays; do not blindly replace them. Avoid mixing old overlay JavaScript with a new embedded frontend/backend.
3. Add the Wavelog controller/view/dashboard changes and the locale catalogs from this branch. Deploy to the installation based on `update/wavelog-3.2.4`; this is not a patch against upstream `master`.
4. To add a PSK download to an existing archive, use Ham-Dash's `-import-psk ... -import-dry-run`, review the report, then use `-import-psk ...` against the backed-up **existing** database. Do not replace it with a standalone example/preview database, which would omit existing live reports.
5. Restart Ham-Dash and verify authenticated Wavelog live/archive navigation, calendar and hourly statistics, map rendering, live PSK reception/UDP relay, and existing Wavelog logbook-status indicators. Confirm anonymous access follows the installation's existing access policy.

Rollback requires restoring the prior Go executable/static files together with its prior SQLite backup, because the old binary is incompatible with the archive schema. Restore the matching Wavelog files/catalogs as well. Do not use a new schema with an old binary.

These steps describe a deployment; preparing the branch and tests does not execute them.

## Verification

```sh
php -l application/controllers/Map.php
php -l application/views/map/propagation.php
php -l application/views/dashboard/index.php
php tests/hamdash_archive.php
```

The focused PHP harness stubs the existing session/layout services, checks the real controller/view, verifies both modes and subdirectory URLs, ensures exactly one active navigation link, verifies unauthorized constructor redirection, and confirms the renderer is private. It does not need a database or network service. GitHub Actions runs these checks on relevant changes.

Local browser integration was also checked with the actual controller/view and an isolated layout/session stub, Ham-Dash below `/wavelog/ham-dash/`, and a read-only copy of the imported PSK archive. That validates automatic archive opening, API mount resolution, day/hour selection, and return to the live route. It is not a production MySQL/login/Apache integration test.
