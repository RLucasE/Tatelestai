---
name: git-commit
description: Create git commits with conventional commit messages following best practices
license: MIT
compatibility: opencode
---

## What I do

- Analyze current changes with `git status` and `git diff`
- Create commit messages following conventional commits format
- Stage and commit changes automatically
- Follow best practices for commit messages

## When to use me

Use this skill when the user asks to:
- Create a commit
- Commit changes
- Save changes to git
- Says "commit", "git commit", or similar

## Conventional Commit Format

Use this format:
```
<type>: <subject>
```

### Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Formatting, missing semicolons, etc.
- `refactor`: Code restructuring
- `test`: Adding tests
- `chore`: Maintenance tasks

### Subject rules:
- Use imperative mood: "add" not "added"
- Don't capitalize first letter
- No period at the end
- Max 50 characters
- Be clear and concise

## Workflow

1. **Check changes**: Run `git status` and `git diff` to see what changed
2. **Analyze**: Understand what type of changes were made
3. **Stage all**: Run `git add .` to stage all changes
4. **Commit**: Create commit with format `<type>: <subject>`
5. **Verify**: Run `git status` to confirm

## Examples

```
feat: add user login form
fix: resolve API timeout issue
docs: update README installation steps
refactor: simplify authentication logic
```

## Important

- Only commit when user explicitly asks
- Use clear, descriptive messages
- Keep messages concise but meaningful
- Don't commit sensitive files like .env
