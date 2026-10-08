# Review: json-unicode

Review brief (template: `.claude/skills/change-workflow/SKILL.md`), kept with the change (`FAIL-006`). One
pass: both groups below, one pull request.

## Groups 1–2 — JSON text in UTF-8, Polish (tasks 1.1–2.2), 2026-10-08

**Ready to commit:** yes, after `make check`. PHPUnit `OK (227 tests)`; Deptrac 0 violations; on the prod image
(isolated stack) `GET /api/statuses` shows `Новая`, `В работе`, `Готово` as written, and a 422 echoes `Ревью`
as written.

**Data flow:** controller → `AbstractController::json()` (options `JSON_HEX_*`) or the error renderer (no
options) → Serializer → `UnescapedUnicodeJsonEncoder` (adds `JSON_UNESCAPED_UNICODE`; no options →
`JSON_PRESERVE_ZERO_FRACTION`, `JsonEncode`'s default) → the framework's `JsonEncoder`.

**Must read:** `src/Shared/Infrastructure/Http/UnescapedUnicodeJsonEncoder.php` (one method with logic,
`encode()`).

**Check by hand:** `curl -s "$API/api/statuses"` — Cyrillic titles readable.

**Key decisions:** design D1 (decorator, not per-controller flags or a response listener); ADR-0007 amended
(D4, "Text").

**Corner-case matrix and red output:** red run: 3 of 4 tests on assertions (escaped `\u0420…`, `\ud83d\ude80`);
`html-characters` green before and after — it guards the escaping that must stay. Owner accepted the tests.

**Findings during the work:**

- The `\uXXXX` sequences were lost when the proposal and spec were first written (the tool turns them into
  letters; known gotcha); `spec-auditor` caught it. Rewritten with the backslash built from `chr(92)`; the
  test file was written the same way.
- PHP facts checked in the container (PHP 8.4): `JSON_HEX_*` escapes are uppercase hex; U+2028 stays escaped
  under `JSON_UNESCAPED_UNICODE`; an emoji is otherwise a lowercase surrogate pair.

**Simplifications:** `\/` stays in all bodies; `violations[].parameters` stays outside the contract (owner).

**Debt:** none. **Not done:** none.

**Maturity:** functionality — production-ready; reliability — production-ready (no new failure mode);
security — production-ready (HTML-sensitive characters and U+2028/2029 stay escaped); maintainability —
production-ready (one class, modules untouched); observability — prototype; consumer experience — improved
(readable `curl` output).

**Extra checks:** `verifier` (task 2.2): no correctness defect; every scenario asserted on the raw body as
written; no other JSON writer bypasses the Serializer; request decoding unchanged. Fixed in this pass (owner): the
ADR-0007 rule and the requirement name `/api/doc.json` as out of scope (Nelmio's own flags); design.md and the
class docblock say that `default_context` `json_encode_options` no longer apply. Own decision: the inner encoder
is typed `EncoderInterface&DecoderInterface`, so a second decorator cannot break it. Not taken: tests on task
endpoints (same encoder, one path).
