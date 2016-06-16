=== Membership 2 ===
Contributors: WPMUDEV
Tags: Content Protection, Control Access, Membership, Membership 2, Multisite Membership, Paid Membership, Pay Wall, Paying Users, Registration, Restrict Content, Subscription, WordPress Membership, WPMU DEV
Requires at least: 3.7
Tested up to: 4.5.2
Stable tag: trunk

Membership 2 transforms your WordPress website into a fully functional membership site.

== Description ==

<p><strong>Harder, Better, Stronger, Faster</strong><br>
The best membership plugin for WordPress just got even better – meet Membership 2. We completely rewrote Membership from the ground up to create a completely new, faster, more <a href="https://wordpress.org/plugins/membership/screenshots/" rel="nofollow">intuitive and beautiful membership system.</a></p>

<p>Transform WordPress into a fully functional membership site. Provide access to downloads, online content, videos, forums, support and more through a flexible membership system. It’s simple to use and yet if you dig in, it’s incredibly adaptable. Membership 2 even includes set-and-forget automated recurring payments, so you can focus on providing value while getting income from your members.</p>

<p><strong>Simpler, More Powerful Controls</strong><br>
With Membership 2 there’s no guesswork and no messing around with confusing settings. Our setup wizard will guide you through configuring your membership site for the best results.</p>

<p>Choose one from one of four base membership options, then configure the perfect system to fit your site’s goals.</p>

<ul>
<li>Standard Membership</li>
<li>Dripped Content Membership</li>
<li>Guest Membership</li>
<li>Default Membership</li>
</ul>

<p>Use Membership 2 to protect all things WordPress – pages, posts, comments, content below the “read more” tag, categories, menus, URLs, special pages, content by user role, media files, forums, downloads, videos, support...you name it</p>

<p><strong>Put Your Content Behind a Paywall</strong><br>
Get paid with PayPal, Stripe, Authorize.net, or good old fashioned manual payments. And, using automated processing and you can get paid without any hassle at all.</p>

<p>Membership 2 even includes 25 currency options out-of-the-box so you can take payments from just about anywhere in the world.</p>

<p><strong>Membership 2 Pro</strong><br>
Need even more? Upgrade to <a href="http://premium.wpmudev.org/project/membership/" rel="nofollow">Membership 2 Pro</a> and get access to all the features available in Membership 2 plus these premium add-ons:</p>

<ul>
<li><strong>Fully integrated coupon system</strong> – offer special promotions and attract new users</li>
<li><strong>Admin-side protection rules</strong> – protect elements inside the WordPress Dashboard</li>
<li><strong>Custom post-type protection</strong> – protect post-types not native to WordPress core</li>
<li><strong>Advanced media protection rules</strong> – more control when protecting media</li>
<li><strong>Network Wide Protection</strong> – manage membership on your entire Multisite network from one place</li>
</ul>

<p><a href="http://premium.wpmudev.org/project/membership/" rel="nofollow">Upgrade to the full version now »</a></p>

<p>Download Membership 2 today – the new standard in WordPress and Multisite membership systems.</p>

== Installation ==

**To install**

1.  Download the plugin file
2.  Unzip the file into a folder on your hard drive

**Standard WP/WPMS (for blog by blog access)**

1.  Upload the membership folder and all it contents to /wp-content/plugins folder on your site
2.  The path to the main plugin file is wp-content/plugins/membership/membership.php

**To activate it on a blog by blog basis**

1.  Log into the blog dashboard that you want to set up membership on.
2.  Go to Plugins > Installed
3.  Click on Activate under Membership lite system

**Enabling your membership plugin**
By default, the membership plugin is disabled when first installed and when you go your Membership Dashboard you will see it says Disabled.

You need to leave this as disabled until you have at least:

1.  Set up your categories
2.  Created and activated a basic level to use for strangers
3.  Assigned the stranger level in Membership > Edit Options panel

