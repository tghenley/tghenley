=== Henley Studio — Mini Sessions ===
Contributors: henleysstudio
Tags: booking, photography, mini sessions, appointments, square
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sell and manage photography mini sessions inside your existing WordPress site. Clients pick a time slot and pay a deposit via Square — or you invoice them.

== Description ==

A self-contained booking system for photographers running mini sessions
(short, themed shoots where clients book a time slot on a set date), inspired
by usesession.com but living natively in your own WordPress site.

* Create sessions in the familiar WordPress editor — title, description,
  featured photo, date, location, pricing.
* Time slots are generated automatically from a start/end time, slot length
  and break.
* Clients browse open sessions, pick a time and book — all on one page via
  the `[mini_sessions]` shortcode.
* Collect a deposit (or full payment) through **Square**. Square hosts the
  checkout page, so card data never touches your site. No Square keys? It runs
  in invoice mode and emails you each booking so you can invoice via Xero or
  similar.
* Manage every booking from wp-admin: see client details, change status
  (pending / confirmed / paid / cancelled). Cancelling frees the slot.
* Booking confirmation emails to both you and the client.
* Optional MailerLite newsletter sign-up: a consent checkbox on the booking
  form adds consenting clients to your MailerLite group automatically.

No WooCommerce required.

== Installation ==

1. Upload the `henleys-mini-sessions` folder to `/wp-content/plugins/`, or
   install the zip via Plugins → Add New → Upload.
2. Activate the plugin through the Plugins menu.
3. Create a WordPress Page (e.g. "Book a Session") and add the shortcode:
   `[mini_sessions]`
4. Go to **Mini Sessions → Settings**, fill in your studio details and select
   that page as the "Booking page".
5. (Optional) Add your Square Access Token and Location ID in Settings to take
   deposits online. Leave blank to use invoice mode.
6. Go to **Mini Sessions → Add Session** to create your first session, then
   publish it.

== Frequently Asked Questions ==

= Do I need WooCommerce? =
No. Payments use Square's hosted Payment Links directly, so there's no
dependency on WooCommerce or any other plugin.

= Is it secure to take payments? =
Card details are entered on Square's own hosted checkout page — they never
pass through your WordPress site, so you are not handling card data.

= What if I don't use Square? =
Leave the Square fields blank. The plugin records each booking and emails you
the details so you can send an invoice (e.g. from Xero) and mark the booking
as paid in the admin.

== Changelog ==

= 1.0.0 =
* Initial release.
