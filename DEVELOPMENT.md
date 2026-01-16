# Development Workflow Guide

## Local Development Setup

Your plugin is now set up for local development using Local by Flywheel.

### Local Site Details
- **Site URL**: http://mcldev.local/
- **Site Name**: mcldev
- **Plugin Location**: Symlinked from your git repository

### Making Changes

1. **Edit files in Cursor**:
   - `assets/chatkit-embed.css` - All styling and visual customization
   - `assets/chatkit-embed.js` - Chat behavior and functionality
   - `admin/settings-page.php` - Admin panel UI

2. **Changes appear immediately** (thanks to symlink):
   - Just hard refresh your browser: `Cmd+Shift+R`
   - No need to copy files manually!

3. **Test locally**:
   - Visit http://mcldev.local/
   - Open the chat widget
   - Verify your changes work

### Deployment to Staging

When you're ready to deploy to staging:

1. **Create deployment package**:
   ```bash
   cd /Users/rakeshkamath/Code/git/OpenAI-ChatKit-for-WordPress
   ./deploy.sh
   ```

2. **Upload via WordPress Admin**:
   - Go to: https://staging16.mycanadianlife.com/wp-admin/
   - Navigate to: **Plugins → Add New → Upload Plugin**
   - Choose: `openai-chatkit-wordpress.zip`
   - Click: **Install Now**
   - When prompted: **Replace current version**
   - Activate if needed

### Quick Reference

**Local Development URL**: http://mcldev.local/  
**Staging URL**: https://staging16.mycanadianlife.com/  
**Plugin Path (Local)**: `~/Local Sites/mcldev/app/public/wp-content/plugins/openai-chatkit-wordpress`  
**Source Code**: `/Users/rakeshkamath/Code/git/OpenAI-ChatKit-for-WordPress`

### Common UI Changes

**Button Styling** (`assets/chatkit-embed.css`):
- Line 4-22: `#chatToggleBtn` - Button appearance
- Line 24-27: `#chatToggleBtn:hover` - Hover effects
- Line 252-262: Button size variations

**Chat Window** (`assets/chatkit-embed.css`):
- Line 51-63: `#myChatkit` - Chat window size and position
- Line 103-150: Mobile responsive styles

**Colors & Theme**:
- Edit accent color in WordPress admin: Settings → ChatKit → Appearance
- Or override in CSS: `#chatToggleBtn { background-color: #YOUR_COLOR !important; }`

### Tips

- Always test on mobile (use browser dev tools: `Cmd+Option+I`)
- Clear browser cache after changes: `Cmd+Shift+R`
- Check browser console for JavaScript errors: `Cmd+Option+J`
- Use browser inspector to preview CSS changes before editing files