If you are running a live site and enable the plugin in your Membership dashboard all content will be automatically protected until you have set up the stranger level.

**The Admin user**
The membership system can initially be administered by the admin user and is always disabled on the front end of your site for this user, you can add other users to the membership administration group by editing them in WordPress and ticking the Membership admin box at the bottom of the User Edit page.

= More Instructions on setup =
More instructions and screenshots on how to configure the Membership plugin can be found on the <a href='http://premium.wpmudev.org/project/membership/installation/'>WPMU DEV site</a>.

= Need help getting started? =
We provide comprehensive and guaranteed support on the <a href='http://premium.wpmudev.org/forums/tags/membership'>WPMU DEV forums</a> and <a href='http://premium.wpmudev.org/live-support/'>live chat</a>.

== Frequently Asked Questions ==

= How easy is it to set up? =
We have an indepth step by step guide to getting the plugin initially setup and configured <a href='http://premium.wpmudev.org/project/membership/installation/'>here</a>.

= Get Support =
We provide comprehensive and guaranteed support on the <a href='http://premium.wpmudev.org/forums/tags/membership'>WPMU DEV forums</a> and <a href='http://premium.wpmudev.org/live-support/'>live chat</a> only.

== Screenshots ==

1. Overview of your Membership packages with quick access to most used features
2. Membership 2 comes with a lot of flexible shortcodes
3. See a quick overview of all activities in a certain Membership
4. Membership 2 offers the most intuitive way for you to manage your protection rules
5. It's easy to filter, search and edit your subscribers
6. All payment activities are thoroughly logged for later review
7. Unleash the true power of this plugin with dozens of Add-ons that come with the plugin
8. Configuration is simple as that - no complicated forms, no confusing technical details
9. Enable and configure the built in payment gateways
10. The default user account screen on your website can be highly customized via shortcodes and CSS
11. Convince your users with a modern, clean and professional checkout form

== Changelog ==

