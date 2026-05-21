# Mileo — Product Requirements Document

**Version:** 1.0  
**Status:** Final  
**Project Type:** Web App (CRUD)  
**Last Updated:** April 2026

---

## Glossary

| Term | Definition |
|---|---|
| **Fill-Up** | A single fueling event — the core unit of data in Mileo. Created each time a user refuels a vehicle. |
| **Fuel Log** | The complete record of a single Fill-Up: odometer reading, trip details, fuel price, liters filled, and computed stats. |
| **Instant Stats** | The computed metrics displayed immediately after a Fill-Up is logged: cost per liter, cost per kilometer, fuel efficiency, and total spend. |
| **Dashboard** | The home screen overview showing current-month vs. prior-period stats at a glance. |
| **Vehicle** | A user-owned car, motorcycle, or motorized vehicle being tracked in Mileo. |
| **Efficiency** | Fuel consumption expressed as km/L or L/100km. Lower or higher can be better depending on the display format. |
| **Odometer** | The cumulative distance reading on the vehicle's dashboard at time of fill-up. |
| **Trip Distance** | The distance driven since the last fill-up (Point A → Point B), either entered manually or computed from consecutive odometer readings. |
| **Log Completion Time** | The elapsed time from when a user opens the Log Fill-Up screen to when they see their Instant Stats. |
| **The Coach** | Mileo's brand personality: direct, honest, practical — like a motivating coach, not a judge. |
| **Data Export** | A downloadable CSV of all Fuel Logs for a given vehicle or date range. |
| **Multi-Vehicle** | The ability for a single user account to track more than one Vehicle. |

---

## 1. What the Product Is (and Is Not)

### What It Is

Mileo is a **frictionless fuel tracking web app** that lets drivers log fill-ups and see their fuel spending and efficiency. It is a personal finance tool for regular drivers who want visibility into one specific recurring cost — fuel — without friction, complexity, or judgment.

Every design decision is governed by a single principle: **the Fill-Up is quick and simple, the user logs their data and gets on with their life.**

### What It Is Not

| Not This | Why |
|---|---|
| A vehicle maintenance tracker | Oil changes, tire rotations, and service logs are out of scope. Forever. |
| A fleet management tool | Mileo is for individuals, not businesses or fleets. |
| A GPS or route tracker | No location tracking. No passive data collection. |
| A tax compliance or mileage deduction tool | No tax or mileage deduction reports. Fuel cost awareness is the job, not tax optimization. |
| A community or social platform | No user comparisons, leaderboards, or social feeds. |
| A gas price aggregator | Users enter the price they paid. Mileo does not crowdsource pump prices. |
| A multi-expense tracker | Tolls, insurance, parking, and carwash logs are out of scope. |
| A subscription-bait app | Free to use. No surprise paywalls or forced upgrades. |

---

## 2. Core Product Principles

These principles govern every feature decision, UX choice, and trade-off in Mileo.

1. **Speed above all.** If a feature adds friction to the log flow, it doesn't ship. Keep the logging process simple and direct.

2. **One job, done completely.** Mileo tracks fuel. Nothing else. Scope creep is product death — any feature that doesn't directly serve logging or analyzing fuel is rejected.

3. **Instant reward.** After every Fill-Up, the user sees something useful immediately. Stats are never behind a paywall, a second fill-up, or a loading state.

4. **Coach tone throughout.** All copy, empty states, confirmations, and nudges use The Coach voice: direct, honest, supportive. Never preachy, never guilt-inducing.

5. **Responsive web app.** Designed to work seamlessly across all device sizes and browsers.

6. **No dark patterns.** No ads in the free tier. No surprise paywalls after onboarding. No nagging to upgrade. What's free stays free.

7. **Data is the user's.** All logged data is exportable. Users can leave at any time with their full history.

---

## 3. Target Users

### Primary: "The Practical Commuter"

**Profile:** Regular car owner, 25–40 years old, drives 5–6 days/week. Pays for fuel out of pocket. Has a gut sense they spend a lot on fuel but can't quote a number. Has tried spreadsheets or other apps and abandoned them due to friction.

**Core need:** Know, at a glance, what fuel costs per month. Log quickly and move on.

**Success state:** Opens Mileo, logs a fill-up, glances at updated monthly cost, closes app.

**Representative persona:** Marco, 31, Cebu — junior professional, one car, moderate commute.

### Secondary: "The Gig Worker Who Needs to Know"

**Profile:** Rideshare driver or delivery courier, 22–35, fills up 2–3 times/week. Fuel is a direct cost of work. Needs records for personal cost-of-work awareness. Has been burned by complicated or expensive tracking apps.

