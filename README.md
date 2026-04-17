<!-- SEO Meta -->
<!--
  Title: Panth Low Stock Notification - Back-in-Stock Email Alerts for Magento 2 | Panth Infotech
  Description: Panth Low Stock Notification for Magento 2 lets customers subscribe for back-in-stock email alerts on out-of-stock products. Admin dashboard, cron-based email delivery, configurable button placement, customizable email templates, and full Hyva + Luma support. Compatible with Magento 2.4.4 - 2.4.8 and PHP 8.1 - 8.4. Built by Top Rated Plus Magento developer Kishan Savaliya.
  Keywords: magento 2 back in stock, magento 2 low stock alert, magento 2 email notifications, magento 2 stock alert, out of stock notification, back in stock notification magento, product alert magento 2, hyva back in stock, luma stock notification, panth infotech
  Author: Kishan Savaliya (Panth Infotech)
  Canonical: https://github.com/mage2sk/module-low-stock-notification
-->

# Panth Low Stock Notification — Back-in-Stock Email Alerts for Magento 2

[![Visitors](https://visitor-badge.laobi.icu/badge?page_id=mage2sk.module-low-stock-notification&left_color=gray&right_color=0d9488&left_text=Visitors)](https://github.com/mage2sk/module-low-stock-notification)
[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange?logo=magento&logoColor=white)](https://magento.com)
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue?logo=php&logoColor=white)](https://php.net)
[![Hyva + Luma](https://img.shields.io/badge/Theme-Hyva%20%2B%20Luma-14B8A6)]()
[![Packagist](https://img.shields.io/badge/Packagist-mage2kishan%2Fmodule--low--stock--notification-orange?logo=packagist&logoColor=white)](https://packagist.org/packages/mage2kishan/module-low-stock-notification)
[![Upwork Top Rated Plus](https://img.shields.io/badge/Upwork-Top%20Rated%20Plus-14a800?logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)
[![Panth Infotech Agency](https://img.shields.io/badge/Agency-Panth%20Infotech-14a800?logo=upwork&logoColor=white)](https://www.upwork.com/agencies/1881421506131960778/)
[![Website](https://img.shields.io/badge/Website-kishansavaliya.com-0D9488)](https://kishansavaliya.com)
[![Get a Quote](https://img.shields.io/badge/Get%20a%20Quote-Free%20Estimate-DC2626)](https://kishansavaliya.com/get-quote)

> **Never lose an out-of-stock sale again.** Let customers subscribe for **back-in-stock email alerts** directly from the product page, manage every subscription from a powerful admin dashboard, send notifications automatically via cron, place the "Notify Me" button exactly where you want it, and customize the email template to match your brand — fully compatible with both **Hyva** and **Luma** themes.

**Panth Low Stock Notification** turns every "Out of Stock" product page into a future sale. When a product is unavailable, a **Notify Me When Available** form appears so shoppers (guest or logged-in) can submit their email and get notified automatically the moment the product is restocked. Store admins get a dedicated subscription dashboard to view, filter, export, and manually trigger alerts. A built-in **cron job** watches stock-status changes and dispatches branded email notifications using a fully customizable email template — no manual work required.

The extension supports **configurable button placement** (before/after add-to-cart, custom container), works seamlessly with **simple, configurable, bundle, and grouped products**, and ships with production-ready templates for both **Hyva (Alpine.js + Tailwind)** and **Luma (Knockout + LESS)** storefronts. MEQP-compliant, translation-ready, and built on the Panth Core foundation.

---

## 🚀 Need Custom Magento 2 Development?

> **Get a free quote for your project in 24 hours** — custom modules, Hyva themes, performance optimization, M1→M2 migrations, and Adobe Commerce Cloud.

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/Get%20a%20Free%20Quote%20%E2%86%92-Reply%20within%2024%20hours-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<table>
<tr>
<td width="50%" align="center">

### 🏆 Kishan Savaliya
**Top Rated Plus on Upwork**

[![Hire on Upwork](https://img.shields.io/badge/Hire%20on%20Upwork-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)

100% Job Success • 10+ Years Magento Experience
Adobe Certified • Hyva Specialist

</td>
<td width="50%" align="center">

### 🏢 Panth Infotech Agency
**Magento Development Team**

[![Visit Agency](https://img.shields.io/badge/Visit%20Agency-Panth%20Infotech-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/agencies/1881421506131960778/)

Custom Modules • Theme Design • Migrations
Performance • SEO • Adobe Commerce Cloud

</td>
</tr>
</table>

**Visit our website:** [kishansavaliya.com](https://kishansavaliya.com) &nbsp;|&nbsp; **Get a quote:** [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote)

---

## Table of Contents

- [Key Features](#key-features)
- [How It Works](#how-it-works)
- [Compatibility](#compatibility)
- [Installation](#installation)
- [Configuration](#configuration)
- [Admin Dashboard](#admin-dashboard)
- [Email Templates](#email-templates)
- [Cron Delivery](#cron-delivery)
- [Frontend — Hyva & Luma](#frontend--hyva--luma)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)
- [Support](#support)
- [About Panth Infotech](#about-panth-infotech)
- [Quick Links](#quick-links)

---

## Key Features

### Customer-Facing
- **Notify Me form** on every out-of-stock product page (simple, configurable, bundle, grouped)
- **Guest & logged-in support** — email is auto-populated for logged-in customers
- **Per-store-view subscriptions** — alerts are respected across multi-store installations
- **Double-opt protection** — duplicate submissions are deduplicated silently
- **My Account panel** — logged-in customers can view and cancel their own alerts
- **Fully translatable** — all strings use Magento `__()` translation

### Admin Dashboard
- **Subscriptions grid** — filter by product, email, store, status, date range
- **Bulk actions** — delete, mark as sent, resend alert
- **Manual trigger** — force-send notifications for selected subscriptions
- **CSV / XML export** — standard Magento export actions
- **Product-level stats** — see how many customers are waiting per SKU
- **Low-stock dashboard widget** (optional) — highlight high-demand out-of-stock products

### Cron-Based Email Delivery
- **Automatic detection** — cron watches stock status transitions and dispatches alerts when a product flips from out-of-stock → in-stock
- **Batch processing** — configurable batch size to protect SMTP deliverability
- **Retry logic** — failed sends are retried on the next cron cycle
- **Audit log** — every send is recorded with timestamp and delivery status
- **Configurable schedule** — default every 5 minutes, adjustable via admin

### Configurable Placement
- **Before or after Add-to-Cart** — pick the position via admin
- **Custom container** — emit the form into any layout container by name
- **Enable/disable per product type** — simple, configurable, bundle, grouped, virtual, downloadable
- **Category & attribute-based rules** (optional) — show only on selected categories or brands

### Email Templates
- **Customizable template** — Magento native transactional email template
- **Variables supported** — product name, URL, image, price, customer name, store name
- **Per-store-view templates** — different content per store or language
- **HTML + plain-text** — accessible multi-part email
- **Preview & test-send** from admin

### Theme Support
- **Hyva** — Alpine.js + Tailwind CSS, no RequireJS, no jQuery
- **Luma** — Knockout + LESS, full responsive design
- **Auto-detection** via `Panth_Core` — correct template served automatically

### Security & Performance
- **MEQP compliant** — passes Adobe's Magento Extension Quality Program
- **CSRF & form-key protected**
- **Email validation + rate limiting** to prevent abuse
- **Indexed subscription table** — scales to millions of subscriptions
- **Zero frontend performance impact** — form is rendered only on out-of-stock pages

---

## How It Works

```
┌───────────────────────────┐
│ Customer visits product   │
│ page (Out of Stock)       │
└────────────┬──────────────┘
             │
             ▼
┌───────────────────────────┐
│ "Notify Me" form is shown │
│ (Hyva or Luma template)   │
└────────────┬──────────────┘
             │ submits email
             ▼
┌───────────────────────────┐
│ Subscription saved to DB  │
│ (panth_low_stock_subscr.) │
└────────────┬──────────────┘
             │
             ▼
┌───────────────────────────┐
│ Cron watches stock status │
│ transitions every 5 min   │
└────────────┬──────────────┘
             │ product back in stock
             ▼
┌───────────────────────────┐
│ Email sent via template   │
│ → Subscription marked SENT│
└───────────────────────────┘
```

---

## Compatibility

| Requirement | Versions Supported |
|---|---|
| Magento Open Source | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce Cloud | 2.4.4 — 2.4.8 |
| PHP | 8.1.x, 8.2.x, 8.3.x, 8.4.x |
| MySQL | 8.0+ |
| MariaDB | 10.4+ |
| Hyva Theme | 1.3+ |
| Luma Theme | Native support |
| Required Dependency | [`mage2kishan/module-core`](https://packagist.org/packages/mage2kishan/module-core) (free) |

---

## Installation

### Composer Installation (Recommended)

```bash
composer require mage2kishan/module-low-stock-notification
bin/magento module:enable Panth_Core Panth_LowStockNotification
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual Installation via ZIP

1. Download the latest release from [Packagist](https://packagist.org/packages/mage2kishan/module-low-stock-notification) or the [Adobe Commerce Marketplace](https://commercemarketplace.adobe.com).
2. Extract to `app/code/Panth/LowStockNotification/`.
3. Install `Panth_Core` the same way if not already present.
4. Run the same commands shown above.

### Verify Installation

```bash
bin/magento module:status Panth_LowStockNotification
# Expected: Module is enabled
```

Then visit:
```
Admin → Stores → Configuration → Panth Extensions → Low Stock Notification
```

---

## Configuration

Navigate to `Stores → Configuration → Panth Extensions → Low Stock Notification`.

### General

| Setting | Default | Description |
|---|---|---|
| Enable Module | Yes | Master on/off toggle |
| Allow Guest Subscriptions | Yes | Let unauthenticated customers subscribe |
| Require Customer Login | No | Force shoppers to log in before subscribing |
| Success Message | Thank you — we'll email you when it's back! | Shown after successful submission |

### Button / Form Placement

| Setting | Default | Description |
|---|---|---|
| Position | After Add to Cart | Before Add to Cart / After Add to Cart / Custom Container |
| Custom Container Name | (empty) | Layout container to render into when Position = Custom |
| Enabled Product Types | simple, configurable, bundle, grouped | Multi-select |
| Restrict to Categories | (none) | Optional category filter |

### Email

| Setting | Default | Description |
|---|---|---|
| Sender | General Contact | Magento email sender identity |
| Email Template | Panth Low Stock Notification — Default | Customize per store view |
| Send Copy To | (empty) | Optional BCC for admin audit |

### Cron

| Setting | Default | Description |
|---|---|---|
| Cron Schedule | `*/5 * * * *` | Cron expression for the delivery worker |
| Batch Size | 50 | Max emails sent per cron run |
| Retry Failed Sends | Yes | Re-attempt on the next cycle |

---

## Admin Dashboard

Navigate to **Panth Infotech → Low Stock Notification → Subscriptions**.

You'll find:

- **Full subscriptions grid** — Product SKU, email, customer name, store, status (Pending / Sent / Cancelled), created date
- **Bulk actions** — Delete, Mark as Sent, Resend Email
- **Row actions** — View details, Send Now, Cancel
- **Filters** — by product, email, store, status, date range
- **Exports** — CSV / XML via the standard Magento export button

A **waiting customers** widget can also be added to any dashboard page that shows top out-of-stock products sorted by number of subscribers — perfect for prioritising restocks.

---

## Email Templates

The module ships with a default transactional email template:

- **Identifier:** `panth_lowstock_notification_template`
- **Location:** `view/frontend/email/notification.html`
- **Variables available:**
  - `{{var product.name}}`
  - `{{var product.url}}`
  - `{{var product.image}}`
  - `{{var product.price}}`
  - `{{var customer.name}}`
  - `{{var store.name}}`
  - `{{var unsubscribe_url}}`

To customize:

1. Go to **Marketing → Communications → Email Templates**
2. Click **Add New Template**
3. Load default template **Panth Low Stock Notification — Default**
4. Edit HTML / text content
5. Save and assign it in the module configuration

---

## Cron Delivery

Panth Low Stock Notification registers a cron group:

```xml
<group id="panth_low_stock">
    <job name="panth_low_stock_notify" instance="Panth\LowStockNotification\Cron\SendNotifications" method="execute">
        <schedule>*/5 * * * *</schedule>
    </job>
</group>
```

On every run, the cron:

1. Queries products that have transitioned from out-of-stock → in-stock since the last check
2. Loads matching **Pending** subscriptions in batches
3. Dispatches each email via the configured template & sender
4. Marks subscriptions as **Sent** (or **Failed** for retry)
5. Writes an entry to `var/log/panth_low_stock.log`

Ensure your server cron is running:

```bash
bin/magento cron:run --group=panth_low_stock
```

---

## Frontend — Hyva & Luma

The correct template is auto-selected by **`Panth_Core`'s** theme detection:

- **Hyva:** `view/frontend/templates/product/notify.phtml` — Alpine.js form, Tailwind classes, no RequireJS
- **Luma:** `view/frontend/templates/product/notify.phtml` — Knockout/jQuery form, responsive LESS styles

Both templates:
- Show only when the product is out of stock
- Pre-fill the email field for logged-in customers
- Submit via AJAX to `/panth_lowstock/subscribe/save`
- Display success / error messages inline
- Are fully translatable

---

## Troubleshooting

| Issue | Cause | Resolution |
|---|---|---|
| "Notify Me" form not visible | Product is in stock or module disabled | Confirm stock status and module config |
| Emails not being sent | Magento cron not running | Run `bin/magento cron:run --group=panth_low_stock` |
| Subscriptions not saving | Form key / cache issue | `bin/magento cache:flush` |
| Wrong template (Luma on Hyva) | `Panth_Core` not installed | `composer require mage2kishan/module-core` |
| Duplicate emails | SMTP retry collision | Enable "Retry Failed Sends" = No temporarily |

Enable **Debug Mode** in Panth Core settings to see detailed logs at `var/log/panth_low_stock.log`.

---

## FAQ

### Does it work for guests?
Yes, guests can subscribe with their email address. You can optionally require login via admin config.

### Does it support configurable / bundle products?
Yes — simple, configurable, bundle, grouped, virtual, and downloadable product types are all supported.

### Will customers get duplicate emails?
No. Each subscription is unique per `(email, product, store)` and marked **Sent** after delivery. Duplicates are silently deduplicated.

### Is it compatible with Hyva?
Yes — Alpine.js + Tailwind template included, detected automatically via Panth Core.

### Does it require Panth Core?
Yes. [Panth Core](https://packagist.org/packages/mage2kishan/module-core) is a **free** required dependency (Composer handles it automatically).

### Can I customize the email?
Yes — standard Magento transactional email template editor; supports HTML, plain-text, and per-store-view overrides.

### How often are emails sent?
By default every 5 minutes. The schedule and batch size are fully configurable.

### Can customers unsubscribe?
Yes. Logged-in customers manage their subscriptions in **My Account → Stock Alerts**. Every email also includes an unsubscribe link.

### Is it multi-store / multi-language safe?
Yes — subscriptions are scoped per store view, templates can be localized, and all strings are translatable.

### Does the extension slow down product pages?
No. The form is rendered server-side only on out-of-stock pages. No JavaScript is loaded for in-stock products.

---

## Support

| Channel | Contact |
|---|---|
| Email | kishansavaliyakb@gmail.com |
| Website | [kishansavaliya.com](https://kishansavaliya.com) |
| WhatsApp | +91 84012 70422 |
| GitHub Issues | [github.com/mage2sk/module-low-stock-notification/issues](https://github.com/mage2sk/module-low-stock-notification/issues) |
| Upwork (Top Rated Plus) | [Hire Kishan Savaliya](https://www.upwork.com/freelancers/~016dd1767321100e21) |
| Upwork Agency | [Panth Infotech](https://www.upwork.com/agencies/1881421506131960778/) |

Response time: 1-2 business days.

### 💼 Need Custom Magento Development?

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/%F0%9F%92%AC%20Get%20a%20Free%20Quote-kishansavaliya.com%2Fget--quote-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<p align="center">
  <a href="https://www.upwork.com/freelancers/~016dd1767321100e21">
    <img src="https://img.shields.io/badge/Hire%20Kishan-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white" alt="Hire on Upwork" />
  </a>
  &nbsp;&nbsp;
  <a href="https://www.upwork.com/agencies/1881421506131960778/">
    <img src="https://img.shields.io/badge/Visit-Panth%20Infotech%20Agency-14a800?style=for-the-badge&logo=upwork&logoColor=white" alt="Visit Agency" />
  </a>
  &nbsp;&nbsp;
  <a href="https://kishansavaliya.com">
    <img src="https://img.shields.io/badge/Visit%20Website-kishansavaliya.com-0D9488?style=for-the-badge" alt="Visit Website" />
  </a>
</p>

---

## License

Proprietary — see `LICENSE.txt`. Single-install license unless otherwise specified on the Adobe Commerce Marketplace.

---

## About Panth Infotech

Built and maintained by **Kishan Savaliya** — [kishansavaliya.com](https://kishansavaliya.com) — a **Top Rated Plus** Magento developer on Upwork with 10+ years of eCommerce experience.

**Panth Infotech** is a Magento 2 development agency specializing in high-quality, security-focused extensions and themes for both Hyva and Luma storefronts. Our extension suite covers SEO, performance, checkout, product presentation, customer engagement, and store management — over 34 modules built to MEQP standards and tested across Magento 2.4.4 to 2.4.8.

Browse the full extension catalog on the [Adobe Commerce Marketplace](https://commercemarketplace.adobe.com) or [Packagist](https://packagist.org/packages/mage2kishan/).

---

## Quick Links

- 🌐 **Website:** [kishansavaliya.com](https://kishansavaliya.com)
- 💬 **Get a Quote:** [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote)
- 👨‍💻 **Upwork Profile (Top Rated Plus):** [upwork.com/freelancers/~016dd1767321100e21](https://www.upwork.com/freelancers/~016dd1767321100e21)
- 🏢 **Upwork Agency:** [upwork.com/agencies/1881421506131960778](https://www.upwork.com/agencies/1881421506131960778/)
- 📦 **Packagist:** [packagist.org/packages/mage2kishan/module-low-stock-notification](https://packagist.org/packages/mage2kishan/module-low-stock-notification)
- 🐙 **GitHub:** [github.com/mage2sk/module-low-stock-notification](https://github.com/mage2sk/module-low-stock-notification)
- 🛒 **Adobe Marketplace:** [commercemarketplace.adobe.com](https://commercemarketplace.adobe.com)
- 📧 **Email:** kishansavaliyakb@gmail.com
- 📱 **WhatsApp:** +91 84012 70422

---

<p align="center">
  <strong>Ready to recover lost sales from out-of-stock products?</strong><br/>
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/%F0%9F%9A%80%20Get%20Started%20%E2%86%92-Free%20Quote%20in%2024h-DC2626?style=for-the-badge" alt="Get Started" />
  </a>
</p>

---

**SEO Keywords:** magento 2 back in stock, magento 2 low stock alert, magento 2 email notifications, magento 2 stock alert, out of stock notification, back in stock notification magento 2, notify me when available magento, product alert magento 2, stock availability email, restock email magento, hyva back in stock module, luma stock alert extension, magento 2 subscription alerts, magento 2 out of stock email, magento 2 waitlist, magento 2 inventory notification, magento 2.4.8 stock alert, php 8.4 magento extension, panth infotech, mage2kishan, mage2sk, hire magento developer, top rated plus upwork magento, kishan savaliya, custom magento development, magento 2 hyva development, magento 2 luma customization, magento 2 conversion optimization, magento 2 email marketing, magento 2 cron notifications, magento 2 customer engagement
