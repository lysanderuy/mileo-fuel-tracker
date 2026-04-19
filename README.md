# Mileo — Frictionless Fuel Tracking

**Mileo** is a fuel tracking web app that turns every fill-up into clear spending and efficiency insights. Log your fuel. Done fast. See your stats instantly, and move on.

## What is Mileo?

Mileo helps regular drivers understand their fuel spending without friction. It's built for one job: make logging a fill-up so fast and easy that you'll actually do it every time—then show you what your fuel costs really are.

### Core Principles

- **Speed above all.** Log a fill-up fast. No complex forms. No unnecessary fields.
- **Instant insights.** See your fuel cost, efficiency, and spending trends immediately after logging.
- **One job, done well.** Mileo tracks fuel. Nothing else. No maintenance logs, no fleet management, no social features.
- **The Coach.** Our tone is direct, honest, and supportive—like a coach, not a judge. We tell you what's happening with your fuel, clearly and practically.

---

## Key Features

### ✓ Quick Fuel Log
Log a fill-up in seconds. Just enter:
- Odometer reading
- Trip distance (A → B)
- Fuel price paid
- Liters filled

One screen. Minimal typing. Done.

### ✓ Instant Stats
The moment you log a fill-up, you see:
- Cost per liter
- Cost per kilometer
- Fuel efficiency (L/100km)
- Total monthly spending

No waiting. No second screen. Stats appear immediately.

### ✓ Simple Dashboard
Overview your fuel data at a glance:
- Current month vs. previous month
- Spending trends
- Efficiency trends
- Quick access to your full log history

### ✓ Multi-Vehicle Support
Track multiple vehicles in one account. Switch between cars and see each one's stats independently. Add, archive, or set a default vehicle.

### ✓ Full History with Sort & Filter
View all your past fill-ups with flexible sorting:
- **Sort by Date** – See your most recent fill-ups first, or browse chronologically
- **Sort by Efficiency** – Find your best and worst fuel economy runs instantly
- **Filter by Date Range** – View This Month, Last Month, Last 3 Months, or custom ranges

### ✓ Data Export
Download all your fuel logs as CSV. Your data is yours—take it with you anytime.

### ✓ Edit & Delete Logs
Correct mistakes or remove incorrect entries. Editing an odometer automatically recalculates affected logs.

---

## Who Should Use Mileo?

**Primary Users:**
- Regular car owners and commuters who drive 5–6 days per week
- Anyone who wants to know what fuel really costs them per month
- Cost-conscious drivers tracking expenses

**Secondary Users:**
- Gig workers and delivery drivers tracking vehicle costs
- Anyone logging fuel for reimbursement or tax purposes

---

## What Mileo Is Not

| Not This | Why |
|---|---|
| A vehicle maintenance tracker | Oil changes, tire rotations, and service logs are out of scope. |
| A fleet management tool | Mileo is for individuals, not businesses. |
| A GPS or route tracker | No location tracking. No passive data collection. |
| A tax compliance or mileage deduction tool | No tax reports or mileage deductions. Fuel cost awareness is the job. |
| A community or social platform | No user comparisons, leaderboards, or social feeds. |
| A gas price aggregator | Users enter the price they paid. Mileo does not crowdsource pump prices. |
| A multi-expense tracker | Tolls, insurance, parking, and carwash logs are out of scope. |
| A subscription-bait app | The free tier is the full product. No paywalls or upgrade prompts. |

---

## How It Works

### 1. Create Account
Sign up with email. One account can track multiple vehicles.

### 2. Quick Log
Open Mileo at the pump:
- Enter odometer
- Enter trip distance
- Enter price per liter
- Enter liters filled
- Tap "Log Fill-Up"

Done fast.

### 3. See Your Stats
Instant stats appear:
- "You paid ₱60/liter"
- "Your efficiency: 8.2 L/100km"
- "Monthly spend: ₱4,200"

