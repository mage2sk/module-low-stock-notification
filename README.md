# Panth Low Stock Notification

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange)]()
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue)]()
[![Hyva + Luma](https://img.shields.io/badge/Theme-Hyva%20%2B%20Luma-green)]()

**Back-in-stock email alerts for Magento 2.** Let customers subscribe
to out-of-stock products and get notified automatically when they
become available again. Fully compatible with both Hyva and Luma themes.

---

## Features

- **Customer subscriptions** — logged-in customers and guests can
  subscribe for back-in-stock notifications on any out-of-stock product.
- **Automatic email notifications** — cron job monitors stock levels
  and sends emails as soon as products are restocked.
- **Hyva + Luma support** — Alpine.js template for Hyva, vanilla JS
  template for Luma. Auto-detected, zero configuration.
- **Configurable placement** — choose where the alert form appears:
  after price, above/below add-to-cart, or above/below description.
- **Admin dashboard** — KPI cards, 7-day trend chart, most-requested
  products, critical alerts (5+ pending), and recent activity.
- **Admin grid management** — view, delete, send, mass-delete, and
  mass-send actions for all stock alerts.
- **Customizable design** — background, border, text, heading, and
  button colors are all configurable from the admin panel.
- **Guest support** — optionally allow guests to subscribe with name
  and email, no login required.

---

## Installation

```bash
composer require mage2kishan/module-low-stock-notification
bin/magento module:enable Panth_LowStockNotification
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Verify

```bash
bin/magento module:status Panth_LowStockNotification
# Module is enabled
```

---

## Requirements

| | Required |
|---|---|
| Magento | 2.4.4 - 2.4.8 (Open Source / Commerce / Cloud) |
| PHP | 8.1 / 8.2 / 8.3 / 8.4 |
| Panth_Core | ^1.0 (installed automatically via Composer) |

---

## Configuration

Open **Stores > Configuration > Panth Extensions > Low Stock Notification**.

### General Settings

| Setting | Default | Description |
|---|---|---|
| Enable Module | Yes | Enable/disable the module globally |
| Allow Guest Subscriptions | No | Allow non-logged-in visitors to subscribe |

### Placement Settings

| Setting | Default | Description |
|---|---|---|
| Enable on Product Page | Yes | Show the alert form on product pages |
| Display Position | After Price | Where the form appears on the product page |

### Design Settings

| Setting | Default | Description |
|---|---|---|
| Box Background Color | #f0fdf4 | Alert form background color |
| Box Border Color | #bbf7d0 | Alert form border color |
| Text Color | #374151 | Body text color |
| Heading Color | #111827 | Heading text color |
| Button Gradient From | #10b981 | Button gradient start color |
| Button Gradient To | #059669 | Button gradient end color |
| Button Text Color | #ffffff | Button text color |
| Button Hover Effect | Yes | Enable hover animation on submit button |

---

## Support

| Channel | Contact |
|---|---|
| Email | kishansavaliyakb@gmail.com |
| Website | https://kishansavaliya.com |
| WhatsApp | +91 84012 70422 |

---

## License

Commercial -- see `LICENSE.txt`. Distribution is restricted to the
Adobe Commerce Marketplace.

---

## About the developer

Built and maintained by **Kishan Savaliya** -- https://kishansavaliya.com.
Builds high-quality, security-focused Magento 2 extensions and themes
for both Hyva and Luma storefronts.
