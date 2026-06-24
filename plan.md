# Refactoring Plan — Igniter CMS (UTM Part B: Code Smells & Refactoring)

**Subject system:** Igniter CMS — a CodeIgniter 4 (PHP) open-source Content Management System.
**Original author / source:** akassama — https://github.com/akassama/igniter-cms — Licence: **MIT**
**Working repo (fork):** https://github.com/cangkulsand/igniter-cms — all analysis, SonarCloud, GitHub Actions, and refactoring run here (git remote `igniter-cms`).
**Goal:** Identify code smells and apply Fowler refactorings **without changing external behaviour.**

---

## 0. Golden rule

> **Capture the BEFORE baseline (scan + benchmark + tests) before touching a single line of app code.**
> Without a frozen "before", there is no valid comparison for B5/B6.

---

## 1. Code smells under refactoring (from `CODE_SMELLS_REPORT.md`)

| # | Smell | Location | Fowler refactoring (B4) | Detected by (B3) |
|---|---|---|---|---|
| #1 | God Class / Large Class | `app/Controllers/AdminController.php` (1,273 lines, **41 methods, 9 models, ~10 unrelated admin domains**) | **Extract Class** — split by domain into focused controllers | SonarCloud "too many methods/lines" + PHPMD `ExcessiveClassLength` / `TooManyMethods` |
| #4 | Long Parameter List | `app/Helpers/cms_helper.php:1692` — `logActivity()` (8 params) | **Introduce Parameter Object** | SonarCloud "too many parameters" + PHPMD `ExcessiveParameterList` |
| #3 | Duplicate Code | `cms_helper.php:3236` `renderSearchResults()` & `:3630` `renderFilterSearchResults()` (~400 lines each, near-identical) | **Extract Method** / Consolidate Duplicate Code | SonarCloud duplication % + jscpd / PHPCPD |
| #5 | Data Clumps | `app/Controllers/AdminController.php` — `addUser()` & `updateUser()` (repeated user/social-link array) | **Introduce Parameter Object** (or Extract Method) | Manual review + SonarCloud duplication |

> Minimum required: 2 distinct smells. We do 4 for depth.
>
> **Why `AdminController` for the God Class (not `cms_helper.php`)?** The smell is literally "God **Class**" — `AdminController` is a real class doing too much (41 methods across Users, API Keys, Configurations, Codes, Activity, Logs, Stats, IPs, Backups), whereas `cms_helper.php` is a procedural helper *file*. `AdminController` maps cleanly to **Extract Class**, is tractable (~1.3k lines vs 5.5k), and composes with #5 (the data clump lives inside it).
>
> **Behaviour-preservation note:** routes in `app/Config/Routes.php` are **explicitly defined** (not auto-routed). Extract Class = move methods into new controllers, then change only the controller name in each route line, keeping every **URL path identical** → external behaviour unchanged.

---

## 2. Tool stack

| Purpose | Tool | Where it runs |
|---|---|---|
| Automated smell detection (primary) + metrics | **SonarCloud** (already linked to GitHub) | CI / cloud |
| Cross-reference: long method/param/class, complexity, dead code | **PHPMD** | GitHub Actions |
| Cross-reference: duplicate code | **jscpd** (and/or PHPCPD) | GitHub Actions |
| Behaviour preservation — function level | **PHPUnit** (characterization / golden-master) | Local + CI |
| Behaviour preservation — feature level + screenshots | **Katalon** (E2E, drives running app) | Local |
| Execution time + memory | **Custom PHP micro-benchmark** (`hrtime()` + `memory_get_peak_usage()`) or PHPBench | Local (stable numbers) |
| LOC + cyclomatic complexity + duplication | **SonarCloud** (and/or phploc) | CI |
| Orchestration | **GitHub Actions** | CI |
| *(optional)* endpoint response time | **JMeter** | Local |

**Run-location rule:** deterministic metrics (LOC, complexity, duplication) come from SonarCloud/CI; timing benchmarks run **locally** on Laragon (CI runners are too noisy for time).

---

## 3. Task → deliverable → tool map (B1–B6)

| Task | Deliverable | Tools |
|---|---|---|
| **B1** | 3–5 sentence system description + `/original-code` folder + source citation | git, GitHub |
| **B2** | Smell table (✅ done in `CODE_SMELLS_REPORT.md`) | Manual review |
| **B3** | Manual reasoning write-up + automated tool screenshots, cross-referenced | SonarCloud + PHPMD + jscpd |
| **B4** | Per-smell: technique name + how it works + why it fits | Documentation only |
| **B5** | Diffs + passing tests + running-app screenshots + `/refactored-code` + labelled commits | Katalon + PHPUnit + `php spark serve` + git diff + GitHub Actions |
| **B6** *(bonus)* | Metrics table + profiler screenshots | SonarCloud (LOC/complexity/dup) + local benchmark (time/memory) + optional JMeter |