### 4. Track Over Time
Visit the Dashboard to see:
- Month-over-month trends
- Efficiency changes
- Spending patterns

### 5. Export Your Data
Download your logs as CSV anytime. No lock-in.

---

## Core Principles

Every feature decision in Mileo is governed by these principles:

1. **Speed is a hard constraint.** If it adds time to logging, it doesn't ship.
2. **One job, done completely.** Fuel tracking. Nothing else.
3. **Instant reward.** Stats appear immediately after every log.
4. **The Coach tone.** Direct, honest, supportive. Never preachy.
5. **No dark patterns.** No ads, no surprise paywalls, no nagging to upgrade.
6. **Your data is yours.** Everything is exportable. Leave anytime.

---

## Tech Stack

- **Frontend:** Vanilla HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Local Development:** XAMPP
- **Deployment:** [TBD]

---

## Core Features (Final)

Mileo is a complete fuel tracking application with all essential functionality:

**Fuel Log Management**
✓ Create fuel logs (Quick Log — 4 required fields)  
✓ Read logs with instant stats (cost per liter, cost per km, efficiency)  
✓ Update logs (with downstream recalculation)  
✓ Delete logs (with confirmation)  

**Dashboard & Analytics**
✓ Current month vs. previous month comparison  
✓ Monthly spending trends and efficiency trends  
✓ Recent fill-ups overview  

**History & Organization**
✓ Full fill-up history (newest first by default)  
✓ Sort by date (newest/oldest)  
✓ Sort by efficiency (best/worst L/100km)  
✓ Filter by date range (This Month, Last Month, Last 3 Months, All Time, custom)  

**Vehicle Management**
✓ Multi-vehicle support (up to 10 vehicles per account)  
✓ Add, edit, and archive vehicles  
✓ Set default vehicle for Quick Log  
✓ View stats per vehicle independently  

**Data & Account**
✓ Export logs as CSV (all fields, respects unit preferences)  
✓ User account signup and login  
✓ Session persistence  
✓ Password reset  
✓ Account deletion  

**User Settings**
✓ Change display name  
✓ Configure units (distance, volume, currency)  
✓ Set default vehicle

---

## Getting Started

### Prerequisites
- **XAMPP** installed on your system (Apache, MySQL, PHP)
- **Git** for version control

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/lysanderuy/mileo-fuel-tracker.git
   cd mileo-fuel-tracker
   ```

2. **Set up XAMPP with public folder:**
   - **Option A (Recommended):** Configure XAMPP to point to the `public/` folder
     - Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf` (Windows)
     - Or edit `/Applications/XAMPP/xamppfiles/etc/httpd/conf/extra/httpd-vhosts.conf` (Mac)
     - Point the virtual host DocumentRoot to your `mileo-fuel-tracker/public` folder
   
   - **Option B (Simple):** Copy only the `public/` folder to htdocs
     - Copy `mileo-fuel-tracker/public` → `C:\xampp\htdocs\mileo-fuel-tracker-public`
     - Keep the rest of the project (app/, config/, database/) locally outside htdocs for security

3. **Start XAMPP:**
   - Open XAMPP Control Panel
   - Start **Apache** and **MySQL** services

4. **Import the database:**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create a new database named `mileo_db`
   - Import the SQL file from `/database/schema.sql`

### Running Locally
Open your browser and navigate to:
```
http://localhost/mileo-fuel-tracker-public
```
(or whatever you configured your DocumentRoot to point to)

The `public/index.php` entry point will route all requests to your application.

### Database Configuration
Edit `config/db.php` if you need to change database credentials:
```php
$host = 'localhost';
$db_name = 'mileo_db';
$user = 'root';
$password = ''; // XAMPP default is empty
```

---

## Project Structure

