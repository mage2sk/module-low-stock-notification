# Panth Low Stock Notification -- User Guide

This guide is for store administrators who want to configure and manage
back-in-stock email notifications for their Magento 2 store.

---

## Table of contents

1. [Installation](#1-installation)
2. [Configuration](#2-configuration)
3. [Admin dashboard](#3-admin-dashboard)
4. [Managing stock alerts](#4-managing-stock-alerts)
5. [How the cron job works](#5-how-the-cron-job-works)
6. [Frontend experience](#6-frontend-experience)
7. [Email template customization](#7-email-template-customization)
8. [Troubleshooting](#8-troubleshooting)

---

## 1. Installation

### Via Composer (recommended)

```bash
composer require mage2kishan/module-low-stock-notification
bin/magento module:enable Panth_LowStockNotification
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Verify installation

```bash
bin/magento module:status Panth_LowStockNotification
```

---

## 2. Configuration

Navigate to **Stores > Configuration > Panth Extensions > Low Stock Notification**.

### General

- **Enable Module** -- turn the entire module on or off.
- **Allow Guest Subscriptions** -- when enabled, non-logged-in visitors
  can subscribe using their name and email address.

### Placement

- **Enable on Product Page** -- show or hide the alert form on product
  detail pages.
- **Display Position** -- choose where the form appears:
  - After Price (default)
  - Above Add to Cart Button
  - Below Add to Cart Button
  - Above Product Description
  - Below Product Description

### Design

All colors are configurable from the admin:

- Box background and border colors
- Text and heading colors
- Button gradient start/end colors and text color
- Button hover animation toggle

---

## 3. Admin dashboard

Navigate to **Panth Infotech > Low Stock Notification > Dashboard**.

The dashboard provides:

- **KPI summary bar** -- total alerts, pending, sent, cancelled,
  today's alerts, and critical items count.
- **7-day trend chart** -- bar chart showing new alert subscriptions
  over the past week.
- **Critical stock alerts** -- products with 5 or more pending alerts,
  flagged for immediate restocking attention.
- **Most requested products** -- ranked list of products by number of
  pending stock alerts.
- **Recent activity feed** -- the latest 15 alert subscriptions with
  status and timestamps.

---

## 4. Managing stock alerts

Navigate to **Panth Infotech > Low Stock Notification > Manage Alerts**.

The admin grid displays all stock alert subscriptions with columns for
alert ID, customer email, customer name, product, status, created date,
and sent date.

### Available actions

- **View** -- see full alert details including product stock status.
- **Send Email** -- manually trigger the back-in-stock notification
  for a specific alert.
- **Delete** -- remove an individual alert subscription.
- **Mass Send** -- select multiple alerts and send emails in bulk.
- **Mass Delete** -- select multiple alerts and delete them in bulk.

---

## 5. How the cron job works

The module registers a cron job that runs automatically. On each
execution it:

1. Loads all alerts with status "Pending" (active).
2. For each alert, checks whether the subscribed product is now in
   stock (via CatalogInventory stock registry).
3. If the product is back in stock, sends the notification email and
   updates the alert status to "Sent".
4. Logs all sent emails and any errors to the Magento log.

No manual intervention is needed once the module is enabled and cron
is running on your Magento installation.

---

## 6. Frontend experience

### Out-of-stock product page

When a customer visits an out-of-stock product page, the stock alert
form is displayed at the configured position. The form adapts based
on the customer's login status:

- **Logged-in customers** -- the form pre-fills the email address and
  requires only a single click to subscribe.
- **Guests** (if allowed) -- the form asks for name and email address.

After subscribing, the form transitions to a "Stock Alert Active"
confirmation with an option to unsubscribe.

### Theme compatibility

- **Hyva themes** use the Alpine.js-powered template with reactive
  state management.
- **Luma themes** use a vanilla JavaScript template with XHR requests.

The correct template is loaded automatically based on layout handles.

---

## 7. Email template customization

The back-in-stock notification email template can be customized at
**Marketing > Communications > Email Templates**.

Create a new template using the `lowstocknotification_email_email_template`
identifier as the base. Available template variables:

- `{{var customer_name}}` -- subscriber's name
- `{{var product_name}}` -- product name
- `{{var product_url}}` -- direct link to the product page
- `{{var product_price}}` -- current product price
- `{{var store}}` -- store object

---

## 8. Troubleshooting

### Alert form not showing

1. Verify the module is enabled: `bin/magento module:status Panth_LowStockNotification`
2. Check configuration: Stores > Configuration > Panth Extensions > Low Stock Notification > General > Enable = Yes
3. Check placement: Ensure "Enable on Product Page" is set to Yes
4. The form only appears on out-of-stock products. Verify the product
   is truly out of stock.
5. Flush cache: `bin/magento cache:flush`

### Emails not sending

1. Verify Magento cron is running: `bin/magento cron:run`
2. Check the Magento logs: `var/log/system.log`
3. Verify email configuration in Stores > Configuration > Advanced > System > Mail Sending Settings

### Guest form not appearing

Ensure "Allow Guest Subscriptions" is set to Yes in the module
configuration.

---

## Support

| Channel | Contact |
|---|---|
| Email | kishansavaliyakb@gmail.com |
| Website | https://kishansavaliya.com |
| WhatsApp | +91 84012 70422 |
