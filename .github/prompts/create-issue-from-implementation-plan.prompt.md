---
mode: 'agent'
description: 'Create GitHub Issues from implementation plan phases using feature_request.yml or chore_request.yml templates.'
tools: ['search/codebase', 'search', 'github', 'create_issue', 'search_issues', 'update_issue']
---
# Create GitHub Issue from Implementation Plan

Create GitHub Issues for the implementation plan at `${file}`.

## Process

1. Analyze plan file to identify phases
2. Check existing issues using `search_issues`
3. Check existing labels to determine if there are enough labels for new issues
4. Assign labels to issues based on the contents of each phase, you can create new labels if necessary that better reflects the issues if the existing ones are not sufficient
5. Create new issue per phase using `create_issue` or update existing with `update_issue`
6. Test with creating one issue first to see if you have access to the right tools for this job. If successful, proceed to create the remaining issues. and if you fail, stop the process and report the error.
7. Use `feature_request.yml` or `chore_request.yml` templates (fallback to default)

## Requirements

- One issue per implementation phase (Each implementation plan may have one or more phases)
- Clear, structured titles and descriptions that ensure the implementation plan phase is fully understood
- Appropriate labels (feature/chore) based on phase type
- Include only changes required by the plan
- Verify against existing issues before creation

## Issue Content

- Title: Use code like PRD-01-Phase-01-Feature-Description
- Description: Phase details, requirements, and context. Make sure Issue description link backs to the implementation plan file and phase section. To keep the context clear and Issue Description concise, summarize the phase details effectively or make a checklist that link back to the exact lines in the implementation plan file.
- Labels: Appropriate for issue type (feature/chore)