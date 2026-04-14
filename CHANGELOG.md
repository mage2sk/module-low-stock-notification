# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] — Initial release

### Added
- **Back-in-stock email alerts** — customers can subscribe to receive
  an email notification when an out-of-stock product becomes available
  again. Works for both logged-in customers and guests (configurable).
- **Hyva and Luma support** — ships with two frontend templates: an
  Alpine.js-powered Hyva version and a vanilla JS Luma version. The
  correct template is loaded automatically based on the active theme.
- **Configurable form placement** — admin can choose where the stock
  alert form appears on the product page: after price, above/below
  add-to-cart, or above/below description.
- **Admin dashboard** — dedicated dashboard with KPI totals (total,
  pending, sent, cancelled, today), 7-day trend chart, most-requested
  products table, critical stock alerts (5+ pending), and recent
  activity feed.
- **Admin alert management** — full UI grid with view, delete, send
  email, mass delete, and mass send actions.
- **Cron-based auto-notification** — cron job checks pending alerts
  against current stock status and automatically sends emails when
  products come back in stock.
- **Customizable design** — admin can configure box background color,
  border color, text color, heading color, button gradient colors,
  button text color, and hover effects.
- **Guest subscription support** — optional setting to allow guest
  visitors to subscribe with name and email without requiring login.
- **Email template** — transactional email template for back-in-stock
  notifications, customizable via Magento's email template system.

### Compatibility
- Magento Open Source / Commerce / Cloud 2.4.4 - 2.4.8
- PHP 8.1, 8.2, 8.3, 8.4
- Hyva Theme and Luma Theme

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
