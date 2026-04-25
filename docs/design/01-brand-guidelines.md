# Mileo Brand Guidelines
**Version 1.0 — April 2026**

This document is the single source of truth for anyone designing, building, or writing for Mileo. Whether you're creating a screen, writing an error message, or choosing a button color — this is your reference.

---

## Table of Contents

1. [Brand Personality](#1-brand-personality)
2. [Logo Usage](#2-logo-usage)
3. [Color System](#3-color-system)
4. [Typography](#4-typography)
5. [Tone of Voice](#5-tone-of-voice)
6. [Imagery & Illustration](#6-imagery--illustration)
7. [UI Personality — Motion, Interactions & Micro-copy](#7-ui-personality--motion-interactions--micro-copy)

---

## 1. Brand Personality

### The Coach

Mileo speaks like a motivating coach — direct, grounded, and on your side. It respects your time. It doesn't lecture. It shows you what's happening with your fuel spending and trusts you to make decisions with that information.

**The three words that govern every decision:**
- **Direct** — Say it once, say it clearly.
- **Practical** — Serve the task, not the ego.
- **Honest** — No spin. No shame. Just data.

**What Mileo feels like:**
| Is | Is Not |
|---|---|
| A trusted colleague | A judgmental accountant |
| Simple and clear | Cluttered and demanding |
| Warm but no-nonsense | Cold or preachy |
| Encouraging without flattery | Gamified or gimmicky |

**The one-sentence gut check:**
> *"Would a coach who respects your time say this?"*

If the answer is no — rewrite it.

---

## 2. Logo Usage

### Concept
The Mileo wordmark is the primary logo. It is set in the display font (DM Sans, Bold) with tight tracking. The brandmark, if used standalone, is a stylized fuel drop formed from the letter "M" — evoking motion, precision, and the pump context.

### Clear Space
Maintain a minimum clear space equal to the cap-height of the "M" on all sides of the logo. Nothing — text, imagery, UI elements — enters this zone.

```
        ↕ [cap-height]
← [cap-height] →  MILEO  ← [cap-height] →
        ↕ [cap-height]
```

### Minimum Size
- **Digital:** 80px wide minimum (wordmark). Below this, legibility breaks.
- **Print:** 25mm wide minimum.
- **Icon-only (app icon):** The brandmark alone may be used at any size where the full wordmark would not fit. Minimum 32×32px.

### Color Variants

| Variant | When to Use |
|---|---|
| **Amber on White** (`#F59500` on `#FFFFFF`) | Default. Light backgrounds, onboarding, marketing. |
| **White on Slate** (`#FFFFFF` on `#232B42`) | Dark surfaces, dark mode, print on dark stock. |
| **Slate on White** (`#232B42` on `#FFFFFF`) | Monochrome contexts, legal documents, greyscale print. |
| **Amber on Dark** (`#F59500` on `#0C1221`) | Dark mode app surfaces. |

### What Not To Do

- ❌ Do not stretch, skew, or rotate the logo.
- ❌ Do not apply drop shadows or gradients to the logo itself.
- ❌ Do not place the logo on a busy photographic background without a clear container.
- ❌ Do not recolor the logo in any color outside the four approved variants above.
- ❌ Do not use a lighter weight than Bold for the wordmark.
- ❌ Do not add taglines, icons, or decorative elements within the clear space zone.
- ❌ Do not use the brand color amber (`#F59500`) as a background behind the amber wordmark.

---

## 3. Color System

### Palette Overview

Mileo uses three color families — Primary (Fuel Amber), Secondary (Slate Ink), and Neutral (Fog) — plus four semantic colors.

| Role | Family | Hex | Use |
|---|---|---|---|
| **Brand / Action** | Primary Amber | `#F59500` | CTAs, FAB, active states, key stats |
| **Structure / Text** | Secondary Slate | `#323A52` | Headings, icons, secondary actions |
| **Background** | Neutral Fog | `#FAFAF8` | App background |
| **Surface** | Neutral | `#FFFFFF` | Cards, sheets, input backgrounds |
| **Body Text** | Neutral | `#38362F` | All primary readable text |
| **Supporting Text** | Neutral | `#57544C` | Captions, secondary labels |
| **Success** | Sage | `#228A55` | Positive deltas, confirmation states |
| **Warning** | Harvest Gold | `#C9A900` | Partial fill flags, soft cautions |
| **Error** | Brick | `#D93520` | Validation errors, destructive actions |
| **Info** | Clear Sky | `#2B72C0` | Neutral informational callouts |

---

### Primary — Fuel Amber

The only action color. Every tappable element of consequence — the FAB, primary buttons, active tab indicators, key stat callouts — lives in amber.

**Full scale:**

| Token | Hex | Usage |
|---|---|---|
| `primary-50` | `#FFF8ED` | Hover tint on amber surfaces |
| `primary-100` | `#FFEFD0` | Amber input focus background |
| `primary-200` | `#FFD999` | — |
| `primary-300` | `#FFC055` | — |
| `primary-400` | `#FFAb1F` | Hover / active state |
| **`primary-500`** | **`#F59500`** | **Core brand. CTAs, FAB, accents.** |
| `primary-600` | `#CC7A00` | Pressed state, text links |
| `primary-700` | `#A36100` | — |
| `primary-800` | `#7A4900` | — |
| `primary-900` | `#3D2500` | — |

**Rules for amber:**
- Use `primary-500` as the CTA background. Use `primary-400` on hover, `primary-600` on press.
- Text on amber buttons is always `#FFFFFF`.
- Amber focus rings use `primary-500` at 25% opacity: `rgba(245, 149, 0, 0.25)`.
- The FAB shadow is amber-tinted: `0px 8px 24px rgba(245, 149, 0, 0.35)`.
- Do not use amber as a background for body copy or large text areas — it is an accent, not a canvas.
- Do not use amber for error states. Reserve amber for positive action only.

---

### Secondary — Slate Ink

The structural color. Used for typography, headings, icons, and secondary interactive elements. Slate provides authority without corporate coldness.

**Key values:**

| Token | Hex | Usage |
|---|---|---|
| `secondary-600` | `#323A52` | Screen headings, bold labels |
| `secondary-700` | `#232B42` | Strong headings, dark mode borders |
| `secondary-800` | `#161D30` | Dark mode card surface |
| `secondary-900` | `#0C1221` | Dark mode app background |

**Secondary interactive (buttons):**
- Background: `#F4F5F7` (secondary-50)
- Hover: `#E5E7EC` (secondary-100)
- Pressed: `#C8CDD8` (secondary-200)
- Text: `#323A52` (secondary-600)

---

### Neutral — Fog

The warm white-to-charcoal scale. The slight warmth (not cool gray) keeps Mileo human and grounded.

| Token | Hex | Usage |
|---|---|---|
| `neutral-0` | `#FFFFFF` | Card / input surfaces |
| `neutral-50` | `#FAFAF8` | App background |
| `neutral-100` | `#F2F1EE` | Sunken wells, section fills |
| `neutral-200` | `#E5E3DE` | Borders, dividers |
| `neutral-300` | `#CCC9C2` | Disabled borders |
| `neutral-400` | `#AAA79E` | Disabled text |
| `neutral-500` | `#7A776E` | Placeholder text |
| `neutral-600` | `#57544C` | Secondary / supporting text |
| `neutral-700` | `#38362F` | Primary body text |
| `neutral-800` | `#211F1A` | Maximum contrast text |

---

### Semantic Colors

Use semantic colors only for their defined meaning. Never repurpose error red as a decorative accent.

**Success — Sage (`#228A55`)**
Used for: positive efficiency deltas (↑ improvement), saved confirmations, checkmarks. Never used decoratively.

**Warning — Harvest Gold (`#C9A900`)**
Used for: partial fill flags, soft informational nudges. Distinct from primary amber — softer, yellower.

**Error — Brick (`#D93520`)**
Used for: validation errors, destructive action buttons, negative deltas with context. Text on error: `#FFFFFF`.

**Info — Clear Sky (`#2B72C0`)**
Used for: neutral informational callouts that are neither positive nor negative.

---

### Dark Mode

Dark mode inverts surfaces to the slate family. Primary amber is unchanged — it remains legible and on-brand on dark backgrounds.

| Light Token | Dark Override |
|---|---|
| `surface-background: #FAFAF8` | `#0C1221` |
| `surface-card: #FFFFFF` | `#161D30` |
| `text-primary: #38362F` | `#F2F1EE` |
| `text-secondary: #57544C` | `#CCC9C2` |
| `border-default: #E5E3DE` | `#232B42` |

Amber CTAs and the FAB remain `#F59500` in dark mode. The FAB shadow is slightly stronger: `rgba(245, 149, 0, 0.45)`.

---

## 4. Typography

### Font Families

| Role | Font | Fallbacks | Why |
|---|---|---|---|
| **Display / Headings** | DM Sans | Sora, sans-serif | Geometric but human. Confident at large sizes. Reads as coach voice — not academic, not corporate. |
| **Body / UI** | Plus Jakarta Sans | DM Sans, sans-serif | Workhorse with personality. Wide apertures = legible outdoors. Distinguished from overused Inter/Roboto. |
| **Numbers / Stats** | JetBrains Mono | Fira Code, monospace | Tabular, trustworthy, scannable. Stats feel precise, not decorative. Numbers are Mileo's content. |

```html
<!-- Add to HTML <head> -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
```

---

### Type Scale

| Size Token | px | Primary Use |
|---|---|---|
| `text-6xl` | 60px | Reserved — future marketing splash |
| `text-5xl` | 48px | Onboarding hero headline |
| `text-4xl` | 36px | **Instant Stats hero number** (Total Cost) |
| `text-3xl` | 30px | Secondary stat values |
| `text-2xl` | 24px | Screen headings; input value text |
| `text-xl` | 20px | Section headings |
| `text-lg` | 18px | Card titles, large body |
| `text-md` | 16px | **Primary body** — base size |
| `text-sm` | 14px | Secondary body, list detail |
| `text-xs` | 12px | Captions, timestamps, unit labels |
| `text-2xs` | 10px | Micro labels, bottom nav labels |

---

### Type Roles (Named Pairings)

These are the only defined combinations. Do not freestyle outside these roles.

**`display_hero`** — Onboarding, splash
- Font: DM Sans | Size: 48px | Weight: 700 | Leading: 1.15 | Tracking: -0.03em

**`heading_screen`** — Screen titles (e.g., "Fill-Up History")
- Font: DM Sans | Size: 24px | Weight: 700 | Leading: 1.25 | Tracking: -0.015em

**`heading_section`** — Section labels (e.g., "This Month")
- Font: DM Sans | Size: 20px | Weight: 600 | Leading: 1.25 | Tracking: -0.015em

**`heading_card`** — Card titles (e.g., "My Civic")
- Font: Plus Jakarta Sans | Size: 18px | Weight: 600 | Leading: 1.25

**`stat_hero`** — The big number (Total Cost on Instant Stats)
- Font: JetBrains Mono | Size: 36px | Weight: 700 | Leading: 1.15 | Tracking: -0.03em

**`stat_secondary`** — Supporting stats (Efficiency, Cost/km)
- Font: JetBrains Mono | Size: 30px | Weight: 600 | Leading: 1.15

**`stat_inline`** — Stats in list rows
- Font: JetBrains Mono | Size: 20px | Weight: 500 | Leading: 1.25

**`body_primary`** — All readable prose, descriptions
- Font: Plus Jakarta Sans | Size: 16px | Weight: 400 | Leading: 1.5

**`body_secondary`** — Supporting details
- Font: Plus Jakarta Sans | Size: 14px | Weight: 400 | Leading: 1.5

**`label_default`** — Field labels, button text in rows
- Font: Plus Jakarta Sans | Size: 14px | Weight: 500 | Leading: 1.25

**`label_caps`** — Uppercase section markers, unit badges
- Font: Plus Jakarta Sans | Size: 12px | Weight: 600 | Leading: 1.25 | Tracking: 0.04em | UPPERCASE

**`caption`** — Timestamps, fine print, meta
- Font: Plus Jakarta Sans | Size: 12px | Weight: 400 | Leading: 1.5

**`input_value`** — Text the user types into a numeric field
- Font: JetBrains Mono | Size: 24px | Weight: 500 | Leading: 1.15

**`input_label`** — The label above an input field
- Font: Plus Jakarta Sans | Size: 14px | Weight: 500 | Leading: 1.25

---

### Typography Rules

1. **Stats always use JetBrains Mono.** Numbers are Mileo's primary content. Monospace keeps them scannable and trustworthy.

2. **Headings always use DM Sans.** Never Plus Jakarta Sans for screen or section headings.

3. **Body copy always uses Plus Jakarta Sans.** Never use DM Sans or JetBrains Mono for sentences or paragraphs.

4. **No more than three type sizes per screen.** Hierarchy is about contrast, not abundance. Pick a hero size, a supporting size, and a caption size — and stop there.

5. **Minimum body text size is 14px.** Never go below this for readable content. Use `text-2xs` (10px) only for bottom nav labels and badges.

6. **Text on amber backgrounds is always white (`#FFFFFF`).** Never use dark text on amber.

7. **Numeric inputs use large monospace.** Input fields for odometer, price, and volume use `text-2xl` (24px) JetBrains Mono for clarity and legibility.

---

## 5. Tone of Voice

### The Voice

Mileo is **The Coach**. The Coach has seen a lot of drivers. They're not surprised by anything. They don't judge. They just tell you the truth in the most useful way possible, then let you get on with it.

The voice is warm but efficient. It respects your time. It never repeats itself. It doesn't use exclamation points to manufacture excitement that isn't warranted.

---

### Writing Principles

**1. Lead with the fact.**
Put the useful information first. Don't bury the number in a sentence.
> ✅ "You spent ₱3,200 on fuel last month."
> ❌ "Based on your recent fill-ups, it looks like your monthly fuel expenditure was approximately ₱3,200."

**2. Be direct without being blunt.**
Clear is kind. Softening language with hedges doesn't help — it creates doubt.
> ✅ "Efficiency is down 12% this month."
> ❌ "It seems like your efficiency might have dipped a little this month."

**3. Use second person ("you"), not third.**
This is your fuel log. The app speaks to you, not about you.
> ✅ "You're all set."
> ❌ "The user has completed setup."

**4. Short sentences. Always.**
If a sentence can be split, split it. If a word can be removed, remove it.

**5. Never shame, never celebrate too hard.**
The Coach is encouraging but never sycophantic. Achievements are acknowledged, not applauded.
> ✅ "Efficiency up 8%. Nice stretch."
> ❌ "🎉 AMAZING! You crushed your efficiency this month!!!"

**6. Tell people what to do, not what not to do.**
Action-oriented language is clearer and less anxiety-inducing.
> ✅ "Enter your odometer reading to continue."
> ❌ "You haven't entered your odometer reading yet."

---

### Example Phrases

| Context | Do Write | Don't Write |
|---|---|---|
| Empty state (no vehicle) | "Add your first vehicle to start tracking." | "It looks like you haven't set up yet!" |
| Empty state (no logs) | "Log your first fill-up to see your numbers." | "It looks like you haven't added any data yet!" |
| After saving a log | "Fill-up saved." (toast) | "Great job! Your fill-up has been successfully logged!" |
| Partial fill flag | "Partial fill — efficiency estimate may vary." | "Warning: This may not be accurate because you didn't fill up completely." |
| Deleting a log | "Delete this fill-up? This can't be undone." | "Are you absolutely sure you want to delete this? This action is permanent and irreversible." |
| Error: odometer too low | "Odometer reading is lower than your last log." | "Error! Invalid odometer value detected." |
| First log (no comparison) | "Log one more fill-up for efficiency data." | "Not enough data to display this metric yet." |
| Efficiency improved | "Efficiency up 8% vs last month." | "You improved your fuel efficiency by 8% — way to go! 🎉" |
| Efficiency dropped | "Efficiency down 5% vs last month." | "Uh oh! Your efficiency dropped this month 😬" |
| Export successful | "Your data is ready to share." | "Your CSV export has been successfully generated and is ready for download!" |

---

### Words to Avoid

| Avoid | Because | Use Instead |
|---|---|---|
| "Amazing," "Great," "Awesome" | Hollow praise. The Coach doesn't flatter. | Specific, factual acknowledgment |
| "Unfortunately," "Sorry to say" | Apologetic framing creates anxiety | State the fact directly |
| "Please" (overused) | Dilutes where it actually matters | Reserve for truly polite asks; remove elsewhere |
| "Successfully" | Redundant after a confirmation | The confirmation is enough |
| "Oops!" | Infantilizing error language | State the problem plainly |
| "Just" (as a minimizer) | "Just enter..." undermines importance | Remove it |
| "We" (unless plural is warranted) | Mileo speaks as the app, not a team | Use "you" or passive construction |
| Exclamation points (in excess) | Manufacture excitement where none exists | Use sparingly — only for genuine wins |
| Jargon: "L/100km," "MPG" (unexplained) | Not everyone knows units immediately | Always pair with a plain label nearby |

---

### Punctuation Rules

- **Sentence case** for all UI copy (labels, button text, headings). Not Title Case.
  > ✅ "Log fill-up" | ❌ "Log Fill-Up"
- **No Oxford comma debates** — just be consistent. Use the serial comma.
- **Ellipses (…)** for loading states only. Never in static copy.
- **Em dashes (—)** for parenthetical clauses. Not double hyphens (--).
- **No periods** on single-sentence labels, button text, or toasts.
  > ✅ "Fill-up saved" | ❌ "Fill-up saved."
- **Periods** on multi-sentence copy, Coach prompts, and any copy longer than one clause.

---

## 6. Imagery & Illustration

### Philosophy

Mileo is a utility app. Imagery serves clarity, not decoration. When in doubt, use no image — let data and typography carry the screen.

### What Mileo Uses

**1. Data visualization elements**
Numbers, delta arrows (↑↓), simple trend indicators. These are the "images" in Mileo. They must be precise and legible.

**2. Functional iconography**
Use a consistent, stroke-based icon library (e.g., Lucide or similar). Icons are utility tools, not decoration. They always accompany labels — never stand alone without accessible text.

Icon rules:
- Stroke weight: 1.5–2px
- Size: 16px (small), 20px (medium), 24px (large)
- Color: `#38362F` (neutral-700) for default; `#F59500` (primary) for active state
- Never filled icons except for the FAB icon (white fill on amber)

**3. Empty state illustrations (if used)**
If empty states use illustration rather than icon + text, the style is:
- **Line art only** — no fills, no color blocks. Single-stroke amber (`#F59500`) lines on a light background.
- **Minimal and literal** — a fuel pump, a car, a speedometer. No metaphorical or abstract concepts.
- **Small and supporting** — never hero-sized. The illustration supports the copy; the copy does the work.
- **No characters or people** — Mileo doesn't have mascots.

### What Mileo Avoids

- ❌ Stock photography (cars, people at pumps, lifestyle imagery)
- ❌ Colorful blob illustrations or gradient art
- ❌ 3D rendered objects
- ❌ Anything that requires explanation
- ❌ Backgrounds that compete with content for attention
- ❌ Decorative dividers, ornamental separators, or visual clutter

### Pattern / Texture (Optional)

A subtle noise texture overlay (3–5% opacity) may be applied to `surface-background` on hero cards to add warmth without distraction. This is optional and should be used only where it meaningfully adds depth.

---

## 7. UI Personality — Motion, Interactions & Micro-copy

### Motion Philosophy

Mileo moves with purpose. Every animation serves a function: confirming an action, providing feedback, or guiding attention. No animation should draw attention to itself.

**The golden rule:** If you can't explain why an animation is there, remove it.

---

### Duration Scale

| Token | Duration | Use |
|---|---|---|
| `instant` | 80ms | Toggle state changes, live cost update |
| `fast` | 150ms | Button press feedback, input focus |
| `normal` | 250ms | Screen transitions, dropdown open |
| `slow` | 400ms | Save confirmation, success spring |
| `slower` | 600ms | Sheet slide-in, onboarding transitions |

---

### Easing Reference

| Token | Curve | Use |
|---|---|---|
| `ease-default` | `cubic-bezier(0.2, 0, 0, 1)` | Most UI transitions — snappy |
| `ease-spring` | `cubic-bezier(0.34, 1.56, 0.64, 1)` | Save confirmations, Instant Stats appear |
| `ease-in` | `cubic-bezier(0.4, 0, 1, 1)` | Elements leaving the screen |
| `ease-out` | `cubic-bezier(0, 0, 0.2, 1)` | Elements entering the screen |

The spring easing is Mileo's signature motion moment. It is used **only** for the Instant Stats reveal — the moment after a fill-up is saved. The slight overshoot signals completion and reward.

---

### Key Motion Moments

**1. Total Cost live update (Quick Log)**
As the user types fuel price or volume, the Total Cost figure updates immediately (`instant`, 80ms). No animation — just instant recalculation. Speed is the feature.

**2. Save Fill-Up → Instant Stats reveal**
The Instant Stats card enters with `ease-spring` at `slow` (400ms). The hero stat (Total Cost) scales up slightly from 0.95 → 1.0. This is the product's peak delight moment. It earns the spring.

**3. Inline validation errors**
Validation errors appear with a quick `ease-out` at `fast` (150ms). No dramatic shake animation — that would feel punishing. Just a clean reveal of the error text below the field.

**4. Toast notifications**
Slide in from the bottom with `ease-out` at `normal` (250ms). Auto-dismiss after 2.5 seconds with `ease-in` at `fast` (150ms). No interaction required.

**5. Bottom sheet / modal**
Slides up with `ease-out` at `slower` (600ms). Backdrop fades in simultaneously. Dismissal is reversed at `normal` (250ms).

**6. FAB press**
Scale to 0.96 on press (`instant`), return to 1.0 on release (`fast`, `ease-spring`). The amber shadow pulses slightly on press.

---

### Interaction Rules

**Tap targets**
All interactive elements must meet a minimum 44×44px tap target (WCAG 2.5.8). The FAB is 56×56px.

**Focus states**
All focusable elements show: `1.5px solid #F59500` border with `rgba(245, 149, 0, 0.25)` glow ring. This is non-negotiable for accessibility.

**Disabled states**
Background: `#E5E3DE` (neutral-200). Text: `#AAA79E` (neutral-400). No opacity tricks — use the explicit disabled color tokens.

**Loading states**
Use skeleton screens, not spinners. Skeleton shapes match the content they represent. Background: `#F2F1EE`. Animated shimmer left-to-right at `slower` (600ms), looping.

**Swipe gestures**
Instant Stats is dismissible by swipe-down. Bottom sheets are dismissible by swipe-down. No other swipe gestures implemented.

---

### Micro-copy Reference

Micro-copy is the small text that lives in the margins of the UI: placeholders, helper text, confirmation labels, toast messages, error explanations. It follows the same Tone of Voice rules — but must also be scannable at a glance.

**Rules specific to micro-copy:**
- Maximum two lines for any error or helper text.
- Placeholder text hints at format, not encouragement.
  > ✅ `Odometer (km)` | ❌ `Enter your odometer reading here!`
- Toast messages: subject + verb, no period.
  > ✅ `Fill-up saved` | `Changes discarded` | `Log deleted`
- Confirmation dialogs: state the consequence, not just the action.
  > ✅ `Delete this fill-up? This can't be undone.` | ❌ `Are you sure you want to delete?`
- Empty states: one Coach prompt + one CTA. Nothing else.
  > ✅ `Log your first fill-up to see your numbers. [Log Fill-Up]`

---

### Component Personality Summary

| Component | Personality Note |
|---|---|
| **FAB** | The loudest element on the screen. Amber with amber glow. Always reachable. Never hidden. |
| **Primary Button** | Full-width where the primary action is clear. Amber. One per screen. |
| **Secondary Button** | Slate background, slate text. Low-key. Supports the primary action — never competes. |
| **Ghost Button** | Text-only with border. Used for destructive-adjacent or low-priority actions (e.g., "Cancel"). |
| **Input fields** | Large numerics (24px mono). Wide tap targets. Amber focus ring. |
| **Cards** | 16px radius. Subtle warm shadow. Slight border (`#F2F1EE`). Content-first — no decorative headers. |
| **Toasts** | Bottom-anchored. Brief. Auto-dismiss only. |
| **Delta indicators** | `↑` with `success-500` for improvement. `↓` with `error-500` for decline. Plain text with no color for neutral. |

---

*This document reflects Mileo v1.0 design system. When the design system is updated, update this document in step. When in doubt, return to Section 1 — The Coach governs everything.*
