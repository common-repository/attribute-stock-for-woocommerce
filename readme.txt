=== WooCommerce Attribute Stock - Shared Stock & Variable Quantities (Lite Version) ===
Contributors: mewz
Tags: attribute stock, shared stock, variable stock, woocommerce, stock
Requires at least: 5.4
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 2.0.3
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Set up complex stock configurations with ease. Shared stock, variable quantities, the possibilities are endless.

== Description ==

**WooCommerce Attribute Stock** gives your stock superpowers by allowing you to share inventories between multiple products/variations, or deduct different amounts of stock for each purchase of a product, variation, or attribute.

Easily track and manage shared stock, variable quantities, product addons, combo packs, measurements, components, and much more!

#### ⚠️ LITE VERSION LIMITATIONS ⚠️

**This is a FREE LITE VERSION for testing and limited use. Most importantly, it does not affect frontend product stock or prevent over-purchasing. It can however be used to track internal attribute stock without affecting product availability. For all features listed below, please purchase the [FULL VERSION](https://codecanyon.net/item/woocommerce-attribute-stock/25796765).**

#### What problem does Attribute Stock solve?

If you've used WooCommerce for a little while, you know its stock capabilities are quite limited. Each product or product variation can have a stock quantity and that's about it. Essentially your products are your stock items.

This works fine when you're selling simple products, but as soon as you need to do things like share stock between multiple products, or deduct variable amounts of stock quantities per sale, you're out of luck.

#### How does Attribute Stock solve this?

Attribute Stock extends WooCommerce's stock functionality by adding a few essential features like [stock items](https://wcas-docs.mewz.dev/attribute-stock) based on attributes instead of product listings, allowing them to be used easily across multiple products. And versatile [stock multipliers](https://wcas-docs.mewz.dev/stock-multipliers) to control the amount of stock deducted for each purchase of a product, variation, attribute term, or stock item.

Whether you make use of powerful attribute stock items, or stick to using product stock with variable multipliers, your stock quantities will be accurately tracked, product availability will be appropriately displayed, and over-purchasing will be prevented.

### ✨ Plugin Features

**Attribute-based Stock**
Manage stock at the attribute level. Share stock between all products/variations with one or more attributes.

**Variable Stock**
Deduct different quantities of product stock or attribute stock per sale with stock multipliers. Useful for measurements, weights, packs, etc.

**Stock Management**
Admin dashboard for easy management of attribute stock items. Set SKUs, stock images, product filters, and more.

**Powerful Customization**
Extensive flexibility to set up virtually any type of stock configuration using match rules, product filters, component stock, and more.

**"Any" Variations**
Use simple variations for your products. Stock will be accurately tracked without the need to specify every combination of attribute term options.

**CSV Import/Export**
Instantly export your attribute stock to CSV. Add or update stock in your favorite spreadsheet editor, then import your changes with a single click.

**REST API & Webhooks**
Manage your attribute stock from external software with our fully integrated WooCommerce REST API endpoint and webhook topics.

**Highly Compatible**
Plays well with most plugins, such as Variation Swatches, POS systems, Subscriptions, Product Bundles, Waitlists, Cart Stock Reducer, WP-Lister, WPML, Polylang, and more.

**Developer Friendly**
Almost anything that can't already be configured can be added or changed with powerful actions and filters.

\* *Please note that WordPress Multisite and multi-store synchronization is not currently supported or planned.*

### 🧩 Usage Examples

#### Example #1 – Variable Stock ([Demo](https://wcas-demo.mewz.dev/wp-admin/post.php?post=69&action=edit))

Let's say you sell tea in packs of different weights. You can simply set your total tea stock at the product level and specify a **stock multiplier** on each product variation. The respective amount will be deducted from your total stock for each purchase. If you need to share variable stock across more than one product listing, you can use **attribute stock items** instead.

#### Example #2 – Multiple Attributes ([Demo](https://wcas-demo.mewz.dev/wp-admin/post.php?post=127&action=edit))

Often your stock will have more than one attribute, such as t-shirts with different **sizes** and **colors**. In this case it's trivial to create **attribute stock items** with rules to match any combination of attributes across any number of products.

#### Example #3 – Product Bundles ([Demo](https://wcas-demo.mewz.dev/wp-admin/post.php?post=369&action=edit))

Sometimes you'll want to sell several **individual products** as well as a **bundle** of these products for a discount. Since attributes can be added to products as variation or non-variation attributes, you can easily create stock items that are shared between all types of products.

#### Example #4 – Component Stock ([Demo](https://wcas-demo.mewz.dev/wp-admin/edit.php?s=candle&post_type=attribute_stock))

In some cases you might need to track stock items made from other stock items. For example, let's say you design and sell hand-painted candles. By setting your plain candle stock as a component of your painted candle stock, you can track and sell each separately while allowing your painted candles to use the additional stock quantity of your plain candles.

Component stock is a powerful feature that can be used for many other advanced stock requirements such as batches and even stock from multiple suppliers. Learn more about how it works in our documentation below.

### 📖 Documentation

Want to learn more about WooCommerce Attribute Stock? Head over to the [online documentation](https://wcas-docs.mewz.dev/).

Be sure to look through the [FAQ & Troubleshooting](https://wcas-docs.mewz.dev/faq-troubleshooting.html) section if you're running into any issues.

== Installation ==

Download the plugin and upload it to your site via **Plugins** ▹ **Add New** ▹ **Upload Plugin**.

Alternatively, unzip the plugin ZIP file and upload the resulting folder to your site's **/wp-content/plugins/** directory. Don't forget to activate it through the **Plugins** menu in WordPress.

### ⭐ Upgrading to the Full Version

Installing the full version will deactivate the lite version. You can safely uninstall the lite version before or after installing the full version. Your attribute stock data will not be deleted.

== Frequently Asked Questions ==

= Do you offer customer support? =
Email support is offered via [CodeCanyon](https://codecanyon.net/item/woocommerce-attribute-stock/25796765) after purchasing the full version. We will also respond to questions on the WordPress support forum, but response time may be delayed.

= How can I get the full version? =
The full version is available to purchase on [CodeCanyon](https://codecanyon.net/item/woocommerce-attribute-stock/25796765).

== Changelog ==

= 2.0.3 (2024-08-13) =
- WordPress 6.6 compatibility.
- WooCommerce 9.1 compatibility.
- Added product SKU inheriting from stock items.
- Added a filter for stock item default values.
- Fixed inherited product data not showing on order items.
- Fixed webhooks relying on the WC legacy API.
- Fixed product stock actions not firing properly for variable quantity products.
- Optimized background tasks to be less aggressive with server resources.

= 2.0.2 (2024-06-30) =
- Fixed a bug with product stock multiplier checking when adding to cart in certain cases.
- Fixed some stock validation bugs when using cart/checkout blocks.
- Fixed stock item match rules being initially focused on page load.

= 2.0.1 (2024-06-27) =
- Fixed "any" attributes not showing in admin stock list.
- Fixed match rules not showing attribute terms with different sort orders.
- Fixed incorrect type on REST API 'components' parameter.
- Fixed bugs related to WPML compatibility.
- Fixed $wp_roles not being set in some cases.
- Renamed "pill" UI components to avoid malicious code detection on some hosts.

= 2.0.0 (2024-06-23) =
This is a major release! Please ensure you have a backup of your site before upgrading. If you have any custom code or integrations with Attribute Stock, you should upgrade on a staging site and test that they still work correctly.

- New documentation site.
- New attribute term multipliers feature.
- New stock components feature.
- New stock images feature.
- Many UI/UX, performance and other improvements.
- WooCommerce 9.0 compatibility.
- WordPress 5.4 / PHP 7.4 minimum requirement.

[See version 1 changelog](https://plugins.svn.wordpress.org/attribute-stock-for-woocommerce/changelog-v1.txt)
