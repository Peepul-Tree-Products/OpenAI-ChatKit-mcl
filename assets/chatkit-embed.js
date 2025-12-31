(function() {
  'use strict';

  const config = typeof chatkitConfig !== 'undefined' ? chatkitConfig : {};
  let isOpen = false;
  let retryCount = 0;
  const MAX_RETRIES = 3;

  // Session preloading state
  let preloadedSecret = null;
  let preloadPromise = null;
  let sessionPreloaded = false;

  // Helper to convert WordPress boolean strings to actual booleans
  function toBool(value) {
    if (typeof value === 'boolean') return value;
    if (typeof value === 'string') return value === '1' || value.toLowerCase() === 'true';
    if (typeof value === 'number') return value === 1;
    return !!value;
  }

  /**
   * Gather client-side context to pass to the session
   * This helps the workflow have more information about the user's environment
   */
  function getClientContext() {
    const context = {};
    
    try {
      // Page title for context
      context.page_title = document.title || '';
      
      // Viewport size (helps with responsive considerations)
      context.viewport_width = window.innerWidth.toString();
      
      // User's timezone
      context.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
      
      // Browser language preference
      context.language = navigator.language || '';
    } catch (e) {
      console.warn('Error gathering client context:', e);
    }
    
    return context;
  }

  /**
   * Preload session in background for faster first interaction
   * Called after page load to have session ready when user opens chat
   */
  async function preloadSession() {
    if (!toBool(config.preloadSession) || preloadPromise || !config.restUrl) {
      return null;
    }

    console.log('🔄 Preloading ChatKit session...');
    
    preloadPromise = (async () => {
      try {
        const body = {};
        
        // Add client context if page context is enabled
        if (toBool(config.passPageContext)) {
          body.context = getClientContext();
        }

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 15000);

        const response = await fetch(config.restUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(body),
          signal: controller.signal,
          credentials: 'same-origin'
        });

        clearTimeout(timeoutId);

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        
        if (data.client_secret) {
          preloadedSecret = data.client_secret;
          sessionPreloaded = true;
          console.log('✅ Session preloaded successfully');
          return preloadedSecret;
        }
        
        throw new Error('No client_secret in response');
      } catch (error) {
        console.warn('⚠️ Session preload failed (will retry on open):', error.message);
        preloadPromise = null;
        return null;
      }
    })();

    return preloadPromise;
  }

  function loadChatkitScript() {
    return new Promise((resolve, reject) => {
      if (customElements.get('openai-chatkit')) {
        resolve();
        return;
      }

      const script = document.createElement('script');
      script.src = 'https://cdn.platform.openai.com/deployments/chatkit/chatkit.js';
      script.defer = true;
      script.onload = resolve;
      script.onerror = () => reject(new Error('Failed to load ChatKit CDN'));
      document.head.appendChild(script);
    });
  }

  async function getClientSecret() {
    try {
      if (!config.restUrl) {
        throw new Error('Missing configuration');
      }

      // Use preloaded secret if available (performance optimization)
      if (preloadedSecret) {
        const secret = preloadedSecret;
        preloadedSecret = null; // Clear for next session
        console.log('⚡ Using preloaded session (faster!)');
        return secret;
      }

      // Wait for preload if in progress
      if (preloadPromise) {
        console.log('⏳ Waiting for preloading session...');
        const secret = await preloadPromise;
        if (secret) {
          preloadedSecret = null;
          preloadPromise = null;
          return secret;
        }
      }

      // Fall back to fetching a new session
      console.log('🔄 Fetching new session...');
      
      const body = {};
      
      // Add client context if page context is enabled
      if (toBool(config.passPageContext)) {
        body.context = getClientContext();
      }

      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 10000);

      const response = await fetch(config.restUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
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

  function setupToggle() {
    const button = document.getElementById('chatToggleBtn');
    const chatkit = document.getElementById('myChatkit');

    if (!button || !chatkit) {
      console.warn('ChatKit toggle elements not found');
      return;
    }

    const originalText = button.textContent || config.buttonText || 'Chat now';
    const closeText = config.closeText || '✕';
    const accentColor = config.accentColor || '#FF4500';

    button.addEventListener('click', () => {
      isOpen = !isOpen;
      chatkit.style.display = isOpen ? 'block' : 'none';
      button.setAttribute('aria-expanded', isOpen);
      chatkit.setAttribute('aria-modal', isOpen);
      
      if (isOpen) {
        button.classList.add('chatkit-open');
        button.textContent = closeText;
        button.style.backgroundColor = accentColor;
        chatkit.style.animation = 'chatkit-slide-up 0.3s ease-out';
        
        setTimeout(() => chatkit.focus(), 100);
        
        if (window.innerWidth <= 768) {
          document.body.style.overflow = 'hidden';
        }
      } else {
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
        disclaimerText: config.disclaimerText ? 'Set' : 'Not set',
        // Performance optimization settings
        preloadSession: config.preloadSession,
        passPageContext: config.passPageContext,
        sessionPreloaded: sessionPreloaded
      });

      // ✅ BUILD BASE OPTIONS with SAFE values
      const options = {
        api: {
          getClientSecret: getClientSecret
        },
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

  // Preload session in background after page fully loads (performance optimization)
  // This makes the first chat interaction faster by having the session ready
  function startPreload() {
    if (toBool(config.preloadSession)) {
      // Delay preload slightly to not compete with critical page resources
      setTimeout(preloadSession, 2000);
    }
  }

  if (document.readyState === 'complete') {
    startPreload();
  } else {
    window.addEventListener('load', startPreload);
  }

  window.addEventListener('beforeunload', () => {
    document.body.style.overflow = '';
  });
})();
