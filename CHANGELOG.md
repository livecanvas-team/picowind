# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Add first-class Handlebars template engine support for `.hbs` and `.handlebars` templates, including partials, Timber helpers, and cross-engine rendering from Twig, Latte, and Blade.

### Changed
- Raise the minimum PHP requirement to 8.2 for the Handlebars renderer dependency.

## [0.0.13] - 2026-03-17

## [0.0.12] - 2026-03-17

## [0.0.11] - 2026-03-17

### Added
- Add REST controller namespace aliases via `#[Controller(aliases: [...])]` and support repeatable controller attributes.

### Fixed
- Update template fallback logic to support child themes

## [0.0.10] - 2026-03-01

### Fixed
- Improve scoper patching for Twig, Latte, and Blade generated cache namespaces.

## [0.0.9] - 2026-02-28

### Fixed
- Fix theme activation warnings when WindPress files or folders are missing.

## [0.0.8] - 2026-02-27

### Fixed
- Expand scoper patch coverage for additional Twig/Latte/Blade compiled runtime references to prevent prefixed-build render fatals.

## [0.0.7] - 2026-02-27

### Fixed
- Fix Twig scoper patching for hardcoded runtime references (`captureOutput` and `Template::ANY_CALL`) in compiled templates.

## [0.0.6] - 2026-02-27

### Fixed
- Fix Latte scoper patcher escaping so `TemplateGenerator` references are correctly prefixed in dist builds.

## [0.0.5] - 2026-02-27

### Fixed
- Fix scoped Twig/Latte/Blade runtime template generation so compiled cache files use prefixed classes and avoid deploy render fatals.

## [0.0.4] - 2026-02-27

### Fixed
- Expose scoped Illuminate helper functions (including `tap`/`value`) to prevent Blade render fatals in prefixed builds.

## [0.0.3] - 2026-02-27

### Fixed
- Fix discovery autoloading scoped vendor classes (`PicowindDeps\...`) to prevent deploy fatals.

## [0.0.2] - 2026-02-27

## [0.0.1] - 2026-02-27

### Added
- 🐣 Initial release.

[unreleased]: https://github.com/livecanvas-team/picowind/compare/0.0.13...HEAD
[0.0.13]: https://github.com/livecanvas-team/picowind/compare/0.0.12...0.0.13
[0.0.12]: https://github.com/livecanvas-team/picowind/compare/0.0.11...0.0.12
[0.0.11]: https://github.com/livecanvas-team/picowind/compare/0.0.10...0.0.11
[0.0.10]: https://github.com/livecanvas-team/picowind/compare/0.0.9...0.0.10
[0.0.9]: https://github.com/livecanvas-team/picowind/compare/0.0.8...0.0.9
[0.0.8]: https://github.com/livecanvas-team/picowind/compare/0.0.7...0.0.8
[0.0.7]: https://github.com/livecanvas-team/picowind/compare/0.0.6...0.0.7
[0.0.6]: https://github.com/livecanvas-team/picowind/compare/0.0.5...0.0.6
[0.0.5]: https://github.com/livecanvas-team/picowind/compare/0.0.4...0.0.5
[0.0.4]: https://github.com/livecanvas-team/picowind/compare/0.0.3...0.0.4
[0.0.3]: https://github.com/livecanvas-team/picowind/compare/0.0.2...0.0.3
[0.0.2]: https://github.com/livecanvas-team/picowind/compare/0.0.1...0.0.2
[0.0.1]: https://github.com/livecanvas-team/picowind/releases/tag/0.0.1
