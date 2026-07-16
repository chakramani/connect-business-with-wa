=== Business Messaging Hub - Chakramani ===
Contributors: chakramanijoshi
Donate link: https://www.paypal.com/donate/?hosted_button_id=LCDCS2KW3Z6PL
Tags: messaging, cloud api, chat widget, customer support
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send and receive Business API messages directly from your WordPress dashboard — inbox, auto-reply bot, chat widget, and full message log included.

== Description ==

Business Messaging Hub - Chakramani connects your WordPress site to the Meta Business Cloud API so you can send and receive messages, run a keyword-based auto-reply bot, and offer visitors a frontend chat widget — all without leaving wp-admin.

= Features =

* **Settings page** — connect your Business Cloud API credentials in one place
* **Test Connection button** — verify credentials before going live
* **Dashboard** — message statistics (total / sent / failed / delivery rate) with a Quick Send form
* **Send Message page** — dedicated page with character counter
* **Two-way Inbox** — view and manage ongoing conversations grouped by contact
* **Auto-reply Bot** — keyword-based responses with working-hours and greeting support
* **Frontend Chat Widget** — lets visitors start a conversation from any page
* **Message Log** — full outgoing message history with status, message IDs, and error details
* **Webhook Setup & Log** — real-time delivery status updates and incoming message handling
* **AJAX-powered** — no page reloads when sending
* **Secure** — nonce verification and `manage_options` capability check on every action

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel.
2. Go to **Plugins → Add New** and search for **Business Messaging Hub - Chakramani**.
3. Click **Install Now**, then **Activate**.
4. Navigate to **WA Business → Settings** and enter your API credentials.

= Manual Installation =

1. Download the plugin zip file.
2. Go to **Plugins → Add New → Upload Plugin** and upload the zip.
3. Click **Install Now**, then **Activate**.
4. Navigate to **WA Business → Settings** and enter your API credentials.

== Getting Your API Credentials ==