```
mileo-fuel-tracker/

## PUBLIC (Only folder exposed to browser / htdocs)
├── public/
│   ├── index.php           # Entry point / router
│   ├── css/
│   │   ├── styles.css      # Main stylesheet
│   │   ├── responsive.css  # Responsive styles for all device sizes
│   │   └── design-tokens.css # Color, spacing, typography variables
│   ├── js/
│   │   ├── app.js          # Main application logic
│   │   ├── fuel-log.js     # Fuel logging functionality
│   │   ├── dashboard.js    # Dashboard stats and display
│   │   ├── utils.js        # Helper functions
│   │   └── api-client.js   # AJAX calls to backend
│   └── assets/
│       ├── icons/          # SVG and PNG icons
│       ├── images/         # Images
│       └── fonts/          # Custom fonts if needed

## APPLICATION LOGIC
├── app/
│   │
│   ├── pages/              # UI pages (views)
│   │   ├── dashboard.php   # Dashboard / home
│   │   ├── quick-log.php   # Quick log form page
│   │   ├── history.php     # View all fuel logs
│   │   ├── vehicles.php    # Multi-vehicle management
│   │   └── settings.php    # User settings
│   │
│   ├── api/                # Backend API endpoints (controllers)
│   │   └── logs/
│   │       ├── create.php  # Create fuel log (POST)
│   │       ├── read.php    # Fetch fuel logs (GET) with sorting support
│   │       │               # Params: sort_by (date|efficiency), order (ASC|DESC)
│   │       ├── update.php  # Update fuel log (PUT)
│   │       ├── delete.php  # Delete fuel log (DELETE)
│   │       ├── stats.php   # Get dashboard stats
│   │       └── export.php  # Export data to CSV
│   │
│   ├── includes/           # Reusable UI components
│   │   ├── header.php      # Header component
│   │   ├── footer.php      # Footer component
│   │   └── navbar.php      # Navigation bar
│   │
│   ├── models/             # Database logic
│   │   ├── Log.php         # Fuel log model
│   │   └── Vehicle.php     # Vehicle model
│   │
│   └── helpers/            # Utility functions
│       ├── response.php    # API response formatting
│       ├── validation.php  # Input validation
│       └── utils.php       # General utilities

## CONFIGURATION
├── config/
│   └── db.php              # Database connection config

## DATABASE
├── database/
│   └── schema.sql          # Database schema and initial data

## PROJECT
├── .gitignore              # Git ignore rules
├── README.md               # This file
└── package.json            # (Optional) For build tools if needed later
```

---

## Design Philosophy

Mileo's design is guided by:
- **Minimal UI.** Only what's necessary appears on screen.
- **Clear labels.** Field names are explicit, no assumptions.
- **Instant feedback.** Every action gets an immediate response.
- **Coach tone in copy.** Supportive, direct, no jargon.

---

## Brand Voice: The Coach

Mileo talks like a coach—direct, honest, practical, and supportive:

**What The Coach does:**
- Tells you straight: "You're spending ₱15,000/month on fuel"
- Motivates without judgment: "Your efficiency improved 12% this month"
- Stays practical: No fluff, no unnecessary complexity
- Supports you: Honest feedback to help you understand your driving costs

**What The Coach doesn't do:**
- Shame or guilt-trip
- Over-explain or patronize
- Hide the numbers
- Make false promises

---

## Documentation

- **[Implementation Guide](docs/technical/01-implementation-guide.md)** – Complete step-by-step guide to building Mileo with the new structure
- **[Database Schema](docs/technical/02-database-schema.md)** – Database structure, tables, and sorting column implementation
- **[API Documentation](docs/technical/03-api-documentation.md)** – Complete guide to all API endpoints, including sorting and filtering parameters
- **[Product Requirements](docs/product/02-product-requirements.md)** – Feature specifications, flows, and business rules
- **[User Stories](docs/product/03-user-stories.md)** – Detailed user stories and acceptance criteria (source for dev tickets)
- **[Design & Brand](docs/design)** – Brand guidelines and design tokens

---

## Questions?

For support or questions about Mileo, reach out to the development team or check the docs folder for detailed guides.