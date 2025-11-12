---
mode: 'agent'
description: 'Create a new implementation plan file for new features, refactoring existing code or upgrading packages, design, architecture or infrastructure.'
---
# Create Implementation Plan

## Primary Directive

You are well verse in this project purpose, hierarchy and concept, mission & vission and goals. You have read all the related PRD relevant to the creation of this plan. You have understood all the principles and design patterns that will be use in this plan. You are also an experience software architech that knows a lot about Laravel way of project structuring. Your goal is to create one or more new implementation plan/s file for `${input:prd_file}`. Your output must be machine-readable, deterministic, and structured for autonomous execution by other AI systems or humans.

## Execution Context

This prompt is designed for AI-to-AI communication and automated processing. All instructions must be interpreted literally and executed systematically without human interpretation or clarification. Since you will likely hit your response length limit, you must break your response into multiple parts, ensuring each part is complete and coherent. To ensure all parts are correctly sequenced, include a part number and total parts at the beginning of each response (e.g., "Part 1 of 3").

## Core Requirements

- Generate implementation plan/s that are fully executable by AI agents or humans
- Use deterministic language with zero ambiguity
- Structure all content for automated parsing and execution
- Ensure complete self-containment with no external dependencies for understanding

## Plan Structure Requirements

Plans must consist of discrete, atomic goals containing executable tasks. Each goal must be independently processable by AI agents or humans without cross-goal dependencies unless explicitly declared.

## Goal Architecture

- Each goal must have measurable completion criteria
- Each goal will be converted into issues later on so it must be order by execution, whereby goal that must be done first is listed first
- To limit the complexity of each plan file, each plan must contain no more than 5 goals
- If the requiremnets in the PRD or Sub PRD exceed what can be covered in 5 goals, create multiple plan files p to maximum 4 plan files per SUB-PRD
- Tasks within goals must be executable in parallel unless dependencies are specified
- All task descriptions must include specific file paths, function names, and exact implementation details
- No task should require human interpretation or decision-making

## AI-Optimized Implementation Standards

- Use explicit, unambiguous language with zero interpretation required
- Structure all content as machine-parseable formats (tables, lists, structured data)
- Include specific file paths, line numbers, and exact code references where applicable
- Define all variables, constants, and configuration values explicitly
- Provide complete context within each task description
- Use standardized prefixes for all identifiers (REQ-, TASK-, etc.)
- Include validation criteria that can be automatically verified

## Output File Specifications

- Save implementation plan files in `docs/plan/` directory
- Use naming convention: `[PRD number]-[PLAN number]-[purpose].md`
- Purpose prefixes: `implement|upgrade|refactor|remove|addition|data|infrastructure|process|architecture|design|optimize`
- Example: `PRD01-SUB02-PLAN01-upgrade-system-command.md`, `PRD01-SUB03-PLAN01-feature-auth-module.md`
- File must be valid Markdown with proper front matter structure

## Mandatory Template Structure

All implementation plans must strictly adhere to the following template. Each section is required and must be populated with specific, actionable content. AI agents must validate template compliance before execution.

## Template Validation Rules

- All front matter fields must be present and properly formatted
- All section headers must match exactly (case-sensitive)
- All identifier prefixes must follow the specified format
- Tables must include all required columns
- No placeholder text may remain in the final output

## Status

The status of the implementation plan must be clearly defined in the front matter and must reflect the current state of the plan. The status can be one of the following (status_color in brackets): `Completed` (bright green badge), `In progress` (yellow badge), `Planned` (blue badge), `Deprecated` (red badge), or `On Hold` (orange badge). It should also be displayed as a badge in the introduction section.

```md
plan: [Concise Title Describing the  Plan's Goal]
version: [Optional: e.g., 1.0, Date]
date_created: [YYYY-MM-DD]
last_updated: [Optional: YYYY-MM-DD]
owner: [Optional: Team/Individual responsible for this spec]
status: 'Completed'|'In progress'|'Planned'|'Deprecated'|'On Hold'
tags: [required: List of relevant tags or that will be use for issue tagging later on. Tag must use standardize tag accepted by Github.com and must reflect the nature of this plan, e.g., `feature`, `upgrade`, `enhancement`, `architecture`, `migration`, `business-logic`, `inventory`, etc]
---

# Introduction

![Status: <status>](https://img.shields.io/badge/status-<status>-<status_color>)

[A short concise introduction to the plan and the goals it is intended to achieve.]

## 1. Requirements & Constraints

[Explicitly list all requirements & constraints that affect the plan and constrain how it is implemented. Use bullet points or tables for clarity.]

- **REQ-001**: Requirement 1
- **SEC-001**: Security Requirement 1
- **[3 LETTERS]-001**: Other Requirement 1
- **CON-001**: Constraint 1
- **GUD-001**: Guideline 1
- **PAT-001**: Pattern to follow 1

## 2. Implementation Steps

### GOAL-001: [Describe the goal of this goal, e.g., "Implement feature X", "Refactor module Y", etc.]

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-02, SR-01      | Description of how this goal addresses the requirements mentioned in the PRd or Sub PRD | ✅ | 2025-04-25 |
| IR-03             | Description of how this goal addresses the requirements mentioned in the PRd or Sub PRD | ✅ | 2025-04-25 |


| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Description of task 1 | ✅ | 2025-04-25 |
| TASK-002 | Description of task 2 | |  |
| TASK-003 | Description of task 3 | |  |

### GOAL-002: [Describe the goal of this goal, e.g., "Implement feature X", "Refactor module Y", etc.]

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-04/IR-01      | Description of how this goal addresses the requirements mentioned in the PRd or Sub PRD | ✅ | 2025-04-25 | 

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-004 | Description of task 4 | |  |
| TASK-005 | Description of task 5 | |  |
| TASK-006 | Description of task 6 | |  |

## 3. Alternatives

[A bullet point list of any alternative approaches that were considered and why they were not chosen. This helps to provide context and rationale for the chosen approach.]

- **ALT-001**: Alternative approach 1
- **ALT-002**: Alternative approach 2

## 4. Dependencies

[List any dependencies that need to be addressed, such as libraries, frameworks, or other components that the plan relies on.]

- **DEP-001**: Dependency 1
- **DEP-002**: Dependency 2

## 5. Files

[List the files that will be creted or affected by the plan file.]

- **relative_path_to_file_1**: Description of file 1
- **relative_path_to_file_2**: Description of file 2

## 6. Testing

[List the tests that need to be implemented to verify the feature or refactoring task.]

- **TEST-001**: Description of test 1
- **TEST-002**: Description of test 2

## 7. Risks & Assumptions

[List any risks or assumptions related to the implementation of the plan.]

- **RISK-001**: Risk 1
- **ASSUMPTION-001**: Assumption 1

## 8. KIV for future implementations
[List any items that are "Keep In View" for future implementations related to this plan. These are not part of the current plan but should be considered for future enhancements or changes due to their relevance to the current master PRD or Sub PRD.]

- **KIV-001**: KIV item 1
- **KIV-002**: KIV item 2

## 9. Related PRD / Further Reading

[Link to related PRD or Sub-Prd documents or further reading materials.]
[Link to relevant external documentation]
```