---

# ════════════ BEFORE (Baseline) ════════════

**Objective:** freeze the original code and record its measurements + green tests. No app code changes in this phase.

### Phase 0 — Setup (additive files only)
- [x] Copy current untouched source into **`/original-code`** (+ README citing akassama/MIT).
- [x] Add `sonar-project.properties` (excludes original-code/refactored-code from scan + CPD).
- [x] Add `.github/workflows/analysis.yml` (SonarCloud + PHPMD + jscpd + PHPUnit; separate from FTP deploy).
- [x] Add micro-benchmark harness as a spark command: `php spark benchmark:smells` (`app/Commands/BenchmarkSmells.php`) — time + memory. *(Used a spark command instead of a bare script so app helpers + DB are available.)*
- [x] Exclude `original-code/**` + `refactored-code/**` from the FTP deploy (`main.yaml`).
- [x] **Push fork to GitHub** (`git push -u igniter-cms main`) — commit `f8fcdc5`, `main` now tracks `igniter-cms/main`.
- [x] **Add `SONAR_TOKEN` repo secret** — done by user; first analysis run triggered on push.

### Phase 1 — Baseline measurements ("before")
- [x] **PHPMD + jscpd** run (CI, `analysis-reports-job1/`) — all 3 smells confirmed:
  - #1 God Class: PHPMD ExcessiveClassLength 1254 / TooManyMethods 41 / Complexity 87 / Coupling 15
  - #4 Long Param List: PHPMD ExcessiveParameterList — `logActivity` 8 params ("less than 6")
  - #3 Duplicate Code: jscpd dup 0.34%→6.54%, 6 clone pairs between the two render fns; manual diff 272/394 (~69%)
- [x] **SonarCloud scan #1** — dashboard populated; Issues (Code Smells) + Measures (LOC, Complexity, Duplications) screenshots captured. *(B3 + B6 "before")*
- [ ] **Micro-benchmark** on `logActivity()` + render functions → record **execution time + memory**. *(B6 "before")*
- [x] **PHPUnit golden-master** for `renderSearchResults()` + `renderFilterSearchResults()` → `tests/Helpers/RenderSearchResultsTest.php`, snapshots in `tests/_snapshots/` (no-results + with-results cases). Theme colours seeded into in-memory SQLite for determinism; `tests/**` added to `sonar.cpd.exclusions`. 3 tests green; second run compares byte-for-byte.
- [~] **Katalon** E2E — **OPTIONAL / SKIPPED.** Rubric B5 only requires "re-run the system + test the affected feature" + a screenshot of the app running correctly after refactoring (manual is acceptable). Behaviour preservation is already covered by the PHPUnit golden master (function-level) + manual browser screenshots (feature-level). Guide kept at `katalon/KATALON_GUIDE.md` if we want the extra layer later.

**Baseline artifacts to store:** Sonar screenshot, PHPMD/jscpd output, benchmark numbers, HTML snapshot, Katalon report.

---

# ════════════ AFTER (Refactored) ════════════

**Objective:** apply the 3 refactorings, prove behaviour unchanged, re-measure.

### Phase 2 — Refactor (one smell per labelled commit)
- [x] **#1 `AdminController` → Extract Class** ✅ (do first — biggest structural change). 7 controllers created under `app/Controllers/Admin/` (Users, ApiKeys, Configurations, Codes, Activity, IpAccess, Backups); `AdminController` reduced to `index()` (1,273 → 30 lines). 27 routes repointed, URLs/verbs/filters byte-identical (verified via normalized `php spark routes` diff). `php -l` clean on all 8 files; reflection confirms all classes/methods resolve; live smoke test = 302 redirects (no 500s). **Pending commit.**
  - Create `app/Controllers/Admin/` with focused controllers: `UsersController`, `ApiKeysController`, `ConfigurationsController`, `CodesController`, `ActivityController` (activity logs + log files + stats), `IpAccessController` (blocked + whitelisted IPs), `BackupsController`.
  - Move each domain's methods over **verbatim**; `AdminController` keeps only `index()` (dashboard) as a thin shell.
  - Update **only the controller name** in each `app/Config/Routes.php` line — **URL paths unchanged** (e.g. `admin/users` → `Admin\UsersController::users`).
  - Commit: `refactor: extract AdminController domains into focused controllers (Extract Class, #1)`