**Core need:** Fast log every fill-up. Clear monthly total. Optional export for personal records.

**Important constraint:** The gig worker wins Mileo by default (speed + free). Mileo does not add GPS or tax-compliance features to serve her. Those are different products.

---

## 4. Core Entities

### 4.1 User

The person who owns the Mileo account.

| Field | Type | Notes |
|---|---|---|
| `id` | INT | Primary key, auto-increment |
| `email` | string | Unique, used for auth |
| `password` | string | Hashed |
| `first_name` | string | Optional, display only |
| `last_name` | string | Optional, display only |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 4.2 Vehicle

A car or motorized vehicle owned by the user.

| Field | Type | Notes |
|---|---|---|
| `id` | INT | Primary key, auto-increment |
| `user_id` | INT | FK → User |
| `name` | string | User-defined label (e.g., "My Civic", "Work Van") |
| `make` | string | Optional (e.g., Honda) |
| `model` | string | Optional (e.g., Civic) |
| `year` | integer | Optional (e.g., 2019) |
| `fuel_type` | enum | `gasoline`, `diesel`, `lpg`, `electric` |
| `color` | string | Optional |
| `plate_number` | string | Optional |
| `is_default` | boolean | The vehicle pre-selected when logging a fill-up |
| `status` | enum | `active`, `archived` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Rules:**
- A User needs at least one active Vehicle to log a fill-up.
- The first Vehicle created is automatically set as `is_default = true`.
- Archived Vehicles are hidden from the Dashboard and Log Fill-Up screen but their Fuel Logs remain visible in history.
- Maximum 10 Vehicles per User.

### 4.3 Fuel Log

The record of a single Fill-Up event. This is the central entity of Mileo.

| Field | Type | Notes |
|---|---|---|
| `id` | INT | Primary key, auto-increment |
| `vehicle_id` | INT | FK → Vehicle |
| `user_id` | INT | FK → User (denormalized for query performance) |
| `logged_at` | timestamp | When the fill-up occurred (user-set, defaults to now) |
| `odometer_reading` | decimal | Cumulative vehicle distance at time of fill-up |
| `trip_distance` | decimal | Distance since last fill-up (A→B). Auto-computed if prior log exists; editable |
| `fuel_price_per_unit` | decimal | Price paid per liter at the pump |
| `volume_filled` | decimal | Amount of fuel added (liters) |
| `is_full_tank` | boolean | Whether the tank was filled completely |
| `total_cost` | decimal | Computed: `fuel_price_per_unit × volume_filled` |
| `cost_per_distance_unit` | decimal | Computed: `total_cost / trip_distance` |
| `efficiency_km_l` | decimal | Computed: `trip_distance / volume_filled` (km/L) |
| `notes` | string | Optional free-text field (max 200 chars) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Computation Rules:**
- `total_cost` = `fuel_price_per_unit × volume_filled` (always computed, not user-entered)
- `trip_distance` = `current_odometer - previous_odometer` if a prior log exists for the same vehicle; otherwise user must enter it manually
- `efficiency_km_l` = `trip_distance / volume_filled` (always computed; partial fills are flagged in the UI but still calculated)

**Validation Rules:**
- `odometer_reading` must be ≥ the previous log's `odometer_reading` for the same Vehicle
- `volume_filled` must be > 0
- `fuel_price_per_unit` must be > 0
- `trip_distance` must be > 0
- `logged_at` cannot be in the future
- `logged_at` cannot be before the Vehicle's `created_at`

---

## 5. Core User Flows

### Flow 1: Onboarding — New User Setup

**Trigger:** User opens Mileo for the first time.

**Steps:**

1. App displays welcome screen.
2. User taps **Get Started**.
3. App requests username and password for account creation.
4. App navigates to Dashboard. Default units are set to km, liters, PHP.
5. Dashboard shows empty state: *"Add your first vehicle to start tracking."* with an Add Vehicle CTA.
6. User adds a vehicle (name + fuel type required). App sets it as the default.
7. Dashboard updates to empty state: *"Log your first fill-up to see your numbers."* with a Log Fill-Up CTA.

**Acceptance Criteria:**
- [ ] Successful sign-up navigates directly to the Dashboard — no forced interstitial screens
- [ ] The Dashboard no-vehicle empty state shows a clear CTA to add a vehicle
- [ ] The Dashboard no-logs empty state shows a clear CTA to log a fill-up
- [ ] No payment, credit card, or subscription prompt appears during onboarding

