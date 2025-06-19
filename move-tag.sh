#!/bin/bash

# Script to move an existing Git tag to current commit
# Usage: ./move-tag.sh [tag-name]
# Example: ./move-tag.sh v1.0.0

set -e

TAG_NAME=${1:-v1.0.0}
CURRENT_COMMIT=$(git rev-parse HEAD)

echo "🏷️  Moving tag $TAG_NAME to current commit..."
echo "Current commit: $CURRENT_COMMIT"

# Check if tag exists
if ! git tag -l | grep -q "^$TAG_NAME$"; then
    echo "❌ Tag $TAG_NAME does not exist!"
    exit 1
fi

# Show current tag position
CURRENT_TAG_COMMIT=$(git rev-list -n 1 $TAG_NAME)
echo "Tag currently points to: $CURRENT_TAG_COMMIT"

if [ "$CURRENT_COMMIT" = "$CURRENT_TAG_COMMIT" ]; then
    echo "ℹ️  Tag $TAG_NAME already points to current commit"
    exit 0
fi

echo ""
echo "⚠️  WARNING: This will move the tag $TAG_NAME"
echo "   From: $CURRENT_TAG_COMMIT"
echo "   To:   $CURRENT_COMMIT"
echo ""

# Confirm action
read -p "Are you sure you want to move this tag? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Operation cancelled"
    exit 1
fi

# Delete the old tag locally
echo "🗑️  Deleting local tag..."
git tag -d $TAG_NAME

# Create new tag at current commit
echo "🏷️  Creating new tag at current commit..."
git tag -a $TAG_NAME -m "Release $TAG_NAME - Updated package details"

echo "✅ Tag $TAG_NAME moved successfully!"
echo ""
echo "📤 To update the remote tag, run:"
echo "   git push origin :refs/tags/$TAG_NAME  # Delete remote tag"
echo "   git push origin $TAG_NAME             # Push new tag"
echo ""
echo "Or use the force push (⚠️ dangerous):"
echo "   git push --force origin $TAG_NAME"
echo ""
echo "⚠️  Note: Moving published tags can affect other users!"
echo "   Make sure to communicate this change to your team."
