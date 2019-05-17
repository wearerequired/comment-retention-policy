<?php
/**
 * Plugin Name: Retention Policy for Comments
 * Plugin URI:  https://github.com/wearerequired/comment-retention-policy
 * Description: Configure a retention period of IP addresses of commenters by specifying how old the comment should be before the IP is deleted.
 * Version:     0.1.0
 * Author:      required
 * Author URI:  https://required.com
 * Text Domain: comment-retention-policy
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * Copyright (c) 2019 required (email: info@required.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Required\CommentRetentionPolicy;

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

const PLUGIN_DIR  = __DIR__;
const PLUGIN_FILE = __FILE__;

register_activation_hook( PLUGIN_FILE, __NAMESPACE__ . '\on_plugin_activation' );
register_deactivation_hook( PLUGIN_FILE, __NAMESPACE__ . '\on_plugin_deactivation' );

bootstrap();
