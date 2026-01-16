# ChatKit Implementation Guide

## Overview

This guide provides practical instructions for implementing new features and maintaining the ChatKit chatbot widget according to the [Style Guide](STYLE_GUIDE.md). Follow these patterns to ensure consistency, accessibility, and maintainability.

## Color Usage Guidelines

### When to Use Primary Red vs Accent Orange

**Primary Red (`#DC143C`)**:
- Headers and footers (if integrated into page)
- Brand-specific elements
- Primary call-to-action buttons (if different from chat)
- Critical error states

**Accent Orange (`#FF4500`)**:
- Chat toggle button (default)
- Chat window controls
- Interactive elements within chat
- Hover states on red elements
- Secondary actions

**Rule of Thumb**: Accent Orange is the default for chat widget elements. Use Primary Red only when specifically matching website header/footer design.

### Color Combinations & Contrast

Always verify contrast ratios:

- **White on Red/Orange**: ✓ 4.5:1+ (WCAG AA)
- **Black on White**: ✓ 21:1 (WCAG AAA)
- **Gray on White**: Check each shade (aim for 4.5:1+)

**Testing Tools**:
- Use browser DevTools to check computed contrast
- Online tools: WebAIM Contrast Checker
- Ensure all text meets WCAG AA (4.5:1) minimum

### Accessibility Considerations

1. **Never rely on color alone** to convey information
2. **Use icons or text labels** in addition to color
3. **Test with color blindness simulators**
4. **Maintain sufficient contrast** in all states (hover, active, disabled)

## Component Implementation

### Adding New Buttons

**Pattern**:
```css
.new-button {
  width: 32px;
  height: 32px;
  padding: 0;
  border: none;
  background: transparent;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(0, 0, 0, 0.65);
  transition: all 0.2s ease;
  user-select: none;
}

.new-button:hover {
  background-color: rgba(0, 0, 0, 0.08);
  color: rgba(0, 0, 0, 0.85);
  transform: translateY(-1px);
}

.new-button:active {
  background-color: rgba(0, 0, 0, 0.12);
  transform: translateY(0);
}

.new-button:focus-visible {
  outline: 2px solid rgba(0, 0, 0, 0.3);
  outline-offset: 2px;
}
```

**JavaScript**:
```javascript
const btn = document.createElement('button');
btn.className = 'chatkit-new-button';
btn.setAttribute('aria-label', 'Button label');
btn.setAttribute('title', 'Button tooltip');
btn.setAttribute('type', 'button');

// Add icon or text
btn.innerHTML = `<span class="chatkit-icon">...</span>`;

// Add event listeners
btn.addEventListener('click', (e) => {
  e.preventDefault();
  e.stopPropagation();
  // Handle click
});
```

### Button Variants

**Primary Button** (Red/Orange background):
- Background: `#FF4500` (Accent Orange)
- Text: `#FFFFFF` (White)
- Border: None
- Padding: `12px 20px`
- Border Radius: `50px` (pill) or `8px` (standard)

**Secondary Button** (Transparent background):
- Background: Transparent
- Text: `rgba(0, 0, 0, 0.65)` or `rgba(255, 255, 255, 0.65)` (dark mode)
- Border: `1px solid rgba(0, 0, 0, 0.1)`
- Padding: `8px 16px`
- Border Radius: `6px`

**Icon Button** (Square, icon only):
- Size: `28px × 28px` (header) or `32px × 32px` (overlay)
- Padding: `0`
- Icon size: `14px × 14px`

### Form Element Styling

**Input Fields**:
```css
.chatkit-input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #E0E0E0;
  border-radius: 8px;
  background: #FFFFFF;
  color: #1A1A1A;
  font-size: 14px;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  transition: border-color 0.2s ease, outline 0.2s ease;
}

.chatkit-input:focus {
  outline: none;
  border-color: #FF4500;
  box-shadow: 0 0 0 2px rgba(255, 69, 0, 0.2);
}
```

**Textareas**: Same pattern as inputs, with `min-height` and `resize: vertical`

**Select/Dropdown**: Match input styling, add custom dropdown arrow

### Control Positioning Guidelines

