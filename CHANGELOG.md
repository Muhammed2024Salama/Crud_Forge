# Changelog

All notable changes to this project are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.2.0] — 2026-05-15

### Added
- Full PHP 8.2 / 8.3 / 8.4 × Laravel 11 / 12 / 13 CI matrix with `fail-fast: false`
- PHPUnit 11 and 12 support declared in `require-dev`
- Larastan 3.x support (`larastan/larastan: ^2.0|^3.0`)

### Changed
- Stabilised `require-dev` constraints: `orchestra/testbench: ^9.0|^10.0|^11.0` and `phpunit/phpunit: ^11.0|^12.0` for reliable cross-version resolution
- Excluded PHP 8.2 + Laravel 13 from the CI matrix (Laravel 13 requires PHP ≥ 8.3)
- PHPStan static analysis job pinned to PHP 8.3 for deterministic results across the matrix

### Fixed
- Resolved Composer dependency resolution conflicts between Orchestra Testbench and PHPUnit across the full PHP × Laravel matrix

---

## [1.1.0] — 2025-xx-xx

### Added
- `CrudForgeRuntimeServiceProvider` — replaces `bootstrap/providers.php` auto-editing with an opt-in runtime registry system
- `crudforge:install-bindings` command with single-module and `--all` modes
- `bootstrap/crudforge-bindings.php` binding registry (interface → repository map); loaded at runtime, never modifies Laravel core files
- `routes/crudforge.php` single manifest file replaces glob-based route loading
- `auto_register` config flag — set to `false` in locked deployments
- `--no-auto-register` CLI flag — skip registry updates per invocation
- `--dry-run` flag — preview generated file paths without writing to disk
- Database driver-aware search: `ILIKE` for PostgreSQL, `LIKE` for MySQL / SQLite — inlined in generated repository, no runtime package dependency
- Path traversal protection (`guardSafePath`) validated with dedicated tests

### Changed
- Route loading replaced: single `routes/crudforge.php` manifest replaces per-module glob
- Generated code has **zero runtime dependency** on `muhammedsalama/crudforge` — the package can be moved to `require-dev` or removed after generation
- `StubRenderer` validates unreplaced placeholders at render time, throwing `RuntimeException` on misconfigured stubs
- `crudforge:generate` calls `crudforge:install-bindings` automatically (when `auto_register = true`)

### Removed
- Automatic editing of `bootstrap/providers.php`
- Glob-based route scanning

---

## [1.0.0] — initial release

- Initial release with Controller / Service / Repository / Interface generation
- Support for PHP 8.2, Laravel 11 / 12
- Field types: string, text, integer, bigInteger, decimal, boolean, date, datetime, timestamp, json, foreignId
- Generated output: model, controller, service, repository, interface, form requests, API resource, migration, factory, feature test, translations

[1.2.0]: https://github.com/Muhammed2024Salama/Crud_Forge/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/Muhammed2024Salama/Crud_Forge/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/Muhammed2024Salama/Crud_Forge/releases/tag/v1.0.0
