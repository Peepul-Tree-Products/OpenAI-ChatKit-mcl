(function() {
  'use strict';

  const config = typeof chatkitConfig !== 'undefined' ? chatkitConfig : {};
  let isOpen = false;
  let retryCount = 0;
  const MAX_RETRIES = 3;

  // Size presets
  const SIZE_PRESETS = {
    small: { width: 360, height: 500 },
    medium: { width: 400, height: 600 },
    large: { width: 600, height: 800 }
  };

  // ChatWindowManager class - centralized state and control management
  class ChatWindowManager {
    constructor(config) {
      this.config = config;
      this.state = {
        isOpen: false,
        size: 'medium', // 'small', 'medium', 'large', 'maximized', 'custom'
        previousSize: null, // Store size before maximize
        width: 400,
        height: 600,
        controlsLocation: null // 'header' or 'overlay'
      };
      this.elements = {
        chatkit: null,
        button: null,
        controls: null,
        overlay: null,
        header: null
      };
      this.observers = [];
      this.init();
    }
    
    init() {
      this.elements.chatkit = document.getElementById('myChatkit');
      this.elements.button = document.getElementById('chatToggleBtn');
      if (!this.elements.chatkit || !this.elements.button) {
        console.warn('ChatKit elements not found');
        return;
      }
      // Store original button text for restore on minimize/close
      this.originalButtonText = this.elements.button.textContent || this.config.buttonText || 'Chat now';
      this.accentColor = this.config.accentColor || '#FF4500';
      this.loadSavedPreferences();
      this.createControls();
    }
    
    loadSavedPreferences() {
      try {
        const saved = localStorage.getItem('chatkit_window_size');
        if (saved) {
          const data = JSON.parse(saved);
          this.state.size = data.size || 'medium';
          this.state.width = data.width || SIZE_PRESETS.medium.width;
          this.state.height = data.height || SIZE_PRESETS.medium.height;
        }
      } catch (e) {
        console.warn('Failed to load size preference:', e);
      }
    }
    
    savePreferences() {
      try {
        localStorage.setItem('chatkit_window_size', JSON.stringify({
          size: this.state.size,
          width: this.state.width,
          height: this.state.height,
          timestamp: Date.now()
        }));
      } catch (e) {
        console.warn('Failed to save size preference:', e);
      }
    }
    
    setSize(size, width, height) {
      if (!this.elements.chatkit) return;
      
      const chatkit = this.elements.chatkit;
      
      // Remove all size classes
      chatkit.classList.remove('chatkit-small', 'chatkit-medium', 'chatkit-large', 'chatkit-maximized');
      
      if (size === 'maximized') {
        chatkit.classList.add('chatkit-maximized');
        this.state.size = 'maximized';
      } else {
        if (size === 'small' || size === 'medium' || size === 'large') {
          chatkit.classList.add(`chatkit-${size}`);
          chatkit.style.width = '';
          chatkit.style.height = '';
          this.state.size = size;
          this.state.width = width;
          this.state.height = height;
        } else {
          // Custom size
          chatkit.style.width = width + 'px';
          chatkit.style.height = height + 'px';
          this.state.size = 'custom';
          this.state.width = width;
          this.state.height = height;
        }
      }
      
      // Ensure window stays within viewport
      this.constrainToViewport();
      
      // Update control position (use requestAnimationFrame to ensure DOM has updated)
      requestAnimationFrame(() => {
        this.updateControlPosition();
      });
      
      // Save preferences
      this.savePreferences();
      
      // Update button states
      this.updateResizeButtons();
    }
    
    constrainToViewport() {
      if (this.state.size === 'maximized' || !this.elements.chatkit) {
        return;
      }
      
      const rect = this.elements.chatkit.getBoundingClientRect();
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;
      
      // Check if window is outside viewport
      if (rect.right > viewportWidth) {
        this.elements.chatkit.style.right = '16px';
        this.elements.chatkit.style.left = '';
      }
      if (rect.bottom > viewportHeight) {
        this.elements.chatkit.style.bottom = '16px';
        this.elements.chatkit.style.top = '';
      }
      if (rect.left < 0) {
        this.elements.chatkit.style.left = '16px';
        this.elements.chatkit.style.right = '';
      }
      if (rect.top < 0) {
        this.elements.chatkit.style.top = '16px';
        this.elements.chatkit.style.bottom = '';
      }
    }
    
    createControls() {
      // Try to inject into header first, fallback to overlay
      this.setupResizeControls(this.elements.chatkit);
    }
    
    setupResizeControls(chatkitElement) {
      // If controls already exist, just update them
      if (this.elements.controls) {
        this.updateResizeButtons();
        return;
      }
      
      // Always try header injection first (preferred approach)
      // Only use overlay as emergency fallback if header truly not available
      this.findHeader(chatkitElement, 10).then(header => {
        if (header && !this.elements.controls) {
          this.injectIntoHeader(header);
        } else if (!this.elements.controls) {
          // Emergency fallback: overlay only if header not found after retries
          this.createOverlayResizeControls(chatkitElement);
        }
      }).catch(() => {
        // Emergency fallback: overlay on error
        if (!this.elements.controls) {
          this.createOverlayResizeControls(chatkitElement);
        }
      });
    }
    
    findHeader(chatkitElement, maxAttempts = 10) {
      return new Promise((resolve) => {
        let attempts = 0;
        
        const tryFind = () => {
          attempts++;
          let header = null;
          
          // Try Shadow DOM first
          if (chatkitElement.shadowRoot) {
            header = chatkitElement.shadowRoot.querySelector('header') ||
                    chatkitElement.shadowRoot.querySelector('[role="banner"]');
          }
          
          // Try regular DOM
          if (!header) {
            header = chatkitElement.querySelector('header') ||
                    chatkitElement.querySelector('[role="banner"]');
          }
          
          // Try finding by class or data attribute
          if (!header) {
            const allElements = chatkitElement.shadowRoot 
              ? Array.from(chatkitElement.shadowRoot.querySelectorAll('*'))
              : Array.from(chatkitElement.querySelectorAll('*'));
            header = allElements.find(el => 
              el.tagName === 'HEADER' || 
              el.getAttribute('role') === 'banner' ||
              el.classList.contains('header') ||
              el.classList.contains('chatkit-header')
            );
          }
          
          if (header) {
            this.elements.header = header;
            resolve(header);
          } else if (attempts < maxAttempts) {
            setTimeout(tryFind, 200);
          } else {
            resolve(null);
          }
        };
        
        // Start trying after a short delay to allow ChatKit to render
        setTimeout(tryFind, 300);
      });
    }
    
    createButtonElements() {
      const sizes = [
        { 
          key: 'small', 
          label: 'Small window', 
          icon: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="10" height="10" rx="1.5"/></svg>' 
        },
        { 
          key: 'large', 
          label: 'Large window', 
          icon: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="1" width="12" height="12" rx="1.5"/><rect x="2.5" y="2.5" width="9" height="9" rx="0.5"/><rect x="4" y="4" width="6" height="6" rx="0.5"/></svg>' 
        },
        { 
          key: 'maximize', 
          label: 'Maximize window', 
          icon: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 5h10v7H2z"/><path d="M5 2h7v7"/></svg>' 
        },
        { 
          key: 'minimize', 
          label: 'Minimize window', 
          icon: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 7h10"/></svg>' 
        }
      ];
      
      const buttons = [];
      sizes.forEach(size => {
        const btn = document.createElement('button');
        btn.className = 'chatkit-resize-btn';
        btn.setAttribute('data-size', size.key);
        btn.setAttribute('aria-label', size.label);
        btn.setAttribute('title', size.label);
        btn.setAttribute('type', 'button');
        btn.innerHTML = `<span class="chatkit-icon">${size.icon}</span>`;
        
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          if (size.key === 'minimize') {
            this.close();
          } else {
            this.handlePresetSize(size.key);
          }
        });
        
        buttons.push(btn);
      });
      
      return buttons;
    }
    
    injectIntoHeader(header) {
      // Check if controls already exist
      const existing = header.querySelector('.chatkit-resize-controls');
      if (existing) {
        this.elements.controls = existing;
        this.state.controlsLocation = 'header';
        this.updateResizeButtons();
        return;
      }
      
      // Create resize controls container
      const controls = document.createElement('div');
      controls.className = 'chatkit-resize-controls';
      controls.setAttribute('role', 'toolbar');
      controls.setAttribute('aria-label', 'Window size controls');
      
      // Create buttons
      const buttons = this.createButtonElements();
      buttons.forEach(btn => controls.appendChild(btn));
      
      // Insert controls into header (prefer right-side placement)
      const rightAction = header.querySelector('[data-right-action]') || 
                         header.querySelector('.right-action') ||
                         header.querySelector('[data-action="right"]') ||
                         header.querySelector('div:last-child');
      
      if (rightAction && rightAction.parentNode === header) {
        header.insertBefore(controls, rightAction);
      } else {
        header.appendChild(controls);
      }
      
      this.elements.controls = controls;
      this.state.controlsLocation = 'header';
      
      // Update button states
      this.updateResizeButtons();
    }
    
    createOverlayResizeControls(chatkitElement) {
      // Check if overlay already exists
      let overlay = document.querySelector('.chatkit-resize-controls-overlay');
      if (overlay) {
        this.elements.overlay = overlay;
        this.elements.controls = overlay.querySelector('.chatkit-resize-controls');
        this.updateControlPosition();
        this.updateResizeButtons();
        return;
      }
      
      // Create overlay container
      overlay = document.createElement('div');
      overlay.className = 'chatkit-resize-controls-overlay';
      
      // Create resize controls container
      const controls = document.createElement('div');
      controls.className = 'chatkit-resize-controls';
      controls.setAttribute('role', 'toolbar');
      controls.setAttribute('aria-label', 'Window size controls');
      
      // Create buttons using shared method
      const buttons = this.createButtonElements();
      buttons.forEach(btn => controls.appendChild(btn));
      
      overlay.appendChild(controls);
      document.body.appendChild(overlay);
      
      this.elements.overlay = overlay;
      this.elements.controls = controls;
      this.state.controlsLocation = 'overlay';
      
      // Setup position updates (only needed for overlay)
      this.setupPositionUpdates();
      
      // Initial positioning
      this.updateControlPosition();
      
      // Update button states
      this.updateResizeButtons();
    }
    
    setupPositionUpdates() {
      // Only set up position updates for overlay controls
      // Header-embedded controls don't need position tracking
      if (this.state.controlsLocation !== 'overlay') {
        return;
      }
      
      const chatkit = this.elements.chatkit;
      if (!chatkit) return;
      
      // Use requestAnimationFrame for smooth updates during transitions
      let rafId = null;
      const updatePosition = () => {
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
          this.updateControlPosition();
          rafId = null;
        });
      };
      
      // Update position when chatkit moves or state changes
      const overlayObserver = new MutationObserver(() => {
        updatePosition();
      });
      overlayObserver.observe(chatkit, { attributes: true, attributeFilter: ['style'] });
      
      // Use ResizeObserver if available (more reliable for size changes)
      let resizeObserver = null;
      if (typeof ResizeObserver !== 'undefined') {
        resizeObserver = new ResizeObserver(() => {
          updatePosition();
        });
        resizeObserver.observe(chatkit);
      }
      
      // Update on window resize (with debouncing)
      let resizeTimeout = null;
      const resizeHandler = () => {
        if (resizeTimeout) clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
          updatePosition();
        }, 16); // ~60fps
      };
      window.addEventListener('resize', resizeHandler);
      
      // Store for cleanup
      this.observers.push({ observer: overlayObserver, resizeObserver: resizeObserver, handler: resizeHandler });
      chatkit._overlayObserver = overlayObserver;
      chatkit._resizeObserver = resizeObserver;
      chatkit._overlayResizeHandler = resizeHandler;
      chatkit._overlayUpdatePosition = updatePosition;
    }
    
    updateControlPosition() {
      // Header-embedded controls: only handle visibility (CSS Grid handles positioning)
      if (this.state.controlsLocation === 'header') {
        if (this.elements.controls && this.elements.chatkit) {
          const isVisible = this.state.isOpen && this.elements.chatkit.style.display !== 'none';
          this.elements.controls.style.display = isVisible ? 'inline-flex' : 'none';
        }
        return;
      }
      
      // Overlay positioning logic (emergency fallback only)
      const overlay = this.elements.overlay;
      const chatkit = this.elements.chatkit;
      
      if (!overlay || !chatkit) return;
      
      // Hide controls when chat is closed
      if (!this.state.isOpen || chatkit.style.display === 'none') {
        overlay.style.display = 'none';
        return;
      }
      
      // Show controls when open
      overlay.style.display = 'block';
      
      const rect = chatkit.getBoundingClientRect();
      const isMaximized = this.state.size === 'maximized';
      const controlPadding = 6;
      
      if (isMaximized) {
        overlay.style.cssText = `
          position: fixed;
          top: ${controlPadding}px;
          right: ${controlPadding}px;
          z-index: 10002;
          pointer-events: none;
        `;
      } else {
        const topOffset = rect.top + controlPadding;
        const rightOffset = window.innerWidth - rect.right + controlPadding;
        overlay.style.cssText = `
          position: fixed;
          top: ${topOffset}px;
          right: ${rightOffset}px;
          z-index: 10002;
          pointer-events: none;
        `;
      }
      
      if (this.elements.controls) {
        this.elements.controls.style.cssText = 'pointer-events: auto;';
      }
    }
    
    handlePresetSize(size) {
      if (!this.elements.chatkit || !this.state.isOpen) return;
      
      if (size === 'maximize') {
        this.toggleMaximize();
      } else {
        const preset = SIZE_PRESETS[size];
        if (preset) {
          // Store previous size if not already stored
          if (this.state.size !== 'maximized' && !this.state.previousSize) {
            const rect = this.elements.chatkit.getBoundingClientRect();
            this.state.previousSize = {
              size: this.state.size,
              width: rect.width,
              height: rect.height
            };
          }
          
          this.setSize(size, preset.width, preset.height);
        }
      }
    }
    
    toggleMaximize() {
      if (!this.elements.chatkit || !this.state.isOpen) return;
      
      if (this.state.size === 'maximized') {
        // Restore to previous size
        if (this.state.previousSize) {
          const prev = this.state.previousSize;
          this.setSize(prev.size, prev.width, prev.height);
          this.state.previousSize = null;
        } else {
          // Default to medium if no previous size
          const preset = SIZE_PRESETS.medium;
          this.setSize('medium', preset.width, preset.height);
        }
      } else {
        // Maximize
        const rect = this.elements.chatkit.getBoundingClientRect();
        this.state.previousSize = {
          size: this.state.size,
          width: rect.width,
          height: rect.height
        };
        this.setSize('maximized', window.innerWidth, window.innerHeight);
      }
    }
    
    updateResizeButtons() {
      if (!this.elements.controls) return;
      
      const buttons = this.elements.controls.querySelectorAll('.chatkit-resize-btn');
      buttons.forEach(btn => {
        const size = btn.getAttribute('data-size');
        const isActive = (size === 'maximize' && this.state.size === 'maximized') ||
                        (size === this.state.size && this.state.size !== 'maximized');
        
        btn.classList.toggle('active', isActive);
        
        // Update maximize/restore button icon
        if (size === 'maximize') {
          const icon = btn.querySelector('.chatkit-icon');
          if (icon) {
            if (this.state.size === 'maximized') {
              // Restore icon (two overlapping windows with diagonal lines)
              icon.innerHTML = '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h8v7H3z"/><path d="M5 2h7v7"/><path d="M5 5l4 4M9 5l-4 4"/></svg>';
              btn.setAttribute('aria-label', 'Restore window size');
            } else {
              // Maximize icon (fullscreen)
              icon.innerHTML = '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 5h10v7H2z"/><path d="M5 2h7v7"/></svg>';
              btn.setAttribute('aria-label', 'Maximize window');
            }
          }
        }
      });
    }
    
    open() {
      this.state.isOpen = true;
      if (this.elements.chatkit) {
        this.elements.chatkit.style.display = 'block';
        this.elements.chatkit.setAttribute('aria-modal', 'true');
      }
      if (this.elements.button) {
        this.elements.button.setAttribute('aria-expanded', 'true');
      }
      this.updateControlPosition();
    }
    
    close() {
      this.state.isOpen = false;
      if (this.elements.chatkit) {
        this.elements.chatkit.style.display = 'none';
        this.elements.chatkit.setAttribute('aria-modal', 'false');
      }
      if (this.elements.button) {
        this.elements.button.setAttribute('aria-expanded', 'false');
        // Restore button to default state (original text, remove open class, restore color)
        this.elements.button.classList.remove('chatkit-open');
        this.elements.button.textContent = this.originalButtonText;
        this.elements.button.style.backgroundColor = this.accentColor;
        this.elements.button.focus();
      }
      document.body.style.overflow = '';
      this.updateControlPosition();
    }
    
    cleanup() {
      // Remove observers
      this.observers.forEach(({ observer, resizeObserver, handler }) => {
        if (observer) observer.disconnect();
        if (resizeObserver) resizeObserver.disconnect();
        if (handler) window.removeEventListener('resize', handler);
      });
      this.observers = [];
      
      // Remove controls from header if embedded
      if (this.state.controlsLocation === 'header' && this.elements.controls) {
        if (this.elements.controls.parentNode) {
          this.elements.controls.remove();
        }
        this.elements.header = null;
      }
      
      // Remove overlay if exists
      if (this.elements.overlay && this.elements.overlay.parentNode) {
        this.elements.overlay.remove();
      }
      
      // Reset state
      this.state.controlsLocation = null;
      this.elements.controls = null;
      this.elements.overlay = null;
    }
  }

  // Global instance
  let chatWindowManager = null;
  

  // Helper to convert WordPress boolean strings to actual booleans
  function toBool(value) {
    if (typeof value === 'boolean') return value;
    if (typeof value === 'string') return value === '1' || value.toLowerCase() === 'true';
    if (typeof value === 'number') return value === 1;
    return !!value;
  }

  function loadChatkitScript() {
    return new Promise((resolve, reject) => {
      if (customElements.get('openai-chatkit')) {
        console.log('✅ ChatKit custom element already registered');
        resolve();
        return;
      }

      const script = document.createElement('script');
      // Use latest ChatKit library from CDN
      script.src = 'https://cdn.platform.openai.com/deployments/chatkit/chatkit.js';
      script.defer = true;
      
      script.onload = () => {
        console.log('✅ ChatKit library loaded from CDN');
        // Wait a bit for custom element to register
        setTimeout(() => {
          if (customElements.get('openai-chatkit')) {
            console.log('✅ ChatKit custom element registered successfully');
            resolve();
          } else {
            console.warn('⚠️ ChatKit custom element not found after load, waiting...');
            customElements.whenDefined('openai-chatkit').then(() => {
              console.log('✅ ChatKit custom element defined');
              resolve();
            }).catch(reject);
          }
        }, 100);
      };
      
      script.onerror = (error) => {
        console.error('❌ Failed to load ChatKit CDN script:', error);
        reject(new Error('Failed to load ChatKit CDN - check network connection and CDN availability'));
      };
      
      document.head.appendChild(script);
      console.log('📥 Loading ChatKit library from CDN...');
    });
  }

  // Store deployment URL if available from session
  let deploymentUrl = null;

  async function getClientSecret() {
    try {
      if (!config.restUrl) {
        throw new Error('Missing configuration');
      }

      const headers = {
        'Content-Type': 'application/json'
      };

      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 10000);

      const response = await fetch(config.restUrl, {
        method: 'POST',
        headers: headers,
        signal: controller.signal,
        credentials: 'same-origin'
      });

      clearTimeout(timeoutId);

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        
        console.error('ChatKit Session Error:', {
          status: response.status,
          statusText: response.statusText,
          error: errorData
        });
        
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }

      const data = await response.json();
      
      if (!data.client_secret) {
        throw new Error('Invalid response: missing client_secret');
      }

      // Store deployment URL if provided
      if (data.deployment_url) {
        deploymentUrl = data.deployment_url;
        console.log('✅ ChatKit: Deployment URL received:', deploymentUrl);
      } else {
        console.warn('⚠️ ChatKit: No deployment URL in session response');
        console.log('📋 Full session response:', data);
      }
      
      return data.client_secret;

    } catch (error) {
      console.error('Fetch Session Error:', error);

      const errorMessage = config.i18n?.unableToStart || '⚠️ Unable to start chat. Please try again later.';
      
      const el = document.getElementById('myChatkit');
      if (el && el.parentNode) {
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = 'padding: 20px; text-align: center; color: #721c24; background: #f8d7da; border-radius: 8px; margin: 20px;';
        errorDiv.setAttribute('role', 'alert');
        errorDiv.innerHTML = `<p style="margin: 0; font-size: 14px;">${errorMessage}</p>`;
        el.parentNode.insertBefore(errorDiv, el);
      }

      if (typeof gtag !== 'undefined') {
        gtag('event', 'exception', {
          description: 'ChatKit session error: ' + error.message,
          fatal: false
        });
      }

      return null;
    }
  }

  // Legacy functions for backward compatibility (delegate to ChatWindowManager)
  function loadSizePreference() {
    if (chatWindowManager) {
      return {
        size: chatWindowManager.state.size,
        width: chatWindowManager.state.width,
        height: chatWindowManager.state.height
      };
    }
    return {
      size: 'medium',
      width: SIZE_PRESETS.medium.width,
      height: SIZE_PRESETS.medium.height
    };
  }
  
  function saveSizePreference(size, width, height) {
    if (chatWindowManager) {
      chatWindowManager.setSize(size, width, height);
    }
  }
  
  function applySize(chatkit, size, width, height) {
    if (chatWindowManager) {
      chatWindowManager.setSize(size, width, height);
    }
  }
  
  function constrainToViewport(chatkit) {
    if (chatWindowManager) {
      chatWindowManager.constrainToViewport();
    }
  }
  
  // Handle window resize (debounced to prevent infinite loops)
  let resizeTimeout = null;
  window.addEventListener('resize', () => {
    if (resizeTimeout) clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      const chatkit = document.getElementById('myChatkit');
      if (chatkit && isOpen) {
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
          // On mobile, hide controls
          if (chatWindowManager && chatWindowManager.elements.overlay) {
            chatWindowManager.elements.overlay.style.display = 'none';
          }
          
          // Reset to mobile layout
          if (chatWindowManager && chatWindowManager.state.size === 'maximized') {
            chatkit.classList.remove('chatkit-maximized');
            chatWindowManager.state.size = 'medium';
          }
          chatkit.style.width = '';
          chatkit.style.height = '';
        } else {
          // On desktop, show controls
          if (chatWindowManager) {
            chatWindowManager.updateControlPosition();
          }
          
          constrainToViewport(chatkit);
        }
      }
    }, 100);
  });

  function setupToggle() {
    const button = document.getElementById('chatToggleBtn');
    const chatkit = document.getElementById('myChatkit');

    if (!button || !chatkit) {
      console.warn('ChatKit toggle elements not found');
      return;
    }

    // Initialize ChatWindowManager
    chatWindowManager = new ChatWindowManager(config);

    const originalText = button.textContent || config.buttonText || 'Chat now';
    const closeText = config.closeText || '✕';
    const accentColor = config.accentColor || '#FF4500';

    button.addEventListener('click', () => {
      isOpen = !isOpen;
      
      if (isOpen) {
        chatWindowManager.open();
        button.classList.add('chatkit-open');
        button.textContent = closeText;
        button.style.backgroundColor = accentColor;
        chatkit.style.animation = 'chatkit-slide-up 0.3s ease-out';
        
        // Ensure chatkit has proper positioning
        const computedPosition = window.getComputedStyle(chatkit).position;
        if (computedPosition === 'static') {
          chatkit.style.position = 'relative';
        }
        if (!chatkit.style.zIndex || parseInt(chatkit.style.zIndex) < 10000) {
          chatkit.style.zIndex = '9998';
        }
        
        // On mobile, always use full screen
        if (window.innerWidth <= 768) {
          document.body.style.overflow = 'hidden';
          // Force mobile layout
          chatkit.classList.remove('chatkit-small', 'chatkit-medium', 'chatkit-large', 'chatkit-maximized');
          chatkit.style.width = '';
          chatkit.style.height = '';
        } else {
          // Restore saved size preference on desktop
          const saved = chatWindowManager.state;
          // Don't restore maximized on mobile-sized screens
          if (saved.size === 'maximized' && window.innerWidth > 768) {
            chatWindowManager.setSize(saved.size, saved.width, saved.height);
          } else if (saved.size !== 'maximized') {
            chatWindowManager.setSize(saved.size, saved.width, saved.height);
          } else {
            // Default to medium if saved was maximized but screen is small
            const preset = SIZE_PRESETS.medium;
            chatWindowManager.setSize('medium', preset.width, preset.height);
          }
        }
        
        setTimeout(() => chatkit.focus(), 100);
      } else {
        chatWindowManager.close();
        button.classList.remove('chatkit-open');
        button.textContent = originalText;
        button.style.backgroundColor = accentColor;
        button.focus();
        document.body.style.overflow = '';
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && isOpen) {
        button.click();
      }
    });

    document.addEventListener('click', (e) => {
      if (isOpen && 
          !chatkit.contains(e.target) && 
          !button.contains(e.target)) {
        button.click();
      }
    });
  }

  function buildPrompts() {
    const prompts = [];

    // Support for new array format
    if (config.prompts && Array.isArray(config.prompts) && config.prompts.length > 0) {
      config.prompts.forEach(prompt => {
        if (prompt && prompt.label && prompt.text) {
          prompts.push({
            icon: prompt.icon || 'circle-question',
            label: prompt.label,
            prompt: prompt.text
          });
        }
      });
    } 
    // Fallback to old format
    else {
      if (config.defaultPrompt1 && config.defaultPrompt1Text) {
        prompts.push({
          icon: 'circle-question',
          label: config.defaultPrompt1,
          prompt: config.defaultPrompt1Text
        });
      }

      if (config.defaultPrompt2 && config.defaultPrompt2Text) {
        prompts.push({
          icon: 'circle-question',
          label: config.defaultPrompt2,
          prompt: config.defaultPrompt2Text
        });
      }

      if (config.defaultPrompt3 && config.defaultPrompt3Text) {
        prompts.push({
          icon: 'circle-question',
          label: config.defaultPrompt3,
          prompt: config.defaultPrompt3Text
        });
      }
    }

    // Fallback default
    if (prompts.length === 0) {
      prompts.push({
        icon: 'circle-question',
        label: 'How can I assist you?',
        prompt: 'Hi! How can I assist you today?'
      });
    }

    return prompts;
  }

  function showUserError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.style.cssText = 'position: fixed; bottom: 20px; right: 20px; padding: 15px 20px; background: #f8d7da; color: #721c24; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); z-index: 9999; max-width: 300px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;';
    errorDiv.setAttribute('role', 'alert');
    errorDiv.innerHTML = `<p style="margin: 0; font-size: 14px;">${message}</p>`;
    document.body.appendChild(errorDiv);

    setTimeout(() => {
      if (errorDiv.parentNode) {
        errorDiv.style.opacity = '0';
        errorDiv.style.transition = 'opacity 0.3s ease';
        setTimeout(() => errorDiv.remove(), 300);
      }
    }, 5000);
  }
  
  // Update resize button active states (delegate to ChatWindowManager)
  function updateResizeButtons() {
    if (chatWindowManager) {
      chatWindowManager.updateResizeButtons();
    }
  }
  
  // Setup resize control buttons (delegate to ChatWindowManager)
  function setupResizeControls(chatkitElement) {
    if (chatWindowManager) {
      chatWindowManager.setupResizeControls(chatkitElement);
    }
  }
  
  // Legacy setupResizeControls function (kept for compatibility)
  function setupResizeControlsLegacy(chatkitElement) {
    // Always add controls on desktop, even if header is disabled (use overlay)
    if (window.innerWidth <= 768) {
      return;
    }
    
    // If header is disabled, use overlay approach
    if (!toBool(config.showHeader)) {
      createOverlayResizeControls(chatkitElement);
      return;
    }
    
    // Check if controls already exist
    if (document.querySelector('.chatkit-resize-controls')) {
      updateResizeButtons();
      return;
    }
    
    // Try multiple approaches to find header
    let header = null;
    let attempts = 0;
    const maxAttempts = 10;
    
    const tryFindHeader = () => {
      attempts++;
      
      // Try shadow DOM first
      if (chatkitElement.shadowRoot) {
        header = chatkitElement.shadowRoot.querySelector('header') ||
                chatkitElement.shadowRoot.querySelector('[role="banner"]');
      }
      
      // Try regular DOM
      if (!header) {
        header = chatkitElement.querySelector('header') ||
                chatkitElement.querySelector('[role="banner"]');
      }
      
      // Try finding by class or data attribute
      if (!header) {
        const allElements = chatkitElement.shadowRoot 
          ? Array.from(chatkitElement.shadowRoot.querySelectorAll('*'))
          : Array.from(chatkitElement.querySelectorAll('*'));
        header = allElements.find(el => 
          el.tagName === 'HEADER' || 
          el.getAttribute('role') === 'banner' ||
          el.classList.contains('header') ||
          el.classList.contains('chatkit-header')
        );
      }
      
      if (header) {
        injectResizeControls(header);
      } else if (attempts < maxAttempts) {
        setTimeout(tryFindHeader, 200);
      } else {
        // Fallback: create overlay controls if header not found
        createOverlayResizeControls(chatkitElement);
      }
    };
    
    // Start trying after a short delay
    setTimeout(tryFindHeader, 300);
  }
  
  // Inject controls into header
  function injectResizeControls(header) {
    // Check if controls already exist
    if (header.querySelector('.chatkit-resize-controls')) {
      updateResizeButtons();
      return;
    }
    
    // Create resize controls container
    const controls = document.createElement('div');
    controls.className = 'chatkit-resize-controls';
    controls.setAttribute('role', 'toolbar');
    controls.setAttribute('aria-label', 'Window size controls');
    
    // Create size buttons with SVG icons
    const sizes = [
      { 
        key: 'small', 
        label: 'Small window', 
        icon: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="10" height="10" rx="1"/></svg>' 
      },
      { 
        key: 'medium', 
        label: 'Medium window', 
        icon: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1.5" y="1.5" width="11" height="11" rx="1"/><rect x="3.5" y="3.5" width="7" height="7" rx="0.5"/></svg>' 
      },
      { 
        key: 'large', 
        label: 'Large window', 
        icon: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="1" width="12" height="12" rx="1"/><rect x="2.5" y="2.5" width="9" height="9" rx="0.5"/><rect x="4" y="4" width="6" height="6" rx="0.5"/></svg>' 
      },
      { 
        key: 'maximize', 
        label: 'Maximize window', 
        icon: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 5h10v7H2z"/><path d="M5 2h7v7"/></svg>' 
      }
    ];
    
    sizes.forEach(size => {
      const btn = document.createElement('button');
      btn.className = 'chatkit-resize-btn';
      btn.setAttribute('data-size', size.key);
      btn.setAttribute('aria-label', size.label);
      btn.setAttribute('title', size.label);
      btn.setAttribute('type', 'button');
      btn.innerHTML = `<span class="chatkit-icon">${size.icon}</span>`;
      
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        handlePresetSize(size.key);
      });
      
      controls.appendChild(btn);
    });
    
    // Insert controls into header
    // Try to find right action area or append to end
    const rightAction = header.querySelector('[data-right-action]') || 
                       header.querySelector('.right-action') ||
                       header.querySelector('div:last-child');
    
    if (rightAction && rightAction.parentNode === header) {
      header.insertBefore(controls, rightAction);
    } else {
      header.appendChild(controls);
    }
    
    // Update button states
    updateResizeButtons();
  }
  
  // Fallback: Create overlay controls if header not accessible
  function createOverlayResizeControls(chatkitElement) {
    // Check if overlay already exists
    const existing = document.querySelector('.chatkit-resize-controls-overlay');
    if (existing) {
      updateResizeButtons();
      return;
    }
    
    // Create overlay container - append to body with fixed positioning
    const overlay = document.createElement('div');
    overlay.className = 'chatkit-resize-controls-overlay';
    
    // Create resize controls container
    const controls = document.createElement('div');
    controls.className = 'chatkit-resize-controls';
    controls.setAttribute('role', 'toolbar');
    controls.setAttribute('aria-label', 'Window size controls');
    
    // Create size buttons with SVG icons
    const sizes = [
      { 
        key: 'small', 
        label: 'Small window', 
        icon: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="10" height="10" rx="1"/></svg>' 
      },
      { 
        key: 'medium', 
        label: 'Medium window', 
        icon: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1.5" y="1.5" width="11" height="11" rx="1"/><rect x="3.5" y="3.5" width="7" height="7" rx="0.5"/></svg>' 
      },
      { 
        key: 'large', 
        label: 'Large window', 
        icon: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="1" width="12" height="12" rx="1"/><rect x="2.5" y="2.5" width="9" height="9" rx="0.5"/><rect x="4" y="4" width="6" height="6" rx="0.5"/></svg>' 
      },
      { 
        key: 'maximize', 
        label: 'Maximize window', 
        icon: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 5h10v7H2z"/><path d="M5 2h7v7"/></svg>' 
      }
    ];
    
    sizes.forEach(size => {
      const btn = document.createElement('button');
      btn.className = 'chatkit-resize-btn';
      btn.setAttribute('data-size', size.key);
      btn.setAttribute('aria-label', size.label);
      btn.setAttribute('title', size.label);
      btn.setAttribute('type', 'button');
      btn.innerHTML = `<span class="chatkit-icon">${size.icon}</span>`;
      
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        handlePresetSize(size.key);
      });
      
      controls.appendChild(btn);
    });
    
    overlay.appendChild(controls);
    document.body.appendChild(overlay);
    
    // Centralized position update function - handles all states
    const updateOverlayPosition = () => {
      const chatkit = document.getElementById('myChatkit');
      
      // Hide controls when chat is closed
      if (!chatkit || !isOpen || chatkit.style.display === 'none') {
        overlay.style.display = 'none';
        return;
      }
      
      // Show controls when open
      overlay.style.display = 'block';
      
      const rect = chatkit.getBoundingClientRect();
      const isMaximized = resizeState.currentSize === 'maximized';
      
      // Reduced padding for tighter integration with window edge (like native window controls)
      const controlPadding = 6;
      
      if (isMaximized) {
        // Maximized: Position at top-right of viewport, close to edge
        overlay.style.cssText = `
          position: fixed;
          top: ${controlPadding}px;
          right: ${controlPadding}px;
          z-index: 10002;
          pointer-events: none;
        `;
      } else {
        // Regular size: Position at top-right of chat window, aligned to window edge
        const topOffset = rect.top + controlPadding;
        const rightOffset = window.innerWidth - rect.right + controlPadding;
        overlay.style.cssText = `
          position: fixed;
          top: ${topOffset}px;
          right: ${rightOffset}px;
          z-index: 10002;
          pointer-events: none;
        `;
      }
      
      controls.style.cssText = 'pointer-events: auto;';
    };
    
    // Initial positioning
    updateOverlayPosition();
    
    // Update position when chatkit moves or state changes
    const overlayObserver = new MutationObserver(() => {
      updateOverlayPosition();
    });
    overlayObserver.observe(chatkitElement, { attributes: true, attributeFilter: ['style'] });
    
    // Update on window resize
    const resizeHandler = () => {
      updateOverlayPosition();
    };
    window.addEventListener('resize', resizeHandler);
    
    // Store for cleanup
    chatkitElement._overlayObserver = overlayObserver;
    chatkitElement._overlayResizeHandler = resizeHandler;
    chatkitElement._overlayUpdatePosition = updateOverlayPosition;
    
    // Update button states
    updateResizeButtons();
  }
  
  
  // Setup keyboard shortcuts
  function setupKeyboardShortcuts(chatkitElement) {
    document.addEventListener('keydown', (e) => {
      if (!isOpen) return;
      
      // Escape key - minimize/close (existing behavior, but ensure it works)
      if (e.key === 'Escape') {
        const button = document.getElementById('chatToggleBtn');
        if (button) {
          button.click();
        }
        return;
      }
      
      // Cmd/Ctrl + M for maximize toggle
      if ((e.metaKey || e.ctrlKey) && e.key === 'm') {
        e.preventDefault();
        toggleMaximize();
        return;
      }
    });
  }
  
  // Setup double-click header to maximize
  function setupDoubleClickMaximize(chatkitElement) {
    let clickTimeout = null;
    let lastClickTarget = null;
    
    const handleClick = (e) => {
      // Don't trigger on button clicks or interactive elements
      if (e.target.closest('.chatkit-resize-btn') || 
          e.target.closest('button') ||
          e.target.closest('a') ||
          e.target.closest('input') ||
          e.target.closest('textarea')) {
        return;
      }
      
      // Check if this is a double click on the same target
      if (clickTimeout !== null && e.target === lastClickTarget) {
        clearTimeout(clickTimeout);
        clickTimeout = null;
        // Double click detected
        e.preventDefault();
        e.stopPropagation();
        toggleMaximize();
      } else {
        lastClickTarget = e.target;
        clickTimeout = setTimeout(() => {
          clickTimeout = null;
          lastClickTarget = null;
        }, 300);
      }
    };
    
    // Try to attach to header
    const tryAttachToHeader = () => {
      const header = chatkitElement.shadowRoot?.querySelector('header') || 
                    chatkitElement.querySelector('header') ||
                    chatkitElement.querySelector('[role="banner"]');
      
      if (header) {
        header.addEventListener('click', handleClick);
      } else {
        // Fallback: attach to chatkit element itself (but only top area)
        chatkitElement.addEventListener('click', (e) => {
          // Only trigger if click is in top 60px (header area)
          const rect = chatkitElement.getBoundingClientRect();
          const clickY = e.clientY - rect.top;
          if (clickY <= 60) {
            handleClick(e);
          }
        });
      }
    };
    
    // Wait a bit for ChatKit to render
    setTimeout(tryAttachToHeader, 500);
  }

  async function initChatKit() {
    try {
      if (!config.restUrl) {
        console.error('ChatKit configuration missing: restUrl not defined');
        const errorMsg = config.i18n?.configError || '⚠️ Chat configuration error. Please contact support.';
        showUserError(errorMsg);
        return;
      }

      await loadChatkitScript();

      if (!customElements.get('openai-chatkit')) {
        await customElements.whenDefined('openai-chatkit');
      }

      const chatkitElement = document.getElementById('myChatkit');
      if (!chatkitElement) {
        console.error('Element #myChatkit not found in DOM');
        
        if (retryCount < MAX_RETRIES) {
          retryCount++;
          console.log(`Retrying ChatKit initialization (${retryCount}/${MAX_RETRIES})...`);
          setTimeout(initChatKit, 1000);
        } else {
          const errorMsg = config.i18n?.loadFailed || '⚠️ Chat widget failed to load. Please refresh the page.';
          showUserError(errorMsg);
        }
        return;
      }

      setupToggle();

      console.log('📋 ChatKit Config Received:', {
        showHeader: config.showHeader,
        headerTitleText: config.headerTitleText,
        historyEnabled: config.historyEnabled,
        enableAttachments: config.enableAttachments,
        disclaimerText: config.disclaimerText ? 'Set' : 'Not set'
      });

      // ✅ BUILD BASE OPTIONS with SAFE values
      const options = {
        api: {
          getClientSecret: getClientSecret
        },
        // Add deployment URL if available (fixes relative path issues)
        ...(deploymentUrl && { deploymentUrl: deploymentUrl }),
        theme: {
          colorScheme: config.themeMode || 'dark',
          // ✅ ALWAYS FIXED (CSS handles visual customization)
          radius: 'round',
          density: 'normal',
          color: {
            accent: {
              primary: config.accentColor || '#FF4500',
              level: parseInt(config.accentLevel) || 2
            }
          },
          // ✅ ALWAYS present (will be overridden if custom)
          typography: {
            baseSize: 16,
            fontFamily: '"OpenAI Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
          }
        },
        composer: {
          attachments: {
            enabled: toBool(config.enableAttachments)
          },
          placeholder: config.placeholderText || 'Send a message...'
        },
        startScreen: {
          greeting: config.greetingText || 'How can I help you today?',
          prompts: buildPrompts()
        }
      };

      // ✅ FILE UPLOAD with extra params (if enabled)
      if (toBool(config.enableAttachments)) {
        try {
          const maxSize = parseInt(config.attachmentMaxSize) || 20;
          const maxCount = parseInt(config.attachmentMaxCount) || 3;
          
          options.composer.attachments = {
            enabled: true,
            maxSize: maxSize * 1024 * 1024,
            maxCount: maxCount,
            accept: {
              'application/pdf': ['.pdf'],
              'image/*': ['.png', '.jpg', '.jpeg', '.gif', '.webp'],
              'text/plain': ['.txt']
            }
          };
          
          console.log('✅ Attachments enabled with params:', { maxSize: maxSize + 'MB', maxCount });
        } catch (e) {
          console.warn('Attachments config error, using basic mode:', e);
        }
      }

      // ✅ INITIAL THREAD ID
      if (config.initialThreadId && config.initialThreadId.trim() !== '') {
        options.initialThread = config.initialThreadId;
        console.log('✅ Initial thread set:', config.initialThreadId);
      }

      // ✅ DISCLAIMER
      if (config.disclaimerText && config.disclaimerText.trim() !== '') {
        options.disclaimer = {
          text: config.disclaimerText,
          highContrast: toBool(config.disclaimerHighContrast)
        };
        console.log('✅ Disclaimer configured');
      }

      // ✅ CUSTOM TYPOGRAPHY (overrides default)
      if (config.customFont && config.customFont.fontFamily && config.customFont.fontFamily.trim() !== '') {
        try {
          options.theme.typography = {
            fontFamily: config.customFont.fontFamily,
            baseSize: parseInt(config.customFont.baseSize) || 16
          };
          console.log('✅ Custom typography applied');
        } catch (e) {
          console.warn('Typography config error, using default:', e);
        }
      }

      // ✅ HEADER
      if (toBool(config.showHeader)) {
        const headerConfig = { enabled: true };
        
        // Custom title
        if (config.headerTitleText && config.headerTitleText.trim() !== '') {
          headerConfig.title = {
            enabled: true,
            text: config.headerTitleText
          };
          console.log('✅ Header custom title:', config.headerTitleText);
        }
        
        // Left action button
        if (config.headerLeftIcon && config.headerLeftUrl && config.headerLeftUrl.trim() !== '') {
          try {
            new URL(config.headerLeftUrl);
            headerConfig.leftAction = {
              icon: config.headerLeftIcon,
              onClick: () => {
                window.location.href = config.headerLeftUrl;
              }
            };
            console.log('✅ Header left button configured:', config.headerLeftIcon);
          } catch (e) {
            console.warn('⚠️ Invalid left button URL, skipping');
          }
        }
        
        // Right action button
        if (config.headerRightIcon && config.headerRightUrl && config.headerRightUrl.trim() !== '') {
          try {
            new URL(config.headerRightUrl);
            headerConfig.rightAction = {
              icon: config.headerRightIcon,
              onClick: () => {
                window.location.href = config.headerRightUrl;
              }
            };
            console.log('✅ Header right button configured:', config.headerRightIcon);
          } catch (e) {
            console.warn('⚠️ Invalid right button URL, skipping');
          }
        }

        options.header = headerConfig;
        console.log('✅ Header enabled');
      } else {
        options.header = { enabled: false };
        console.log('✅ Header disabled');
      }

      // ✅ HISTORY
      options.history = { 
        enabled: toBool(config.historyEnabled) 
      };
      console.log('✅ History:', toBool(config.historyEnabled) ? 'enabled' : 'disabled');

      // ✅ LOCALE
      if (config.locale && config.locale.trim() !== '') {
        options.locale = config.locale;
        console.log('✅ Locale set to:', config.locale);
      }

      // Initialize ChatKit
      console.log('🚀 Initializing ChatKit with final config:', options);
      chatkitElement.setOptions(options);
      
      // Wait for ChatKit to initialize, then setup resize controls
      setTimeout(() => {
        setupResizeControls(chatkitElement);
        setupKeyboardShortcuts(chatkitElement);
        setupDoubleClickMaximize(chatkitElement);
      }, 500);

      // Monitor for iframe creation and fix relative URLs
      const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          mutation.addedNodes.forEach((node) => {
            if (node.nodeName === 'IFRAME' || (node.querySelector && node.querySelector('iframe'))) {
              const iframe = node.nodeName === 'IFRAME' ? node : node.querySelector('iframe');
              if (iframe && iframe.src) {
                // Check if iframe src is a relative path (starts with /)
                if (iframe.src.startsWith('/') && !iframe.src.startsWith('//')) {
                  console.error('❌ ChatKit: Detected relative iframe URL:', iframe.src);
                  console.error('⚠️ This should be an absolute URL from OpenAI CDN');
                  
                  // Try to construct absolute URL from CDN
                  const cdnBase = 'https://cdn.platform.openai.com';
                  const fixedUrl = cdnBase + iframe.src;
                  console.log('🔧 Attempting to fix URL to:', fixedUrl);
                  
                  // Note: We can't directly modify iframe src due to CORS, but we can log it
                  // The real fix needs to come from the ChatKit library configuration
                } else {
                  console.log('✅ ChatKit iframe URL looks correct:', iframe.src);
                }
              }
            }
          });
        });
      });

      // Start observing the chatkit element for iframe creation
      observer.observe(chatkitElement, {
        childList: true,
        subtree: true
      });

      console.log('✅ ChatKit initialized successfully');

      if (typeof gtag !== 'undefined') {
        gtag('event', 'chatkit_initialized', {
          event_category: 'engagement',
          event_label: 'ChatKit Ready'
        });
      }

    } catch (error) {
      console.error('❌ ChatKit Initialization Error:', error);
      
      if (retryCount < MAX_RETRIES) {
        retryCount++;
        console.log(`Retrying after error (${retryCount}/${MAX_RETRIES})...`);
        setTimeout(initChatKit, 2000);
      } else {
        const errorMsg = config.i18n?.loadFailed || '⚠️ Chat initialization failed. Please refresh the page.';
        showUserError(errorMsg);
      }
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChatKit);
  } else {
    setTimeout(initChatKit, 0);
  }

  window.addEventListener('beforeunload', () => {
    document.body.style.overflow = '';
  });
})();
