# Mileo — User Stories

**Version:** 1.0

**Status:** Draft

---

## Roles

| Role | Description |
|---|---|
| **Driver** | The account owner. The only role. Can be a commuter, gig worker, or any regular driver. |
| **New Driver** | A Driver who has just signed up and has not yet added a vehicle or logged a fill-up. |

---

## Table of Contents

1. [Onboarding](#1-onboarding)
2. [Record a Fill-Up](#2-record-a-fill-up)
3. [Instant Stats](#3-instant-stats)
4. [Dashboard](#4-dashboard)
5. [Fill-Up History](#5-fill-up-history)
6. [Edit a Fuel Log](#6-edit-a-fuel-log)
7. [Delete a Fuel Log](#7-delete-a-fuel-log)
8. [Vehicle Management](#8-vehicle-management)
9. [Account & Auth](#9-account--auth)
10. [Screen Inventory](#screen-inventory)

---

## 1. Onboarding

### US-001 — Welcome and Sign Up

**Role:** New Driver
**Priority:** Urgent

> As a New Driver, I want to create an account quickly so that I can start tracking fuel without a long setup process.

**Acceptance Criteria:**
- [ ] A welcome screen is shown on first app launch
- [ ] The New Driver can sign up using email and password

---

## 2. Record a Fill-Up

### US-010 — Open Log Fill-Up Screen

**Role:** Driver
**Priority:** Urgent

> As a Driver, I want to open the Log Fill-Up screen from the Dashboard so that I can start logging my fill-up.

**Acceptance Criteria:**
- [ ] A prominent **Log Fill-Up** button (FAB or persistent CTA) is visible on the Dashboard when at least one vehicle exists
- [ ] The button is not shown when no vehicles exist
- [ ] The Log Fill-Up screen opens when the button is tapped
- [ ] The default vehicle is pre-selected at the top of the screen

---

### US-011 — Log a Fill-Up (Required Fields)

**Role:** Driver
**Priority:** Urgent

> As a Driver, I want to enter my odometer reading, fuel price, volume filled, and trip distance on the Log Fill-Up screen.

**Acceptance Criteria:**
- [ ] The Log Fill-Up screen displays all required input fields: Odometer, Trip Distance, Fuel Price per Unit, Volume Filled
- [ ] The **Save Fill-Up** button is accessible on the Log Fill-Up screen
- [ ] All field values are editable

---

### US-012 — Auto-Computed Trip Distance

**Role:** Driver
**Priority:** Urgent

> As a Driver, I want the app to automatically calculate my trip distance from my odometer reading so that I don't have to do the math myself.

**Acceptance Criteria:**
- [ ] If a prior Fuel Log exists for the selected vehicle, Trip Distance is auto-computed as `current_odometer − previous_odometer` and displayed read-only before the user types anything
- [ ] An edit icon is visible next to the auto-computed Trip Distance, allowing the user to override it
- [ ] If no prior log exists, Trip Distance is an editable required field with no default value
- [ ] Odometer input validates that the new reading is ≥ the last logged odometer for that vehicle; an inline error is shown if not

---

### US-013 — Live Total Cost Display

**Role:** Driver
**Priority:** Urgent

> As a Driver, I want to see my total cost update in real time as I enter price and volume so that I can confirm the cost before saving.

**Acceptance Criteria:**
- [ ] Total Cost is displayed prominently on the Log Fill-Up screen as a read-only computed field
- [ ] Total Cost updates instantly as the user edits Fuel Price per Unit or Volume Filled
- [ ] Total Cost is never directly editable by the user
- [ ] Total Cost displays in the user's configured currency

---

### US-014 — Full Tank Toggle

**Role:** Driver
**Priority:** Urgent

> As a Driver, I want to indicate whether I filled the tank completely so that the app can accurately calculate my fuel efficiency.

**Acceptance Criteria:**
- [ ] A **Full Tank** toggle is present on the Log Fill-Up screen, defaulting to `true`
- [ ] When set to `false`, efficiency is still computed but flagged with a Coach-tone note: *"Partial fill — efficiency estimate may vary"*
- [ ] The toggle state is saved with the Fuel Log

---

### US-015 — Optional Notes on a Fill-Up

**Role:** Driver
**Priority:** Normal

> As a Driver, I want to optionally add a note to a fill-up so that I can record context (e.g., "highway trip," "car felt sluggish") without it cluttering the default screen.

**Acceptance Criteria:**
- [ ] A **Notes** field exists on the Log Fill-Up screen, collapsed by default
- [ ] A single tap expands the Notes field
- [ ] The Notes field accepts free text up to 200 characters
- [ ] The Notes field is not required — omitting it does not block saving
- [ ] Notes content is saved with the Fuel Log and visible in the detail screen

---

### US-016 — Adjust Fill-Up Date and Time

**Role:** Driver
**Priority:** Normal

> As a Driver, I want to change the date and time of a fill-up so that I can correct a log I forgot to record at the pump.

**Acceptance Criteria:**
- [ ] Date/time defaults to the current time when the Log Fill-Up screen opens
- [ ] The date/time field is tappable and opens a date-time picker
- [ ] The user cannot set a future date/time
- [ ] The user cannot set a date before the vehicle's `created_at` date
- [ ] The adjusted date/time is saved with the Fuel Log

---

### US-017 — Switch Vehicle on Log Fill-Up Screen

**Role:** Driver
**Priority:** Normal

> As a Driver with multiple vehicles, I want to switch the active vehicle on the Log Fill-Up screen so that I can log for the right car without going back to the Dashboard.

**Acceptance Criteria:**
- [ ] The vehicle selector at the top of the Log Fill-Up screen is tappable
- [ ] Tapping shows a list of all active vehicles
- [ ] Selecting a different vehicle updates the auto-computed Trip Distance based on that vehicle's last log
- [ ] The selected vehicle is saved with the Fuel Log

---

### US-018 — Validation Errors on Log Fill-Up Screen

**Role:** Driver
**Priority:** Urgent

> As a Driver, I want to see inline validation errors on the Log Fill-Up screen so that I know exactly which field needs fixing without losing my entered data.

**Acceptance Criteria:**
- [ ] Tapping **Save Fill-Up** with any required field empty shows an inline error beneath that field (not a modal)
- [ ] Inline errors also appear for: `liters_filled ≤ 0`, `fuel_price ≤ 0`, `trip_distance ≤ 0`, odometer less than previous reading
- [ ] All other field values are preserved when a validation error is shown
- [ ] Errors clear when the user corrects the relevant field

---

## 3. Instant Stats

### US-020 — View Instant Stats After Logging

**Role:** Driver
**Priority:** Urgent

> As a Driver, I want to see my fuel stats after saving a fill-up so that I can see the results of my log.

**Acceptance Criteria:**
- [ ] The Instant Stats screen appears after tapping **Save Fill-Up**
- [ ] The screen displays: Total Cost (prominently), Cost per kilometer, Efficiency (km/L), and Volume Filled
- [ ] No upgrade prompts or app-rating CTAs appear on this screen

---

### US-021 — Comparison to Previous Fill-Up

**Role:** Driver
**Priority:** High

> As a Driver, I want to see how my latest fill-up compares to my previous one so that I can immediately notice if my efficiency has changed.

**Acceptance Criteria:**
- [ ] When a prior Fuel Log exists for the same vehicle, the Instant Stats screen shows delta indicators (↑↓ with percentage) for both Cost and Efficiency vs. the previous fill-up
- [ ] When no prior log exists (first fill-up for the vehicle), deltas are not shown — only single-fill stats are displayed
- [ ] A partial-fill flag is shown with a caveat note when `is_full_tank = false`

---

### US-022 — Dismiss Instant Stats

**Role:** Driver
**Priority:** Urgent

> As a Driver, I want to dismiss Instant Stats and return to the Dashboard easily so that I can close the app and get on with my day.

**Acceptance Criteria:**
- [ ] A **Done** button is present on the Instant Stats screen
- [ ] The screen is also dismissible via a downward swipe gesture
- [ ] Both actions return the user to the Dashboard with updated stats

---

## 4. Dashboard

### US-030 — View Monthly Fuel Summary

**Role:** Driver
**Priority:** Urgent

> As a Driver, I want to see my current month's fuel spend and related stats on the home screen so that I can track my fuel costs.

**Acceptance Criteria:**
- [ ] The Dashboard displays a "This Month" panel showing: Total Spend, Total Volume, Average Efficiency, Fill-Up Count
- [ ] "This Month" is scoped to the current calendar month (1st of month → today)
- [ ] Average Efficiency is only displayed when ≥2 full-tank logs exist in the selected period

---

### US-031 — Compare This Month to Last Month

**Role:** Driver
**Priority:** High

> As a Driver, I want to see how my current month's fuel spend and efficiency compare to last month so that I can spot trends without digging into history.

**Acceptance Criteria:**
- [ ] A delta line below the "This Month" panel shows: `+/-X% in spend` and `+/-X% in efficiency` vs. the prior full calendar month
- [ ] When no prior month data exists (e.g., account is less than a month old), the delta line is hidden
- [ ] Delta values are color-coded or use directional arrows (↑↓) for quick scanning

---

### US-032 — View Recent Fill-Ups on Dashboard

**Role:** Driver
**Priority:** High

> As a Driver, I want to see my last 5 fill-ups on the Dashboard so that I can quickly review recent activity without navigating away.

**Acceptance Criteria:**
- [ ] The Dashboard shows the 5 most recent Fuel Logs for the selected vehicle
- [ ] Each row shows: Date, Total Cost, Volume, Efficiency (if available)
- [ ] Tapping a row opens the full Fuel Log detail screen
- [ ] A "View all" link navigates to the full History screen

---

### US-033 — Dashboard Empty States

**Role:** Driver
**Priority:** Urgent

> As a Driver on a fresh account, I want to see a helpful prompt on the Dashboard so that I know exactly what to do next rather than seeing a blank screen.

**Acceptance Criteria:**
- [ ] When no vehicles exist, the Dashboard displays a Coach-tone prompt: *"Add your first vehicle to start tracking."* with a CTA to Add Vehicle
- [ ] When a vehicle exists but no Fuel Logs have been logged, the Dashboard displays: *"Log your first fill-up to see your numbers."* with a CTA to log a fill-up
- [ ] The Log Fill-Up FAB is not shown when no vehicles exist
- [ ] Empty states are not shown once at least one log exists for the active vehicle

---

### US-034 — Switch Vehicles on Dashboard

**Role:** Driver
**Priority:** Normal

> As a Driver with multiple vehicles, I want to switch between vehicles on the Dashboard so that I can see stats for each car without navigating to a separate screen.

**Acceptance Criteria:**
- [ ] The active vehicle name is always displayed on the Dashboard
- [ ] A vehicle switcher is visible only when more than one active vehicle exists
- [ ] Switching vehicles updates all Dashboard stats and the recent fill-ups list without leaving the screen
- [ ] The selected vehicle persists as the active view until the user changes it again or logs out

---

## 5. Fill-Up History

### US-040 — View Full Fill-Up History

**Role:** Driver
**Priority:** High

> As a Driver, I want to browse all my past fill-ups in a scrollable list so that I can review my logging history at any time.

**Acceptance Criteria:**
- [ ] The History screen shows all Fuel Logs for the selected vehicle, sorted newest first by default
- [ ] Each row shows: Date, Total Cost, Volume, Efficiency (or a partial-fill indicator if applicable)
- [ ] The list paginates or loads lazily — it does not fetch all records in a single request
- [ ] Partial-fill logs display a visible indicator (e.g., a half-fill icon)

---

### US-041 — Filter History by Date Range

**Role:** Driver
**Priority:** Normal

> As a Driver, I want to filter my fill-up history by date range so that I can review spending for a specific period (e.g., last month, last 3 months).

**Acceptance Criteria:**
- [ ] Date range presets are available: This Month, Last Month, Last 3 Months, All Time
- [ ] A custom date range picker is available for arbitrary ranges
- [ ] Filters apply immediately and update the displayed list without a full page reload
- [ ] Filter state persists while the user is on the History screen; it resets when they navigate away

---

### US-041a — Sort History by Date

**Role:** Driver
**Priority:** High

> As a Driver, I want to sort my fill-up history by date so that I can view logs in chronological order (newest or oldest first).

**Acceptance Criteria:**
- [ ] A sort control is visible at the top of the History screen
- [ ] A dropdown or button selector allows choosing between "Date" and "Efficiency" sort fields
- [ ] For Date sort: two order options are available: "Newest First" (DESC) and "Oldest First" (ASC)
- [ ] The default sort is "Date, Newest First"
- [ ] Sort order is reflected immediately in the list view
- [ ] Sort state persists while on the History screen; resets on navigation away

---

### US-041b — Sort History by Efficiency

**Role:** Driver
**Priority:** Normal

> As a Driver, I want to sort my fill-up history by fuel efficiency so that I can spot my best and worst fuel economy runs at a glance.

**Acceptance Criteria:**
- [ ] The sort control allows selecting "Efficiency" as a sort field
- [ ] For Efficiency sort: two order options are available: "Best First" (highest L/100km, DESC) and "Worst First" (lowest L/100km, ASC)
- [ ] Partial-fill logs (where `is_full_tank = false`) are still included but sorted with a visual caveat
- [ ] Sort order is reflected immediately in the list view
- [ ] Sort state persists while on the History screen; resets on navigation away

---

### US-042 — View Full Fill-Up Detail

**Role:** Driver
**Priority:** High

> As a Driver, I want to tap a fill-up in the history list to see its full details so that I can review all fields including notes and computed stats.

**Acceptance Criteria:**
- [ ] Tapping any row in History opens the Fuel Log Detail screen
- [ ] The Detail screen shows all stored fields: date/time, vehicle, odometer, trip distance, price per unit, volume filled, total cost, cost per kilometer, efficiency, notes, is full tank
- [ ] The Detail screen shows the comparison delta to the prior fill-up (if one exists)
- [ ] **Edit** and **Delete** actions are accessible from the Detail screen

---

## 6. Edit a Fuel Log

### US-050 — Edit an Existing Fill-Up

**Role:** Driver
**Priority:** High

> As a Driver, I want to edit a previously logged fill-up so that I can correct a mistake without deleting and re-entering the record.

**Acceptance Criteria:**
- [ ] The Edit screen is accessible via the **Edit** button on the Fuel Log Detail screen
- [ ] The Edit screen is pre-populated with all fields from the existing log
- [ ] The Edit screen layout is identical to the Log Fill-Up screen (no new UI to learn)
- [ ] All validations from the Log Fill-Up screen apply on edit
- [ ] Computed fields (Total Cost, Efficiency) update in real time as the user edits fields
- [ ] Tapping **Save Changes** persists the update and returns to the Detail screen with updated values
- [ ] A success toast confirms the save
- [ ] Tapping **Cancel** discards all changes and returns to the Detail screen unchanged

---

### US-051 — Downstream Recomputation on Edit

**Role:** Driver
**Priority:** High

> As a Driver, I want the app to automatically recompute affected stats in subsequent logs when I edit an odometer reading so that my efficiency history stays accurate.

**Acceptance Criteria:**
- [ ] When an odometer reading is edited, the Trip Distance and Efficiency of the immediately following Fuel Log for the same vehicle are recomputed silently
- [ ] The recomputation does not trigger a visible notification or require user action
- [ ] The updated values are reflected immediately when the user views the affected subsequent log

---

## 7. Delete a Fuel Log

### US-060 — Delete a Fill-Up

**Role:** Driver
**Priority:** High

> As a Driver, I want to delete an incorrectly logged fill-up so that it no longer affects my stats or history.

**Acceptance Criteria:**
- [ ] The **Delete** action is accessible from the Fuel Log Detail screen
- [ ] Tapping Delete shows a confirmation dialog: *"Delete this fill-up? This can't be undone."*
- [ ] Deletion only proceeds after the user explicitly confirms
- [ ] After deletion, the user is returned to the History screen
- [ ] The deleted log no longer appears in History, Dashboard stats, or exports
- [ ] Undo is not available — the confirmation dialog is the only safeguard

---

### US-061 — Downstream Recomputation on Delete

**Role:** Driver
**Priority:** High

> As a Driver, I want the app to automatically recompute affected stats in adjacent logs when I delete a fill-up so that my history remains consistent.

**Acceptance Criteria:**
- [ ] When a Fuel Log is deleted and it falls between two other logs for the same vehicle, the following log's Trip Distance is recomputed using the remaining prior log's odometer reading
- [ ] The recomputed values are reflected in the History screen and Dashboard without manual refresh

---

## 8. Vehicle Management

### US-070 — Add a Vehicle

**Role:** Driver
**Priority:** Urgent

> As a Driver, I want to add a new vehicle to my account so that I can track fuel for more than one car.

**Acceptance Criteria:**
- [ ] An **Add Vehicle** option is accessible from the Vehicle management screen (reachable from the Dashboard vehicle switcher or account menu)
- [ ] Vehicle Name and Fuel Type are the only required fields
- [ ] Make, Model, and Year are optional
- [ ] A Driver cannot exceed 10 vehicles; an informative message is shown if the limit is reached
- [ ] The new vehicle appears immediately in the Dashboard vehicle selector
- [ ] Adding a vehicle does not change the current default vehicle

---

### US-071 — Set a Default Vehicle

**Role:** Driver
**Priority:** Normal

> As a Driver with multiple vehicles, I want to set a default vehicle so that the correct car is pre-selected every time I log a fill-up.

**Acceptance Criteria:**
- [ ] The Vehicle management screen shows which vehicle is currently set as default
- [ ] The Driver can change the default vehicle from this screen
- [ ] The new default is immediately reflected in the Log Fill-Up vehicle selector
- [ ] Only one vehicle can be the default at a time

---

### US-072 — Archive a Vehicle

**Role:** Driver
**Priority:** Normal

> As a Driver who no longer uses a vehicle, I want to archive it so that it's hidden from active views without losing my historical data.

**Acceptance Criteria:**
- [ ] An **Archive** option is available on the Vehicle detail or management screen
- [ ] Archived vehicles are hidden from the Dashboard vehicle selector and Log Fill-Up vehicle picker
- [ ] All Fuel Logs for an archived vehicle are preserved and accessible via the History screen with a filter for archived vehicles
- [ ] Archiving a vehicle that is currently the default automatically prompts the user to select a new default (if other active vehicles exist)

---

### US-073 — Delete a Vehicle

**Role:** Driver
**Priority:** Low

> As a Driver, I want to delete a vehicle I added by mistake so that it doesn't clutter my vehicle list.

**Acceptance Criteria:**
- [ ] A vehicle with no Fuel Logs can be permanently deleted
- [ ] A vehicle with existing Fuel Logs cannot be deleted — only archived; an informative message explains this
- [ ] Deletion requires confirmation (same pattern as Fuel Log deletion)

---

## 9. Account & Auth

### US-100 — Log In to Existing Account

**Role:** Driver
**Priority:** Urgent

> As a returning Driver, I want to log in with my username and password so that I can access my data.

**Acceptance Criteria:**
- [ ] A Log In screen is accessible from the Welcome screen
- [ ] Email and password fields are present
- [ ] Incorrect credentials show an inline error
- [ ] Successful login navigates directly to the Dashboard

---

### US-101 — Stay Logged In

**Role:** Driver
**Priority:** Urgent

> As a Driver, I want to remain logged in between app sessions so that I don't have to re-authenticate every time I open the app.

**Acceptance Criteria:**
- [ ] After successful login, the session persists across app restarts
- [ ] The user is not prompted to log in again unless the session has expired or they explicitly log out
- [ ] Session tokens are stored securely (Keychain on iOS, Keystore on Android)

---

### US-102 — Log Out

**Role:** Driver
**Priority:** High

> As a Driver, I want to log out of my account so that I can secure my data if I share a device.

**Acceptance Criteria:**
- [ ] A **Log Out** option is accessible from the account menu
- [ ] Logging out clears the session and navigates to the Welcome screen
- [ ] Local cached data is cleared on logout

---

## Screen Inventory

| Screen ID | Screen Name | Role | Trigger |
|---|---|---|---|
| **S01** | Welcome / Splash | New Driver | First app launch (no active session) |
| **S02** | Sign Up | New Driver | Tap "Get Started" on S01 |
| **S03** | Log In | Driver | Tap "Log In" on S01 |
| **S06** | Dashboard | Driver | After sign-up, after login, after completing a fill-up log |
| **S07** | Log Fill-Up | Driver | Tap "Log Fill-Up" CTA from S06 |
| **S08** | Instant Stats | Driver | Successful save from S07 |
| **S09** | Fill-Up History | Driver | Tap "View all" on S06, or navigate via main navigation |
| **S10** | Fuel Log Detail | Driver | Tap any fill-up row on S09 or S06 recent list |
| **S11** | Edit Fuel Log | Driver | Tap "Edit" on S10 |
| **S12** | Delete Fuel Log — Confirmation Dialog | Driver | Tap "Delete" on S10 |
| **S13** | Vehicle Management | Driver | Navigate from Dashboard vehicle switcher or account menu |
| **S14** | Add Vehicle | Driver | Tap "Add Vehicle" on S13, or from the Dashboard no-vehicle empty state |
| **S15** | Vehicle Detail / Edit | Driver | Tap a vehicle on S13 |

---

*This document is derived from docs/product/01-product-requirements.md v1.0. All screen IDs and story IDs should be referenced in design tickets and engineering tasks. When the PRD changes, update this document accordingly.*
