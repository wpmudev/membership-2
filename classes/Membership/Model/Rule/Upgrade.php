<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * Rule class responsible just for upgrade message
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 */
class Membership_Model_Rule_Upgrade extends Membership_Model_Rule {

	public function on_creation() {
		$this->name = 'upgrade';
	}
	public function admin_sidebar( $dragged ) {
		?>
			<li>
				<a class="m-pro-update" href="http://premium.wpmudev.org/project/membership/" title="<?php _e('Upgrade Now', 'membership'); ?> &raquo;"><?php _e('Upgrade to enable these rules &raquo;', 'membership'); ?></a>
			</li>	
		<?php 
	}
}
