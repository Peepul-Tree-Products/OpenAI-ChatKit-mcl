# Quick Start Guide

## ✅ Setup Complete!

Your local development environment is ready.

### 🚀 Start Developing

1. **Edit files in Cursor**:
   - `assets/chatkit-embed.css` - UI styling
   - `assets/chatkit-embed.js` - Functionality

2. **Preview changes**:
   - Visit: http://mcldev.local/
   - Hard refresh: `Cmd+Shift+R`
   - Changes appear instantly (symlinked!)

3. **Deploy to staging**:
   ```bash
   ./deploy.sh
   ```
   Then upload `openai-chatkit-wordpress.zip` via wp-admin

### 📍 Important URLs

- **Local Site**: http://mcldev.local/
- **Local Admin**: http://mcldev.local/wp-admin/
- **Staging Site**: https://staging16.mycanadianlife.com/
- **Staging Admin**: https://staging16.mycanadianlife.com/wp-admin/

### 🔧 Next Steps

1. **Activate the plugin** (if not already):
   - Go to http://mcldev.local/wp-admin/plugins.php
   - Find "OpenAI ChatKit for WordPress"
   - Click "Activate"

2. **Configure the plugin**:
   - Go to Settings → ChatKit
   - Enter your OpenAI API Key and Workflow ID
   - Customize appearance and settings
   - Save

3. **Start making UI changes!**

### 💡 Pro Tips

- Use browser dev tools (`Cmd+Option+I`) to inspect and test CSS changes
- Test on mobile viewport (dev tools → device toolbar)
- Clear cache: `Cmd+Shift+R` after each change
- Check console for errors: `Cmd+Option+J`
