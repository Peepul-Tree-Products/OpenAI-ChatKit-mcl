# ChatKit Style Guide - My Canadian Life

## Overview

This style guide defines the design system for the OpenAI ChatKit WordPress plugin as it appears on My Canadian Life website. All UI components, colors, typography, and interactions should follow these guidelines to ensure consistency with the website's brand identity.

## Brand Colors

### Primary Colors

- **Primary Red**: `#DC143C` (Crimson)
  - Used for: Headers, footers, primary buttons, brand elements
  - WCAG AA compliant for white text on red background

- **Accent Orange**: `#FF4500` (OrangeRed)
  - Used for: Chat toggle button, interactive elements, highlights
  - Default accent color for the chatbot
  - WCAG AA compliant for white text

- **Secondary Red**: `#FF0000` (Pure Red)
  - Alternative primary color option
  - Use when higher contrast is needed

### Neutral Colors

- **Background White**: `#FFFFFF`
  - Used for: Main chat window background, content areas
  - Default background for all surfaces

- **Background Gray Light**: `#F5F5F5`
  - Used for: Subtle backgrounds, disabled states
  - Hover states for interactive elements

- **Background Gray Dark** (Dark Mode): `#1E1E1E` or `#2A2A2A`
  - Used for: Dark mode backgrounds
  - Chat window background in dark mode

### Text Colors

- **Text Primary**: `#000000` or `#1A1A1A`
  - Used for: Primary content, headings
  - Contrast ratio: 21:1 on white (WCAG AAA)

- **Text Secondary**: `#666666`
  - Used for: Secondary content, descriptions
  - Contrast ratio: 7:1 on white (WCAG AA)

- **Text Tertiary**: `#999999`
  - Used for: Placeholders, subtle text
  - Minimum contrast ratio: 4.5:1 on white (WCAG AA)

- **Text on Red/Orange**: `#FFFFFF`
  - Used for: All text on red or orange backgrounds
  - Ensures WCAG AA compliance

- **Text on Dark** (Dark Mode): `#FFFFFF` or `#E5E5E5`
  - Used for: Primary text in dark mode
  - Minimum contrast ratio: 4.5:1 on dark backgrounds

### Border Colors

- **Border Light**: `#E0E0E0`
  - Used for: Subtle borders, dividers
  - Standard border color

- **Border Medium**: `#CCCCCC`
  - Used for: More prominent borders
  - Focus states

- **Border Dark** (Dark Mode): `rgba(255, 255, 255, 0.1)` or `rgba(255, 255, 255, 0.15)`
  - Used for: Borders in dark mode

### Status Colors (if needed)

- **Success**: `#28A745` (Green)
- **Warning**: `#FFC107` (Yellow/Amber)
- **Error**: `#DC3545` (Red)
- **Info**: `#17A2B8` (Blue)

## Typography

### Font Family

**Primary Stack**: `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif`

- Uses system fonts for optimal performance
- Ensures native look and feel across platforms
- Fallback to Arial/sans-serif for older browsers

### Font Sizes

- **Button Text**: `15px` (0.9375rem)
- **Body Text**: `14px` (0.875rem) to `16px` (1rem)
- **Small Text**: `12px` (0.75rem)
- **Headings**: `18px` (1.125rem) to `24px` (1.5rem) depending on context

### Font Weights

- **Regular**: `400` - Body text
- **Medium**: `500` - Emphasized text
- **Bold**: `700` - Buttons, headings, important text

### Line Heights

- **Tight**: `1.2` - Headings, buttons
- **Normal**: `1.4` to `1.5` - Body text
- **Relaxed**: `1.6` - Long-form content

## Component Styles

### Chat Toggle Button

- **Background**: Accent Orange (`#FF4500`) or Primary Red (configurable)
- **Text Color**: White (`#FFFFFF`)
- **Border**: None
- **Border Radius**: `50px` (fully rounded/pill shape)
- **Padding**: `12px 20px`
- **Font Size**: `15px`
- **Font Weight**: `700`
- **Position**: Fixed, bottom-right (16px from edges)
- **Z-index**: `9999`
- **Shadow**: `0 4px 16px rgba(0, 0, 0, 0.15)`

**Hover State**:
- `transform: translateY(-2px)`
- `box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2)`
- `filter: brightness(1.1)`

**Active State**:
- `transform: translateY(0)`

**Focus State**:
- `outline: 2px solid rgba(255, 255, 255, 0.5)`
- `outline-offset: 2px`

### Chat Window