**Header-Embedded Controls**:
- Prefer injection into ChatKit header when available
- Use `margin-left: auto` to push to right
- Add subtle separator (1px border) before controls
- Match header styling (inherit colors, respect theme)

**Overlay Controls**:
- Position `fixed` relative to chat window
- Use `6px` padding from window edge
- Rounded on 3 sides, sharp on right (aligned to edge)
- Backdrop blur for glassmorphism effect
- High z-index (`10002`) to appear above chat

**Mobile Considerations**:
- Hide resize controls on mobile (`max-width: 768px`)
- Simplify UI for touch interactions
- Larger touch targets (minimum 44px)

## Responsive Design Rules

### Mobile-First Approach

1. **Start with mobile styles** (base styles)
2. **Add desktop enhancements** with media queries
3. **Test at multiple breakpoints** (320px, 375px, 768px, 1024px, 1440px)

**Pattern**:
```css
/* Mobile (base) */
.component {
  width: 100%;
  padding: 12px;
}

/* Desktop */
@media (min-width: 769px) {
  .component {
    width: auto;
    padding: 16px;
  }
}
```

### Breakpoint Usage

**Standard Breakpoints**:
- `max-width: 480px` - Very small mobile
- `max-width: 768px` - Mobile/Tablet
- `min-width: 769px` - Desktop
- `min-width: 1024px` - Large desktop

**Mobile-Specific Styles**:
- Hide resize controls
- Full-screen chat window
- Simplified interactions
- Larger touch targets

**Desktop-Specific Styles**:
- Resize controls visible
- Resizable chat window
- Hover states
- Keyboard navigation

### Touch Target Sizes

- **Minimum**: `44px × 44px` (Apple HIG, Android Material)
- **Recommended**: `48px × 48px` for better usability
- **Spacing**: Minimum `8px` between touch targets
- **Padding**: Ensure clickable area matches visual size

## Animation Guidelines

### Transition Timing Standards

**Standard Transition**: `0.2s ease`
- Use for: Hover states, color changes, opacity
- Example: `transition: all 0.2s ease;`

**Fast Transition**: `0.15s ease`
- Use for: Quick feedback, micro-interactions

**Slow Transition**: `0.3s ease`
- Use for: Major state changes, window animations
- Example: Chat window opening

### Animation Patterns

**Slide Up** (Chat window opening):
```css
@keyframes chatkit-slide-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
```

**Fade In/Out**:
```css
@keyframes chatkit-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}
```

**Scale** (Button press):
```css
.button:active {
  transform: scale(0.98);
}
```

### Reduced Motion Support

**Always include** reduced motion support:

```css
@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
    animation: none !important;
  }
  
  @keyframes chatkit-slide-up {
    from, to {
      opacity: 1;
      transform: translateY(0);
    }
  }
}
```

**When to disable animations**:
- User has `prefers-reduced-motion: reduce` set
- Critical accessibility settings
- Performance optimization needs

## Code Organization

### CSS Structure

**Organization Pattern**:
1. Base styles (button, window)
2. Component styles (controls, overlays)
3. Responsive styles (media queries)
4. Dark mode styles
5. Accessibility styles (reduced motion, focus states)

**Example Structure**:
```css
/* ===== BASE STYLES ===== */
#chatToggleBtn { ... }
#myChatkit { ... }

/* ===== COMPONENT STYLES ===== */
.chatkit-resize-controls { ... }
.chatkit-resize-btn { ... }

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) { ... }

/* ===== DARK MODE ===== */
@media (prefers-color-scheme: dark) { ... }

/* ===== ACCESSIBILITY ===== */
@media (prefers-reduced-motion: reduce) { ... }
```

### CSS Naming Conventions

**BEM-like Pattern**: `.chatkit-{component}-{element}--{modifier}`

- **Component**: `resize-controls`, `toggle-btn`, `chat-window`
- **Element**: `btn`, `icon`, `overlay`
- **Modifier**: `active`, `disabled`, `small`, `large`

**Examples**:
- `.chatkit-resize-controls`
- `.chatkit-resize-btn`
- `.chatkit-resize-btn--active`
- `.chatkit-resize-controls-overlay`

