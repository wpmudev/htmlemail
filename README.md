# HTML Email Templates

**INACTIVE NOTICE: This plugin is unsupported by WPMUDEV, we've published it here for those technical types who might want to fork and maintain it for their needs.**


Add an HTML header and footer to your WordPress system emails for better brand integration. Use built-in templates or create your own layout. Plus with Multisite integration, you can set custom templates for each site on your network.

* Style WordPress system emails 
* Includes 8 starter templates 
* Make notification responsive 
* Style preview and send a test email 
* Add custom branding to system emails 
* Embed social links 

## HTML Email Templates allows you to use your beautiful HTML email templates for emails WordPress sends.

By default, WordPress sends out plain boring email notifications for events such as new comments, comments in moderation and new users.

### Spice Up System Notifications

This plugin is for any brand-freak that loves completely white-labelled products, or for those who just want WordPress to send nice emails.

![html-email-templates-2][40]

Get WordPress to send beautiful emails to you!

### Complete Email Branding

Enable template override and choose different templates for each site on your network.You can even preview your styles and changes to see what they will look like.

HTML Email Templates for WordPress lets you brand one of the most important parts of your Website – Email Notifications.

### To Get Started:

_Note for Multisite installs: this plugin is designed to be network activated and, by default, it rebrands the emails for all sites network-wide. However, you can enable a template override option in network settings to allow different templates per site. More on this below._

Once installed and network-activated, you’ll see a new menu item in the Settings menu in your network admin: HTML Email Template.

![HTML Email Templates Network Menu][43]

When activated on a single site install, you’ll see the new menu item under Settings in your wp-admin. Note that if you enable the template override option in a network install, each sub-site in your network can access the settings here too.

![HTML Email Templates Single Site Menu][44]

### Configuring Your Email Template

The first thing you’ll likely notice is that are really no settings for this plugin; it just works. :)

All you need to do is configure the HTML that will be used to rebrand all emails sent by your WordPress install, even the ones from BuddyPress if you have that installed.

If you’re not very handy with HTML, don’t worry, we’ve got you covered with several pre-made templates you can choose from. Simply click the “Choose from sample Templates” toggle located just beneath Step 1 in the instructions to pop open a slider where you can scroll through the available templates.

![HTML Email Templates Template Select][45]

Click the thumbnail of any template you like to highlight it, then click the “Load Template…” button to populate the large text area below with all the glorious pre-made HTML.

![HTML Email Templates Template Load][46]

Now click the “Preview” button to pop open a live preview of what your emails will look like with your selected template.

![HTML Email Templates Template Preview][47]

If you are happy with how your template looks out-of-the-box, click the “Save” button. Then click the “Test Email” button, enter your own email address and click “Send” to see your work pay off in your own inbox. Now all your WordPress emails will be sent using your selected template. How cool is that?

### Allow Subsite Templates

If you are running a multisite install and want to allow your subsite admins to override your network template with their own custom stuff, check the box at the bottom of the network settings screen.

![HTML Email Templates Network Override][48]

Every site in your network will now have a settings screen enabling admins to customize the template to be used for all emails from their sites.

### Customizing a Template

If you are not quite happy with how your selected template looks by default, you can customize it to your heart’s content by simply editing the HTML in the large textarea.

If you do not want to use any of the pre-made templates, you can enter your own custom HTML instead. Simply delete what is already there, and paste in your own.

You’ll notice that there are several variable placeholders throughout the HTML in each template, enclosed in parentheses like so:  
`{MESSAGE} `

To see all the available variables you can use when customizing your template, click the “List of variables that can be used in template” toggle located just beneath Step 1 in the instructions.

![HTML Email Templates Template Variables][50]

Each of those variables can be used anywhere you need them in your template. The one that must be used somewhere in your template to ensure that all your WordPress emails work properly is:

* `{MESSAGE}` Outputs the email content (required)

All of these are optional:

* `{SIDEBAR_TITLE}` Title for the sidebar in templates with a sidebar. Default is “What’s New?”.
* `{FROM_NAME}` Sender’s name if sender’s email is associated with a user account.
* `{FROM_EMAIL}` Sender’s email, email specified in site Settings.
* `{BLOG_URL}` Blog / Site URL.
* `{BLOG_NAME}` Blog / Site name.
* `{ADMIN_EMAIL}` Email address of the support or contact person. Same as `{FROM_EMAIL}`
* `{BLOG_DESCRIPTION}` Taken from the Tagline as entered in the site Settings.
* `{DATE}` Current date as configured in the site Settings.
* `{TIME}` Current time as configured in the site Settings.

### Additional Customization Options

You may have noticed that there is one sample template that has a sidebar in it. That template also has something special that can be used in other templates too: a linked list of your most recent blog posts.

Go ahead and select that template now; you can’t miss it, it’s called “Sidebar”. :)

Scroll down to about halfway through the HTML until you see 4 list items like this:  
`
https://premium.wpmudev.org/%7BPOST_1_LINK%7D" style="margin: 0; padding: 10px 16px; font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; color: #666; border-bottom: 1px solid #777777; border-top: 1px solid #FFFFFF; cursor: pointer; display: block; margin-right: 10px; text-decoration: none;">{POST_1} »
`

Those 4 list items will include the titles of the 4 most recent posts in your blog, and link back to them on your site.

The code can be copied to any other template, or used in your own custom HTML too. You can modify the HTML & CSS to fit your branding, but there are 2 things that must be present in each one for this feature to work:  
`href="https://premium.wpmudev.org/%7BPOST_1_LINK%7D"  
{POST_1}`

Those are the filters & placeholders that fetch the permalink & title of the posts. You can have up to 4 of them as seen in the “Sidebar” template, but if you only want 1, 2 or 3, simply remove what you don’t need.

Advanced: there are also 2 filters you can use in case you want to include some other posts in your emails:

* `htmlemail_sidebar_title` to change the sidebar title (takes a string).
* `htmlemail_sidebar_posts` to change the post list (takes an array of posts containing ID and post_title).

[40]: https://premium.wpmudev.org/wp-content/uploads/2010/06/html-email-templates-2.jpg
[43]: https://premium.wpmudev.org/wp-content/uploads/2010/06/html-email-templates-2000-menu-network.png
[44]: https://premium.wpmudev.org/wp-content/uploads/2010/06/html-email-templates-2000-menu-single.png
[45]: https://premium.wpmudev.org/wp-content/uploads/2010/06/html-email-templates-2000-template-select.png
[46]: https://premium.wpmudev.org/wp-content/uploads/2010/06/html-email-templates-2000-template-load.png
[47]: https://premium.wpmudev.org/wp-content/uploads/2010/06/html-email-templates-2000-template-preview.png
[48]: https://premium.wpmudev.org/wp-content/uploads/2010/06/html-email-templates-2000-network-override.png
[50]: https://premium.wpmudev.org/wp-content/uploads/2010/06/html-email-templates-2000-template-variables.png
