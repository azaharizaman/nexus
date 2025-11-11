---
mode: 'agent'
description: 'Create GitHub Issues from implementation plan phases using feature_request.yml or chore_request.yml templates.'
---
# Create GitHub Issue from Implementation Plan

Create GitHub Issues for the implementation plan at `${file}`.

## Process

1. Analyze plan file to identify phases
2. Check existing issues using `github/github-mcp-server/search_issues`
3. Check existing labels to determine if there are enough labels for new issues
4. Assign labels to issues based on the contents of each plan file, you can create new labels if necessary that better reflects the issues if the existing ones are not sufficient
5. Create 1 new issue per plan file using `github/github-mcp-server/issue_write` or update existing with `github/github-mcp-server/issue_write`
6. Test with creating one issue first to see if you have access to the right tools for this job.

## Requirements

- One issue per plan file (Each implementation plan may have one or more GOALS and all goals must be concolidated into a single issue)
- Clear, structured titles and descriptions that ensure the implementation plan phase is fully understood
- Appropriate labels based on GOAL description of nature
- Include only changes required by the plan
- Verify against existing issues before creation

## Issue Content

- Title: Use a format like `PRD01-SUB01-PLAN01: Issue Description`. The title must include the Sub-PRD number, the PLAN number. For example: "PRD01-SUB01-PLAN01: Setup Multi-Tenancy Database Schema"
- Description: GOAL details, requirements, and context. Make sure Issue description link backs to the implementation plan file and GOAL section. To keep the context clear and Issue Description concise, summarize the GOAL details effectively or make a checklist that link back to the exact lines in the plan file.
- Labels: Appropriate for issue nature
