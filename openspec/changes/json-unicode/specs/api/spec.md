## ADDED Requirements

### Requirement: REQ-API-json-utf8 — JSON responses carry non-ASCII text as UTF-8

Every JSON response of the API endpoints, success and error (the contract document `/api/doc.json` is
rendered by NelmioApiDocBundle and is not covered), SHALL write non-ASCII characters as their UTF-8 bytes, not
as `\uXXXX` escape sequences, except U+2028 and U+2029, which SHALL stay escaped as `\u2028` and `\u2029`.
In success responses the characters `<`, `>`, `&`, `'` and `"` inside strings SHALL stay escaped as `\u003C`,
`\u003E`, `\u0026`, `\u0027` and `\u0022`. "An escaped non-ASCII character" means a match of the
case-insensitive pattern `\\u(?!00[0-7][0-9a-f])[0-9a-f]{4}` other than `\u2028` and `\u2029`. Source:
`IMP-009`; owner, 2026-10-08; ADR-0007.

#### Scenario: REQ-API-json-utf8.success

- **WHEN** a client creates a status with `title` `Ревью кода 🚀` and then lists the statuses
- **THEN** the raw body of both responses contains `Ревью кода 🚀` as written, the raw body of the list also
  contains the seeded titles `Новая`, `В работе` and `Готово` as written, and neither body contains an escaped
  non-ASCII character

#### Scenario: REQ-API-json-utf8.error

- **WHEN** a client sends `{"name": "Ревью", "title": "Ревью кода"}` to `POST /api/statuses`
- **THEN** the response is 422, its raw body contains `Ревью` as written and no escaped non-ASCII character
  (the value is echoed by the framework in `violations[].parameters`, which the contract does not describe;
  owner, 2026-10-08)

#### Scenario: REQ-API-json-utf8.html-characters

- **WHEN** a client creates a status with `title` `<b>"Q&A's"</b>`
- **THEN** the raw body of the 201 response contains
  `"title":"\u003Cb\u003E\u0022Q\u0026A\u0027s\u0022\u003C\/b\u003E"` and the decoded `title` equals
  `<b>"Q&A's"</b>`

#### Scenario: REQ-API-json-utf8.line-separators

- **WHEN** a client creates a status whose `title` contains U+2028 between two Cyrillic words
- **THEN** the raw body of the 201 response contains the words as written and the separator as `\u2028`