= 4.0.1.0 =
* Added: Template system, now M2 pages (Membership List, Payment Form, Registration Page, Account Page) can be overwritten from theme
* Added: Option to select different list for different membership in Mailchimp addon
* Added: Option to show BP XProfile fields in M2 account page
* Added: ms_get_blog_list_args filter added to show non-public blogs in protectin rules page in network admin
* Added: Define 'MS_CPT_ENABLE_ACCESS_BOX' to enable protection meta box in custom post type
* Added: Filter 'ms_helper_listtable_billing_default_column' added for default column in billing table
* Added: Filter 'ms_gateway_stripe_charge_amount' and 'ms_gateway_stripe_form_details_after' added to customize the amount for stripe
* Added: New Addon! WP reCaptcha integration
* Added: Batch Process - Membership Status check, helpful when you have lots of members
* Added: Bulk Delete Membership feature
* Added: Constant 'MS_DISABLE_WP_NEW_USER_NOTIFICATION' to disable WP default welcome on registration
* Added: Constant 'MS_LOG_EMAILS' to enable log and display email logs
* Added: Constant 'MS_PAYPAL_TRIAL_SUBSCRIPTION' to enable subscription for Paypal when trial mode is enabled (works only at Paypal)
* Added: Constant 'MS_PROTECTED_MESSAGE_REVERSE_RULE' to implement reverse logic on membership based protection message
* Added: Constant M2_FORCE_NO_SSL to avoid forcing SSL in Stripe Live Mode
* Added: Filters 'ms_helper_color_index' and 'ms_model_membership_get_color'
* Added: Constant 'MS_PROCESS_PER_BATCH' to set number of members for processing per batch
* Added: New import option to import each membership on its own, to prevent memory- or similar overflow issues.
* Added: New option 'non-admin' to the shortcode [ms-user]
* Added: Settings for PayPal gateways now also list the country "Croatia".
* Improved: Memberships can't be assigned to admin users any longer
* Improved: Minor issues in PayPal Standard gateway, related to imported subscriptions
* Improved: Payment-matching for imported subscriptions
* Improved: Support for protection rules on admin-side (shortcode, menu-items)
* Improved: Edit-Member page to only offer valid subscription status options
* Improved: transaction logs page and show better description for PayPal transactions
* Fixed: JS conflict with Be Theme
* Fixed: JS conflict with LayerSlider
* Fixed: JS conflict with Visual Composer
* Fixed: Expired email was sent even the user is not really expired
* Fixed: Bug that caused expiration emails to be sent multiple times
* Fixed: Confirmation emails not sent when registering new user from admin end
* Fixed: Duplicate expiration emails were sent on expiration date.
* Fixed: Test Memberships was not working (simulation mode)
* Fixed: Protected posts appears in archive page
* Fixed: Conflict with WPEngine Deployment
* Fixed: Wrong message when a coupon is removed
* Fixed: Wrong logic on category protection for multiple membership
* Fixed: Inactive Memberships still available for renewal
* Fixed: BP activation page is always protected in multisite
* Fixed: Some unexpected notices and warnings
* Fixed: Input validation of registration page fails to redirect to correct page when shortcode is used
* Fixed: Price on SignUp page not shows correctly for the administrator
* Fixed: Deleting an user doesn't not remove the membership information from Membership page
* Fixed: JS Conflict for #password ID
* Fixed: Media Protection doesn't work in some cases
* Fixed: Display name was not being saved from edit account page
* Fixed: Buddy Press extended profile fields data not showing on Membership 2 My Account page
* Fixed: Cannot remove value for payment button in payment gateway settings
* Fixed: Free membership setup with Membership 2 shows still payment information
* Fixed: "Already have a user account" doesn't redirect to purchase subscription after login
* Fixed: "Already have an account" link not sending user to login form after failed register attempt
* Fixed: Advanced Menu Protection Replace Menu not working
* Fixed: BuddyPress Integration - No validation error message shown
* Fixed: BuddyPress Integration - No xProfile field validation
* Fixed: BuddyPress Integration addon: "All BuddyPress Pages" rule being overwritten by rule set in Pages
* Fixed: BuddyPress Members directory could not be protected
* Fixed: BuddyPress sitewide pages are not protected
* Fixed: BuddyPress XProfile issue (caused by M2 loading before BP was initialized)
* Fixed: Category protected posts was being appeared in home and search result page
* Fixed: Content Protection Message based on membership protection
* Fixed: Currency was always USD in 2Ccheckout gateway
* Fixed: Expired date mismatch with the original expired date in PayPal
* Fixed: Expired trial is not going to payment gateway when clicking "sign up" in invoice.
* Fixed: Expiry date not set while importing data in Finite membership
* Fixed: Finite paid membership subscription period gets doubled for a member while importing data
* Fixed: Finite paid membership turned into free membership while importing data
* Fixed: Force SSL on Stripe checkout page in Live Mode
* Fixed: Gateway mode changed from Sandbox to Live on plugin update
* Fixed: Gateway mode changed into Live mode when something is changed in settings
* Fixed: HTTPS not being forced when Stripe gateway is set to Live mode
* Fixed: IE and Edge users can now log in from front end again
* Fixed: Import of M1 data now correctly creates recurring memberships.
* Fixed: Inernal Server Error on adding a member in a multisite configuration
* Fixed: issue in Account page that did not save the users email address
* Fixed: Login on non-SSL page is broken when SSL forced for wp-admin
* Fixed: Membership Payment amount could not be saved in Firefox
* Fixed: Menu item protection was not working for Guest Membership
* Fixed: Missing string in default lost password default email no longer missing
* Fixed: Multiple Membership addon: Membership was not removed when set as cancel in upgrade path and pay later
* Fixed: Nickname now saves when BuddyPress profile fields add on is activated
* Fixed: Protected post are being appeared in feed
* Fixed: Redirect add-on works properly again
* Fixed: Search was not working on front-end
* Fixed: Select drop down in Authorize.net checkout was broken
* Fixed: Small issue in Authorize.Net payment settings that would not save Secure Payments setting
* Fixed: Small issue in URL-protection that ignored the last slash of the URL rule
* Fixed: Some post types were not working for protection
* Fixed: Taxamo addon: tax was not added as tax in PayPal and 2Checkout gateways
* Fixed: User can't register in Opera Mini browser
* Fixed: Visitors coming from search engines links get blank page, Error 500
* Fixed: Warning on edit membership screen when % sign is in the membership name
* Fixed: Warning when the database table prefix is not the default "wp_"
* Fixed: White screen in WP-Touch settings page
* Fixed: Wrong logic that would hide the Billing menu item if all paid memberships were private
* Fixed: XProfile Date field was not being saved in registration form
* A lot more of small changes, fixed typos, translations, etc.

