# Source

Copied from https://github.com/anthropics/skills/tree/main/skills/doc-coauthoring
at commit 00756142ab04c82a447693cf373c4e0c554d1005 (2025-12-04), with one local change (below).
License: Apache 2.0 — the upstream README states that the skills in the repo are Apache 2.0 except the
source-available document skills (docx, pdf, pptx, xlsx). The upstream folder has no license file, so
`LICENSE.txt` here is the standard Apache 2.0 text (from the GitHub licenses API), as section 4 of the
license requires.

Used for large documents written from scratch (README, architecture description); the reader-test
stage is referenced by the `writing` skill. Decision: docs/pre-init/19-agent-writing.md, 20-skills.md.

Local change: `disable-model-invocation: true` added to the frontmatter (line 3). The upstream
description triggers on "proposals, technical specs" and would hijack OpenSpec proposals; here the
skill runs only when invoked explicitly as `/doc-coauthoring`. Verify: diff against the upstream file
at the commit above shows only this line. On upstream updates, review the diff before copying.
