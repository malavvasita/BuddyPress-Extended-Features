# BuddyPress Extended Features

A WordPress plugin that extends BuddyPress with additional sharing features, allowing users to share content with their friends via private messages.

## Description

BuddyPress Extended Features adds a "Share" button to posts, pages, and BuddyPress activities. When clicked, it opens a modal showing the user's BuddyPress friends list, allowing them to select friends and share the content via private messages.

## Features

- **Share Button**: Adds a customizable share button to selected content types
- **Friends Modal**: Beautiful modal interface showing user's friends with avatars
- **Search Functionality**: Filter friends list by name
- **Select All**: Quick option to select all friends
- **AJAX Powered**: Smooth, non-refreshing user experience
- **Admin Settings**: Comprehensive settings page to configure the plugin
- **WordPress Coding Standards**: Fully compliant with PHPCS and WPCS
- **OOP Architecture**: Clean, object-oriented code structure

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- BuddyPress plugin (active)
- BuddyPress Private Messaging component (must be enabled)
- BuddyPress Friends component (optional, but required for friends list)

## Installation

1. Upload the `buddypress-extended-features` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure BuddyPress is installed and activated
4. Enable the Private Messaging component in BuddyPress settings
5. Configure the plugin settings under Settings > BP Extended Features

## Configuration

### Settings Page

Navigate to **Settings > BP Extended Features** to configure:

- **Enable Share Feature**: Toggle the share functionality on/off
- **Content Types**: Select which post types and activities should display the share button
- **Share Button Text**: Customize the text displayed on the share button
- **Share Button CSS Class**: Add custom CSS classes for styling

## Usage

1. Once configured, the share button will appear on enabled content types
2. Logged-in users can click the share button
3. A modal will open showing their BuddyPress friends
4. Users can search for friends and select one or more
5. Click "Send" to share the content via private message

## Technical Details

### File Structure

```
buddypress-extended-features/
├── buddypress-extended-features.php  # Main plugin file
├── includes/
│   ├── class-component-checker.php   # Checks BuddyPress components
│   ├── class-admin-settings.php      # Admin settings page
│   └── class-share-feature.php       # Share functionality
├── assets/
│   ├── css/
│   │   └── share-style.css           # Styles for share button and modal
│   └── js/
│       └── share-script.js           # JavaScript for modal and AJAX
└── README.md                          # This file
```

### Hooks and Filters

The plugin uses standard WordPress and BuddyPress hooks:

- `the_content` - Adds share button to post content
- `bp_activity_entry_meta` - Adds share button to activities
- `wp_ajax_bpef_get_friends` - AJAX handler for friends list
- `wp_ajax_bpef_send_share` - AJAX handler for sending shares

### Security

- All AJAX requests use nonces for verification
- All user input is sanitized and validated
- Database queries use prepared statements
- All output is properly escaped

## Coding Standards

This plugin follows:
- WordPress Coding Standards (WPCS)
- PHP_CodeSniffer (PHPCS) with WordPress-Core, WordPress-Extra, and WordPress-Docs rulesets
- PSR-4 autoloading standards
- Object-Oriented Programming principles

## Support

For issues, feature requests, or contributions, please contact the plugin author.

## Changelog

### 1.0.0
- Initial release
- Share button functionality
- Friends modal interface
- Admin settings page
- Search and filter functionality

## License

GPL-2.0-or-later

