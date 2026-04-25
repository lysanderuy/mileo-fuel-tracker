# Mileo

Mileo is the coach in your glove box for fuel costs. It helps drivers log fill-ups quickly, understand what fuel is really costing them, and keep track of their vehicles without friction.

## What is Mileo?

Mileo helps drivers track fuel without turning it into a chore. It gives you a fast way to log fill-ups, see your spending, and keep your vehicles organized in one place.

### Core Principles

- **Speed above all.** Logging should be quick enough to do at the pump.
- **Instant insight.** Users should see useful numbers without extra screens or waiting.
- **One job, done well.** Mileo stays focused on fuel tracking, not broad vehicle management.
- **Coach tone.** Copy and feedback should be direct, practical, and supportive.
- **No dark patterns.** No surprise paywalls, no pressure, no noise.

### Who Should Use Mileo

- **Regular drivers.** People who want a simple way to understand monthly fuel costs.
- **Commuters.** Drivers who want a clear record of what each fill-up costs.
- **Gig workers.** Anyone who needs a quick, repeatable fuel log for daily driving.
- **Cost-conscious owners.** Users who want cleaner visibility into vehicle spending and efficiency.

### Key Features

- **Quick fuel logging.** Capture a fill-up with minimal friction.
- **Dashboard overview.** See stats and recent fill-ups at a glance.
- **Vehicle management.** Add, edit, archive, restore, and delete vehicles.
- **Archive support.** Keep inactive vehicles out of the way without losing their data.
- **Room to grow.** The current structure leaves space for the future fuel-log flow and history views.

## Project Structure (Working Copy)

```text
mileo/
├── app/
│   ├── api/
│   │   ├── auth/
│   │   │   ├── login.php
│   │   │   ├── logout.php
│   │   │   └── signup.php
│   │   └── vehicles/
│   │       ├── archive.php
│   │       ├── create.php
│   │       ├── delete.php
│   │       ├── list.php
│   │       └── update.php
│   ├── includes/
│   │   ├── components/
│   │   │   ├── fillups_section.php
│   │   │   ├── stat_card.php
│   │   │   └── vehicles_section.php
│   │   ├── footer.php
│   │   └── header.php
│   └── pages/
│       ├── dashboard.php
│       ├── landing.php
│       ├── login.php
│       ├── logout.php
│       ├── signup.php
│       └── vehicles.php
├── config/
│   └── db.php
├── database/
│   ├── migrations/
│   │   └── 20260425_add_vehicle_archive.sql
│   └── schema.sql
├── docs/
│   ├── design/
│   │   ├── 01-brand-guidelines.md
│   │   ├── 02-design-tokens.yaml
│   │   └── 03-tokens.css
│   ├── product/
│   │   ├── 01-product-requirements.md
│   │   └── 02-user-stories.md
│   └── technical/
│       ├── 01-implementation-guide.md
│       ├── 02-database-schema.md
│       └── 03-api-documentation.md
├── public/
│   ├── css/
│   │   ├── auth.css
│   │   ├── dashboard.css
│   │   ├── design-tokens.css
│   │   ├── landing.css
│   │   └── vehicles.css
│   ├── index.php
│   └── js/
│       ├── landing.js
│       └── vehicles.js
└── README.md
```

## Setup

1. Install XAMPP with Apache, MySQL, and PHP.
2. Point your document root at `public/`.
3. Create the `mileo` database.
4. Import `database/schema.sql`.
5. Update `config/db.php` if your local credentials differ.

## Questions?

For support or questions about Mileo, check the docs folder for detailed guides and implementation notes.
