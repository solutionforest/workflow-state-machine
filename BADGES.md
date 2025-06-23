# Badge Documentation

This document explains the badges used in our README.md and provides templates for future use.

## Current Badges

### GitHub Actions Badge
```markdown
[![Tests](https://github.com/solutionforest/workflow-state-machine/workflows/Tests/badge.svg)](https://github.com/solutionforest/workflow-state-machine/actions)
```

This badge will automatically show:
- ✅ Green when all tests pass
- ❌ Red when tests fail
- 🟡 Yellow when tests are running

### Quality Badge (Static)
```markdown
[![Quality](https://img.shields.io/badge/quality-A+-brightgreen.svg?style=flat)](https://github.com/solutionforest/workflow-state-machine)
```

### PHPStan Badge (Static)
```markdown
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen.svg?style=flat)](https://phpstan.org/)
```

### Larastan Badge (Static)
```markdown
[![Larastan](https://img.shields.io/badge/Larastan-enabled-brightgreen.svg?style=flat)](https://github.com/larastan/larastan)
```

### PHP Version Badge (Static)
```markdown
[![PHP Version](https://img.shields.io/badge/php-8.2%20%7C%208.3%20%7C%208.4-787CB5.svg?style=flat)](https://php.net)
```

### Laravel Version Badge (Static)
```markdown
[![Laravel Version](https://img.shields.io/badge/laravel-%5E11.0-FF2D20.svg?style=flat)](https://laravel.com)
```

## Future Badge Options

### Packagist Badges (When Published)
```markdown
[![Latest Stable Version](https://poser.pugx.org/solutionforest/workflow-state-machine/v/stable)](https://packagist.org/packages/solutionforest/workflow-state-machine)
[![Total Downloads](https://poser.pugx.org/solutionforest/workflow-state-machine/downloads)](https://packagist.org/packages/solutionforest/workflow-state-machine)
[![License](https://poser.pugx.org/solutionforest/workflow-state-machine/license)](https://packagist.org/packages/solutionforest/workflow-state-machine)
```

### Coverage Badge (If Codecov/Coveralls is Set Up)
```markdown
[![Coverage Status](https://coveralls.io/repos/github/solutionforest/workflow-state-machine/badge.svg?branch=main)](https://coveralls.io/github/solutionforest/workflow-state-machine?branch=main)
```

## Badge Guidelines

1. **Consistent Style**: Use `?style=flat` for all shields.io badges
2. **Color Scheme**: 
   - Green (#brightgreen) for passing/good status
   - Red (#red) for failing/bad status  
   - Yellow (#yellow) for warning status
   - Blue (#blue) for informational
3. **Order**: Arrange from most important (CI status) to least important (version info)
4. **Links**: Always link badges to relevant pages (GitHub Actions, Packagist, etc.)

## Notes

- GitHub Actions badge will only work after workflows are pushed to the repository
- Packagist badges require the package to be published on Packagist
- Update static badges when version requirements change