- **Background**: White (`#FFFFFF`)
- **Border**: None
- **Border Radius**: `16px`
- **Shadow**: `0 8px 32px rgba(0, 0, 0, 0.25)`
- **Default Size**: `400px × 600px`
- **Min Size**: `280px × 400px`
- **Max Size**: Maximized (viewport - 32px padding)
- **Position**: Fixed, bottom-right aligned
- **Z-index**: `9998`
- **Transition**: `width 0.2s ease, height 0.2s ease`

**Size Presets**:
- Small: `360px × 500px`
- Medium: `400px × 600px` (default)
- Large: `600px × 800px`
- Maximized: `calc(100vw - 32px) × calc(100vh - 80px)`

### Resize Controls

**When in Header**:
- **Container**: `.chatkit-resize-controls`
- **Display**: `flex`
- **Gap**: `4px`
- **Padding**: `0 4px`
- **Margin**: `margin-left: auto` (pushes to right)
- **Separator**: `1px` vertical line before controls (`rgba(0, 0, 0, 0.1)`)

**When in Overlay**:
- **Background**: `rgba(255, 255, 255, 0.95)` with `backdrop-filter: blur(12px)`
- **Border Radius**: `8px 0 8px 8px` (rounded on 3 sides, sharp on right)
- **Padding**: `4px 6px 4px 4px`
- **Shadow**: `0 4px 16px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.06)`
- **Position**: Fixed, top-right of chat window
- **Z-index**: `10002`

### Control Buttons (Resize Buttons)

- **Size**: `28px × 28px` (header) or `30-32px × 30-32px` (overlay)
- **Border**: None
- **Border Radius**: `5px` to `6px`
- **Background**: Transparent
- **Color**: `rgba(0, 0, 0, 0.65)` (light mode) or `rgba(255, 255, 255, 0.65)` (dark mode)
- **Padding**: `0`
- **Display**: `flex` with centered content

**Hover State**:
- `background-color: rgba(0, 0, 0, 0.08)` (light) or `rgba(255, 255, 255, 0.1)` (dark)
- `color: rgba(0, 0, 0, 0.85)` (light) or `rgba(255, 255, 255, 0.9)` (dark)
- `transform: translateY(-1px)`

**Active State**:
- `background-color: rgba(0, 0, 0, 0.12)` (light) or `rgba(255, 255, 255, 0.15)` (dark)
- `transform: translateY(0)`

**Focus State**:
- `outline: 2px solid rgba(0, 0, 0, 0.3)`
- `outline-offset: 2px`

### Input Fields

- **Background**: White (`#FFFFFF`)
- **Border**: `1px solid #E0E0E0`
- **Border Radius**: `8px` (varies by ChatKit theme)
- **Padding**: `12px 16px`
- **Font Size**: `14px` to `16px`
- **Color**: `#1A1A1A`

**Focus State**:
- Border color: Accent Orange (`#FF4500`) or Primary Red
- `outline: 2px solid rgba(255, 69, 0, 0.2)`
- `outline-offset: 2px`

## Spacing System

### Base Unit: 4px

All spacing values should be multiples of 4px for consistency:

- **2px**: Hairline gaps, borders
- **4px**: Tight spacing (icon padding, small gaps)
- **8px**: Standard spacing (button padding, small margins)
- **12px**: Comfortable spacing (control padding, medium gaps)
- **16px**: Major spacing (element margins, window padding)
- **20px**: Large spacing (section spacing)
- **24px**: Extra large spacing (major sections)

### Component Spacing

- **Chat Toggle Button**: `16px` from bottom and right edges
- **Chat Window**: `16px` from bottom and right (when not maximized)
- **Resize Controls**: `6px` from chat window edge (when in overlay)
- **Button Padding**: `12px 20px` (chat toggle), `4px 6px` (control buttons)
- **Control Gap**: `4px` to `6px` between buttons

## Border Radius

- **Chat Toggle Button**: `50px` (fully rounded/pill)
- **Chat Window**: `16px` (moderately rounded)
- **Overlay Controls**: `8px` to `10px` (slightly rounded)
- **Control Buttons**: `5px` to `6px` (subtle rounding)
- **Input Fields**: `8px` (standard rounding)

## Shadows

### Elevation Levels

- **Level 1** (Subtle): `0 2px 8px rgba(0, 0, 0, 0.1)`
  - Used for: Hover states, subtle elevations

- **Level 2** (Standard): `0 4px 16px rgba(0, 0, 0, 0.15)`
  - Used for: Chat toggle button, standard elevations

- **Level 3** (Prominent): `0 8px 32px rgba(0, 0, 0, 0.25)`
  - Used for: Chat window, major elevations

- **Level 4** (Overlay Controls): `0 4px 16px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.06)`
  - Used for: Overlay control panels with backdrop blur

### Dark Mode Shadows