---

### Flow 2: Record a Fill-Up

**Trigger:** User taps the **Log Fill-Up** button (primary CTA, accessible from Dashboard).

**Steps:**

1. Log Fill-Up screen opens. Selected vehicle is the default vehicle (tappable to switch).
2. User enters **Odometer reading** (pre-filled with last known value).
3. App auto-computes **Trip Distance** from previous log. Displayed with an edit icon if user needs to override.
4. User enters **Fuel Price per liter**.
5. User enters **Liters filled**.
6. `Total Cost` is displayed live as user types (computed field, not editable).
7. **Full tank?** toggle — defaults to `true`.
8. Optional: **Notes** field (collapsed by default, expandable).
9. Optional: **Date/time** field (defaults to now, tappable to adjust).
10. User taps **Save Fill-Up**.
11. App saves the Fuel Log, computes all stats.
12. **Instant Stats screen** appears showing: Total Cost, Cost per kilometer, Efficiency (km/L), and a comparison to the user's previous fill-up (if one exists).
13. User taps **Done** or returns to Dashboard.

**Acceptance Criteria:**
- [ ] All required fields (odometer, price, volume, trip distance) are accessible on the Log Fill-Up screen
- [ ] Total Cost updates in real time as price and volume fields are edited
- [ ] If a previous log exists for the same vehicle, Trip Distance is auto-computed
- [ ] Tapping Save with any required field empty shows an inline validation error (not a modal)
- [ ] The first fill-up for a new vehicle shows Instant Stats without a comparison delta

---

### Flow 3: View Dashboard

**Trigger:** User opens the app (or navigates to the home tab).

**Steps:**

1. Dashboard displays:
   - **Active Vehicle** — vehicle name always shown; switcher visible only when more than one active vehicle exists
   - **This Month** panel: total spend, total liters, avg efficiency, number of fill-ups
   - **vs. Last Month** delta line: `+/-X% in spend`, `+/-X% in efficiency`
   - **Recent Fill-Ups** list: last 5 logs, showing date, cost, efficiency per row
2. User can tap any Fill-Up in the list to view its full detail.
3. User can tap the vehicle switcher (if visible) to change the active vehicle.
4. **Log Fill-Up** button is always visible (FAB or persistent button).

**Acceptance Criteria:**
- [ ] "This Month" stats reflect all Fuel Logs for the current calendar month
- [ ] Switching vehicles updates all stats without navigating away
- [ ] No-vehicle empty state shows a CTA to add a vehicle; Log Fill-Up FAB is hidden
- [ ] No-logs empty state (vehicle exists, no logs) shows a CTA to log a fill-up

---

### Flow 4: View Fill-Up History

**Trigger:** User taps "View all" from the Dashboard recent list, or navigates to History tab.

**Steps:**

1. History screen shows all Fuel Logs for the selected vehicle, sorted by date descending.
2. Each row shows: date, total cost, volume, efficiency.
3. User can filter by: date range, vehicle (if multi-vehicle).
4. User taps a row to view the full Fuel Log detail screen.
5. Detail screen shows all fields including notes, computed stats, and a comparison to the prior fill-up.
6. Detail screen has **Edit** and **Delete** actions.

**Acceptance Criteria:**
- [ ] History list paginates or loads lazily — does not load all records at once
- [ ] Filter by date range works correctly across month/year boundaries
- [ ] Each log row is tappable and opens the correct detail record
- [ ] The detail screen shows all stored and computed fields
- [ ] Edit and Delete actions are accessible from the detail screen

---

### Flow 5: Edit a Fuel Log

**Trigger:** User taps **Edit** from a Fuel Log detail screen.

**Steps:**

1. Edit screen opens pre-populated with all fields from the existing log.
2. User modifies one or more fields.
3. Computed fields (total cost, efficiency) update in real time.
4. User taps **Save Changes**.
5. App validates inputs, saves, returns to detail screen with updated values.
6. If the edited odometer affects the trip distance of the *next* log, the next log's trip distance and efficiency are also recomputed silently.

