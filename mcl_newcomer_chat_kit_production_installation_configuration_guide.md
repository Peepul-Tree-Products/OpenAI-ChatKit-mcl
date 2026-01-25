# MCL Newcomer ChatKit – Production Installation & Configuration Guide

This guide walks through **installing and configuring the MCL Newcomer ChatKit plugin on the production WordPress site**, based on the working configuration in staging.

> **Important scope notes**
> - This guide **does not cover OpenAI-side setup** (Agent Builder, API keys, workflows, domain allowlists, etc.).
> - The **OpenAI API key will be provided separately** and should *not* be hard-coded in the WordPress admin UI.
> - Everything else required for a correct production deployment **is explicitly specified below**.

---

## 1. Prerequisites

Before starting, confirm:

- You have **Administrator** access to the production WordPress admin
- You have received:
  - The **ChatKit plugin ZIP file**
  - The **Workflow ID** to be used in production (starts with `wf_`)
- You know the **production domain** (for example: `mycanadianlife.com`)

---

## 2. Plugin Installation (Production)

### Step 1: Upload and Install the Plugin

1. Log into **WordPress Admin (Production)**
2. Navigate to:
   ```
   Plugins → Add New → Upload Plugin
   ```
3. Upload the provided **ChatKit plugin ZIP file**
4. Click **Install Now**
5. Once installation completes, click **Activate**

You should now see **MCL Newcomer ChatKit** under:
```
Settings → MCL Newcomer ChatKit
```

---

## 3. Accessing Plugin Settings

Navigate to:
```
WordPress Admin → Settings → MCL Newcomer ChatKit
```

The settings UI is divided into tabs:

- **Basic Settings**
- **Appearance**
- **Messages & Prompts**
- **Advanced**

Each tab must be configured as described below.

---

## 4. Basic Settings (Required)

### 4.1 OpenAI API Key

**Enter OpenAI API KEY FROM RAKESH HERE.**

**Field:** OpenAI API Key

**Value:**

```
sk-proj-xxxxxxxx
```


---

### 4.2 Workflow ID

**Field:** Workflow ID

**Value:**
```
wf_68f275b27a208190a1a7564a769cd780082e17e0eb918b53
```
(Starts with `wf_` – enter this value exactly as aboe)

Purpose:
- Connects the widget to the correct OpenAI Agent Builder workflow

---

### 4.3 File Upload Configuration (Informational)

No action required here if:
- Domain is registered
- Workflow supports file uploads

This section is informational only in production.

---

### 4.4 Display Options

Configure as follows:

- ✅ **Show widget on ALL pages automatically** → **Enabled**

Purpose:
- Ensures the chat widget appears site-wide

#### Exclusions

- **Exclude Specific Pages/Posts:** Leave empty
- **Exclude Page Types:**
  - Homepage → ⬜ Unchecked
  - Archives → ✅ Checked
  - Search Results → ⬜ Unchecked
  - 404 Page → ✅ Checked

Purpose:
- Avoids showing the widget on low-intent or error pages

---

## 5. Appearance Tab

This tab controls branding, UI layout, and user-facing visual behavior.

### 5.1 Button Text

- **Button Text:**
  ```
  New to Canada? Questions?
  ```

Purpose:
- Invites newcomers without sounding technical or salesy

---

### 5.2 Close Button Text

- **Value:** `✕`

Purpose:
- Simple, universally understood close indicator

---

### 5.3 Accent Color

- **Color:** `#ff4500`
- **Intensity Level:** `0 – Subtle`

Purpose:
- Matches MyCanadianLife brand accent
- Subtle intensity avoids overwhelming content

---

### 5.4 Theme

- **Theme:** Light

Purpose:
- Matches overall site design and readability standards

---

### 5.5 Button Size

- **Button Size:** Large

Purpose:
- Improves discoverability and accessibility

---

### 5.6 Button Position

- **Position:** Bottom Right

Purpose:
- Standard placement, minimal conflict with content

---

### 5.7 Border Radius

- **Border Radius:** Round

Purpose:
- Friendly, modern UI feel

---

### 5.8 Shadow Style

- **Shadow Style:** Normal

