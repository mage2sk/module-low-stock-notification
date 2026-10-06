# Magento 2 Low Stock Notification

Low Stock Notification adds a "Notify Me When Available" form to product pages whose product is not salable. Guests and logged-in customers can subscribe with their email address; each subscription is stored in the `panth_stock_alert` table. A cron job checks pending subscriptions every six hours and sends the "Stock Alert Notification" email once the product is salable again in the store the subscription was made in. Store administrators get a dashboard with subscription totals and a grid where they can view, send and delete alerts.

The module is used by store owners who want to record demand for out-of-stock products and contact those customers when the products are available again. The storefront form ships in two versions: a vanilla JavaScript template for Luma and an Alpine.js template that is switched in automatically on Hyva through the `hyva_catalog_product_view` layout handle (only loaded by Hyva themes).

Product page: [kishansavaliya.com/magento-2-low-stock-notification.html](https://kishansavaliya.com/magento-2-low-stock-notification.html)

## Features

- Subscription form rendered by `Panth\LowStockNotification\Block\StockAlert` on `catalog_product_view` when the module is enabled and the product is not salable.
- Guest subscriptions (name and email required) can be switched off; logged-in customers subscribe with the email and name from their session.
- Duplicate check: no second pending alert is stored for the same product and email address. Logged-in customers see "You are already subscribed"; guests get the normal success message so the response does not reveal whether an address is already subscribed.
- Subscribe and unsubscribe requests are POST requests to `lowstocknotification/alert/stock` and `lowstocknotification/alert/unstock` returning JSON; the form shows the result inline and lets the customer remove the alert again. Both are rate limited per client IP address.
- Every back-in-stock email contains a tokenized unsubscribe link.
- Cron job `lowstocknotification_stock_alert` sends the notification when the product is salable in the alert's store, then marks the alert as "Sent" and records `sent_at`.
- Transactional email template "Stock Alert Notification" with the variables `customer_name`, `product_name`, `product_url`, `product_price` and `store`.
- Admin dashboard: totals for all, pending, sent, cancelled and today's alerts, a critical items count (products with five or more pending alerts), a 7-day bar chart of new subscriptions (today's count and the chart use the store timezone), most requested products and the 15 most recent alerts.
- Admin grid "Manage Stock Alerts" with filters, a keyword search (customer email, customer name, product name, or the product ID when the keyword is a number), row actions (View, Delete, Send Email) and the mass actions Delete and Send Email.
- Alert detail page showing subscriber, store, timestamps, product SKU, type, price, stock status and quantity.
- Five form positions on the product page (after price, above or below the add-to-cart button, above or below the description) selected through the `lowstocknotification/placement/display_position` config path.
- Luma and Hyva storefront templates.

## Compatibility

| Platform | Versions |
|---|---|
| Magento Open Source | 2.4.4 to 2.4.8 |
| Adobe Commerce | 2.4.4 to 2.4.8 |
| PHP | 8.1, 8.2, 8.3, 8.4 |
| Themes | Luma, Hyva |

Composer constraints from `composer.json`: `magento/framework` ^103.0, `magento/module-catalog` ^104.0, `magento/module-customer` ^103.0, `magento/module-catalog-inventory` ^100.4, `magento/module-email` ^101.1, `magento/module-config` ^101.2, `magento/module-backend` ^102.0, `magento/module-store` ^101.1, `magento/module-ui` ^101.2, `magento/module-cron` ^100.4.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.4 to 2.4.8
- PHP `~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0`
- `mage2kishan/module-core` ^1.0 (module `Panth_Core`; installed automatically by Composer and listed in the module sequence)
- A working Magento cron (`magento/module-cron`) for automatic sending
- Working outbound email from Magento

## Installation

