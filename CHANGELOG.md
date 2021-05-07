# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), 
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
