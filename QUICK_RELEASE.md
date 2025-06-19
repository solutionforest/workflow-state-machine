# Quick Release Guide

## For First Release (v1.0.0)

```bash
# 1. Run the release script
./release.sh v1.0.0 "Initial stable release with Laravel 11 support"

# 2. Push to GitHub
git push origin main
git push origin v1.0.0

# 3. Create GitHub Release (manually on GitHub.com)
```

## For Future Releases

### Bug Fix Release (1.0.1)
```bash
./release.sh v1.0.1 "Fix workflow transition bug"
```

### Feature Release (1.1.0)
```bash
./release.sh v1.1.0 "Add new workflow features"
```

### Breaking Change Release (2.0.0)
```bash
./release.sh v2.0.0 "Breaking: Update Laravel 12 support"
```

## Manual Tag Creation (Alternative)

```bash
# Create tag
git tag -a v1.0.0 -m "Release v1.0.0"

# Push tag
git push origin v1.0.0

# List all tags
git tag -l

# Delete a tag (if needed)
git tag -d v1.0.0
git push origin :refs/tags/v1.0.0
```

## Users Can Install Like This

```bash
# Latest version
composer require your-vendor/workflow-state-machine

# Specific major version
composer require your-vendor/workflow-state-machine:^1.0

# Exact version
composer require your-vendor/workflow-state-machine:1.0.0
```