= 4.0.0.7 =
* Fix fatal error on Settings page when the Additional Email Templates Add-on is active

= 4.0.0.6 =
* **Highlight**: Our free plugin now supports recurring payments! Check it out :)
* Add new email template: User account created (i.e. welcome email)
* Add new email template: Forgot Password email top optionally customize default text
* Add new variable for Automated Emails: Membership Description
* Add a warning when BuddyPress pages conflict with M2 Membership pages
* Add a warning when M2 is installed in an deprecated directory (can cause conflicts)
* Add a warning when unsupported Permalink options are detected
* Add new filter in Members list to filter members by subscription status (active/expired)
* Add a "Retry" function to Transaction logs to re-process a single transaction
* Add a "Auto Matching" screen to the billings page to link M1 payments with memberships
* Add new option to Authorize.Net gateway settings: "Secure Payment" asks for CVC code on every payment
* Add API functions: ms_api, get_membership_id, add_subscription (see API Docs for details)
* Update the Authorize.Net library to latest version that uses the new API URLs
* Update several background libraries (mainly select2, fontawesome, jQueryUI)
* Improve Membership Description to allow shortcodes
* Improve the shortcode column in the admin membership list
* Improve UI of the Authorize.Net payment form on front end
* Improve transaction logs with more details and small layout improvements
* Improve performance of the billings page, show additional details in edit-invoice screen
* Improve billings list to hide invoices with status "new" by default
* Improve layout of [ms-note] messages on front-end and select lists in admin pages
* Fix some issues with Authorize.Net Gateway
* Fix Taxamo Add-on to send the invoice-currency and correct user IP for PayPal IPN payments
* Fix display of line-breaks in membership description on front-end
* Fix for caching issues on WP Engine
* Fix wrong registration workflow that showed membership list instead of payment form
* Fix missing error messages when registering username that is already in use
* Fix bug that allowed logged-in users to register a new account
* Fix the Reset Password workflow
* Fix issue in WP 4.3 that sent password-reset emails during user registration
* Fix wrong signup-logic to instantly disable the old subscription on membership upgrade/downgrade
* Fix some import issues when importing data from old Membership plugin
* Fix an issue that would not activate imported subscriptions
* Fix dates that displayed in GMT instead of local timezone
* Fix ms-protect-content shortcode: Resolve shortcodes in protected content also for admin users
* Fix bug in setup-wizard that sometimes did not finish after creating the first membership
* Fix issue where Manual Payment displayed price multiple times (e.g. when using Yoast SEO)
* Fix an issue with Internet Explorer privacy settings that caused IE to reject M2 cookies
* Fix the user-name variables in email/shortcodes (use display name instead of login name)
* Fix the "Ignore" action in the Transaction log list
* Fix several issues in PayPal Standard gateway that would not process transactions correctly
* Fix protection bug that would hide custom posts in certain situations, e.g. when using wp_list_pages()
* Fix a PHP error that occured when paying the first invoice of a member via manual gateway
* Fix a possible recursive redirection when showing the Autorize.Net payment form
* Fix displayed payment method for subscriptions in Member editor screen
* Fix issue that did not notify the blog admin of new user signups
* Fix wrong redirects/URLs on some SSL sites
* Fix detection of valid subscriptions to fix issues with subscriptions getting expired too early
* Fix the BuddyPress registration form/workflow
* Fix bug that prevented deleting invoices
* Fix a bug that would temporarily grant access to a membership when user cancels payment
* Fix a bug that created duplicate email templates every time an email was sent
* Lot of small changes like fix PHP notices, correct typos, add new filters and code cleanup

