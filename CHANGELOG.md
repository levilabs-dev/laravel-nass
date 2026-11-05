# Changelog

All notable changes to `levilabs/laravel-nass` will be documented in this file.

## Unreleased

### Added

- NASS E-Payment Gateway portal flow: login, create transaction, status check, notify callback parsing.
- Nass Wallet merchant flow aligned with [Nasswallet](https://github.com/Nasswallet) samples: login (`grantType`), initTransaction, checkTransaction (`transactionStatus` mapping), checkout URL builder (`transactionId` or `id` query param), callback verification via `parseCallback`.
- Basic auth header accepts raw base64 or full `Basic …` value; wallet API retries once on HTTP 401 after clearing cached token.
- Cached bearer tokens per merchant, DTOs, events, optional persistence tables, and sync commands.
- Pest tests, PHPStan level 8, GitHub Actions CI matrix (Laravel 11–13).
