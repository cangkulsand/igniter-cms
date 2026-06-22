# UNIVERSITI TEKNOLOGI MALAYSIA

## PART B: DESIGN OF CODE, CODE SMELLS, AND REFACTORING

Students must identify code quality issues in an existing software system and apply appropriate refactoring techniques to improve its structure, readability, and maintainability — without altering its external behaviour.

### Task B1: Select or Develop a Software Codebase
Identify an existing software project to serve as the subject of your analysis. The codebase must satisfy the following criteria:
* Minimum size: 500 lines of code (LOC), excluding blank lines and comments
* Language: Any object-oriented or structured programming language (e.g., Java, Python, C#, PHP, JavaScript)
* Source: Final Year Project (FYP) repositories, past coursework, open-source GitHub/Jenkins projects, or a system developed by your group
* If using an external codebase, cite the source clearly (URL, author, licence)

Provide a brief description of the system (3–5 sentences): its purpose, primary features, and the programming language/framework used.
Include the full source code in your Jenkins repository under a folder named `/original-code`.

### Task B2: Identify and List Code Smells
Examine the codebase and identify a minimum of TWO (2) distinct code smells.
For each code smell, document the following in a structured table:

| No. | Code Smell Type | Location (File & Line No.) | Brief Description |
| :--- | :--- | :--- | :--- |
| 1 | e.g., Long Method | e.g., UserController.java, Line 45–120 | Method processLogin() exceeds 75 lines with mixed responsibilities |
| 2 | e.g., Duplicate Code | e.g., ReportUtils.py & ExportHelper.py | Identical data formatting logic repeated in two separate modules |

Refer to the following recognized code smell categories when making your identification:
* **Long Method** – a method that has grown too large and handles multiple concerns
* **God Class / Large Class** – a single class that knows or does too much
* **Duplicate Code** – identical or near-identical code appearing in multiple locations
* **Dead Code** – unreachable code, unused variables, or obsolete methods
* **Long Parameter List** – a method that takes too many parameters (more than 4–5)
* **Feature Envy** – a method that seems more interested in another class than its own
* **Data Clumps** – groups of data items that always appear together
* **Shotgun Surgery** – a single change requires modifying many different classes

### Task B3: Explain Code Smell Detection Methods
For each code smell identified in Task B2, explain how it was detected. Your explanation must cover two levels:
* **Manual Detection**: Describe the code review process, the warning signs observed (e.g., method length, cyclomatic complexity), and the reasoning behind the classification.
* **Automated Detection**: Identify and apply at least one static analysis tool to validate your findings. Recommended tools include:
  * SonarQube / SonarLint (Java, Python, JS, C#)
  * PMD or Checkstyle (Java)
  * ESLint / JSHint (JavaScript)
  * Pylint / Flake8 / Radon (Python)

Provide screenshots of the tool output highlighting the detected smells. Cross-reference the tool findings with your manual analysis.

### Task B4: Explain Refactoring Methods
For each code smell identified, describe the specific refactoring technique(s) you plan to apply. Your explanation must include:
* The name of the refactoring technique (based on Martin Fowler's refactoring catalogue)
* A clear description of how the technique works in general
* Why this technique is appropriate for the specific smell identified

Reference the following commonly applied refactoring techniques:

| Refactoring Technique | Description & When to Apply |
| :--- | :--- |
| Extract Method | Split a long method into smaller, well-named methods. Apply to Long Method smell. |
| Extract Class | Move related responsibilities from a large class into a new, focused class. Apply to God Class. |
| Move Method | Move a method to the class where it logically belongs. Apply to Feature Envy. |
| Introduce Parameter Object | Replace a group of parameters with a single object. Apply to Long Parameter List. |
| Consolidate Duplicate Code | Extract duplicated code into a shared method or utility class. Apply to Duplicate Code. |
| Remove Dead Code | Delete unused variables, methods, or unreachable branches. Apply to Dead Code smell. |

### Task B5: Apply Refactoring and Re-Run System
Apply the refactoring techniques described in Task B4 to the original codebase. For each refactoring:
* Provide a before/after code comparison (side-by-side table or annotated diff). Highlight the changed lines clearly.
* Confirm the original functionality is preserved by re-running the system and testing the affected feature.
* Provide a screenshot of the system running correctly after refactoring.
* Summarise the improvement: what changed, why it is better, and whether any new issues were introduced.

Store the refactored code in your Jenkins repository under a folder named `/refactored-code`.
Your commit history must clearly label refactoring commits (e.g., "refactor: extract method processLogin into AuthService").

### Task B6 (BONUS): Performance Comparison – Original vs. Refactored Code
(This task is optional. Successful completion earns up to 2 additional bonus marks.)
Use a profiling or benchmarking tool to objectively compare the execution time and/or memory consumption of the original code versus the refactored code.

Present results in the following format:

| Metric | Original Code | Refactored Code | Improvement (%) |
| :--- | :--- | :--- | :--- |
| Average Execution Time (ms) | e.g., 320 ms | e.g., 195 ms | e.g., 39.1% |
| Memory Usage (MB) | — | — | — |
| Lines of Code (LOC) | — | — | — |
| Cyclomatic Complexity | — | — | — |

Recommended tools: Apache JMeter (response time), VisualVM / JProfiler (Java memory), cProfile (Python), Chrome DevTools (web apps).
Include screenshots of the profiler output for both versions.

### Part B: Submission Requirements
* Written Documentation (PDF format) – structured report covering Tasks B1–B5 (and B6 if attempted).
* Minimum 15 pages, proper sections with headings, before/after code comparisons, and screenshots.
* Source Code: Jenkins Repository Link – repository must contain `/original-code` and `/refactored-code` folders with clear commit messages.

### DUE DATES AND SUBMISSION INSTRUCTIONS

| Component | Due Date | Submission Platform | Format |
| :--- | :--- | :--- | :--- |
| Part A | Week 15 (see E-Learning for exact date) | E-Learning (UTM e-Pelajar) | Slides (PDF/PPTX) + Video (MP4) + Jenkins Link |
| Part B | Week 16 (see E-Learning for exact date) | E-Learning (UTM e-Pelajar) | Report (PDF) + Jenkins Link |

**IMPORTANT NOTES:**
* Late submissions without prior approval from the instructor will be penalised 10% per day, up to a maximum of 50% deduction.
* Plagiarism or submission of work that is not your own will result in zero marks for the entire project and may be subject to further disciplinary action under UTM academic integrity policy.
* Each group member is equally responsible for all submitted work.
* Unequal contribution must be declared in writing to the instructor before the submission deadline.
* Ensure the Jenkins repository remains accessible to the instructor throughout the examination period until final grades are released.