- Use lighter shadows with more opacity in dark mode
- Example: `0 4px 16px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1)`

## Responsive Design

### Breakpoints

- **Mobile**: `max-width: 768px`
  - Full-screen chat window
  - Hidden resize controls
  - Simplified UI

- **Desktop**: `min-width: 769px`
  - Resizable chat window
  - Resize controls visible
  - Full feature set

### Mobile Considerations

- Touch targets minimum: `44px × 44px`
- Larger tap areas for buttons
- Simplified interactions
- Full-screen chat experience

### Desktop Considerations

- Precise cursor interactions
- Keyboard navigation support
- Window resize controls
- Hover states

## Animation & Transitions

### Transition Timing

- **Standard**: `0.2s ease`
- **Fast**: `0.15s ease`
- **Slow**: `0.3s ease`

### Animation Patterns

- **Slide Up**: Chat window opening
  - From: `opacity: 0, transform: translateY(20px)`
  - To: `opacity: 1, transform: translateY(0)`
  - Duration: `0.3s ease-out`

- **Fade**: Overlay appearances
  - From: `opacity: 0`
  - To: `opacity: 1`
  - Duration: `0.2s ease`

### Reduced Motion

Always respect `prefers-reduced-motion: reduce`:
- Set `transition: none !important`
- Disable animations
- Remove transform effects

```css
@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
    animation: none !important;
  }
}
```

## Dark Mode

### Color Mapping

- **Background**: White → Dark Gray (`#1E1E1E` or `#2A2A2A`)
- **Text**: Black → White (`#FFFFFF` or `#E5E5E5`)
- **Borders**: Light Gray → `rgba(255, 255, 255, 0.1)`
- **Shadows**: Dark → Lighter with more opacity

### Implementation

Use `@media (prefers-color-scheme: dark)` to detect dark mode:

```css
@media (prefers-color-scheme: dark) {
  /* Dark mode styles */
}
```

## Accessibility

### Color Contrast

- **WCAG AA Minimum**: 4.5:1 for normal text, 3:1 for large text
- **WCAG AAA Target**: 7:1 for normal text, 4.5:1 for large text
- **Interactive Elements**: Minimum 3:1 contrast for focus indicators

### Touch Targets

- **Minimum Size**: `44px × 44px`
- **Recommended**: `48px × 48px` for better usability
- **Spacing**: Minimum `8px` between touch targets

### Keyboard Navigation

- All interactive elements must be keyboard accessible
- Visible focus indicators (2px outline minimum)
- Logical tab order
- Escape key to close/exit

### Screen Readers

- Semantic HTML (`<button>`, `<header>`, etc.)
- ARIA labels for all interactive elements
- ARIA roles where appropriate (`role="toolbar"`, `role="button"`)
- Proper heading hierarchy

### Focus Management

- Visible focus outlines (2px minimum)
- Focus trap in modal/chat window
- Return focus to trigger element when closing

## Icon Guidelines

### Icon Size

- **Control Buttons**: `14px × 14px` (SVG)
- **Buttons**: `16px × 16px`
- **Large Icons**: `24px × 24px`

### Icon Style

- Use SVG icons for scalability
- Stroke width: `1.5px` for 14px icons
- Use `currentColor` for fill/stroke to inherit text color
- Consistent visual weight

### Resize Control Icons

- **Small**: Single box outline
- **Medium**: Two overlapping boxes
- **Large**: Three overlapping boxes
- **Maximize**: Two overlapping windows (fullscreen)
- **Restore**: Two overlapping windows with diagonal lines

## Z-Index Scale

- **Base Content**: `1` to `1000`
- **Chat Window**: `9998`
- **Chat Toggle Button**: `9999`
- **Overlays/Modals**: `10000`
- **Resize Controls Overlay**: `10002`

## Code Conventions

### CSS Naming

- Use BEM-like naming: `.chatkit-{component}-{element}--{modifier}`
- Prefix all ChatKit styles with `chatkit-`
- Use kebab-case for all class names

Examples:
- `.chatkit-resize-controls`
- `.chatkit-resize-btn`
- `.chatkit-resize-btn--active`
- `.chatkit-resize-controls-overlay`

### JavaScript

- Use camelCase for variables and functions
- Use PascalCase for classes
- Prefix ChatKit-specific globals with `chatkit` if needed

### File Organization

- **CSS**: `assets/chatkit-embed.css`
- **JavaScript**: `assets/chatkit-embed.js`
- **Main Plugin**: `chatkit-wp.php`
- **Admin**: `admin/settings-page.php`

## Version History

- **v1.0** - Initial style guide based on My Canadian Life website design
  - Established color palette
  - Defined component styles
  - Set spacing and typography standards