= 4.0.0.5 =
* Fix an fatal error in membership editor screen
* Fix displayed payment method for subscriptions in Member editor screen
* Small improvements and fixed php notices

= 4.0.0.4 =
* Fix several fatal errors (last update did copy some wrong files, sorry for this!)

= 4.0.0.3 =
* Add a dedicated Edit Membership page with improved layout
* Add a new Add/Edit Member page where subscription details can be modified
* Improve caching and reduce SQL queries to make the plugin faster
* Improve HTML output of shortcodes to be compatible with most themes (remove line breaks inside HTML tags)
* Improve payment logs to display additional/better information for errors
* Improve the Billings list (status-indicator, overdue payments, quick-pay for manual payment gateway)
* Add a new Automated Email Response: User account created (i.e. welcome email)
* Add a warning when BuddyPress pages conflict with M2 Membership pages
* Add a warning when using a wrong value in the PayPal Standard settings
* Add an admin notice when no payment gateway is active but paid memberships exist
* Add an edit page to change the Membership Type at any time
* Add bulk actions to the Members admin page
* Add link to Members-List from the Membership list (click on the member-count value)
* Add logging for payment gateway transactions, can be viewed via "Billings > View Transaction logs"
* Add new functions to Payment Logs to manually handle invalid payments
* Add new option to customize which users are considered Admin users (details in the Help > Advanced page)
* Add new template tag function `ms_has_membership()`
* Add possibility to change payment options even when membership has active subscribers!
* Add template support so themes can define custom Membership pages (m2-account.php, m2-memberships.php, m2-protected-content.php, m2-register.php, m2-registration-complete.php, m2-invoice.php)
* Fix a caching issue that caused problems with Subscriptions when memcache was enabled
* Fix a critical bug that caused protection rules to be reset on plugin activation
* Fix a minor security hole in the data import module
* Fix a PHP error that occured when paying the first invoice of a member via manual gateway
* Fix a rare 403 error that happened when a user was accessing protected content
* Fix a typo in the PayPal Single gateway that caused M2 to ignore some payment information
* Fix a wrong action hook used in registration form that would display wrong fields when certain plugins are activated
* Fix a wrong parameters that limited search results of list tables to 5 items in some cases
* Fix Billings search logic to find all users that contain the search word (no exact username required anymore)
* Fix bug that did not activate Subscription when an invoice was paid via Manual Gateway
* Fix bug that prevented deleting invoices
* Fix bug that set the wrong expire date for "Finite Access" subscriptions
* Fix bug that would always add M2 menu items when a new membership is created
* Fix bug that would not give access to protected content while user is in trial period
* Fix bug that would send some emails even when the 'MS_STOP_EMAILS' flag was active
* Fix bugs that prevented removing protection from individual posts or pages
* Fix compatibility issue with WP Recaptcha during user registration
* Fix issue that did not display any "Page" rules in the Membership Overview screen
* Fix Mailchimp Add-on to fetch all lists from Mailchimp, not only 25
* Fix plugin logic to allow changing Protection Rules even when Content Protection is disabled
* Fix plugin translation using .mo files, added a readme file with instructions to /languages dir
* Fix possible memory issue that happened when updating the plugin
* Fix protection of the Private Message feature in the BuddyPress Add-on
* Fix rare error that happened when dripped content had no/invalid date settings
* Fix registration logic to honor domain limitation for signup email addresses
* Fix the bulk actions in the Protecion Rules page
* Fix the description text of the ms-protected-content shortcode for admin users
* Fix the search function on the Members page
* Fix two possible infinite loops that resulted in timeouts or rule values not being saved
* Fix wrong rounding-logic in Stripe gateway
* Fix wrong subscription logic that activated Subscriptions without payment in a few cases
* Fix wrong WordPress action that was called in the user-registration form
* Hide inactive memberships in the Members and Protection Rules pages
* Hide the Guest and Default memberships in the Members list, as they are useless there
* Remove condition that auto-injected missing M2 shortcodes on Membership pages
* Improve the BuddyPress Add-on to optionally use the M2 registration page
* Improve Transaction logs to also log inactice or invalid gateway calls
* Improved third party library by adding a class prefix to avoid class collisions (Stripe, Mailchimp, AuthorizeNet)
* Improved and added some API functions (see the API Docs link in the Help page)
* A lot of small improvements behind the scenes (cleanup, fix warnings, add new filters, etc.)

