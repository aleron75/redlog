# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.1.0] - 2019-10-29
### Added
- Add HTTP call to try to get activities by project

## [2.0.1] - 2019-10-29
### Changed
- Fix version in CLI

## [2.0.0] - 2019-10-26
This is a major release that breaks backward compatibility. You need to
change `.env` and add a `config.yml` file to make it work. 

### Added
- Add configuration for activities in `config.yml`
- Add configuration-based validation for tracked activities
- Add `project` command to get project details

### Changed
- Update gitignore
- Update dependencies to use `symfony/yaml` library

### Removed
- Remove configuration for activities from `.env`

## [1.0.0] - 2019-09-09
### Added
- First release
