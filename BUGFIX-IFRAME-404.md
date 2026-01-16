# Bug Fix: ChatKit Iframe 404 Error

## Problem
- Chat button displays correctly
- When clicked, blank white dialog appears
- Iframe tries to load: `/deployments/chatkit/index-T5eFlTlK3g3.html`
- Returns 404 - file doesn't exist on server
- ChatKit library is using relative paths instead of CDN URLs

## Root Cause
The OpenAI ChatKit library is constructing iframe URLs as relative paths (`/deployments/chatkit/...`) instead of absolute CDN URLs (`https://cdn.platform.openai.com/deployments/chatkit/...`). This happens when:
1. The ChatKit library detects the wrong base URL
2. The session response doesn't include deployment URL information
3. The ChatKit library version has a bug with relative path handling

## Changes Made

### 1. Enhanced Session Response Handling (`chatkit-wp.php`)
- Added logging of full session response for debugging
- Capture `deployment_url` from session response if available
- Pass deployment URL to frontend in REST API response

### 2. Improved JavaScript Error Handling (`assets/chatkit-embed.js`)
- Enhanced `getClientSecret()` to capture full session response
- Store deployment URL if provided by API
- Added deployment URL to ChatKit options if available
- Added MutationObserver to detect iframe creation with relative URLs
- Improved ChatKit library loading with better error messages
- Added comprehensive console logging for debugging

### 3. Debugging Features Added
- Full session response logged to PHP error log
- Deployment URL detection and logging
- Iframe URL monitoring and error detection
- Better error messages for troubleshooting

## Testing Steps

1. **Check Browser Console**:
   - Open browser DevTools (F12)
   - Look for ChatKit initialization logs
   - Check for any iframe URL warnings or errors

2. **Check PHP Error Log**:
   - Look for "ChatKit Session Response:" log entry
   - Verify session response includes all expected fields
   - Check if deployment_url is present

3. **Verify ChatKit Library Loads**:
   - Check Network tab for `chatkit.js` loading from CDN
   - Verify no CORS or loading errors

4. **Test Chat Widget**:
   - Click chat button
   - Check if iframe loads correctly
   - If still 404, check console for detected relative URL warnings

## Expected Behavior After Fix

- Session response includes deployment URL (if API provides it)
- ChatKit options include deployment URL if available
- Console shows detailed logging of initialization process
- Iframe should load from OpenAI CDN, not relative path
- Better error messages if issues persist

## If Issue Persists

If the iframe still tries to load relative paths:

1. **Check OpenAI API Response**:
   - Look in PHP error log for full session response
   - Verify if `deployment_url` field exists in response
   - Check if workflow is properly configured in OpenAI

2. **Verify Domain Allowlist**:
   - Ensure staging domain is in OpenAI ChatKit Domain Allowlist
   - Domain should be verified (green checkmark)

3. **Check ChatKit Library Version**:
   - The CDN URL loads latest version automatically
   - If issue persists, might be a bug in ChatKit library itself

4. **Contact OpenAI Support**:
   - If deployment URL is missing from session response
   - If ChatKit library has known issues with relative paths

## Files Modified

- `chatkit-wp.php` - Enhanced session response handling
- `assets/chatkit-embed.js` - Improved error handling and deployment URL support

## Next Steps

1. Deploy updated plugin to staging
2. Test chat widget functionality
3. Monitor browser console and PHP error logs
4. If issue persists, check OpenAI API response structure
5. Consider contacting OpenAI support if deployment URL is missing