= 4.0.0.2 =
* Fix the import tool to import data from old Membership plugin correctly (recurring payments, subscription status and end date)
* Fix the i18n support, the plugin is translated correctly again
* Fix error message that was displayed when dates of dripped memberships were saved

= 4.0.0.1 =
* Fix fatal error that was displayed right after update
* Fix compatibility issues with PHP 5.2.4 - note that you still need PHP 5.3 to use the Stripe Gateways
* Fix a major bug in the automatic import wizard that prevented the plugin from importing old Membership data
* Fix wrong redirect during setup wizard that ended in "Not allowed to view page" errors
* Fix the protection rule for "Friendship Request" in the BuddyPress Add-on
* Fix a bug in the Coupons Add-on that discarded changes instead of saving them
* Fix some PHP notices and warnings that did happen during first setup
* Fix some typos in admin pages
* Rename page "Protected Content" to "Protection Rules"

= 4.0.0.0 =
* Plugin name changed from Protected Content to Membership 2 
* New official plugin API added (see Membership 2 > Help page) 
* New Payment gateway added: Stripe Subscriptions 
* New option in Membership Payment settings to disable individual payment gateways. 
* Improved payment settings page to use number-input fields instead of text-fields 
* Fix issue where Simulation (Test) mode added HTML code to all Ajax responses 
* Fix blank screen after submitting the password reset form 
* Minor improvements all over the place

= 3.4.4.3 =
* Fixed: Shortcodes now work on the Protected Content page.

= 3.4.4.2 =
* Modified: [subscriptionprice] shortcode now accepts new argument, level, to choose which price of the description to show.  E.g. [subscriptionprice subscription=“1” level=“1”]
* Fixed: Deleting a user now also drops the subscription. Also fixes incorrect membership counts.
* Fixed: Fixed deprecated database prepare code.
* Fixed: Deprecated PHP code.

= 2.0.7 =
* WP3.3 Styling Compatibility

= 2.0.6 =
* WP3.2 Compatibility

= 2.0.2 =
* Bug fixes

= 2.0 =
* Removes need for set admin usernames - now detects who activated plugin
* Added persistent configuration capability
* Added redirecting No Access page
* Added URL Groups settings and rules
* Added quick start steps
* Added Communications capability - automessage for membership
* Added Pings system
* Added integration with WP roles
* Added Account page setting and shortcode
* Added renewal and upgrade functionality and shortcode
* Added single payment paypal gateway
* Added upgrade and cancel capability to paypal gateways
* Fixed filtering problems with members admin page
* Fixed general bugs and other issues
* Added more hooks and filters for customisation
* Added define checks to completely override signup / subscription / renewal and account pages
* Added filters to override the register and account links for standard wordpress to now direct to membership pages.

= 1.0.2 =
* Allowed membership admin menu to be visible for all admin level users

= 1.0 =
* Initial release.