Purpose:
- Visual separation without distraction

---

### 5.9 UI Density

- **UI Density:** Normal

Purpose:
- Balanced spacing for readability across devices

---

### 5.10 Custom Typography

- **Enable custom font:** ❌ Disabled

Purpose:
- Uses ChatKit defaults for consistency and performance

---

### 5.11 Language Locale

- **Value:** Leave empty

Purpose:
- Defaults to browser language
- Allows future localization without redeploy

---

## 6. Messages & Prompts Tab

### 6.1 Greeting Text

```text
Hey new Canadian! How can I help you today?
```

Purpose:
- Friendly, contextual greeting

---

### 6.2 Input Placeholder

```text
Have a question regarding your tax situation or anything else? Ask here.
```

Purpose:
- Guides users toward meaningful first questions

---

### 6.3 Quick Prompts

Configure **exactly five** prompts as follows.

---

#### Quick Prompt 1

- **Label:** New to Canadian taxes. Help!
- **Text:**
  ```
  I’m new to Canada — how do I file taxes in Canada?
  ```
- **Icon:** ❓ Question

Purpose:
- High-frequency newcomer question

---

#### Quick Prompt 2

- **Label:** RRSP basics for new Canadians
- **Text:**
  ```
  I’m a newcomer to Canada and keep hearing about RRSPs. Can you explain what an RRSP is, how it works for saving on taxes, and what a new Canadian should know before opening one?
  ```
- **Icon:** ℹ️ Info

Purpose:
- Financial literacy onboarding

---

#### Quick Prompt 3

- **Label:** Write about preparing my child for Grade 9 transition as a newcomer family
- **Text:**
  ```
  Write about preparing my child for Grade 9 transition as a newcomer family
  ```
- **Icon:** ✍️ Write

Purpose:
- Long-form guidance use case

---

#### Quick Prompt 4

- **Label:** Explain RESPs and education savings for immigrant families just arriving in Canada
- **Text:**
  ```
  Explain RESPs and education savings for immigrant families just arriving in Canada
  ```
- **Icon:** ℹ️ Info

Purpose:
- Education + financial planning

---

#### Quick Prompt 5

- **Label:** How do I go about getting my drivers license?
- **Text:**
  ```
  As a newcomer to Canada, how do I go about getting my drivers license?
  ```
- **Icon:** ❓ Question

Purpose:
- Common provincial navigation question

---

## 7. Advanced Tab

### 7.1 File Attachments

- **Enable file uploads:** ⬜ Disabled

Purpose:
- Not required for MVP
- Reduces risk and complexity in production

---

### 7.2 Advanced Features

- **Keep conversation history (via cookie):** ✅ Enabled

Purpose:
- Allows follow-up questions to remain contextual

---

### 7.3 UI Regions

- Show header → ⬜ Disabled
- Show conversation history → ⬜ Disabled

Purpose:
- Cleaner UI
- Content-first experience

---

### 7.4 Header Buttons

- **Header Title:** Leave empty
- **Left Button:** None
- **Right Button:** None

Purpose:
- Avoids unnecessary navigation in MVP

---

### 7.5 Disclaimer

**Text:**
```markdown
This is an AI. It can make mistakes. Verify the information before acting on it.
```

- **High contrast:** ⬜ Disabled

Purpose:
- Legal clarity without dominating UI

---

### 7.6 Initial Thread ID

- **Value:** Leave empty

Purpose:
- Always starts a fresh session

---

## 8. Save and Validate

1. Click **Save Settings**
2. Click **Test API Connection**
3. Confirm:
   - Connection succeeds
   - Widget loads on production pages
   - Quick prompts work
   - Follow-up questions maintain context

---

## 9. Post-Deployment Checklist

- [ ] Widget visible on all intended pages
- [ ] Taxation questions surface correct CTA
- [ ] No API keys stored in database
- [ ] Cookies working correctly
- [ ] No console errors on frontend

---

## 10. Notes for Future Changes

- Workflow changes do **not** require WordPress redeploys
- UI copy can be updated safely without touching OpenAI config
- File uploads can be enabled later if required

---

**End of Guide**