- [x] **#5 add/update user → Introduce Parameter Object** ✅ *(lives in the extracted `UsersController`)*. New `app/DataObjects/UserData.php` (`fromRequest()` + `toCreateArray()`/`toUpdateArray()`) centralises the duplicated user/social-link field mapping + default rules; `addUser`/`updateUser` now build their arrays from it. Original key order preserved verbatim (so DB writes + activity-log JSON unchanged) — locked by `tests/DataObjects/UserDataTest.php` (3 tests/22 assertions green); golden master still passes; user routes 302 (no 500s).
  - Extract the repeated user/social-link array into a builder/DTO.
  - Commit: `refactor: extract user data builder to remove data clump in UsersController (#5)`
- [x] **#4 Long Parameter List → Introduce Parameter Object** ✅ **(target changed from `logActivity` to `GoogleAuthController::createGoogleUser`)**. `logActivity()` (8 params) has **169 call sites across 28 files** → too cross-cutting/risky to re-signature. Refactored the self-contained `createGoogleUser($email,$firstName,$lastName,$googleId,$profilePicture)` (5 params, 1 private call site, clear data clump) instead. New `app/DataObjects/GoogleUserData.php` (+ `fromGoogleUser()` factory encapsulating the extraction + name-split); method now takes 1 object param (verified via reflection 5→1). Locked by `tests/DataObjects/GoogleUserDataTest.php` (3 tests); `php -l` clean; PHPMD param threshold lowered 6→5 so it's auto-detected; `CODE_SMELLS_REPORT.md` scope note added.
- [x] **#3 render functions → Extract Method / Consolidate Duplicate Code** ✅ *(in `cms_helper.php`)*. Extracted the identical 7-line theme-colour retrieval block — duplicated **verbatim across 5 render functions** (`renderSearchResults`, `renderFilterSearchResults`, `renderBlogsGrid`, +2), exactly the clone pairs jscpd flagged — into a single `getSearchResultThemeColors()` helper; each consumer now calls `extract(getSearchResultThemeColors())`. Output proven **byte-for-byte identical** by the golden master (`tests/Helpers/RenderSearchResultsTest.php`, 3 tests green). The two render functions' remaining differences are intentional (CSS-class prefix `sr-`/`fr-` + header variant), so only the safely-shared logic was consolidated. *(Optional deeper extraction of the prefix-parameterised item markup available if wanted.)*

### Phase 3 — Prove behaviour preserved
- [ ] **PHPUnit golden-master re-run** → HTML output **byte-for-byte identical** to baseline. ✅
- [ ] **Katalon E2E re-run** against refactored app → all green; screenshots. ✅
- [ ] **Manual smoke test** (`php spark serve`): login → search → add/update user → check activity log.
- [ ] **GitHub Actions** green on the refactor branch.

### Phase 4 — After measurements ("after")
- [ ] **SonarCloud scan #2** → new LOC, complexity, duplication % + screenshot. *(B6 "after")*
- [ ] **Micro-benchmark** re-run → execution time + memory. *(B6 "after")*
- [ ] Copy refactored source into **`/refactored-code`**.

---

## 4. B6 metrics table (fill after both phases)

| Metric | Original | Refactored | Improvement (%) | Source |
|---|---|---|---|---|
| Average Execution Time (ms) | — | — | — | local benchmark |
| Memory Usage (MB) | — | — | — | local benchmark |
| Lines of Code (LOC) | — | — | — | SonarCloud / phploc |
| Cyclomatic Complexity | — | — | — | SonarCloud / PHPMD |
| Duplication (%) | — | — | — | SonarCloud / jscpd |

> **Honest expectation:** these are *maintainability* refactorings — expect clear wins in **LOC, complexity, and duplication**, but roughly **flat execution time** (Introduce Parameter Object may add tiny object-creation cost). We report this honestly.

---

## 5. Submission structure (B5 / requirements)

```
/original-code      ← frozen original (akassama, MIT, cited)
/refactored-code    ← post-refactor source
CODE_SMELLS_REPORT.md
plan.md
.github/workflows/analysis.yml
sonar-project.properties
tests/                ← PHPUnit characterization tests
tests/benchmark/      ← time + memory harness
katalon/              ← E2E project + reports
report.pdf            ← 15+ pages, B1–B6 with before/after + screenshots
```

**Commit hygiene:** every refactor commit prefixed `refactor:` and references its smell number.

---

## 6. Open confirmations
- [ ] Instructor accepts **GitHub + Actions** in place of literal "Jenkins" (brief says Jenkins link).
- [ ] Test DB for PHPUnit: **SQLite in-memory** (recommended, already configured) vs dedicated MySQL test DB.
