# Retention Policy for Comments

Allows to configure a retention period of IP addresses of commenters by specifying how old the comment should be before the IP is deleted.  
In the future the plugin may be extended to support other comment fields like user agents or URLs.

## Installation

If you're using [Composer](https://getcomposer.org/) to manage dependencies, you can use the following command to add the plugin to your site:

```bash
composer require wearerequired/comment-retention-policy
```

## Setup

WordPress' default retention period is to keep the data indefinitely. To configure the retention period:

1. Navigate to the Discussion settings below the general Settings menu item.
2. Scroll to Retention Period
3. Select any of the following options:
   1. Keep data (default) – No retention period will be configured and the data will be kept indefinitely.
   2. Delete IPs for comments older than – Use this option to delete comment IPs that are older than the configured retention period. You can specify a number of days, weeks, or months.