**Acceptance Criteria:**
- [ ] Edit screen is identical to Log Fill-Up layout (no new UI to learn)
- [ ] All validations from the Log Fill-Up screen apply on edit
- [ ] Saving an edit recomputes downstream logs (next fill-up's trip distance and efficiency)
- [ ] A success toast confirms the save
- [ ] Cancel discards all changes and returns to the detail screen unchanged

---

### Flow 6: Delete a Fuel Log

**Trigger:** User taps **Delete** from a Fuel Log detail screen.

**Steps:**

1. App shows confirmation dialog: *"Delete this fill-up? This can't be undone."*
2. User confirms.
3. Log is deleted. App returns to History screen.
4. If the deleted log was between two other logs, the next log's trip distance is recomputed using the remaining prior log's odometer.

**Acceptance Criteria:**
- [ ] Deletion requires explicit confirmation (no accidental deletes)
- [ ] Deleting a log in the middle of a sequence recomputes the following log's trip distance
- [ ] Deleted logs do not appear in History, Dashboard stats, or exports
- [ ] Undo is not available — confirmation dialog is the safeguard

---

### Flow 7: Add a Vehicle

**Trigger:** User taps **Add Vehicle** from the Vehicle management screen.

**Steps:**

1. Form opens: Name (required), Make, Model, Year, Fuel Type (required).
2. User fills in fields and taps **Save Vehicle**.
3. New Vehicle appears in the vehicle selector on Dashboard.
4. Default vehicle is unchanged (user must manually set a new default).

**Acceptance Criteria:**
- [ ] Name and Fuel Type are the only required fields
- [ ] A User cannot exceed 10 Vehicles
- [ ] New Vehicle appears immediately in the Dashboard vehicle selector
- [ ] Adding a Vehicle does not navigate away from the Dashboard flow unexpectedly

---


## 6. Feature Specifications

### 6.1 Log Fill-Up

**Purpose:** The primary feature for logging fill-ups. Creates a new Fuel Log.

**States:**

| State | Description |
|---|---|
| `idle` | Screen open, all fields empty or pre-populated defaults |
| `typing` | User is actively entering a field; total cost updates live |
| `ready` | All required fields have valid values; Save button is active |
| `saving` | Save tapped; loading indicator shown; fields locked |
| `error` | Validation failed; inline error messages shown per field |
| `success` | Saved; Instant Stats screen shown |

**Rules:**
- Required fields: Odometer, Trip Distance, Fuel Price, Volume Filled
- Total Cost is a live-computed read-only display — never a user input
- Vehicle selector at top — defaults to `is_default` vehicle
- Date/time defaults to now; tappable to change (date picker)
- Full Tank toggle defaults to `true`; when `false`, efficiency is computed but flagged with a note: *"Partial fill — efficiency estimate may vary"*
- Notes field is hidden by default; expandable on tap
- Users can navigate between fields freely

### 6.2 Instant Stats

**Purpose:** Shows computed metrics after a fill-up is logged.

**Displayed Metrics:**
- Total Cost (large, prominent)
- Cost per kilometer
- Efficiency (km/L)
- Volume filled
- Comparison to previous fill-up: delta for Cost and Efficiency with ↑↓ indicator (if prior log exists)

**States:**

| State | Description |
|---|---|
| `first_log` | No previous fill-up exists — show single-fill stats only; no comparison delta |
| `normal` | Previous fill-up exists — show full stats with comparison |
| `partial_tank` | `is_full_tank = false` — efficiency shown with warning note |

**Rules:**
- Instant Stats appear after the fill-up is saved
- The screen is dismissible via a **Done** button or swipe gesture
- No CTAs to upgrade or rate the app on this screen

### 6.3 Dashboard

**Purpose:** The home screen. Gives the user their numbers at a glance.

**Sections:**
1. **Active Vehicle** — vehicle name always shown; switcher visible only when more than one active vehicle exists
2. **This Month Panel** — Total Spend, Total Volume, Avg Efficiency, Fill-Up Count
3. **vs. Last Month** — delta line for spend and efficiency
4. **Recent Fill-Ups** — last 5 logs in a scrollable list

**States:**

| State | Description |
|---|---|
| `no_vehicle` | No vehicles added yet; prompt to add first vehicle; Log Fill-Up FAB hidden |
| `no_logs` | Vehicle exists but no Fuel Logs yet; prompt to log first fill-up |
| `one_log` | One log exists; This Month stats shown; no comparison (no prior month) |
| `normal` | Two or more logs; full Dashboard rendered |
| `loading` | Data fetching; skeleton screen shown |

**Rules:**
- "This Month" = current calendar month (Jan 1 – today)
- "Last Month" = prior full calendar month
- Avg Efficiency only displayed if ≥2 full-tank logs exist in the selected period
- Dashboard refreshes when returning from the Log Fill-Up screen or after edit/delete

### 6.4 Fill-Up History

**Purpose:** Full list of all Fuel Logs for a vehicle.

**Rules:**
- Default sort: newest first (by date, descending)
- Sortable by: **Date** (newest/oldest first) or **Efficiency** (best/worst L/100km first)
- Filterable by: date range (presets: This Month, Last Month, Last 3 Months, All Time; custom range)
- Each row: Date, Total Cost, Volume, Efficiency (if available)
- Tapping a row opens the Full Detail screen
- Partial-fill logs display a visual indicator (e.g., half-filled icon)
- Sort state persists while on the History screen; resets on navigation away

### 6.5 Multi-Vehicle Support

**Purpose:** Allow users to track more than one vehicle.

**Rules:**
- Max 10 vehicles per account
- Each vehicle has its own isolated Fuel Log history and stats
- Dashboard shows one vehicle at a time; vehicle switcher always visible
- One vehicle is marked `is_default` — this is the vehicle pre-selected when logging a fill-up
- User can archive a vehicle (hides it from active views but preserves all data)


---

## 7. Roles and Permissions

Mileo has a single user role: **Driver** (the account owner).

| Action | Driver |
|---|---|
| Create account | ✅ |
| Add Vehicle | ✅ (up to 10) |
| Edit Vehicle | ✅ (own vehicles only) |
| Archive Vehicle | ✅ |
| Delete Vehicle | ✅ (only if no Fuel Logs exist; else archive only) |
| Create Fuel Log | ✅ |
| Edit Fuel Log | ✅ (own logs only) |
| Delete Fuel Log | ✅ (own logs only) |
| View Dashboard | ✅ |
| View History | ✅ |
| Access another user's data | ❌ |


---

## 8. System States

### 8.1 User Account States

| State | Description | Transitions |
|---|---|---|
| `active` | Normal operational state | → `suspended` on policy violation; → `deleted` on user request |
| `suspended` | Account locked by system | → `active` on resolution |
| `deleted` | Account and data deleted | Terminal |

### 8.2 Vehicle States

| State | Description |
|---|---|
| `active` | Vehicle is visible in Dashboard, Log Fill-Up screen, and History |
| `archived` | Vehicle hidden from active UI; historical data preserved and accessible via search/filter |

### 8.3 Fuel Log States

| State | Description |
|---|---|
| `draft` | In-progress entry on the Log Fill-Up screen (not persisted) |
| `saved` | Successfully persisted; appears in History and Dashboard |
| `deleted` | Removed by user; not shown anywhere; downstream logs recomputed |

### 8.4 Computed Stat Availability States

| State | Trigger | UI Behavior |
|---|---|---|
| `unavailable` | First fill-up for vehicle (no trip distance baseline) | Stats displayed except efficiency; note: *"Log one more fill-up for efficiency data"* |
| `partial_fill` | `is_full_tank = false` | Efficiency shown with caveat: *"Partial fill — estimate may vary"* |
| `available` | Full tank, ≥1 prior log exists | Full stats shown |

---

## 9. Success Metrics

### Primary Metrics (Product Health)

| Metric | Why It Matters |
|---|---|
| **30-Day Retention** | Habit formation. One-time users are not the goal. |
| **Fill-Ups per Active User per Month** | Proxy for real-world driving frequency and product engagement. |
| **Log Completion Rate** | Detects friction in the log flow. |

### Secondary Metrics (Health Indicators)

| Metric | Note |
|---|---|
| **Onboarding Completion Rate** | Track signups who add a vehicle and log their first fill-up |
| **Crash-Free Session Rate** | Track for stability |
| **User Satisfaction** | Positive feedback in user interviews and support conversations |

### Qualitative Signals

- User feedback mentions "fast," "simple," or "finally"
- Reddit mentions in r/personalfinance or local car communities
- Users describe Mileo as their fuel tracker (not "one of the apps I use")
- Users report abandoning spreadsheets / Fuelio / pen-and-paper in favor of Mileo

### What Not to Measure

- Revenue / premium conversion (not in scope)
- DAU (too volatile at early stage)
- Feature adoption breadth (wrong optimization target — depth > breadth)
- Community engagement (not applicable)

---

## 10. Data Model Summary

```
User
 └── has many Vehicles
      └── has many Fuel Logs
```

All Fuel Logs belong to exactly one Vehicle. All Vehicles belong to exactly one User. There are no shared entities.

---

## 11. Units

All values are fixed — there are no user-configurable unit preferences.

| Dimension | Unit |
|---|---|
| Distance | km |
| Volume | Liters |
| Currency | PHP |
| Efficiency | km/L |

---

*This document defines Mileo. When in doubt, refer to the Core Product Principles in Section 2.*
