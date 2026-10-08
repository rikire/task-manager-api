# Proposal: json-unicode

## Why

API responses escape every non-ASCII character: the seeded status `in_progress` comes back as
`"title":"\u0412 \u0440\u0430\u0431\u043e\u0442\u0435"` instead of `"title":"В работе"`. It is valid JSON, but
a reviewer reading `curl` output cannot read titles, and the assignment's own example bodies are in Russian
(`docs/task/assignment.txt:40-44, 87-91`). Recorded as `IMP-009-json-unescaped-unicode`; the owner scheduled it
as a separate change after `status-delete` (2026-10-07) and confirmed doing it before submission (2026-10-08).

## What Changes

One pass, one task group plus Polish:

- Every JSON response of the API — success and error — writes non-ASCII characters as UTF-8, not as `\uXXXX`;
  U+2028 and U+2029 stay escaped (owner, 2026-10-08).
- Success responses keep escaping `<`, `>`, `&`, `'`, `"` as `\uXXXX` sequences (Symfony's default for
  `AbstractController::json()`), for example `<` as `\u003C`; only non-ASCII changes.
- Mechanism: a decorator of the Serializer's JSON encoder in the `Shared` layer adds `JSON_UNESCAPED_UNICODE`
  to whatever encoding options a caller passes (design D1). Modules do not change.
- The curl-examples note about escaped Cyrillic is removed; `IMP-009` is closed; README "Что дальше" drops it.

## Out of scope

- Unescaping `/` (`\/` stays in all bodies), the HTML-sensitive characters above and U+2028/U+2029 (owner,
  2026-10-08).
- Describing `violations[].parameters` in the contract: the error scenario relies on the framework echoing the
  client's value there; the contract stays as it is (owner, 2026-10-08).
- The OpenAPI document `/api/doc.json` (served by NelmioApiDocBundle, not by the API endpoints).

## Capabilities

### New Capabilities

- `api`: rules that apply to every endpoint of the API; first requirement `REQ-API-json-utf8`.

### Modified Capabilities

None.

## Impact

- New code: `src/Shared/Infrastructure/Http/` — the encoder decorator, wired by `#[AsDecorator]` like
  `DomainProblemNormalizer`; `config/services.yaml` does not change.
- Docs: ADR-0007 amended (JSON text in UTF-8), `docs/api/curl-examples.md`, README "Что дальше",
  `docs/registers/debt.md` (`IMP-009` closed), `docs/README.md` (`<CAP>` gains `API`).
- No schema change, no new dependency, no contract change (`docs/api/openapi.yaml` describes strings, not
  their escaping).

## Roadmap

`docs/roadmap.md`, row 10 `json-unicode`.

## Coverage

| Category | Status | Rationale |
|---|---|---|
| Scope | clear | owner, 2026-10-08: all JSON responses, success and error |
| Data | clear | no schema change; text is stored as UTF-8 already |
| Edge cases and failures | clear | matrix below; HTML-sensitive characters stay escaped (owner) |
| Constraints | clear | `CON-STACK-rest`, `CON-DELIV-justify-extras` (README mentions it), `CON-PLAN-deadline` |
| Terminology | clear | "escaped" = written as `\uXXXX`; "UTF-8" = the character's own bytes (RFC 8259 §8.1) |
| Non-functional | clear | `QAS-MAINT-layering`: the decorator lives in `Shared`, modules untouched (ADR-0006) |
| Done criteria | clear | every `REQ-API-json-utf8` scenario tested on the raw response body; curl output readable on the prod image |

## Corner cases

| Input | Dimension | Expected behaviour |
|---|---|---|
| response body | content: Cyrillic in a success response (`title`) | literal UTF-8 in the raw body, no escaped non-ASCII character |
| response body | content: Cyrillic in an error response (a 422 violation echoes the client's value `"Ревью"`) | literal UTF-8 |
| response body | content: a 4-byte character (emoji) in `title` | literal UTF-8, not the surrogate pair `\ud83d\ude80` (same scenario as Cyrillic) |
| response body | content: `<`, `>`, `&`, `'`, `"` in a success response | still escaped: `\u003C`, `\u003E`, `\u0026`, `\u0027`, `\u0022` (uppercase hex, checked with PHP 8.4); `/` as `\/` |
| response body | content: U+2028, U+2029 (the `title` rule allows them: not category Cc) | stay escaped as `\u2028`, `\u2029` (owner; PHP keeps them escaped under `JSON_UNESCAPED_UNICODE` without `JSON_UNESCAPED_LINE_TERMINATORS`, checked with PHP 8.4) |
| request body | encoding: invalid UTF-8 | unchanged: 400 as before (ADR-0005), not in scope |

## Confirmed

- Owner, 2026-10-08: do `IMP-009` before submission; mechanism — decorator of the JSON encoder; applies to all
  JSON responses including errors; keep escaping `<` `>` `&` `'` `"`; new spec `api` with `REQ-API-json-utf8`
  plus an ADR-0007 amendment.
- Owner, 2026-10-08 (after `spec-auditor`): U+2028 and U+2029 stay escaped; the error scenario relies on the
  framework's `violations[].parameters` echo without a contract change; the auditor's factual corrections
  applied.

## Assumptions

None.

## Open questions

None.