```bash
composer require mage2kishan/module-low-stock-notification
bin/magento module:enable Panth_Core Panth_LowStockNotification
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

`setup:di:compile` is only needed in production mode. Check the result with:

```bash
bin/magento module:status Panth_LowStockNotification
```

## Configuration

Admin path: Stores > Configuration > Panth Extensions > Low Stock Notification. All fields can be set at default, website and store view scope. The configuration section requires the ACL resource `Panth_LowStockNotification::config` and is also linked from the admin menu entry Low Stock Alerts > Configuration.

### General Settings

| Setting | Default | What it does |
|---|---|---|
| Enable Stock Alerts | Yes | Renders the subscription form on product pages and accepts subscribe/unsubscribe requests. When set to No the form is not rendered and both frontend controllers answer with "Stock alerts are disabled." |
| Product Page Display Style | Compact | Compact shows one "Notify me when back in stock" link that opens the form in a dialog (focus kept inside, Esc closes, focus returns to the link). Full form shows the whole subscription card on the page. Config path `lowstocknotification/general/display_style`. |
| Allow Guest Subscriptions | Yes | Lets visitors who are not logged in subscribe with a name and email address. When set to No, guests see a "Sign in" link to the customer login page instead of the form, and the controller answers "Please log in to subscribe to stock alerts." to guest requests. |

Config paths: `lowstocknotification/general/enabled`, `lowstocknotification/general/allow_guests`.

### Email Notifications

| Setting | Default | What it does |
|---|---|---|
| Email Sender Identity | General Contact (`general`) | Store email identity used as the sender of the notification. |
| Email Template | Stock Alert Notification (`lowstocknotification_email_email_template`) | Transactional email template used by the cron job, the single Send action and the mass Send action, read at the alert's store scope. |

Config paths: `lowstocknotification/email/sender`, `lowstocknotification/email/email_template`.

### Abuse Protection

| Setting | Default | What it does |
|---|---|---|
| Requests per Window | 10 | Maximum number of subscribe or unsubscribe requests (counted separately) one client IP address may send per window. Further requests get HTTP 429 with "Too many requests. Please try again later.". 0 disables the limit. |
| Window Length (seconds) | 600 | Length of the fixed rate limit window. |

Config paths: `lowstocknotification/security/rate_limit`, `lowstocknotification/security/rate_limit_window`. Counters are kept in the Magento cache. The client IP is read with `Magento\Framework\HTTP\PhpEnvironment\RemoteAddress`, so behind a proxy or load balancer configure the trusted forwarded-for headers in `app/etc/env.php`; otherwise all visitors share the proxy's address.

### Design & Colors

This group contains no fields. It shows a note that form colors are managed in the theme file `app/design/frontend/Panth/Infotech/web/tailwind/theme-config.json` (section `modules.low-stock-notification`) and regenerated with `node generate-theme-css.js`. The Hyva template reads CSS custom properties named `--lowstocknotification-*` and falls back to built-in colors when they are not defined; the Luma template uses fixed colors.

### Config paths without admin fields

The following values are defined in `etc/config.xml` and can be changed with `bin/magento config:set` or in `core_config_data`:

| Config path | Default | What it does |
|---|---|---|
| `lowstocknotification/placement/enable_on_product_page` | 1 | When set together with the module being enabled, the observer adds the layout handle for the selected position on `catalog_product_view`. |
| `lowstocknotification/placement/display_position` | `after_price` | One of `after_price`, `above_add_to_cart`, `below_add_to_cart`, `above_description`, `below_description`. See Usage for what each position does. |

## Usage

### Storefront

1. A visitor opens a product page. The block renders only when "Enable Stock Alerts" is Yes and `$product->isSalable()` is false.
2. Guests enter a name and an email address; logged-in customers see their email address and press the button. The templates also read the `customer` section from `mage-cache-storage` in local storage so the logged-in state is correct on full-page-cached pages.
3. The form posts `product_id`, `email`, `customer_name` and `form_key` to `lowstocknotification/alert/stock`. The controller validates the input (email and name at most 255 characters), answers "Product not found." for products that do not exist, are disabled or are not assigned to the current website, rejects products that are salable ("This product is currently in stock.") and does not store duplicates. On success a row with status Pending is saved with the current store ID.
4. The form switches to a "Stock Alert Active" state with a Remove button that posts to `lowstocknotification/alert/unstock`, which deletes the pending alerts for that product. For logged-in customers it acts on the account email; for guests it only acts on alerts created in the current browser session (the `email` parameter is ignored), and otherwise answers "No active stock alert found for this product." On page load the form asks `lowstocknotification/alert/status` (GET, not cached) whether a pending alert already exists, with the same identity rules, and shows the "Stock Alert Active" state when it does, so the state survives a reload.
5. Every back-in-stock email contains an "Unsubscribe from back-in-stock alerts" link, `lowstocknotification/unsubscribe/index/id/<alert id>/token/<token>`. The token is a random 32-character value generated when the alert is saved (column `unsubscribe_token`). With the correct token all pending alerts of that email address in that store view are set to Cancelled and the visitor is redirected to the home page with a confirmation; a wrong token shows "This unsubscribe link is invalid or has expired." and changes nothing.

### Form position

The base layout places the block in `product.info.main` after all other children. When `lowstocknotification/placement/enable_on_product_page` is 1, the observer `Observer\AddPlacementLayoutHandle` adds the handle `lowstocknotification_placement_<position>`:

| Position | Layout handle effect |
|---|---|
| `after_price` | No move; the block stays where the base layout put it. |
| `above_add_to_cart` | Moved into `product.info.main` before `product.info.quantity`. |
| `below_add_to_cart` | Moved into `product.info.additional.actions` as first child. |
| `above_description` | Moved into `product.info.details` before `product.info.details.description`. |
| `below_description` | Moved into `product.info.details` after `product.info.details.description`. |

On Hyva the `hyva_catalog_product_view.xml` layout switches the template to the Alpine.js version and moves the block into `product.info.additional`.

### Cron job

| Job | Schedule | Group | Class |
|---|---|---|---|
| `lowstocknotification_stock_alert` | `0 */6 * * *` (every 6 hours) | default | `Panth\LowStockNotification\Cron\StockAlertNotification` |

On each run the job walks through the alerts with status Pending in batches of 200. Alerts whose store has "Enable Stock Alerts" set to No are skipped. For the others it loads the product in the alert's store and, when `$product->isSalable()` is true (this follows MSI stock for the store's website when MSI is installed), sends the email, sets the status to Sent and stores `sent_at`. An alert is only marked Sent after the email was handed to the mail transport; failures are written to the Magento log and the alert stays Pending. There is no threshold setting.

### Email

- Template identifier: `lowstocknotification_email_email_template` (label "Stock Alert Notification", file `view/frontend/email/stock_alert.html`, frontend area).
- Subject: "Great news! {{var product_name}} is back in stock".
- Variables: `customer_name` (falls back to "Valued Customer"), `product_name`, `product_url`, `product_price`, `unsubscribe_url`, `store`. Custom templates created before 1.1.0 need `{{var unsubscribe_url}}` added.
- Recipient: the email address stored on the alert. Sender: the identity chosen in "Email Sender Identity".
- `product_price` is the product's final price (or regular price when there is no final price), converted and formatted in the store's currency, including the currency symbol. All send paths use the same values.

### Admin

The admin menu entry Low Stock Alerts (under the `Panth_Core::panth_extensions` parent) has three items:

- Dashboard (`lowstocknotification/dashboard/index`): totals bar, a warning banner when any product has five or more pending alerts, the "Alert Trends (7 Days)" bar chart, "Critical Stock" and "Most Requested Products" tables (top 10 by pending alerts, linked to the product edit page) and "Recent Alert Activity" (latest 15 alerts).
- Manage Alerts (`lowstocknotification/alert/index`): UI component grid `lowstocknotification_alert_listing` with the columns ID, Product Name, Customer Email, Customer Name, Status, Created At and Actions; Product ID, Customer ID and Sent At are available from the Columns menu. Row actions are View, Delete and, for Pending alerts, Send Email. Mass actions are Delete and Send Email. The keyword search matches the customer email, customer name, product name, or the product ID when the keyword is a number. Emails sent from the grid or the alert page use the storefront unsubscribe link of the alert store view.
- Configuration: opens the system configuration section.

Sending from the admin (row action, detail page button or mass action) does not check the product's stock status; it sends the email immediately and marks the alert as Sent. Only Pending alerts are sent: the single Send action refuses other statuses and the mass action skips them. The mass action reports how many emails were sent, skipped and failed. Delete, Send Email and the mass actions accept POST requests only; the row actions and the detail page buttons submit a form with the form key.

Alert statuses are 1 = Pending, 2 = Sent, 3 = Cancelled. The unsubscribe link in the email sets the Cancelled status; the Remove button on the product page deletes the row.

## Developer Notes

- Module name: `Panth_LowStockNotification`; Composer package: `mage2kishan/module-low-stock-notification`; PHP namespace: `Panth\LowStockNotification`.
- Sequence: `Panth_Core`, `Magento_Catalog`, `Magento_Customer`, `Magento_CatalogInventory`, `Magento_Email`, `Magento_Config`.
- Routes: frontend `lowstocknotification` (controllers `Controller\Alert\Stock`, `Controller\Alert\Unstock`, both `HttpPostActionInterface`, and `Controller\Unsubscribe\Index`, GET, token link); adminhtml `lowstocknotification` (`Controller\Adminhtml\Dashboard\Index`, `Controller\Adminhtml\Alert\Index`, `View`, `Delete`, `Send`, `MassDelete`, `MassSend`).
- Key classes: `Helper\Data` (config accessors and `XML_PATH_*` constants), `Block\StockAlert` (storefront block), `Model\StockAlert` with `Model\ResourceModel\StockAlert` and its `Collection`, `Model\EmailSender::sendAlertEmail()` (used by the single Send action), `Cron\StockAlertNotification::execute()`, `Observer\AddPlacementLayoutHandle` (event `layout_load_before`, frontend area), `Model\RateLimiter` (per-IP limit, cache keys prefixed `panth_lowstocknotification_rl_`), data patch `Setup\Patch\Data\BackfillUnsubscribeTokens`, `Model\Config\Source\Placement`, `Model\Source\Status`, `Block\Adminhtml\Dashboard`, `Block\Adminhtml\Alert\Edit`, UI columns `Ui\Component\Listing\Column\ProductName`, `CustomerName`, `AlertActions`.
- `etc/di.xml` registers the grid data source `lowstocknotification_alert_listing_data_source` as the virtual type `Model\ResourceModel\StockAlert\Grid\Collection` on table `panth_stock_alert`. There are no plugins, preferences, console commands or web API endpoints.
- The model uses the event prefix `panth_stock_alert` and the cache tag `panth_stock_alert`.
- ACL resources: `Panth_LowStockNotification::panth_lowstocknotification` ("Stock Alerts") with `::dashboard`, `::alerts` (`::alert_view`, `::alert_delete`, `::alert_send`) and `::config`.
- Database table `panth_stock_alert` (from `etc/db_schema.xml`): `alert_id` (PK), `customer_id` (nullable, FK to `customer_entity.entity_id`, cascade delete), `product_id` (FK to `catalog_product_entity.entity_id`, cascade delete), `email`, `customer_name`, `store_id`, `status` (default 1), `created_at`, `sent_at`, `unsubscribe_token`; indexes on `customer_id`, `product_id`, `email` and `status`.
- The admin dashboard chart uses Chart.js 4.5.0 bundled in `view/adminhtml/web/js/lib/chart.umd.min.js` (MIT license in `LICENSE-chartjs.txt`); nothing is loaded from a CDN. In production mode deploy admin static content after installing.
- Layouts: `view/frontend/layout/catalog_product_view.xml`, `hyva_catalog_product_view.xml`, five `lowstocknotification_placement_*.xml` handles; templates `view/frontend/templates/stock-alert-form.phtml` (Hyva, Alpine.js) and `view/frontend/templates/luma/stock-alert-form.phtml` (Luma, XMLHttpRequest).

## Uninstallation

```bash
bin/magento module:disable Panth_LowStockNotification
composer remove mage2kishan/module-low-stock-notification
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

