# Session Notes — UTM Part B (Code Smells & Refactoring)

> Handoff document so work can continue in a new session. Last updated: 2026-06-26.
> Companion to `plan.md` (the master plan with BEFORE/AFTER phases). Read both.

---

## 1. Goal & context

UTM assignment **Part B: Design of Code, Code Smells, and Refactoring** (`projectq2.md`).
Identify code smells in an existing codebase and apply Fowler refactorings **without
changing external behaviour**. Deliverables: report (B1–B6) + repo with `/original-code`
and `/refactored-code` and clearly labelled `refactor:` commits.

- **Subject system:** Igniter CMS — CodeIgniter 4 (PHP 8.3) CMS.
- **Original author/source:** akassama — https://github.com/akassama/igniter-cms — MIT.
- **Working fork (push + SonarCloud + Actions):** https://github.com/cangkulsand/igniter-cms (git remote `igniter-cms`).
- **Local path:** `C:\laragon\www\igniter-cms` (Laragon; `composer`/`mysql` only on Laragon Terminal PATH, not the plain shell).
- **Other remotes:** `upstream` = akassama (citation), `origin` = sp-mohdsharhan (NOT used).

---

## 2. The 4 code smells (B2) + chosen refactorings (B4)

| # | Smell | Location | Refactoring | Refactor order |
|---|---|---|---|---|
| #1 | God Class | `app/Controllers/AdminController.php` (1,273 lines, 41 methods, 9 models, ~10 domains) | **Extract Class** → split into `app/Controllers/Admin/` (UsersController, ApiKeysController, ConfigurationsController, CodesController, ActivityController, IpAccessController, BackupsController); update `Routes.php` controller names, keep URLs identical | 1st |
| #5 | Data Clumps | `AdminController` `addUser()` (L62-125) & `updateUser()` (L148-212) — repeated user/social-link array | **Introduce Parameter Object** (SocialLinks / UserProfileData DTO) | 2nd (lives in extracted UsersController) |
| #4 | Long Parameter List | `app/Helpers/cms_helper.php:1692` `logActivity()` (8 params) | **Introduce Parameter Object** (ActivityLogData DTO) | 3rd (independent) |
| #3 | Duplicate Code | `cms_helper.php:3236` `renderSearchResults()` & `:3630` `renderFilterSearchResults()` (~69% identical) | **Extract Method** (shared render helper) | 4th (independent) |

---

## 3. Tooling (decided)

| Purpose | Tool | Notes |
|---|---|---|
| Primary detection + metrics | **SonarCloud** | Linked to GitHub; `SONAR_TOKEN` secret set. `projectKey=cangkulsand_igniter-cms`, `org=cangkulsand` (verify if 404). |
| Cross-ref: long method/param/class | **PHPMD** | `phpmd-ruleset.xml` — lowered `ExcessiveParameterList` minimum to 6 so 8-param logActivity is flagged. |
| Duplicate code | **jscpd** | thresholds `--min-lines 10 --min-tokens 50`. |
| Behaviour preservation (unit) | **PHPUnit** | golden-master for render fns; test DB = **SQLite in-memory** (default `tests` group). |
| Behaviour preservation (E2E) | **Katalon** | drives running app; B5 feature screenshots. |
| Perf: time + memory (B6) | `php spark benchmark:smells` | `app/Commands/BenchmarkSmells.php`; run **locally** (CI too noisy). |
| Perf: LOC/complexity/dup (B6) | **SonarCloud** | deterministic; before vs after. |
| CI | **GitHub Actions** | `.github/workflows/analysis.yml` (separate from FTP deploy). |

---

## 4. B3 detection evidence ALREADY captured

From CI run, artifacts in **`analysis-reports-job1/`** (the correct tuned run; `analysis-reports/` is the stale FIRST run — still committed, should be replaced).

- **#1 God Class** (PHPMD): ExcessiveClassLength 1254, TooManyMethods 41, TooManyPublicMethods 40, ExcessiveClassComplexity 87, CouplingBetweenObjects 15.
- **#4 Long Param List** (PHPMD): `logActivity` 8 parameters, "reduce to less than 6". (SonarCloud S107 too.)
- **#3 Duplicate Code** (jscpd): overall 6.54% (2,735 dup lines / 167 clones). 6 clone pairs between render fns: L3240-3254↔L3642-3656, L3242-3254↔L4059-4071, L3310-3320↔L3724-3734, L3362-3372↔L3776-3786, L3436-3449↔L3850-3863, L3608-3621↔L4036-4050. Manual diff: 272/394 (~69%).
- **#5 Data Clumps** (jscpd): `AdminController` L88-99↔L178-190 (12 lines, 148 tokens) = the addUser/updateUser data array; also L127-145↔L214-232.

---

## 5. Report doc state — `answer/Draft Part B (updated).docx`

> Original `Draft Part B.docx` gets **locked when open in Word** → edits saved to `(updated)`. Backup: `Draft Part B.backup.docx`. Edit via `python-docx` (installed, v1.2.0). ALWAYS back up first; insert by matching anchor paragraph text.

- **B1** — description + source done. ⚠️ **Baseline metrics table BLANK** (Original LOC / Cyclomatic Complexity / Code Smells / Duplicated Lines) — fill from SonarCloud (dup already = 6.54%).
- **B2** — 4-smell table done.
- **B3 Automatic (tools)** — Finding 1 (Large Class: SonarCloud screenshot placeholder + PHPMD log), Finding 2 (Long Param: SonarCloud placeholder), **Finding 3 (Duplicate: jscpd evidence added)**, **Finding 4 (Data Clumps: jscpd evidence added)**. ⚠️ Screenshots still to be captured.
- **B3 Manual** — all 4 findings written (code-review reasoning + counts).
- **B4 / B5 / B6** — NOT started.

