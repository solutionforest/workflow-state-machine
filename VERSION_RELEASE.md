# Version Release Guide

This document outlines the process for releasing new versions of the Workflow State Machine library.

## Semantic Versioning

This project follows [Semantic Versioning](https://semver.org/) (SemVer):

- **MAJOR** version (e.g., 2.0.0): Incompatible API changes
- **MINOR** version (e.g., 1.1.0): New functionality, backwards compatible
- **PATCH** version (e.g., 1.0.1): Bug fixes, backwards compatible

## Release Process

### 1. Prepare for Release

```bash
# Make sure all tests pass
./run-tests.sh

# Ensure code is formatted
composer pint

# Run static analysis
composer phpstan
```

### 2. Update Documentation

- Update `README.md` if needed
- Update `CHANGELOG.md` (create if doesn't exist)
- Verify all examples work

### 3. Create a Git Tag

```bash
# For a new major release (breaking changes)
git tag -a v1.0.0 -m "Release version 1.0.0 - Initial stable release"

# For a minor release (new features)
git tag -a v1.1.0 -m "Release version 1.1.0 - Added new features"

# For a patch release (bug fixes)
git tag -a v1.0.1 -m "Release version 1.0.1 - Bug fixes"
```

### 4. Push to GitHub

```bash
# Push the code
git push origin main

# Push the tags
git push origin --tags

# Or push a specific tag
git push origin v1.0.0
```

### 5. Create GitHub Release (Optional but Recommended)

1. Go to your GitHub repository
2. Click "Releases" → "Create a new release"
3. Select your tag
4. Add release notes describing changes
5. Publish the release

## Example Release Workflow

```bash
# 1. Ensure everything is committed and tested
git add .
git commit -m "Prepare for v1.0.0 release"
./run-tests.sh

# 2. Create and push tag
git tag -a v1.0.0 -m "Release version 1.0.0 - Initial stable release with Laravel 11 support"
git push origin main
git push origin v1.0.0

# 3. Verify the tag exists
git tag -l
```

## Installation for Users

Once tagged, users can install specific versions:

```bash
# Install latest version
composer require solution-forest/workflow-state-machine

# Install specific version
composer require solution-forest/workflow-state-machine:^1.0

# Install exact version
composer require solution-forest/workflow-state-machine:1.0.0
```

## Version History Suggestions

- **v1.0.0**: Initial stable release with Laravel 11 support
- **v1.1.0**: Add new features (if any)
- **v1.0.1**: Bug fixes and improvements

## Notes

- Never include a `version` field in `composer.json` - Composer reads it from Git tags
- Always test thoroughly before tagging
- Use meaningful commit messages
- Consider creating a `CHANGELOG.md` for tracking changes
