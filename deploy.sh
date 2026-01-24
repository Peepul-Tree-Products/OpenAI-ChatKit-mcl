#!/bin/bash

# Deployment script for OpenAI ChatKit WordPress Plugin
# This creates a ZIP file ready for wp-admin upload

set -e

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_NAME="openai-chatkit-wordpress"
TIMESTAMP=$(date +"%Y%m%d-%H%M%S")
ZIP_NAME="${PLUGIN_NAME}-${TIMESTAMP}.zip"
TEMP_DIR=$(mktemp -d)

echo "📦 Packaging plugin for deployment..."

# Copy plugin files to temp directory (excluding .git, node_modules, etc.)
rsync -av \
  --exclude='.git' \
  --exclude='.gitignore' \
  --exclude='.DS_Store' \
  --exclude='node_modules' \
  --exclude='*.zip' \
  --exclude='deploy.sh' \
  --exclude='.cursor' \
  --exclude='*.md' \
  "${PLUGIN_DIR}/" "${TEMP_DIR}/${PLUGIN_NAME}/"

# Create ZIP file
cd "${TEMP_DIR}"
zip -r "${PLUGIN_DIR}/${ZIP_NAME}" "${PLUGIN_NAME}" -q

# Cleanup
rm -rf "${TEMP_DIR}"

echo "✅ Deployment package created: ${PLUGIN_DIR}/${ZIP_NAME}"
echo ""
echo "📤 Next steps:"
echo "   1. Go to https://staging16.mycanadianlife.com/wp-admin/"
echo "   2. Navigate to Plugins → Add New → Upload Plugin"
echo "   3. Choose the file: ${ZIP_NAME}"
echo "   4. Click 'Install Now' and then 'Replace current version'"
echo ""