---

## 6. Git / CI state

- Commits: `f8fcdc5` (Phase 0 tooling+original-code+benchmark), `b603cb3` (deploy skip on forks + detection tuning + baseline reports).
- `main` tracks `igniter-cms/main`. Push: `git push igniter-cms main`.
- FTP deploy (`main.yaml`) gated `if: github.repository == 'akassama/igniter-cms'` → **skipped on fork** (correct).
- End commit messages with: `Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>`.

---

## 7. Phase status

- **Phase 0 (setup):** ✅ complete.
- **Phase 1 (baseline "before"):** automated detection ✅. **PHPUnit golden-master ✅** — `tests/Helpers/RenderSearchResultsTest.php` (+ `tests/_snapshots/*.html`), seeds theme into in-memory SQLite, 3 tests green, byte-for-byte on re-run; `tests/**` added to `sonar.cpd.exclusions`. **SonarCloud scan #1 screenshots ✅** (Issues + Measures captured). Remaining: run `php spark benchmark:smells` locally for B6 "before" (`.env` + DB confirmed working — app live at `http://igniter-cms.test`); **Katalon baseline — OPTIONAL/SKIPPED** (rubric B5 only needs re-run + after-screenshot, satisfied by golden master + manual screenshots). Guide kept at `katalon/KATALON_GUIDE.md` if wanted later.
- **Phase 2 (refactor):** 🟢 #1 DONE (Extract Class, commit `005f61a` on branch `refactor/extract-admincontroller`) — 7 controllers in `app/Controllers/Admin/`, `AdminController` now `index()` only, 27 routes repointed (URLs identical). 🟢 #5 DONE (Introduce Parameter Object) — `app/DataObjects/UserData.php` removes the add/update user data clump; locked by `tests/DataObjects/UserDataTest.php`. 🟢 #4 DONE (Introduce Parameter Object) — **now refactors the actual detected smell `logActivity` (8 params), which is "Code Smell 2" in the report.** Approach: keep original `logActivity()` UNTOUCHED (the documented smell + B5 "Before"); add `refLogActivity(ActivityLogData)` **sibling** in `cms_helper.php` that unpacks the object and delegates to `logActivity()` (behaviour-preserving by construction, so the ~170 call sites stay safe). New `app/DataObjects/ActivityLogData.php` (8 fields, same defaults as the signature, `auditableId` nullable), locked by `tests/DataObjects/ActivityLogDataTest.php` (field-carrying + defaults). **3 `UsersController` call sites converted** (USER_CREATION, USER_UPDATE, FAILED_USER_CREATION) as proof of concept. Commit `10554f6`. Full suite green (16 tests, 52 assertions). The earlier `GoogleAuthController::createGoogleUser` → `GoogleUserData` work (commit history) remains as a *supporting* second example but is NOT what the report's Code Smell 2 section shows. PHPMD param min was lowered 6→5 earlier (detection-config change, footnote in report). 🟢 #3 DONE (Extract Method / Consolidate Duplicate Code) — `getSearchResultThemeColors()` consolidates the theme-colour block duplicated across 5 render functions; golden master byte-identical green. **All 4 refactorings complete.** Optional: deeper prefix-parameterised item-markup extraction of the two render fns.
- **Phase 3 (prove behaviour):** 🟢 in progress — full PHPUnit suite green on refactored branch (16 tests / 52 assertions incl. golden master + all DTO tests).
- **Phase 4 (after metrics + /refactored-code):** 🟢 in progress — "after" PHPMD + jscpd captured (`analysis-reports-after/`). **#1 God Class eliminated**: `AdminController` gone from PHPMD entirely (was LOC 1254 / 41 methods / 40 public / cx 87 / coupling 15 → none). **#3 Duplicate Code**: jscpd 6.54%→6.37% (2,735→2,681 dup lines, 167→163 clones). Still TODO: SonarCloud "after" LOC/complexity numbers, local `php spark benchmark:smells` "after" run, populate `/refactored-code`.

---

## 8. NEXT STEPS (start here next session)

All 4 refactorings are DONE, committed, and pushed (branch `refactor/extract-admincontroller`; latest `10554f6`). Remaining is reporting + after-metrics:

1. **Report doc** lives in `answer/Draft Part B.docx` (latest master, often open in Word → write generated edits to `answer/Draft Part B (updated).docx`, backup first). B5 "Code Smell 2 → After Refactor" is FILLED in the `(updated)` copy (ActivityLogData + refLogActivity + before/after call site). **Reconcile `(updated)` → master once Word is closed.**
2. **Capture "Before" screenshots/code** still pending: Code Smell 2 "Before Refactor" still has a `Figure X:` placeholder for the original `logActivity()` (8-param) source.
3. Fill **B1 baseline metrics** (LOC + cyclomatic complexity; dup = 6.54%) from SonarCloud "before".
4. Capture remaining SonarCloud + jscpd screenshots into B3.
5. Run `php spark benchmark:smells --iterations 2000` locally for B6 "before" AND "after".
6. Get SonarCloud "after" LOC/complexity numbers (branch `refactor/extract-admincontroller` in the branch dropdown).
7. Populate `/refactored-code` directory; replace stale `analysis-reports/` with `analysis-reports-job1/` (before) and add `analysis-reports-after/` (after).

## 9. Open confirmations
- Instructor accepts **GitHub + Actions** vs literal "Jenkins" repo link (brief says Jenkins).
- Keep B3 with BOTH manual + automated per smell (rubric wording) — currently doing both.
