# Реестр источников из заметок

Реестр собран механически 2026-10-03 скриптом на Python: из всех файлов `notes/*.md` (кроме этого) регулярным выражением извлечены ссылки `http(s)://…`, с конца ссылок срезана пунктуация и непарные закрывающие скобки, ссылки дедуплицированы и сгруппированы по домену (без префикса `www.`). Для каждой ссылки указаны заметки, в которых она встречается. Аннотаций и оценки надёжности нет: метки [П]/[В]/[И] и контекст цитирования смотрите в самих заметках. Ссылки не открывались повторно и могли устареть; возможны артефакты извлечения (обрезанные или склеенные URL).

Итого: **377** уникальных ссылок, **163** доменов, **9** файлов заметок: `01-landscape.md`, `02-sdd-frameworks.md`, `03-evidence.md`, `04a-weaknesses-behavior.md`, `04b-weaknesses-code.md`, `05a-mech-requirements-planning.md`, `05b-mech-architecture-testing.md`, `05c-mech-context-security-docs.md`, `05d-mech-guardrails-harness.md`.

## Домены по числу ссылок

| Домен | Ссылок |
|---|---|
| [arxiv.org](#arxivorg) | 79 |
| [github.com](#githubcom) | 42 |
| [code.claude.com](#codeclaudecom) | 18 |
| [anthropic.com](#anthropiccom) | 9 |
| [simonwillison.net](#simonwillisonnet) | 9 |
| [claudeissues.com](#claudeissuescom) | 6 |
| [martinfowler.com](#martinfowlercom) | 6 |
| [metr.org](#metrorg) | 6 |
| [kiro.dev](#kirodev) | 5 |
| [raw.githubusercontent.com](#rawgithubusercontentcom) | 5 |
| [thoughtworks.com](#thoughtworkscom) | 5 |
| [cloud.google.com](#cloudgooglecom) | 4 |
| [infoq.com](#infoqcom) | 4 |
| [alphaxiv.org](#alphaxivorg) | 3 |
| [blog.robbowley.net](#blogrobbowleynet) | 3 |
| [codex.danielvaughan.com](#codexdanielvaughancom) | 3 |
| [forum.cursor.com](#forumcursorcom) | 3 |
| [github.blog](#githubblog) | 3 |
| [learn.chatgpt.com](#learnchatgptcom) | 3 |
| [openai.com](#openaicom) | 3 |
| [addyosmani.com](#addyosmanicom) | 2 |
| [aiweekly.co](#aiweeklyco) | 2 |
| [augmentcode.com](#augmentcodecom) | 2 |
| [aws.amazon.com](#awsamazoncom) | 2 |
| [codemyspec.com](#codemyspeccom) | 2 |
| [conf.researchr.org](#confresearchrorg) | 2 |
| [cusy.io](#cusyio) | 2 |
| [every.to](#everyto) | 2 |
| [faros.ai](#farosai) | 2 |
| [morphllm.com](#morphllmcom) | 2 |
| [skillselion.com](#skillselioncom) | 2 |
| [sonarsource.com](#sonarsourcecom) | 2 |
| [tessl.io](#tesslio) | 2 |
| [tianpan.co](#tianpanco) | 2 |
| [veracode.com](#veracodecom) | 2 |
| [2026.msrconf.org](#2026msrconforg) | 1 |
| [aclanthology.org](#aclanthologyorg) | 1 |
| [adr.github.io](#adrgithubio) | 1 |
| [agentpatterns.ai](#agentpatternsai) | 1 |
| [agents.md](#agentsmd) | 1 |
| [agentskills.io](#agentskillsio) | 1 |
| [ai.engineer](#aiengineer) | 1 |
| [alistairmavin.com](#alistairmavincom) | 1 |
| [anvilogic.com](#anvilogiccom) | 1 |
| [aol.com](#aolcom) | 1 |
| [assets.anthropic.com](#assetsanthropiccom) | 1 |
| [atlassian.com](#atlassiancom) | 1 |
| [beta.pkg.go.dev](#betapkggodev) | 1 |
| [bleepingcomputer.com](#bleepingcomputercom) | 1 |
| [blog.fsck.com](#blogfsckcom) | 1 |
| [blog.google](#bloggoogle) | 1 |
| [blog.jetbrains.com](#blogjetbrainscom) | 1 |
| [blog.scottlogic.com](#blogscottlogiccom) | 1 |
| [brainonllm.com](#brainonllmcom) | 1 |
| [buttondown.com](#buttondowncom) | 1 |
| [claude.com](#claudecom) | 1 |
| [coderabbit.ai](#coderabbitai) | 1 |
| [cognition.com](#cognitioncom) | 1 |
| [colton.dev](#coltondev) | 1 |
| [computer.org](#computerorg) | 1 |
| [conffab.com](#conffabcom) | 1 |
| [cucumber.io](#cucumberio) | 1 |
| [cursor.com](#cursorcom) | 1 |
| [data.mendeley.com](#datamendeleycom) | 1 |
| [dev.classmethod.jp](#devclassmethodjp) | 1 |
| [dev.to](#devto) | 1 |
| [devclass.com](#devclasscom) | 1 |
| [developers.openai.com](#developersopenaicom) | 1 |
| [devops.com](#devopscom) | 1 |
| [diataxis.fr](#diataxisfr) | 1 |
| [discourse.llvm.org](#discoursellvmorg) | 1 |
| [docs.astral.sh](#docsastralsh) | 1 |
| [docs.bmad-method.org](#docsbmad-methodorg) | 1 |
| [docs.fedoraproject.org](#docsfedoraprojectorg) | 1 |
| [docs.github.com](#docsgithubcom) | 1 |
| [docs.kernel.org](#docskernelorg) | 1 |
| [docs.sonarsource.com](#docssonarsourcecom) | 1 |
| [dora.dev](#doradev) | 1 |
| [en.wikipedia.org](#enwikipediaorg) | 1 |
| [engineering.fb.com](#engineeringfbcom) | 1 |
| [engineering.nyu.edu](#engineeringnyuedu) | 1 |
| [evals.alignment.org](#evalsalignmentorg) | 1 |
| [eweek.com](#eweekcom) | 1 |
| [fastly.com](#fastlycom) | 1 |
| [findanexpert.unimelb.edu.au](#findanexpertunimelbeduau) | 1 |
| [geminicli.com](#geminiclicom) | 1 |
| [getdx.com](#getdxcom) | 1 |
| [git-scm.com](#git-scmcom) | 1 |
| [gitclear.com](#gitclearcom) | 1 |
| [github.github.io](#githubgithubio) | 1 |
| [greaterwrong.com](#greaterwrongcom) | 1 |
| [horadecodar.com.br](#horadecodarcombr) | 1 |
| [huggingface.co](#huggingfaceco) | 1 |
| [i-programmer.info](#i-programmerinfo) | 1 |
| [infoworld.com](#infoworldcom) | 1 |
| [invariantlabs.ai](#invariantlabsai) | 1 |
| [itsfoss.com](#itsfosscom) | 1 |
| [itwire.com](#itwirecom) | 1 |
| [jellyfish.co](#jellyfishco) | 1 |
| [joelonsoftware.com](#joelonsoftwarecom) | 1 |
| [kenhuangus.substack.com](#kenhuangussubstackcom) | 1 |
| [labs.cloudsecurityalliance.org](#labscloudsecurityallianceorg) | 1 |
| [leaddev.com](#leaddevcom) | 1 |
| [letsdatascience.com](#letsdatasciencecom) | 1 |
| [lilting.ch](#liltingch) | 1 |
| [linuxiac.com](#linuxiaccom) | 1 |
| [llvm.org](#llvmorg) | 1 |
| [lwn.net](#lwnnet) | 1 |
| [marmelab.com](#marmelabcom) | 1 |
| [media.mit.edu](#mediamitedu) | 1 |
| [medium.com](#mediumcom) | 1 |
| [microsoft.com](#microsoftcom) | 1 |
| [mise.jdx.dev](#misejdxdev) | 1 |
| [mitsloan.mit.edu](#mitsloanmitedu) | 1 |
| [modern-genai-se.github.io](#modern-genai-segithubio) | 1 |
| [nesbitt.io](#nesbittio) | 1 |
| [netbsd.org](#netbsdorg) | 1 |
| [neurips.cc](#neuripscc) | 1 |
| [newsletter.kentbeck.com](#newsletterkentbeckcom) | 1 |
| [okta.com](#oktacom) | 1 |
| [oreilly.com](#oreillycom) | 1 |
| [owasp.org](#owasporg) | 1 |
| [ox.security](#oxsecurity) | 1 |
| [papers.ssrn.com](#papersssrncom) | 1 |
| [phoronix.com](#phoronixcom) | 1 |
| [pistack.xyz](#pistackxyz) | 1 |
| [pith.science](#pithscience) | 1 |
| [pkg.go.dev](#pkggodev) | 1 |
| [platform.claude.com](#platformclaudecom) | 1 |
| [ppc.land](#ppcland) | 1 |
| [producthunt.com](#producthuntcom) | 1 |
| [qemu.org](#qemuorg) | 1 |
| [qodo.ai](#qodoai) | 1 |
| [redirect.github.com](#redirectgithubcom) | 1 |
| [redmonk.com](#redmonkcom) | 1 |
| [render.com](#rendercom) | 1 |
| [rfc-editor.org](#rfc-editororg) | 1 |
| [rogerwong.me](#rogerwongme) | 1 |
| [seangoedecke.com](#seangoedeckecom) | 1 |
| [searchenginejournal.com](#searchenginejournalcom) | 1 |
| [sphinx-graph.readthedocs.io](#sphinx-graphreadthedocsio) | 1 |
| [strictdoc.readthedocs.io](#strictdocreadthedocsio) | 1 |
| [survey.stackoverflow.co](#surveystackoverflowco) | 1 |
| [t2informatik.de](#t2informatikde) | 1 |
| [techcrunch.com](#techcrunchcom) | 1 |
| [techstrong.ai](#techstrongai) | 1 |
| [techtarget.com](#techtargetcom) | 1 |
| [the-decoder.com](#the-decodercom) | 1 |
| [themodelwire.com](#themodelwirecom) | 1 |
| [theneuron.ai](#theneuronai) | 1 |
| [thenewstack.io](#thenewstackio) | 1 |
| [theregister.com](#theregistercom) | 1 |
| [transluce.org](#transluceorg) | 1 |
| [trychroma.com](#trychromacom) | 1 |
| [upwind.io](#upwindio) | 1 |
| [usagebar.com](#usagebarcom) | 1 |
| [venturebeat.com](#venturebeatcom) | 1 |
| [vercel.com](#vercelcom) | 1 |
| [visualstudiomagazine.com](#visualstudiomagazinecom) | 1 |
| [webcf.waybackmachine.org](#webcfwaybackmachineorg) | 1 |
| [wiki.gentoo.org](#wikigentooorg) | 1 |
| [www-cdn.anthropic.com](#www-cdnanthropiccom) | 1 |
| [yuvalyeret.com](#yuvalyeretcom) | 1 |

## Ссылки по доменам

### arxiv.org

- <https://arxiv.org/abs/2310.01798> — `04a-weaknesses-behavior.md`
- <https://arxiv.org/abs/2310.13548> — `04a-weaknesses-behavior.md`
- <https://arxiv.org/abs/2402.13521> — `05b-mech-architecture-testing.md`
- <https://arxiv.org/abs/2404.13076> — `04a-weaknesses-behavior.md`
- <https://arxiv.org/abs/2405.17739v1> — `03-evidence.md`
- <https://arxiv.org/abs/2406.09834v3> — `04b-weaknesses-code.md`
- <https://arxiv.org/abs/2406.10279> — `04b-weaknesses-code.md`
- <https://arxiv.org/abs/2407.11406> — `05b-mech-architecture-testing.md`
- <https://arxiv.org/abs/2410.06107> — `01-landscape.md`
- <https://arxiv.org/abs/2410.12944v3> — `03-evidence.md`
- <https://arxiv.org/abs/2410.21136> — `05b-mech-architecture-testing.md`
- <https://arxiv.org/abs/2412.18531> — `05b-mech-architecture-testing.md`
- <https://arxiv.org/abs/2501.12862> — `04b-weaknesses-code.md`, `05b-mech-architecture-testing.md`
- <https://arxiv.org/abs/2502.08177> — `04a-weaknesses-behavior.md`
- <https://arxiv.org/abs/2502.11844> — `04b-weaknesses-code.md`
- <https://arxiv.org/abs/2502.13069> — `04a-weaknesses-behavior.md`, `05a-mech-requirements-planning.md`
- <https://arxiv.org/abs/2503.01449> — `05b-mech-architecture-testing.md`
- <https://arxiv.org/abs/2505.06120v1> — `05c-mech-context-security-docs.md`
- <https://arxiv.org/abs/2506.08837v3> — `05c-mech-context-security-docs.md`
- <https://arxiv.org/abs/2506.12469> — `05b-mech-architecture-testing.md`
- <https://arxiv.org/abs/2507.11538> — `05c-mech-context-security-docs.md`
- <https://arxiv.org/abs/2507.15003> — `03-evidence.md`
- <https://arxiv.org/abs/2508.04448> — `05b-mech-architecture-testing.md`
- <https://arxiv.org/abs/2508.11126v1> — `01-landscape.md`
- <https://arxiv.org/abs/2508.12358> — `05a-mech-requirements-planning.md`
- <https://arxiv.org/abs/2509.06216> — `01-landscape.md`
- <https://arxiv.org/abs/2510.09907> — `05b-mech-architecture-testing.md`
- <https://arxiv.org/abs/2510.19692> — `01-landscape.md`
- <https://arxiv.org/abs/2510.20270> — `05b-mech-architecture-testing.md`
- <https://arxiv.org/abs/2511.04427v2> — `04b-weaknesses-code.md`
- <https://arxiv.org/abs/2511.21654> — `04a-weaknesses-behavior.md`
- <https://arxiv.org/abs/2601.03878> — `05a-mech-requirements-planning.md`
- <https://arxiv.org/abs/2601.07786> — `05a-mech-requirements-planning.md`
- <https://arxiv.org/abs/2601.07786v1> — `04b-weaknesses-code.md`
- <https://arxiv.org/abs/2601.20245> — `03-evidence.md`
- <https://arxiv.org/abs/2601.20404v1> — `04a-weaknesses-behavior.md`, `05c-mech-context-security-docs.md`
- <https://arxiv.org/abs/2602.00180> — `02-sdd-frameworks.md`
- <https://arxiv.org/abs/2602.05868v1> — `05b-mech-architecture-testing.md`
- <https://arxiv.org/abs/2602.11988> — `04a-weaknesses-behavior.md`, `05c-mech-context-security-docs.md`
- <https://arxiv.org/abs/2602.11988v1> — `04b-weaknesses-code.md`
- <https://arxiv.org/abs/2603.00539> — `05a-mech-requirements-planning.md`
- <https://arxiv.org/abs/2603.12123> — `04a-weaknesses-behavior.md`
- <https://arxiv.org/abs/2603.22106> — `01-landscape.md`
- <https://arxiv.org/abs/2604.05278> — `02-sdd-frameworks.md`
- <https://arxiv.org/abs/2604.24712> — `05a-mech-requirements-planning.md`
- <https://arxiv.org/abs/2605.15245> — `01-landscape.md`
- <https://arxiv.org/abs/2605.21537> — `05a-mech-requirements-planning.md`
- <https://arxiv.org/abs/2606.18168> — `03-evidence.md`
- <https://arxiv.org/abs/2607.03316> — `05b-mech-architecture-testing.md`
- <https://arxiv.org/abs/2608.17177> — `02-sdd-frameworks.md`
- <https://arxiv.org/abs/2608.25202> — `02-sdd-frameworks.md`
- <https://arxiv.org/html/2503.22674v2> — `05a-mech-requirements-planning.md`
- <https://arxiv.org/html/2506.12286v4> — `03-evidence.md`
- <https://arxiv.org/html/2507.02778v1> — `05a-mech-requirements-planning.md`
- <https://arxiv.org/html/2507.02778v2> — `04a-weaknesses-behavior.md`
- <https://arxiv.org/html/2507.11538v1> — `05c-mech-context-security-docs.md`
- <https://arxiv.org/html/2510.20270> — `04a-weaknesses-behavior.md`
- <https://arxiv.org/html/2512.03262v2> — `03-evidence.md`
- <https://arxiv.org/html/2512.11589v1> — `05a-mech-requirements-planning.md`
- <https://arxiv.org/html/2601.20109> — `04b-weaknesses-code.md`
- <https://arxiv.org/html/2602.04226v1> — `03-evidence.md`
- <https://arxiv.org/html/2602.06176v1> — `04a-weaknesses-behavior.md`
- <https://arxiv.org/html/2602.11988v1> — `05c-mech-context-security-docs.md`
- <https://arxiv.org/html/2603.00187> — `05a-mech-requirements-planning.md`
- <https://arxiv.org/html/2603.06276v1> — `04a-weaknesses-behavior.md`
- <https://arxiv.org/html/2603.24755v1> — `04b-weaknesses-code.md`
- <https://arxiv.org/html/2603.28592v1> — `04b-weaknesses-code.md`
- <https://arxiv.org/html/2605.02741v1> — `04b-weaknesses-code.md`
- <https://arxiv.org/html/2605.29442> — `04a-weaknesses-behavior.md`
- <https://arxiv.org/html/2608.00661v1> — `04b-weaknesses-code.md`
- <https://arxiv.org/pdf/2309.15606> — `04b-weaknesses-code.md`
- <https://arxiv.org/pdf/2410.21136v1> — `04b-weaknesses-code.md`
- <https://arxiv.org/pdf/2504.07244> — `05a-mech-requirements-planning.md`
- <https://arxiv.org/pdf/2511.21654> — `04a-weaknesses-behavior.md`
- <https://arxiv.org/pdf/2601.04886> — `04b-weaknesses-code.md`
- <https://arxiv.org/pdf/2602.11988> — `04b-weaknesses-code.md`
- <https://arxiv.org/pdf/2604.20911> — `04a-weaknesses-behavior.md`
- <https://arxiv.org/pdf/2606.18168> — `04b-weaknesses-code.md`
- <https://arxiv.org/pdf/2609.10548> — `04b-weaknesses-code.md`

### github.com

- <http://github.com/> — `05d-mech-guardrails-harness.md`
- <https://github.com/Fission-AI/OpenSpec> — `02-sdd-frameworks.md`, `05a-mech-requirements-planning.md`
- <https://github.com/Fission-AI/OpenSpec/blob/main/docs/concepts.md> — `02-sdd-frameworks.md`
- <https://github.com/MrLesk/Backlog.md> — `05a-mech-requirements-planning.md`
- <https://github.com/Pimzino/spec-workflow-mcp> — `02-sdd-frameworks.md`
- <https://github.com/Shopify/packwerk> — `05b-mech-architecture-testing.md`
- <https://github.com/TNG/ArchUnit> — `05b-mech-architecture-testing.md`
- <https://github.com/adr/madr> — `05b-mech-architecture-testing.md`
- <https://github.com/agent-sh/agnix/wiki> — `05d-mech-guardrails-harness.md`
- <https://github.com/agentskills/agentskills/tree/main/skills-ref> — `05d-mech-guardrails-harness.md`
- <https://github.com/anthropics/claude-code-security-review> — `05b-mech-architecture-testing.md`
- <https://github.com/anthropics/claude-code/issues/21027> — `04b-weaknesses-code.md`
- <https://github.com/anthropics/claude-code/issues/31890> — `04b-weaknesses-code.md`
- <https://github.com/anthropics/claude-code/issues/41957> — `04b-weaknesses-code.md`
- <https://github.com/bmad-code-org/BMAD-METHOD> — `02-sdd-frameworks.md`
- <https://github.com/bmad-code-org/BMAD-METHOD/blob/main/LICENSE> — `02-sdd-frameworks.md`
- <https://github.com/boxed/mutmut> — `05b-mech-architecture-testing.md`
- <https://github.com/buildermethods/agent-os> — `02-sdd-frameworks.md`
- <https://github.com/buildermethods/agent-os/blob/main/CHANGELOG.md> — `02-sdd-frameworks.md`
- <https://github.com/coleam00/context-engineering-intro> — `02-sdd-frameworks.md`
- <https://github.com/deptrac/deptrac> — `05b-mech-architecture-testing.md`
- <https://github.com/eyaltoledano/claude-task-master> — `02-sdd-frameworks.md`
- <https://github.com/eyaltoledano/claude-task-master/blob/main/LICENSE> — `02-sdd-frameworks.md`
- <https://github.com/flyerhzm/bullet> — `05b-mech-architecture-testing.md`
- <https://github.com/github/spec-kit> — `02-sdd-frameworks.md`
- <https://github.com/github/spec-kit/blob/main/spec-driven.md> — `05b-mech-architecture-testing.md`
- <https://github.com/github/spec-kit/releases> — `02-sdd-frameworks.md`
- <https://github.com/gotalab/cc-sdd> — `02-sdd-frameworks.md`
- <https://github.com/gsd-build/get-shit-done> — `02-sdd-frameworks.md`
- <https://github.com/jamesmhall/ailint> — `05c-mech-context-security-docs.md`
- <https://github.com/jmcarp/nplusone> — `05b-mech-architecture-testing.md`
- <https://github.com/mehdy/todocheck> — `05a-mech-requirements-planning.md`
- <https://github.com/nizos/tdd-guard> — `05b-mech-architecture-testing.md`
- <https://github.com/obra/superpowers> — `02-sdd-frameworks.md`
- <https://github.com/open-gsd/gsd-core> — `02-sdd-frameworks.md`
- <https://github.com/ryoppippi/ccusage> — `05d-mech-guardrails-harness.md`
- <https://github.com/seddonym/import-linter> — `05b-mech-architecture-testing.md`
- <https://github.com/shawilly/agnix> — `05d-mech-guardrails-harness.md`
- <https://github.com/sourcefrog/cargo-mutants> — `05b-mech-architecture-testing.md`
- <https://github.com/steveyegge/beads> — `05a-mech-requirements-planning.md`
- <https://github.com/sverweij/dependency-cruiser> — `05b-mech-architecture-testing.md`
- <https://github.com/upstash/context7> — `05a-mech-requirements-planning.md`

### code.claude.com

- <https://code.claude.com/docs/en/best-practices> — `01-landscape.md`, `05a-mech-requirements-planning.md`, `05b-mech-architecture-testing.md`
- <https://code.claude.com/docs/en/context-window> — `05c-mech-context-security-docs.md`
- <https://code.claude.com/docs/en/hooks> — `05b-mech-architecture-testing.md`, `05c-mech-context-security-docs.md`, `05d-mech-guardrails-harness.md`
- <https://code.claude.com/docs/en/hooks-guide> — `04a-weaknesses-behavior.md`, `04b-weaknesses-code.md`
- <https://code.claude.com/docs/en/how-claude-code-works> — `05c-mech-context-security-docs.md`
- <https://code.claude.com/docs/en/memory> — `05c-mech-context-security-docs.md`, `05d-mech-guardrails-harness.md`
- <https://code.claude.com/docs/en/monitoring-usage> — `05d-mech-guardrails-harness.md`
- <https://code.claude.com/docs/en/output-styles> — `05c-mech-context-security-docs.md`
- <https://code.claude.com/docs/en/permissions> — `05b-mech-architecture-testing.md`, `05c-mech-context-security-docs.md`, `05d-mech-guardrails-harness.md`
- <https://code.claude.com/docs/en/plugin-evals.md> — `05d-mech-guardrails-harness.md`
- <https://code.claude.com/docs/en/prompt-caching> — `05c-mech-context-security-docs.md`
- <https://code.claude.com/docs/en/sandboxing> — `05c-mech-context-security-docs.md`
- <https://code.claude.com/docs/en/sessions> — `05c-mech-context-security-docs.md`
- <https://code.claude.com/docs/en/settings-reference> — `05c-mech-context-security-docs.md`
- <https://code.claude.com/docs/en/skills> — `05c-mech-context-security-docs.md`
- <https://code.claude.com/docs/en/sub-agents> — `05d-mech-guardrails-harness.md`
- <https://code.claude.com/docs/en/tools-reference> — `05c-mech-context-security-docs.md`
- <https://code.claude.com/docs/llms.txt> — `05c-mech-context-security-docs.md`

### anthropic.com

- <https://www.anthropic.com/engineering/demystifying-evals-for-ai-agents> — `05d-mech-guardrails-harness.md`
- <https://www.anthropic.com/engineering/effective-context-engineering-for-ai-agents> — `01-landscape.md`, `05c-mech-context-security-docs.md`
- <https://www.anthropic.com/engineering/effective-harnesses-for-long-running-agents> — `01-landscape.md`, `04a-weaknesses-behavior.md`, `05b-mech-architecture-testing.md`, `05c-mech-context-security-docs.md`
- <https://www.anthropic.com/engineering/equipping-agents-for-the-real-world-with-agent-skills> — `01-landscape.md`
- <https://www.anthropic.com/engineering/harness-design-long-running-apps> — `04a-weaknesses-behavior.md`, `05b-mech-architecture-testing.md`
- <https://www.anthropic.com/engineering/multi-agent-research-system> — `05d-mech-guardrails-harness.md`
- <https://www.anthropic.com/research/AI-assistance-coding-skills> — `03-evidence.md`, `05c-mech-context-security-docs.md`
- <https://www.anthropic.com/research/emergent-misalignment-reward-hacking> — `04a-weaknesses-behavior.md`
- <https://www.anthropic.com/research/how-ai-is-transforming-work-at-anthropic> — `03-evidence.md`

### simonwillison.net

- <https://simonwillison.net/2025/Aug/6/not-10x> — `03-evidence.md`
- <https://simonwillison.net/2025/Jul/12/ai-open-source-productivity/> — `01-landscape.md`
- <https://simonwillison.net/2025/Jun/16/the-lethal-trifecta/> — `05c-mech-context-security-docs.md`
- <https://simonwillison.net/2025/Oct/7/vibe-engineering/> — `01-landscape.md`
- <https://simonwillison.net/2026/Feb/15/cognitive-debt> — `01-landscape.md`
- <https://simonwillison.net/2026/Mar/24/package-managers-need-to-cool-down/> — `05c-mech-context-security-docs.md`
- <https://simonwillison.net/2026/feb/23/agentic-engineering-patterns> — `01-landscape.md`
- <https://simonwillison.net/guides/agentic-engineering-patterns/> — `01-landscape.md`
- <https://simonwillison.net/guides/agentic-engineering-patterns/what-is-agentic-engineering> — `01-landscape.md`

### claudeissues.com

- <https://claudeissues.com/issue/1501-bug-claude-code-reports-false-test-results-and-actions> — `04a-weaknesses-behavior.md`
- <https://claudeissues.com/issue/35138-docs-plugin-validation-docs-omit-frontmatter-and-hooks-hooks-json-coverage> — `05d-mech-guardrails-harness.md`
- <https://claudeissues.com/issue/44707-docs-hook-exit-codes-exit-1-silently-non-blocking-needs-prominent-warning> — `05d-mech-guardrails-harness.md`
- <https://claudeissues.com/issue/44955-claude-fabricates-verified-claims-without-evidence-3-consecutive-lies-in-one-ses> — `04a-weaknesses-behavior.md`
- <https://claudeissues.com/issue/46940-claude-fabricates-test-results-reports-all-passed-when-tests-are-failing> — `04a-weaknesses-behavior.md`
- <https://claudeissues.com/issue/72480-claude-code-cannot-be-trusted-every-response-requires-adversarial-verification> — `04a-weaknesses-behavior.md`

### martinfowler.com

- <https://martinfowler.com/articles/exploring-gen-ai/context-engineering-coding-agents.html> — `01-landscape.md`
- <https://martinfowler.com/articles/exploring-gen-ai/harness-engineering.html> — `05b-mech-architecture-testing.md`
- <https://martinfowler.com/articles/exploring-gen-ai/sdd-3-tools.html> — `01-landscape.md`, `02-sdd-frameworks.md`, `05a-mech-requirements-planning.md`, `05b-mech-architecture-testing.md`
- <https://martinfowler.com/articles/harness-engineering.html> — `01-landscape.md`
- <https://martinfowler.com/articles/sensors-for-coding-agents.html> — `01-landscape.md`
- <https://martinfowler.com/fragments/2026-01-08.html> — `02-sdd-frameworks.md`

### metr.org

- <https://metr.org/blog/2025-06-05-recent-reward-hacking/> — `04a-weaknesses-behavior.md`
- <https://metr.org/blog/2025-07-10-early-2025-ai-experienced-os-dev-study/> — `01-landscape.md`, `03-evidence.md`, `05a-mech-requirements-planning.md`
- <https://metr.org/blog/2025-08-12-research-update-towards-reconciling-slowdown-with-time-horizons/> — `03-evidence.md`
- <https://metr.org/blog/2026-02-24-uplift-update/> — `03-evidence.md`, `05a-mech-requirements-planning.md`
- <https://metr.org/notes/2026-02-17-exploratory-transcript-analysis-for-estimating-time-savings-from-coding-agents/> — `03-evidence.md`
- <https://metr.org/notes/2026-03-10-many-swe-bench-passing-prs-would-not-be-merged-into-main/> — `03-evidence.md`

### kiro.dev

- <https://kiro.dev/docs/billing/> — `02-sdd-frameworks.md`
- <https://kiro.dev/docs/hooks/> — `02-sdd-frameworks.md`
- <https://kiro.dev/docs/specs/> — `02-sdd-frameworks.md`, `05a-mech-requirements-planning.md`
- <https://kiro.dev/docs/specs/feature-specs/> — `02-sdd-frameworks.md`, `05a-mech-requirements-planning.md`
- <https://kiro.dev/docs/steering/> — `02-sdd-frameworks.md`

### raw.githubusercontent.com

- <https://raw.githubusercontent.com/ghostty-org/ghostty/main/AI_POLICY.md> — `05c-mech-context-security-docs.md`
- <https://raw.githubusercontent.com/github/spec-kit/main/templates/commands/analyze.md> — `05a-mech-requirements-planning.md`
- <https://raw.githubusercontent.com/github/spec-kit/main/templates/commands/clarify.md> — `05a-mech-requirements-planning.md`
- <https://raw.githubusercontent.com/github/spec-kit/main/templates/spec-template.md> — `05a-mech-requirements-planning.md`
- <https://raw.githubusercontent.com/github/spec-kit/main/templates/tasks-template.md> — `05a-mech-requirements-planning.md`

### thoughtworks.com

- <https://thoughtworks.com/radar> — `05b-mech-architecture-testing.md`
- <https://www.thoughtworks.com/content/dam/thoughtworks/documents/radar/2026/04/tr_technology_radar_vol_34_en.pdf> — `02-sdd-frameworks.md`
- <https://www.thoughtworks.com/insights/podcasts/technology-podcasts/themes-technology-radar-33> — `01-landscape.md`
- <https://www.thoughtworks.com/radar> — `01-landscape.md`
- <https://www.thoughtworks.com/radar/techniques/spec-driven-development> — `02-sdd-frameworks.md`

### cloud.google.com

- <https://cloud.google.com/blog/products/ai-machine-learning/announcing-the-2025-dora-report> — `03-evidence.md`
- <https://cloud.google.com/blog/products/ai-machine-learning/introducing-doras-inaugural-ai-capabilities-model> — `01-landscape.md`, `03-evidence.md`
- <https://cloud.google.com/blog/products/devops-sre/announcing-the-2024-dora-report> — `03-evidence.md`
- <https://cloud.google.com/resources/content/2025-dora-ai-capabilities-model-report> — `01-landscape.md`

### infoq.com

- <https://infoq.com/articles/agentic-fitness-functions-evolutionary-architecture> — `05b-mech-architecture-testing.md`
- <https://infoq.com/news/2026/02/ai-coding-skill-formation/> — `05c-mech-context-security-docs.md`
- <https://www.infoq.com/jp/news/2026/03/openai-harness-engineering-codex/> — `01-landscape.md`
- <https://www.infoq.com/news/2025/09/dora-state-of-ai-in-dev-2025> — `01-landscape.md`

### alphaxiv.org

- <https://www.alphaxiv.org/abs/2603.22106> — `05c-mech-context-security-docs.md`
- <https://www.alphaxiv.org/abs/2603.26233> — `05a-mech-requirements-planning.md`
- <https://www.alphaxiv.org/abs/2608.your-agents-are-not-time-aware> — `04a-weaknesses-behavior.md`

### blog.robbowley.net

- <https://blog.robbowley.net/2025/10/01/dora-2025-ai-assisted-dev-report-some-benefit-most-dont/> — `01-landscape.md`
- <https://blog.robbowley.net/2025/12/04/ai-is-still-making-code-worse-a-new-cmu-study-confirms/> — `04b-weaknesses-code.md`
- <https://blog.robbowley.net/2026/04/04/metrs-developer-productivity-research-2026-update/> — `03-evidence.md`

### codex.danielvaughan.com

- <https://codex.danielvaughan.com/2026/04/13/agnix-linting-codex-cli-agent-configurations/> — `05c-mech-context-security-docs.md`
- <https://codex.danielvaughan.com/2026/04/23/codex-cli-hooks-graduate-stable-v0124-mcp-observation-inline-config/> — `05d-mech-guardrails-harness.md`
- <https://codex.danielvaughan.com/2026/04/28/codex-cli-architecture-decision-records-adr-automated-governance/> — `05b-mech-architecture-testing.md`

### forum.cursor.com

- <https://forum.cursor.com/t/beforeshellexecution-hook-permissions-allow-ask-ignored-allow-list-takes-precedence/144244> — `05d-mech-guardrails-harness.md`
- <https://forum.cursor.com/t/built-a-small-cli-to-detect-agents-md-drift-missing-commands-dead-paths-conflicting-nested-files/159582> — `05c-mech-context-security-docs.md`
- <https://forum.cursor.com/t/hooks-not-firing-cannot-have-guardrails/168407> — `05d-mech-guardrails-harness.md`

### github.blog

- <https://github.blog/2025-09-02-spec-driven-development-with-ai-get-started-with-a-new-open-source-toolkit/> — `02-sdd-frameworks.md`
- <https://github.blog/ai-and-ml/generative-ai/spec-driven-development-with-ai-get-started-with-a-new-open-source-toolkit> — `01-landscape.md`
- <https://github.blog/news-insights/research/does-github-copilot-improve-code-quality-heres-what-the-data-says/> — `03-evidence.md`

### learn.chatgpt.com

- <https://learn.chatgpt.com/docs/agent-configuration/agents-md> — `05c-mech-context-security-docs.md`
- <https://learn.chatgpt.com/docs/hooks> — `05d-mech-guardrails-harness.md`
- <https://learn.chatgpt.com/docs/security> — `05c-mech-context-security-docs.md`

### openai.com

- <https://openai.com/index/expanding-on-sycophancy/> — `04a-weaknesses-behavior.md`
- <https://openai.com/index/why-we-no-longer-evaluate-swe-bench-verified/> — `03-evidence.md`
- <https://www.openai.com/index/harness-engineering> — `01-landscape.md`

### addyosmani.com

- <https://addyosmani.com/blog/agentic-code-review/> — `01-landscape.md`
- <https://addyosmani.com/blog/comprehension-debt/> — `03-evidence.md`, `05c-mech-context-security-docs.md`

### aiweekly.co

- <https://aiweekly.co/node/1877> — `04a-weaknesses-behavior.md`
- <https://aiweekly.co/node/5874> — `03-evidence.md`

### augmentcode.com

- <https://www.augmentcode.com/guides/ai-productivity-paradox-engineering-delivery> — `03-evidence.md`
- <https://www.augmentcode.com/guides/spec-driven-development-vs-waterfall> — `02-sdd-frameworks.md`

### aws.amazon.com

- <https://aws.amazon.com/blogs/devops/ai-driven-development-life-cycle/> — `01-landscape.md`
- <https://aws.amazon.com/security/security-bulletins/AWS-2025-015/> — `05c-mech-context-security-docs.md`

### codemyspec.com

- <https://codemyspec.com/blog/openspec-vs-spec-kit> — `02-sdd-frameworks.md`
- <https://codemyspec.com/blog/tessl-review> — `02-sdd-frameworks.md`

### conf.researchr.org

- <https://conf.researchr.org/details/icse-2025/icse-2025-research-track/198/LLMs-Meet-Library-Evolution-Evaluating-Deprecated-API-Usage-in-LLM-based-Code-Comple> — `04b-weaknesses-code.md`
- <https://conf.researchr.org/details/icse-2025/icse-2025-software-engineering-in-practice/26/How-much-does-AI-impact-development-speed-An-enterprise-based-randomized-controlled-> — `03-evidence.md`

### cusy.io

- <https://cusy.io/en/blog/dora-report-2024> — `03-evidence.md`
- <https://cusy.io/en/blog/dora-report-2025.html> — `03-evidence.md`

### every.to

- <https://every.to/chain-of-thought/compound-engineering-how-every-codes-with-agents> — `01-landscape.md`
- <https://every.to/guides/compound-engineering> — `05d-mech-guardrails-harness.md`

### faros.ai

- <https://faros.ai/research> — `03-evidence.md`
- <https://faros.ai/research/ai-acceleration-whiplash> — `03-evidence.md`

### morphllm.com

- <https://www.morphllm.com/beads-agent-memory> — `05c-mech-context-security-docs.md`
- <https://www.morphllm.com/context-rot> — `05c-mech-context-security-docs.md`

### skillselion.com

- <https://skillselion.com/marketplace/EveryInc/compound-engineering-plugin> — `01-landscape.md`
- <https://skillselion.com/skills/github/awesome-copilot/create-architectural-decision-record> — `05b-mech-architecture-testing.md`

### sonarsource.com

- <https://www.sonarsource.com/blog/llm-coding-personality-traits/> — `04b-weaknesses-code.md`
- <https://www.sonarsource.com/company/press-releases/the-coding-personalities-of-leading-llms/> — `04b-weaknesses-code.md`

### tessl.io

- <https://tessl.io/> — `02-sdd-frameworks.md`
- <https://tessl.io/registry/spec-driven-devlopment/spec-as-source/2.2.0> — `02-sdd-frameworks.md`

### tianpan.co

- <https://tianpan.co/blog/2026-04-20-sycophancy-trap-ai-validation> — `04a-weaknesses-behavior.md`
- <https://tianpan.co/forum/t/karpathy-says-vibe-coding-is-passe-at-its-one-year-anniversary-agentic-engineering-is-the-new-paradigm/628> — `01-landscape.md`

### veracode.com

- <https://www.veracode.com/blog/genai-code-security-report/> — `03-evidence.md`
- <https://www.veracode.com/press-release/ai-generated-code-poses-major-security-risks-in-nearly-half-of-all-development-tasks-veracode-research-reveals/> — `03-evidence.md`, `04b-weaknesses-code.md`, `05b-mech-architecture-testing.md`

### 2026.msrconf.org

- <https://2026.msrconf.org/details/msr-2026-mining-challenge/28/> — `04b-weaknesses-code.md`

### aclanthology.org

- <https://aclanthology.org/2026.findings-acl.1759/> — `04a-weaknesses-behavior.md`

### adr.github.io

- <https://adr.github.io/> — `05b-mech-architecture-testing.md`

### agentpatterns.ai

- <https://agentpatterns.ai/human/agent-time-estimates-not-schedules/> — `04a-weaknesses-behavior.md`

### agents.md

- <https://agents.md/> — `01-landscape.md`

### agentskills.io

- <https://agentskills.io> — `05c-mech-context-security-docs.md`

### ai.engineer

- <https://www.ai.engineer/talks/tbDDYKRFjhk-ai-developer-productivity> — `03-evidence.md`

### alistairmavin.com

- <https://alistairmavin.com/ears/> — `05a-mech-requirements-planning.md`

### anvilogic.com

- <https://www.anvilogic.com/threat-reports/cisa-shai-hulud-npm-worm> — `05c-mech-context-security-docs.md`

### aol.com

- <https://www.aol.com/articles/man-coined-vibe-coding-says-203029543.html> — `01-landscape.md`

### assets.anthropic.com

- <https://assets.anthropic.com/m/12f214efcc2f457a/original/Claude-Sonnet-4-5-System-Card.pdf> — `04a-weaknesses-behavior.md`

### atlassian.com

- <https://www.atlassian.com/blog/developer/developer-experience-report-2025> — `03-evidence.md`

### beta.pkg.go.dev

- <https://beta.pkg.go.dev/github.com/akupila/todolint> — `05a-mech-requirements-planning.md`

### bleepingcomputer.com

- <https://bleepingcomputer.com/news/security/amazon-ai-coding-agent-hacked-to-inject-data-wiping-commands> — `05c-mech-context-security-docs.md`

### blog.fsck.com

- <https://blog.fsck.com/2025/10/09/superpowers/> — `02-sdd-frameworks.md`

### blog.google

- <https://blog.google/technology/developers/dora-report-2025/> — `01-landscape.md`

### blog.jetbrains.com

- <https://blog.jetbrains.com/research/2025/10/state-of-developer-ecosystem-2025/> — `03-evidence.md`

### blog.scottlogic.com

- <https://blog.scottlogic.com/2025/11/26/putting-spec-kit-through-its-paces-radical-idea-or-reinvented-waterfall.html> — `02-sdd-frameworks.md`

### brainonllm.com

- <https://www.brainonllm.com/> — `03-evidence.md`

### buttondown.com

- <https://buttondown.com/anatol/archive/are-ai-time-horizons-still-doubling/> — `03-evidence.md`

### claude.com

- <https://claude.com/blog/improving-skill-creator-test-measure-and-refine-agent-skills> — `05d-mech-guardrails-harness.md`

### coderabbit.ai

- <https://coderabbit.ai/blog/state-of-ai-vs-human-code-generation-report> — `03-evidence.md`, `04b-weaknesses-code.md`

### cognition.com

- <https://cognition.com/blog/dont-build-multi-agents> — `05d-mech-guardrails-harness.md`

### colton.dev

- <https://colton.dev/blog/curing-your-ai-10x-engineer-imposter-syndrome/> — `03-evidence.md`

### computer.org

- <https://www.computer.org/volunteering/boards-and-committees/professional-educational-activities/software-engineering-committee/swebok-evolution> — `01-landscape.md`

### conffab.com

- <https://conffab.com/elsewhere/agents-rule-of-two-a-practical-approach-to-ai-agent-security/> — `05c-mech-context-security-docs.md`

### cucumber.io

- <https://cucumber.io/blog/bdd/example-mapping-introduction/> — `05a-mech-requirements-planning.md`

### cursor.com

- <https://cursor.com/docs/context/rules> — `05c-mech-context-security-docs.md`

### data.mendeley.com

- <https://data.mendeley.com/datasets/4x529fdhp3/1> — `04a-weaknesses-behavior.md`

### dev.classmethod.jp

- <https://dev.classmethod.jp/en/articles/pnpm-11-minimum-release-age-dependabot-ci-failure/> — `05c-mech-context-security-docs.md`

### dev.to

- <https://dev.to/bwca/the-hidden-ai-tax-on-tech-debt-4k10> — `05b-mech-architecture-testing.md`

### devclass.com

- <https://devclass.com/2025/02/20/ai-is-eroding-code-quality-states-new-in-depth-report/> — `04b-weaknesses-code.md`

### developers.openai.com

- <https://developers.openai.com/cookbook/articles/codex_exec_plans> — `05a-mech-requirements-planning.md`

### devops.com

- <https://devops.com/study-finds-no-devops-productivity-gains-from-generative-ai/> — `03-evidence.md`

### diataxis.fr

- <https://diataxis.fr/> — `05a-mech-requirements-planning.md`, `05c-mech-context-security-docs.md`

### discourse.llvm.org

- <https://discourse.llvm.org/t/rfc-llvm-ai-tool-policy-human-in-the-loop/89159> — `05c-mech-context-security-docs.md`

### docs.astral.sh

- <https://docs.astral.sh/ruff/rules/missing-todo-link> — `05a-mech-requirements-planning.md`

### docs.bmad-method.org

- <https://docs.bmad-method.org/plan/choose-a-planning-path/> — `02-sdd-frameworks.md`

### docs.fedoraproject.org

- <https://docs.fedoraproject.org/ca/council/policy/ai-contribution-policy/> — `05c-mech-context-security-docs.md`

### docs.github.com

- <https://docs.github.com/en/copilot/tutorials/coding-agent/get-the-best-results> — `01-landscape.md`

### docs.kernel.org

- <https://docs.kernel.org/process/coding-assistants.html> — `05c-mech-context-security-docs.md`

### docs.sonarsource.com

- <https://docs.sonarsource.com/sonarqube-server/2026.3/quality-standards-administration/ai-code-assurance/quality-gates-for-ai-code.md> — `05b-mech-architecture-testing.md`

### dora.dev

- <https://dora.dev/insights/dora-2025-year-in-review/> — `03-evidence.md`

### en.wikipedia.org

- <https://en.wikipedia.org/wiki/Sycophancy_(artificial_intelligence)> — `04a-weaknesses-behavior.md`

### engineering.fb.com

- <https://engineering.fb.com/2025/02/05/security/revolutionizing-software-testing-llm-powered-bug-catchers-meta-ach/> — `05b-mech-architecture-testing.md`

### engineering.nyu.edu

- <https://engineering.nyu.edu/news/its-gpt-3-code-fun-fast-and-full-flaws> — `03-evidence.md`

### evals.alignment.org

- <https://evals.alignment.org/blog/2026-1-29-time-horizon-1-1/> — `03-evidence.md`

### eweek.com

- <https://www.eweek.com/news/replit-ai-coding-assistant-failure/> — `04a-weaknesses-behavior.md`

### fastly.com

- <https://www.fastly.com/blog/senior-developers-ship-more-ai-code> — `03-evidence.md`

### findanexpert.unimelb.edu.au

- <https://findanexpert.unimelb.edu.au/scholarlywork/2345244-an%20empirical%20study%20of%20self-admitted%20technical%20debt%20in%20ai%20agents> — `05a-mech-requirements-planning.md`

### geminicli.com

- <https://geminicli.com/docs/hooks/reference/> — `05d-mech-guardrails-harness.md`

### getdx.com

- <https://getdx.com/blog/2024-dora-report-summary-laura-tacho/> — `03-evidence.md`

### git-scm.com

- <https://git-scm.com/docs/githooks> — `05d-mech-guardrails-harness.md`

### gitclear.com

- <https://www.gitclear.com/ai_assistant_code_quality_2025_research> — `04b-weaknesses-code.md`

### github.github.io

- <https://github.github.io/spec-kit/guides/evolving-specs.html> — `02-sdd-frameworks.md`

### greaterwrong.com

- <https://www.greaterwrong.com/posts/eAbuPXbjakop5rSJx/your-agents-are-not-time-aware> — `04a-weaknesses-behavior.md`

### horadecodar.com.br

- <https://horadecodar.com.br/spec-driven-development-avaliar-adocao/> — `01-landscape.md`

### huggingface.co

- <https://huggingface.co/papers/2511.12884> — `04a-weaknesses-behavior.md`

### i-programmer.info

- <https://www.i-programmer.info/news/105-artificial-intelligence/17871-gitclear-reveals-ais-negative-impact-on-code-quality.html> — `03-evidence.md`

### infoworld.com

- <https://infoworld.com/article/4031673/ai-use-among-software-developers-grows-but-trust-remains-an-issue-stack-overflow-survey.html> — `03-evidence.md`

### invariantlabs.ai

- <https://invariantlabs.ai/blog/mcp-github-vulnerability> — `05c-mech-context-security-docs.md`

### itsfoss.com

- <https://itsfoss.com/news/linux-ai-coding-assistants-policy/> — `05c-mech-context-security-docs.md`

### itwire.com

- <https://itwire.com/business-it-news/data/as-ai-accelerates-software-complexity,-thoughtworks-technology-radar-urges-a-return-to-engineering-fundamentals-to-combat-cognitive-debt> — `01-landscape.md`

### jellyfish.co

- <https://jellyfish.co/blog/ai-impact-data-june-2025/> — `03-evidence.md`

### joelonsoftware.com

- <https://www.joelonsoftware.com/2007/10/26/evidence-based-scheduling/> — `05a-mech-requirements-planning.md`

### kenhuangus.substack.com

- <https://kenhuangus.substack.com/p/the-rule-of-two-vs-reality-why-metas> — `05c-mech-context-security-docs.md`

### labs.cloudsecurityalliance.org

- <https://labs.cloudsecurityalliance.org/research/csa-research-note-slopsquatting-ai-supply-chain-20260419/> — `05c-mech-context-security-docs.md`

### leaddev.com

- <https://leaddev.com/ai/ai-coding-creates-two-kinds-of-debt-youre-only-measuring-one> — `01-landscape.md`, `05c-mech-context-security-docs.md`

### letsdatascience.com

- <https://letsdatascience.com/news/openai-introduces-harness-engineering-for-automated-developm-703d90b6> — `01-landscape.md`

### lilting.ch

- <https://lilting.ch/en/articles/linux-kernel-ai-coding-assistant-policy> — `05c-mech-context-security-docs.md`

### linuxiac.com

- <https://linuxiac.com/qemu-may-relax-its-ban-on-ai-generated-contributions/> — `05c-mech-context-security-docs.md`

### llvm.org

- <https://llvm.org/docs/AIToolPolicy.html> — `05c-mech-context-security-docs.md`

### lwn.net

- <https://lwn.net/Articles/1083275/> — `05c-mech-context-security-docs.md`

### marmelab.com

- <https://marmelab.com/blog/2025/11/12/spec-driven-development-waterfall-strikes-back.html> — `02-sdd-frameworks.md`

### media.mit.edu

- <https://www.media.mit.edu/publications/your-brain-on-chatgpt/> — `03-evidence.md`

### medium.com

- <https://medium.com/@addyosmani/comprehension-debt-the-hidden-cost-of-ai-generated-code-285a25dac57e> — `03-evidence.md`

### microsoft.com

- <https://www.microsoft.com/en-us/research/publication/the-impact-of-generative-ai-on-critical-thinking-self-reported-reductions-in-cognitive-effort-and-confidence-effects-from-a-survey-of-knowledge-workers/> — `03-evidence.md`

### mise.jdx.dev

- <https://mise.jdx.dev/dev-tools/mise-lock.html> — `05d-mech-guardrails-harness.md`

### mitsloan.mit.edu

- <https://mitsloan.mit.edu/ideas-made-to-matter/how-generative-ai-affects-highly-skilled-workers> — `03-evidence.md`

### modern-genai-se.github.io

- <https://modern-genai-se.github.io/f2026/assets/paperPDFs/becker-metr-design-update.pdf> — `03-evidence.md`

### nesbitt.io

- <https://nesbitt.io/2026/03/04/package-managers-need-to-cool-down> — `05c-mech-context-security-docs.md`

### netbsd.org

- <https://www.netbsd.org/developers/commit-guidelines.html> — `05c-mech-context-security-docs.md`

### neurips.cc

- <https://neurips.cc/virtual/2025/122384> — `04a-weaknesses-behavior.md`, `05a-mech-requirements-planning.md`

### newsletter.kentbeck.com

- <https://newsletter.kentbeck.com/p/augmented-coding-beyond-the-vibes> — `01-landscape.md`, `05b-mech-architecture-testing.md`

### okta.com

- <https://www.okta.com/blog/threat-intelligence/the-s1ngularity-attack--when-attackers-prompt-your-ai-agents-to/> — `05c-mech-context-security-docs.md`

### oreilly.com

- <https://www.oreilly.com/radar/comprehension-debt-the-hidden-cost-of-ai-generated-code/> — `03-evidence.md`

### owasp.org

- <https://owasp.org/www-project-application-security-verification-standard/> — `05b-mech-architecture-testing.md`

### ox.security

- <https://www.ox.security/blog/nx-supply-chain-breach-how-s1ngularity-weaponized-ai/> — `05c-mech-context-security-docs.md`

### papers.ssrn.com

- <https://papers.ssrn.com/abstract=4945566> — `03-evidence.md`

### phoronix.com

- <https://www.phoronix.com/news/Fedora-Allows-AI-Contributions> — `05c-mech-context-security-docs.md`

### pistack.xyz

- <https://www.pistack.xyz/posts/2026-06-15-self-hosted-requirements-management-rmtoo-doorstop-strictdoc/> — `05a-mech-requirements-planning.md`

### pith.science

- <https://pith.science/paper/2607.22880> — `04b-weaknesses-code.md`

### pkg.go.dev

- <https://pkg.go.dev/github.com/steveyegge/beads@v0.21.9> — `05c-mech-context-security-docs.md`

### platform.claude.com

- <https://platform.claude.com/docs/en/build-with-claude/prompt-engineering/claude-prompting-best-practices> — `04a-weaknesses-behavior.md`

### ppc.land

- <https://ppc.land/llms-txt-adoption-rises-8-8x-but-97-of-files-get-zero-ai-requests/> — `05c-mech-context-security-docs.md`

### producthunt.com

- <https://www.producthunt.com/p/ctxlint> — `05c-mech-context-security-docs.md`

### qemu.org

- <https://www.qemu.org/docs/master/devel/code-provenance.html> — `05c-mech-context-security-docs.md`

### qodo.ai

- <https://www.qodo.ai/blog/why-ai-self-review-fails-the-technical-case-for-independent-ai-systems> — `05a-mech-requirements-planning.md`

### redirect.github.com

- <https://redirect.github.com/sawyerh/eslint-plugin-todo-plz/blob/main/docs/rules/ticket-ref.md> — `05a-mech-requirements-planning.md`

### redmonk.com

- <https://redmonk.com/rstephens/2025/12/18/dora2025/> — `03-evidence.md`

### render.com

- <https://render.com/blog/half-the-feature-is-the-lesson-how-kieran-klaassen-runs-cora-without-touching-the-code> — `01-landscape.md`

### rfc-editor.org

- <https://www.rfc-editor.org/rfc/rfc8174> — `05a-mech-requirements-planning.md`

### rogerwong.me

- <https://rogerwong.me/2026/03/spec-driven-development> — `02-sdd-frameworks.md`

### seangoedecke.com

- <https://www.seangoedecke.com/impact-of-ai-study/> — `03-evidence.md`

### searchenginejournal.com

- <https://searchenginejournal.com/97-of-llms-txt-files-got-no-requests-ahrefs-data-shows/579478> — `05c-mech-context-security-docs.md`

### sphinx-graph.readthedocs.io

- <https://sphinx-graph.readthedocs.io/en/main/> — `05a-mech-requirements-planning.md`

### strictdoc.readthedocs.io

- <https://strictdoc.readthedocs.io/en/stable/_source_files/tests/integration/features/source_code_traceability/_RELATION_FIELD/test.itest.html> — `05a-mech-requirements-planning.md`

### survey.stackoverflow.co

- <https://survey.stackoverflow.co/2025/ai> — `03-evidence.md`

### t2informatik.de

- <https://t2informatik.de/en/smartpedia/swebok/> — `01-landscape.md`

### techcrunch.com

- <https://techcrunch.com/2025/02/21/report-ai-coding-assistants-arent-a-panacea> — `03-evidence.md`

### techstrong.ai

- <https://techstrong.ai/features/ai-doesnt-fix-whats-already-broken-what-doras-new-model-tells-us-about-getting-ai-right/> — `01-landscape.md`, `03-evidence.md`

### techtarget.com

- <https://www.techtarget.com/searchsoftwarequality/news/366627829/Replit-AI-agent-snafu-shot-across-the-bow-for-vibe-coding> — `04a-weaknesses-behavior.md`

### the-decoder.com

- <https://the-decoder.com/ai-agents-have-no-sense-of-time-and-are-not-aware-of-it/> — `04a-weaknesses-behavior.md`

### themodelwire.com

- <https://themodelwire.com/article/coding-assistants-drastically-overestimate-task-duration-and-self-performance-01M194KN00C43QCWFZZYYE9N2H> — `04a-weaknesses-behavior.md`

### theneuron.ai

- <https://www.theneuron.ai/explainer-articles/everything-to-know-about-claude-opus-4-5> — `04a-weaknesses-behavior.md`

### thenewstack.io

- <https://thenewstack.io/vibe-coding-is-passe-karpathy-has-a-new-name-for-the-future-of-software/> — `01-landscape.md`

### theregister.com

- <https://www.theregister.com/2025/07/24/amazon_q_ai_prompt/> — `05c-mech-context-security-docs.md`

### transluce.org

- <https://transluce.org/investigating-o3-truthfulness> — `04a-weaknesses-behavior.md`

### trychroma.com

- <https://www.trychroma.com/research/context-rot> — `05c-mech-context-security-docs.md`

### upwind.io

- <https://www.upwind.io/?p=14565> — `05c-mech-context-security-docs.md`

### usagebar.com

- <https://usagebar.com/blog/kiro-pricing-and-free-tier> — `02-sdd-frameworks.md`

### venturebeat.com

- <https://venturebeat.com/ai/openai-rolls-back-chatgpts-sycophancy-and-explains-what-went-wrong> — `04a-weaknesses-behavior.md`

### vercel.com

- <https://vercel.com/blog/agents-md-outperforms-skills-in-our-agent-evals> — `05c-mech-context-security-docs.md`

### visualstudiomagazine.com

- <https://visualstudiomagazine.com/Articles/2025/09/03/GitHub-Open-Sources-Kit-for-Spec-Driven-AI-Development.aspx> — `01-landscape.md`

### webcf.waybackmachine.org

- <https://webcf.waybackmachine.org/web/20221113074609/https://arxiv.org/pdf/2211.03622.pdf> — `03-evidence.md`

### wiki.gentoo.org

- <https://wiki.gentoo.org/wiki/Project:Council/AI_policy> — `05c-mech-context-security-docs.md`

### www-cdn.anthropic.com

- <https://www-cdn.anthropic.com/6be99a52cb68eb70eb9572b4cafad13df32ed995.pdf> — `04a-weaknesses-behavior.md`

### yuvalyeret.com

- <https://yuvalyeret.com/blog/spec-driven-development-isnt-waterfall-unless-youre-using-it-that-way/> — `02-sdd-frameworks.md`
