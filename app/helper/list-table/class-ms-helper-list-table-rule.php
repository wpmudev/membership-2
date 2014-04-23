<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 * 
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License, version 2, as  
 * published by the Free Software Foundation.                           
 *
 * This program is distributed in the hope that it will be useful,      
 * but WITHOUT ANY WARRANTY; without even the implied warranty of       
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        
 * GNU General Public License for more details.                         
 *
 * You should have received a copy of the GNU General Public License    
 * along with this program; if not, write to the Free Software          
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               
 * MA 02110-1301 USA                                                    
 *
*/

/**
 * Membership List Table 
 *
 *
 * @since 4.0.0
 *
 */
class MS_Helper_List_Table_Rule extends MS_Helper_List_Table {

	protected $id = 'rule';
	
	const RULE_SAVE_NONCE = 'rule_save_nonce';
	
	protected $nonce;
	
	protected $model;
	
	public function __construct( $model ) {
		parent::__construct();
		$this->model = $model;
		$this->nonce = wp_create_nonce( self::RULE_SAVE_NONCE );
	}
		
	public function get_columns() {
		return apply_filters( "membership_helper_list_table_{$this->id}_columns", array(
			'cb'     => '<input type="checkbox" />',
			'content' => __( 'Content', MS_TEXT_DOMAIN ),
			'rule_type' => __( 'Rule type', MS_TEXT_DOMAIN ),
			'dripped' => __( 'Dripped Content', MS_TEXT_DOMAIN ),
		) );
	}
	
	public function get_hidden_columns() {
		return apply_filters( "membership_helper_list_table_{$this->id}_hidden_columns", array() );
	}
	
	public function get_sortable_columns() {
		return apply_filters( "membership_helper_list_table_{$this->id}_sortable_columns", array(
				'content' => 'content',
				'access' => 'access',
				'dripped' => 'dripped',
		) );
	}
	
	public function get_bulk_actions() {
		return apply_filters( "membership_helper_list_table_{$this->id}_bulk_actions", array(
				'give_access' => __( 'Give Access', MS_TEXT_DOMAIN ),
				'no_access' => __( 'No Access', MS_TEXT_DOMAIN ),
				'drip_access' => __( 'Drip Access', MS_TEXT_DOMAIN ),
		) );
	}
	
	public function prepare_items() {
	
		$this->items = apply_filters( "membership_helper_list_table_{$this->id}_items", $this->model->get_content() );
	
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
	}
	
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			default:
				$html = print_r( $item, true ) ;
				break;
		}
		return $html;
	}
	
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="item[]" value="%1$s" %2$s/>', $item->id, checked( $item->access, true, false ) );
	}
	
	public function column_access( $item ) {
	
		if( $item->access ) {
			$status =  ( $item->delayed_period ) ? __( 'Dripped Content', MS_TEXT_DOMAIN ) : __( 'Has Access', MS_TEXT_DOMAIN );
			if( $item->delayed_period ) {
				$actions = array(
						sprintf( '<a href="?page=%s&tab=%s&membership_id=%s&action=%s&item=%s&rule_save_nonce=%s">%s</a>',
								$_REQUEST['page'],
								$_REQUEST['tab'],
								$_REQUEST['membership_id'],
								'no_access',
								$item->id,
								$this->nonce,
								__('Remove Access', MS_TEXT_DOMAIN )
						),
				);
			}
		}
		else {
			$status = __( 'No Access', MS_TEXT_DOMAIN );
			$actions = array(
					sprintf( '<a href="?page=%s&tab=%s&membership_id=%s&action=%s&item=%s&rule_save_nonce=%s">%s</a>',
							$_REQUEST['page'],
							$_REQUEST['tab'],
							$_REQUEST['membership_id'],
							'give_access',
							$item->id,
							$this->nonce,
							__('Give Access', MS_TEXT_DOMAIN )
					),
					sprintf( '<a href="?page=%s&tab=%s&membership_id=%s&action=%s&item=%s&rule_save_nonce=%s">%s</a>',
							$_REQUEST['page'],
							$_REQUEST['tab'],
							$_REQUEST['membership_id'],
							'drip_access',
							$item->id,
							$this->nonce,
							__('Drip Access', MS_TEXT_DOMAIN )
					),
			);
		}
		//nonce in url is NOT a good pratice
		//$actions = apply_filters( "membership_helper_list_table_{$this->id}_column_access_actions", $actions, $item );
		$actions = array();
		return sprintf( '%1$s %2$s', $status, $this->row_actions( $actions ) );
	}
	
	public function column_dripped( $item ) {
		$actions = array(
				sprintf( '<a href="?page=%s&tab=%s&membership_id=%s&action=%s&item=%s">%s</a>',
						$_REQUEST['page'],
						$_REQUEST['tab'],
						$_REQUEST['membership_id'],
						'drip_edit',
						$item->id,
						__('Edit', MS_TEXT_DOMAIN )
				),
		);
		$actions = apply_filters( "membership_helper_list_table_{$this->id}_column_dripped_actions", $actions, $item );
	
		return sprintf( '%1$s %2$s', $item->delayed_period, $this->row_actions( $actions ) );
	}
	
	public function display() {
		wp_nonce_field( self::RULE_SAVE_NONCE, self::RULE_SAVE_NONCE );
		
		parent::display();
	}
	
	public function get_views(){
		return apply_filters( "membership_helper_list_table_{$this->id}_views", array(
				'all' => sprintf( '<a href="%s">%s</a>', add_query_arg( array ('status' => 'all') ), __( 'All', MS_TEXT_DOMAIN ) ),
				'has_access' => sprintf( '<a href="%s">%s</a>', add_query_arg( array ('status' => 'has_access') ), __( 'Has Access', MS_TEXT_DOMAIN ) ),
				'dripped' => sprintf( '<a href="%s">%s</a>', add_query_arg( array ('status' => 'dripped') ), __( 'Dripped Content', MS_TEXT_DOMAIN ) ),
				'no_access' => sprintf( '<a href="%s">%s</a>', add_query_arg( array ('status' => 'no_access') ), __( 'No Access', MS_TEXT_DOMAIN ) ),
		) );
	}
}
