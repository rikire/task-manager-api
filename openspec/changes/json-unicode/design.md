# Design: json-unicode

## Context

Motivation: `proposal.md`. Behaviour: `specs/api/spec.md`. Decided: ADR-0006 (`Shared` layer, modules do not
depend on it), ADR-0007 (API conventions; amended by this change).

How JSON is written today (read in `vendor/`): controllers call `AbstractController::json()`, which passes
`json_encode_options` = `JsonResponse::DEFAULT_ENCODING_OPTIONS` (the four `JSON_HEX_*` flags) to the Serializer
unless the caller's context overrides it — no controller here does (`AbstractController.php:155-157`);
`JsonEncode` takes options from the context before its default context (`JsonEncode.php:39`). So
`framework.serializer.default_context` would not reach success responses.
Errors are serialized by `SerializerErrorRenderer` without options (`SerializerErrorRenderer.php:62`), so they
get the encoder's default, `JSON_PRESERVE_ZERO_FRACTION` (`JsonEncode.php:28-30`).

Significance checklist: no schema change, no new module or dependency, no new failure semantics; one class in
an existing layer. The decision is local; the convention goes into ADR-0007 as an amendment.

## Decisions

### D1. Decorate the Serializer's JSON encoder

Scope: local. ADR: ADR-0007 (amended), ADR-0006 (`Shared`). `Shared\Infrastructure\Http\UnescapedUnicodeJsonEncoder`
decorates `serializer.encoder.json`, implements `EncoderInterface` and `DecoderInterface`, delegates both, and
on `encode()` sets `json_encode_options` to the caller's options (or, when none, `JsonEncode`'s default
`JSON_PRESERVE_ZERO_FRACTION`) OR `JSON_UNESCAPED_UNICODE`. Wired by `#[AsDecorator('serializer.encoder.json')]`,
as `DomainProblemNormalizer` is. Callers' `JSON_HEX_*` flags survive, which keeps the HTML-character escaping
of success responses. Rejected (owner, 2026-10-08): passing the flag in each controller (seven `$this->json()`
calls, easy to miss in a new one) and a response listener re-encoding bodies (decodes and encodes every body
twice). Known solutions (`RUL-CODE-reuse-first`): `framework.serializer.default_context` would reach only error
bodies (above), and with the decorator its `json_encode_options` no longer reach any body, because the decorator
always sets the key; a shared base controller overriding `json()` would make modules depend on `Shared`, which
ADR-0006 forbids. Decoration is the framework's own extension point, not a wrapper around it
(`RUL-CODE-yagni`).

The Serializer reaches the decorator: `SerializerPass` collects encoder ids before optimisation
(`SerializerPass.php:56,77`), then `DecoratorServicePass` points `serializer.encoder.json` at the decorator and
moves the tags to it (`DecoratorServicePass.php:114,119`); autoconfiguration also tags the decorator, so it is
listed twice — the same service, harmless (read by `spec-auditor`; the functional tests confirm it).

## Risks / Trade-offs

- [A future Symfony version changes how `json()` passes options] → the functional tests read raw bodies and fail.
- [Clients that compare raw bytes, not decoded JSON] → not a risk for a JSON client: both forms decode to the
  same string (RFC 8259 §7).
