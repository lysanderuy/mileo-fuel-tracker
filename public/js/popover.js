/**
 * Custom Popover Dropdown Component
 * Replaces native <select> elements with accessible custom dropdowns
 *
 * Usage:
 *   1. Call Popover.init(selectElement, options) to initialize
 *   2. Or call Popover.initAll(selector) to initialize multiple
 */
(function () {
  'use strict';

  const POPOVER_TEMPLATE = `
    <div class="mm-popover-wrapper">
      <button class="mm-popover-trigger" type="button" aria-haspopup="listbox" aria-expanded="false">
        <span class="mm-popover-trigger__label"></span>
        <svg class="mm-popover-trigger__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m6 9 6 6 6-6"/>
        </svg>
      </button>
      <div class="mm-popover" role="listbox">
      </div>
    </div>
  `;

  /**
   * Create a custom popover dropdown to replace a native <select>
   * @param {HTMLSelectElement} selectElement - The native select element to replace
   * @param {Object} options - Configuration options
   * @param {string} [options.placeholder] - Placeholder text when no option selected
   * @param {function} [options.onChange] - Callback when selection changes
   * @returns {Object} Popover API with destroy method
   */
  function init(selectElement, options = {}) {
    if (!selectElement || selectElement.tagName !== 'SELECT') {
      console.error('Popover.init: First argument must be a <select> element');
      return null;
    }

    const wrapper = document.createElement('div');
    wrapper.innerHTML = POPOVER_TEMPLATE.trim();
    const popoverWrapper = wrapper.firstElementChild;

    const trigger = popoverWrapper.querySelector('.mm-popover-trigger');
    const triggerLabel = popoverWrapper.querySelector('.mm-popover-trigger__label');
    const popover = popoverWrapper.querySelector('.mm-popover');

    let backdrop = null;
    let isDestroyed = false;

    // Build options from select
    function buildOptions() {
      popover.innerHTML = '';
      const options = Array.from(selectElement.options);

      options.forEach((opt, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'mm-popover-option';
        button.setAttribute('role', 'option');
        button.setAttribute('data-index', index);
        button.setAttribute('data-value', opt.value);
        button.textContent = opt.textContent;

        if (opt.selected) {
          button.classList.add('is-selected');
        }

        popover.appendChild(button);
      });

      updateTriggerLabel();
    }

    function updateTriggerLabel() {
      const selectedOption = selectElement.options[selectElement.selectedIndex];
      triggerLabel.textContent = selectedOption ? selectedOption.textContent : (options.placeholder || '');
    }

    function openPopover() {
      if (isDestroyed) return;

      trigger.setAttribute('aria-expanded', 'true');
      popover.classList.add('is-open');

      // Create backdrop
      backdrop = document.createElement('div');
      backdrop.className = 'mm-popover-backdrop';
      backdrop.addEventListener('click', closePopover);
      document.body.appendChild(backdrop);

      // Position popover
      positionPopover();
    }

    function closePopover() {
      if (isDestroyed) return;

      trigger.setAttribute('aria-expanded', 'false');
      popover.classList.remove('is-open');

      if (backdrop) {
        backdrop.remove();
        backdrop = null;
      }
    }

    function positionPopover() {
      const triggerRect = trigger.getBoundingClientRect();
      const popoverRect = popover.getBoundingClientRect();

      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;

      // Horizontal positioning
      let left = triggerRect.left;
      if (left + popoverRect.width > viewportWidth) {
        left = viewportWidth - popoverRect.width - 16;
      }
      left = Math.max(8, left); // Keep away from left edge

      // Vertical positioning - flip to top if needed
      const spaceBelow = viewportHeight - triggerRect.bottom;
      const spaceAbove = triggerRect.top;
      const popoverHeight = Math.min(popoverRect.height, 280); // max-height from CSS

      if (spaceBelow < popoverHeight && spaceAbove > spaceBelow) {
        // Position above trigger
        popover.style.top = (triggerRect.top - popoverHeight - 2) + 'px';
        popover.style.bottom = 'auto';
      } else {
        // Position below trigger
        popover.style.top = (triggerRect.bottom + 2) + 'px';
        popover.style.bottom = 'auto';
      }

      popover.style.left = left + 'px';
      popover.style.width = triggerRect.width + 'px';
    }

    function selectOption(index) {
      if (index < 0 || index >= selectElement.options.length) return;

      // Update native select
      selectElement.selectedIndex = index;

      // Update UI
      Array.from(popover.querySelectorAll('.mm-popover-option')).forEach((opt, i) => {
        opt.classList.toggle('is-selected', i === index);
      });

      updateTriggerLabel();
      closePopover();

      // Trigger native change event
      selectElement.dispatchEvent(new Event('change', { bubbles: true }));

      // Call custom callback
      if (typeof options.onChange === 'function') {
        options.onChange(selectElement.value, index);
      }
    }

    // Event listeners
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (trigger.getAttribute('aria-expanded') === 'true') {
        closePopover();
      } else {
        openPopover();
      }
    });

    popover.addEventListener('click', (e) => {
      const option = e.target.closest('.mm-popover-option');
      if (option) {
        const index = parseInt(option.getAttribute('data-index'), 10);
        selectOption(index);
      }
    });

    // Keyboard navigation
    popover.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closePopover();
        trigger.focus();
      } else if (e.key === 'Enter' || e.key === ' ') {
        const option = e.target.closest('.mm-popover-option');
        if (option) {
          e.preventDefault();
          const index = parseInt(option.getAttribute('data-index'), 10);
          selectOption(index);
        }
      }
    });

    // Handle window resize
    function handleResize() {
      if (trigger.getAttribute('aria-expanded') === 'true') {
        positionPopover();
      }
    }
    window.addEventListener('resize', handleResize);
    window.addEventListener('scroll', handleResize, true);

    // Replace select with popover
    selectElement.style.display = 'none';
    selectElement.parentNode.insertBefore(popoverWrapper, selectElement.nextSibling);

    // Initial build
    buildOptions();

    // Return API
    return {
      destroy() {
        isDestroyed = true;
        closePopover();
        popoverWrapper.remove();
        selectElement.style.display = '';
        window.removeEventListener('resize', handleResize);
        window.removeEventListener('scroll', handleResize, true);
      },
      refresh() {
        buildOptions();
      },
      open: openPopover,
      close: closePopover,
      getValue() {
        return selectElement.value;
      },
      setValue(value) {
        selectElement.value = value;
        buildOptions();
      }
    };
  }

  /**
   * Initialize popovers for all matching select elements
   * @param {string} selector - CSS selector for select elements
   * @param {Object} options - Configuration options
   * @returns {Array} Array of popover instances
   */
  function initAll(selector, options = {}) {
    const selects = document.querySelectorAll(selector);
    const instances = [];

    selects.forEach((select, index) => {
      const instance = init(select, options);
      if (instance) {
        instances.push(instance);
      }
    });

    return instances;
  }

  // Expose API
  window.Popover = { init, initAll };
})();
