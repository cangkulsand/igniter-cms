# Code Smell Report — Igniter CMS (CodeIgniter 4)

**Project:** Igniter CMS (CodeIgniter 4, PHP)
**Repository:** `D:\utm\sc\project\igniter-cms`
**Commit:** `4d49172` — *CodeIgniter 4 starter - Version 53*
**Date:** 2026-06-22

All findings below were verified against the actual source files and line numbers.

---

## Summary Table

| No. | Code Smell Type | Location (File & Line No.) | Brief Description |
|-----|----------------|----------------------------|------------------|
| 1 | **God Class / Large File** | `app/Helpers/cms_helper.php` (entire file, 5,576 lines) | A single procedural helper file contains **111 functions** spanning DB access, HTML rendering, search, comments, activity logging, file previews, etc. It knows and does too much — every front-end concern is dumped here instead of being split into focused helpers/classes. |
| 2 | **Long Method** | `app/Helpers/cms_helper.php`, `renderAdminBar()` — Lines **5107–5577** (~470 lines) | One function is ~470 lines long and mixes multiple responsibilities: auth checks, building HTML markup, inline CSS/JS, and conditional menu logic. (Siblings `renderFilterSearchResults()` L3630 ≈427 lines and `renderSearchResults()` L3236 ≈394 lines are equally oversized.) |
| 3 | **Duplicate Code** | `app/Helpers/cms_helper.php`, `renderSearchResults()` L3236–3629 **and** `renderFilterSearchResults()` L3630–4056 | Two near-identical ~400-line functions render blog/page search results with the same formatting and markup logic, differing only by a filter/type parameter. The result-rendering logic is copy-pasted between them. |
| 4 | **Long Parameter List** | `app/Helpers/cms_helper.php`, `logActivity(...)` — Line **1692** | `logActivity($activityBy, $activityType, $activityDetails='', $url='', $auditableType='', $auditableId='', $oldValues='', $newValues='')` takes **8 parameters**. Call sites pass long positional argument lists (e.g. `AdminController.php:112`, `:121`), making them error-prone and hard to read. A parameter object/DTO is warranted. |
| 5 | **Duplicate Code / Data Clumps** | `app/Controllers/AdminController.php`, `addUser()` L83–98 **and** `updateUser()` L176–188 | The same data array (`first_name, last_name, … twitter_link, facebook_link, instagram_link, linkedin_link, about_summary, password_change_required`) is rebuilt almost verbatim in both methods. The four social-link fields are a recurring **data clump** that always travel together and should be grouped/extracted. |

---

## Refactoring scope note — Long Parameter List (#4)

`logActivity()` (8 params) is the most visible Long Parameter List, but it is
called from **169 sites across 28 files**, so changing its signature is a
cross-cutting change with wide behavioural risk. The **refactoring** for this
smell is therefore applied to a second, self-contained instance:
`GoogleAuthController::createGoogleUser($email, $firstName, $lastName, $googleId,
$profilePicture)` — **5 parameters** that are all attributes of one Google user
(a textbook data clump) with a **single, private call site**. This makes the
Introduce Parameter Object refactoring fully localized and provably
behaviour-preserving (see `app/DataObjects/GoogleUserData.php` and
`tests/DataObjects/GoogleUserDataTest.php`). The PHPMD `ExcessiveParameterList`
threshold is set to 5 so this instance is flagged automatically. `logActivity()`
is left unchanged and documented as a larger, deferred instance.

---

## Supporting Observations

- **Shotgun Surgery / repeated boilerplate:** The pattern *validate → build array → `createX()`/`update()` → `setFlashdata()` → `logActivity()` → redirect* is copy-pasted across nearly every `add*/update*` pair in `AdminController.php` (`addUser`/`updateUser`, `addApiKey`/`updateApiKey`, `addConfiguration`/`updateConfiguration`, `addCode`, `addBlockedIP`, `addWhitelistedIP`, `generateDbBackup`). A change to the logging or flash-message convention would force edits in ~15 places.

- **Long Method (controller):** `AdminController::generateDbBackup()` — Lines **1076–1182** (~107 lines) mixes DB-config reading, raw SQL dumping via output buffering, filesystem I/O (`mkdir`/`file_put_contents`), model persistence, activity logging, and flash/redirect handling in one method.

- **Other large files** worth refactoring: `app/Helpers/global_functions_helper.php` (1,953 lines), `AdminController.php` (50 KB), `PluginsController.php` (40 KB), `FormRequestsController.php` (36 KB).

---

## Recommendation

If only **two** distinct smells are required, report **#1 (God Class / Large File)** and **#4 (Long Parameter List)** — both are unambiguous and independently verifiable. Findings #2, #3, and #5 add depth across the *Long Method* and *Duplicate Code* categories.
