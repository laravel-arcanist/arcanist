# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), 
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.7.0] — 2022-04-15

### Changed

- Pass `Request` to `transformWizardMethod` (#34, @ziming)

## [0.6.0] — 2022-03-16

### Added

- Added support for Laravel 9 (#41, @ziming)

## [0.5.2] — 2021-08-20

### Changed

- Changed visibility of `$responseRenderer` from `private` to `protected` in `AbstractWizard` (#21, @erikwittek)
- Changed visibility of `AbstractWizard::fields()` method from `private` to `protected` (#21, @erikwittek)

## [0.5.1] — 2021-08-17

### Added

- Added protected `onAfterDelete` method to `AbstractWizard`. This method gets called
  after a wizard was deleted instead of a hardcoded redirect (#20, @erikwittek)

## [0.5.0] — 2021-08-04

### Changed

- Changed visibility of `fields` method to `public`. This should make it possible to have generic
  templates by looping over the field definitions (#17, @thoresuenert)

## [0.4.0] — 2021-07-20

### Changed

- **Breaking change:** Changed return type declarations of `ResponseRenderer` interface to be less restrictive

## [0.3.1] — 2021-07-15

### Changed

- Use `static` inside of `Field::make()` to make it easier to extend from the `Field` class (#15)

## [0.3.0] — 2021-05-21

### Added

- Added `make:wizard` and `make:wizard-step` commands
- Added `transform` method to `Field` class to register arbitrary callbacks for a field.
  Note that whatever the callback returns is the value that gets persisted for the field.

## [0.2.0] — 2021-05-07

### Changed

- `WizardRepository` implementations no longer throw an exception when trying to delete
  a wizard that doesn’t exist. (#1)

### Fixed

- Data from last step is now available in action (#2)
- Fix crashing migration in MySQL 8 (#3). This was due to the fact that MySQL 8 doesn't
  support default values on JSON columns. Since a wizard always gets created
  with at least an empty array as its data, this can safely be removed.

## [0.1.0] — 2021-05-04

Initial release

[0.7.0]: https://github.com/laravel-arcanist/arcanist/compare/0.6.0...0.7.0
[0.6.0]: https://github.com/laravel-arcanist/arcanist/compare/0.5.2...0.6.0
[0.5.2]: https://github.com/laravel-arcanist/arcanist/compare/0.5.1...0.5.2
[0.5.1]: https://github.com/laravel-arcanist/arcanist/compare/0.5.0...0.5.1
[0.5.0]: https://github.com/laravel-arcanist/arcanist/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/laravel-arcanist/arcanist/compare/0.3.1...0.4.0
[0.3.1]: https://github.com/laravel-arcanist/arcanist/compare/0.3.0...0.3.1
[0.3.0]: https://github.com/laravel-arcanist/arcanist/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/laravel-arcanist/arcanist/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/laravel-arcanist/arcanist/releases/tag/0.1.0
