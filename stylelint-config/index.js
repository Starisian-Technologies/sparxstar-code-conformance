// @starisian/stylelint-config — Starisian Technologies shared Stylelint config
// Rationale for prohibited properties: GPU compositing on $50 Android / 2 GB RAM.

/** @type {import('stylelint').Config} */
export default {
  extends: ['stylelint-config-standard'],
  ignoreFiles: ['**/node_modules/**', '**/vendor/**', '**/dist/**'],
  rules: {
    // No invalid hex colours
    'color-no-invalid-hex': true,

    // !important is forbidden
    'declaration-no-important': true,

    // No ID selectors — specificity cap: 0,1,0 (single class)
    'selector-max-id': 0,

    // Selector complexity cap: max 3 compound selectors per rule (limits chained .a.b.c or a > b > c patterns)
    'selector-max-compound-selectors': 3,

    // Design token pattern: CSS custom properties must follow --token-name convention
    'custom-property-pattern': '^[a-z][a-z0-9]*(-[a-z0-9]+)*$',

    // Prohibited properties (GPU-intensive; degrade performance on low-resource target devices)
    // filter: blur() — GPU compositor layer; forbidden entirely
    // backdrop-filter: blur() — extremely GPU-intensive; forbidden entirely
    // text-shadow on body text — paint cost, no readability gain; warn
    // box-shadow with spread > 4px increases paint area — cannot be enforced by property-disallowed-list alone; flagged in code review
    'property-disallowed-list': [
      'filter',       // STD: CSS — GPU compositing; use @supports if genuinely needed and document the exception
      'backdrop-filter', // STD: CSS — extremely GPU-intensive
    ],

    // Animation on layout properties triggers recalc — restrict to composited properties only
    // Full enforcement requires a custom rule; the following catches the most common violations
    'declaration-property-value-disallowed-list': {
      'transition': ['/width/', '/height/', '/top/', '/left/', '/bottom/', '/right/'],
    },

    // Font sizes must use rem (respects user settings)
    'unit-disallowed-list': [],  // Override in consuming config if needed

    // Minimum font size: 1rem (16px default) — enforced in code review; no Stylelint rule available
  },
};
