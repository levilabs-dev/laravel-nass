# Changelog

All notable changes to `levilabs/laravel-nass` will be documented in this file.

## Unreleased

### Added

- NASS E-Payment Gateway portal flow: login, create transaction, status check, notify callback parsing.
- Nass Wallet merchant flow: login, initTransaction, checkTransaction, checkout URL builder.
- Cached bearer tokens per merchant, DTOs, events, optional persistence tables, and sync commands.
- Pest tests, PHPStan level 8, GitHub Actions CI matrix (Laravel 11–13).
