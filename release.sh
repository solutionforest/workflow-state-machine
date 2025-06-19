#!/bin/bash

# Laravel Workflow State Machine - Release Script
# Usage: ./release.sh [version] [message]
# Example: ./release.sh v1.0.0 "Initial stable release"

set -e

VERSION=${1:-v1.0.0}
MESSAGE=${2:-"Release $VERSION"}

echo "🚀 Preparing release $VERSION..."

# 1. Run all tests to ensure everything works
echo "📋 Running all quality checks..."
./run-tests.sh

if [ $? -ne 0 ]; then
    echo "❌ Tests failed! Please fix issues before releasing."
    exit 1
fi

# 2. Stage all changes
echo "📦 Staging changes..."
git add .

# 3. Commit if there are changes
if ! git diff --staged --quiet; then
    echo "💾 Committing changes..."
    git commit -m "Prepare for $VERSION release"
else
    echo "ℹ️  No changes to commit"
fi

# 4. Create tag
echo "🏷️  Creating tag $VERSION..."
git tag -a "$VERSION" -m "$MESSAGE"

# 5. Show what will be pushed
echo "📤 Ready to push:"
echo "   - Commits to main branch"
echo "   - Tag: $VERSION"
echo ""
echo "To complete the release, run:"
echo "   git push origin main"
echo "   git push origin $VERSION"
echo ""
echo "Or push everything at once:"
echo "   git push origin main && git push origin --tags"
echo ""
echo "✅ Release $VERSION prepared successfully!"
echo ""
echo "📋 Next steps:"
echo "1. Push to GitHub using the commands above"
echo "2. Go to GitHub and create a release from the tag"
echo "3. Add release notes describing the changes"
echo "4. Publish the release"