**Rules**:
- Always prefix with `chatkit-`
- Use kebab-case
- Be descriptive but concise
- Group related classes

### JavaScript Class Patterns

**ChatWindowManager Pattern**:
```javascript
class ChatWindowManager {
  constructor(config) {
    this.config = config;
    this.state = {
      // State properties
    };
    this.elements = {
      // DOM element references
    };
    this.observers = [];
    this.init();
  }
  
  init() {
    // Initialization logic
  }
  
  // Public methods
  methodName() {
    // Implementation
  }
  
  // Private methods (prefixed with _ if needed)
  _privateMethod() {
    // Implementation
  }
  
  cleanup() {
    // Cleanup logic
  }
}
```

**Function Organization**:
1. Helper functions (toBool, etc.)
2. Core class definition
3. Global instance and initialization
4. Legacy compatibility functions

### File Organization Standards

**Plugin Structure**:
```
/
├── assets/
│   ├── chatkit-embed.css      # Frontend styles
│   └── chatkit-embed.js        # Frontend JavaScript
├── admin/
│   └── settings-page.php       # Admin settings
├── chatkit-wp.php              # Main plugin file
├── STYLE_GUIDE.md              # Style guide
├── IMPLEMENTATION_GUIDE.md     # This file
└── .cursorrules                # Cursor IDE rules
```

**CSS File Organization**:
1. Button styles
2. Chat window styles
3. Animation keyframes
4. Responsive styles
5. Resize functionality
6. Dark mode
7. Accessibility

**JavaScript File Organization**:
1. Configuration and constants
2. Helper functions
3. Core classes (ChatWindowManager)
4. Initialization functions
5. Event handlers
6. Legacy compatibility

## Adding New Features

### Step-by-Step Process