1. Log in to [Meta for Developers](https://developers.facebook.com).
2. Create an App (type: Business) and add the Business Cloud product.
3. In the API Setup section you will find:
   * A temporary access token (valid 24 hours) — or generate a permanent System User token for production.
   * A Phone Number ID — the numeric ID shown under your registered phone number.
4. Paste both values into **WA Business → Settings**, then click **Save Settings**.
5. Click **Test Connection** — you should see a green success notice.

== Usage ==

= Sending a Message =

Go to **WA Business → Send Message**, enter the recipient's number in E.164 format (e.g. +977980XXXXXXX) and your message text, then click **Send Message**. You can also use the Quick Send form on the Dashboard page.

= Sending from PHP =

You can send messages programmatically from your theme or another plugin:

`
$result = WABMH_Messenger::send( '+977980XXXXXXX', 'Hello from WordPress!' );
if ( is_wp_error( $result ) ) {
    error_log( $result->get_error_message() );
}
`

= Viewing the Message Log =

Go to **WA Business → Message Log** to browse outgoing messages with delivery status, message IDs, and any error details.

= Setting Up the Webhook =

Go to **WA Business → Webhook** for step-by-step instructions on registering your WordPress site as a webhook endpoint with Meta. This enables incoming messages and delivery receipts.

== Frequently Asked Questions ==

= Do I need a paid plan? =

The plugin itself is free. You need a Meta Developer account and a registered Business phone number. Meta provides a free test number for development; production use is subject to Meta's messaging pricing.

= What phone number format should I use? =

Use E.164 international format — country code followed by the number, no spaces or dashes. For example: +977980XXXXXXX.

= Why can't I send messages to some contacts? =

The Business Cloud API enforces a 24-hour messaging window. You can only send free-form text messages within 24 hours of the last customer-initiated message. Outside that window you must use a pre-approved Message Template.

= Is the Access Token stored securely? =

The token is stored in the WordPress options table (encrypted at rest if you use a security plugin that encrypts options). Treat it like a password and use a permanent System User token — not the temporary 24-hour token — in production.

= Which version of WordPress is required? =

WordPress 5.8 or higher. Tested up to WordPress 6.8.

= Which version of PHP is required? =

PHP 7.4 or higher.

== External services ==

This plugin connects to Meta's WhatsApp Business Platform to send and receive messages. Because that platform is provided by a third party (Meta Platforms, Inc.), the details of what is sent, when, and how to control it are documented below for every external service the plugin uses.

= Meta Graph API / Facebook Graph API =

The plugin uses Meta's Graph API (`https://graph.facebook.com/`) as the underlying transport for all WhatsApp Business Cloud API calls — Graph API and the WhatsApp Cloud API are the same Meta platform, addressed through the same endpoint family.

* **Purpose:** Authenticates the plugin's requests, sends outgoing WhatsApp messages, checks/registers/deregisters the connected business phone number, and retrieves phone-number status and quality-rating information.
* **What data is sent:** Your WhatsApp Business access token and app secret (for authentication); the connected Phone Number ID; the recipient's phone number and message text for each message you send from Send Message, Dashboard Quick Send, the Inbox, or the frontend chat widget; and the 6-digit PIN you enter when registering/deregistering the phone number.
* **When data is sent:** Only when you actively use a feature that requires it — clicking Test Connection, sending a message, checking phone status, or registering/deregistering the number. No data is sent to Meta automatically or on a schedule.
* **Can users disable it:** Yes. The plugin performs no Graph API requests until you save Business Cloud API credentials on the Settings page. If you deactivate the plugin or remove your credentials, no further requests are made. There is no way to use the plugin's core messaging features without this connection, since it is the plugin's core purpose.
* **Terms of Service:** https://www.facebook.com/terms
* **Privacy Policy:** https://www.facebook.com/privacy/policy/

= WhatsApp Cloud API (Webhooks) =

In addition to the outbound calls above, Meta's WhatsApp Cloud API sends data *to* your site via a webhook that this plugin registers at `wp-json/wabmh/v1/webhook`.

* **Purpose:** Delivers incoming customer messages and delivery/read status updates for messages you've sent, so the Inbox, Message Log, and Auto-reply Bot can react to them in real time.
* **What data is sent:** Meta sends the incoming message content, the sender's phone number, message IDs (wamid), and delivery/read status events to your site's webhook endpoint. This is inbound data *from* Meta to your site, not data your site sends elsewhere.
* **When data is sent:** Whenever a customer messages your connected WhatsApp number, or whenever the status of a message you sent changes (sent/delivered/read/failed) — entirely driven by Meta's platform, not by a schedule this plugin controls.
* **Can users disable it:** Yes. The webhook only receives data if you complete the setup on the Webhook page and register the callback URL with Meta. Removing the webhook subscription in Meta's App Dashboard, or deactivating the plugin, stops all incoming data.
* **Terms of Service:** https://www.whatsapp.com/legal/business-terms/
* **Privacy Policy:** https://www.whatsapp.com/legal/privacy-policy

== Screenshots ==

1. Dashboard — message statistics and Quick Send form
2. Send Message page with character counter
3. Two-way Inbox grouped by contact
4. Keyword-based Auto-reply Bot settings
5. Frontend Chat Widget on a live page
6. Message Log with delivery status
7. Webhook Setup and Webhook Log
8. Settings page — API credentials

== Changelog ==

= 1.0.0 =
* Initial release — Settings, Dashboard, Send Message, and Message Log.
* Added two-way Inbox for managing ongoing conversations.
* Added keyword-based Auto-reply Bot with working-hours support.
* Added frontend Chat Widget for visitor-initiated conversations.
* Added Webhook setup wizard and Webhook Log.


== License ==

This plugin is licensed under the GNU General Public License v2.0 or later.
See https://www.gnu.org/licenses/gpl-2.0.html for the full license text.