The table `panth_stock_alert` and the `lowstocknotification/*` rows in `core_config_data` are not removed by these commands; drop them manually if they are no longer needed. `Panth_Core` stays installed if other Panth modules depend on it.

## Support

- Product page: [kishansavaliya.com/magento-2-low-stock-notification.html](https://kishansavaliya.com/magento-2-low-stock-notification.html)
- Contact form: [kishansavaliya.com/contact](https://kishansavaliya.com/contact)
- Email: kishansavaliyakb@gmail.com
- Issues: [GitHub issue tracker](https://github.com/mage2sk/module-low-stock-notification/issues)

## Documentation

[USER_GUIDE.md](USER_GUIDE.md) is written for store administrators and covers installation, configuration, the admin dashboard, managing alerts, how the cron job works, the storefront behaviour, email template customization and troubleshooting.

## License

Commercial software license. See [LICENSE.txt](LICENSE.txt) in this repository.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Links

- Website: [kishansavaliya.com](https://kishansavaliya.com)
- All extensions: [kishansavaliya.com/magento-extensions.html](https://kishansavaliya.com/magento-extensions.html)
- GitHub: [mage2sk/module-low-stock-notification](https://github.com/mage2sk/module-low-stock-notification)
- Packagist: [mage2kishan/module-low-stock-notification](https://packagist.org/packages/mage2kishan/module-low-stock-notification)