1. **Define in Style Guide** (if it's a design element)
   - Add to appropriate section
   - Document colors, sizes, spacing

2. **Create CSS Styles**
   - Follow naming conventions
   - Include all states (hover, active, focus)
   - Add responsive styles
   - Add dark mode support
   - Add accessibility support

3. **Implement JavaScript**
   - Follow class/function patterns
   - Add proper event handling
   - Include cleanup logic
   - Add error handling

4. **Test Responsiveness**
   - Test at breakpoints
   - Test touch interactions
   - Test keyboard navigation

5. **Test Accessibility**
   - Verify color contrast
   - Test with screen reader
   - Test keyboard navigation
   - Test reduced motion

6. **Document**
   - Add comments in code
   - Update style guide if needed
   - Update this guide if new patterns

### Example: Adding a New Control Button

**1. Add CSS**:
```css
.chatkit-new-control {
  /* Base styles following pattern */
  width: 32px;
  height: 32px;
  /* ... */
}
```

**2. Create JavaScript**:
```javascript
// In ChatWindowManager or appropriate location
createNewControl() {
  const btn = document.createElement('button');
  btn.className = 'chatkit-new-control';
  // ... setup button
  return btn;
}
```

**3. Add to DOM**:
```javascript
// In appropriate method
const newControl = this.createNewControl();
this.elements.controls.appendChild(newControl);
```

**4. Add Event Handler**:
```javascript
newControl.addEventListener('click', (e) => {
  e.preventDefault();
  e.stopPropagation();
  this.handleNewControl();
});
```

**5. Add Cleanup**:
```javascript
// In cleanup method
if (this.elements.newControl) {
  this.elements.newControl.remove();
}
```

## Accessibility Implementation

### Required ARIA Attributes

**Buttons**:
```javascript
btn.setAttribute('aria-label', 'Descriptive label');
btn.setAttribute('title', 'Tooltip text');
btn.setAttribute('type', 'button');
```

**Toolbars**:
```javascript
controls.setAttribute('role', 'toolbar');
controls.setAttribute('aria-label', 'Toolbar purpose');
```

**Modals/Windows**:
```javascript
chatkit.setAttribute('aria-modal', 'true');
chatkit.setAttribute('role', 'dialog');
chatkit.setAttribute('aria-label', 'Chat window');
```

### Keyboard Navigation

**Required Keys**:
- **Tab**: Navigate between interactive elements
- **Enter/Space**: Activate buttons
- **Escape**: Close/exit modals and windows
- **Arrow keys**: Navigate within components (if applicable)

**Implementation Pattern**:
```javascript
element.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault();
    element.click();
  }
  
  if (e.key === 'Escape') {
    e.preventDefault();
    this.close();
  }
});
```

### Focus Management

**Opening Modal/Window**:
```javascript
// Set focus to first interactive element
window.addEventListener('focus', () => {
  firstInteractiveElement.focus();
});
```

**Closing Modal/Window**:
```javascript
// Return focus to trigger element
closeButton.addEventListener('click', () => {
  const trigger = document.getElementById('trigger');
  this.close();
  trigger.focus();
});
```

### Screen Reader Support

- Use semantic HTML (`<button>`, `<header>`, `<nav>`, etc.)
- Add ARIA labels for all interactive elements
- Provide text alternatives for icons
- Maintain logical reading order

## Performance Considerations

### CSS Optimization

- Use efficient selectors (avoid deep nesting)
- Minimize use of `!important`
- Use CSS variables for repeated values (consider for future)
- Optimize animations (use `transform` and `opacity`)

### JavaScript Optimization

- Debounce resize events
- Use `requestAnimationFrame` for smooth updates
- Cache DOM queries
- Clean up event listeners and observers
- Use `MutationObserver` efficiently

### Image/Icon Optimization

- Use SVG icons (scalable, small file size)
- Optimize any raster images
- Use `currentColor` in SVGs for theming

## Testing Checklist

### Visual Testing

- [ ] Colors match style guide
- [ ] Spacing follows 4px grid
- [ ] Typography matches specifications
- [ ] Shadows and borders correct
- [ ] Dark mode works correctly

### Responsive Testing

- [ ] Mobile (< 768px) styles correct
- [ ] Desktop (> 768px) styles correct
- [ ] Touch targets adequate size
- [ ] No horizontal scrolling
- [ ] Text readable at all sizes

### Accessibility Testing

- [ ] Color contrast meets WCAG AA
- [ ] Keyboard navigation works
- [ ] Screen reader compatible
- [ ] Focus indicators visible
- [ ] Reduced motion respected

### Functional Testing

- [ ] All interactions work correctly
- [ ] No JavaScript errors
- [ ] Proper cleanup on close
- [ ] State persists correctly
- [ ] Edge cases handled

## Common Patterns

### Creating Overlays

```javascript
const overlay = document.createElement('div');
overlay.className = 'chatkit-overlay';
overlay.style.cssText = `
  position: fixed;
  top: ${top}px;
  right: ${right}px;
  z-index: 10002;
  pointer-events: none;
`;
document.body.appendChild(overlay);
```

### Position Updates with requestAnimationFrame

```javascript
let rafId = null;
const updatePosition = () => {
  if (rafId) cancelAnimationFrame(rafId);
  rafId = requestAnimationFrame(() => {
    // Update position
    rafId = null;
  });
};
```

### ResizeObserver Pattern

```javascript
if (typeof ResizeObserver !== 'undefined') {
  const resizeObserver = new ResizeObserver(() => {
    updatePosition();
  });
  resizeObserver.observe(element);
  // Store for cleanup
  this.observers.push({ resizeObserver });
}
```

## Maintenance Guidelines

### Updating Styles

1. Update style guide first (if design change)
2. Update CSS files
3. Test across browsers
4. Verify accessibility
5. Update documentation

### Adding Features

1. Check style guide for existing patterns
2. Follow naming conventions
3. Include all states (hover, active, focus)
4. Add responsive styles
5. Add accessibility support
6. Test thoroughly

### Refactoring

1. Maintain backward compatibility where possible
2. Update all references
3. Test existing functionality
4. Update documentation

## Resources

- [Style Guide](STYLE_GUIDE.md) - Complete design system reference
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [MDN Web Docs](https://developer.mozilla.org/)
- [Can I Use](https://caniuse.com/) - Browser compatibility

## Version History

- **v1.0** - Initial implementation guide
  - Established component patterns
  - Defined code organization
  - Set testing standards
