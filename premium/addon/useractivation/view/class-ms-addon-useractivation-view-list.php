<?php
/**
 * Renders unapproved members
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since  1.0.3
 *
 * @package Membership2
 * @subpackage View
 */
class MS_Addon_Useractivation_View_List extends MS_View {
    
    public function to_html() {
        
        $members = MS_Factory::create( 'MS_Addon_Useractivation_Helper_Listtable' );
	$members->prepare_items();
        
        $title = __( 'Unapproved Members', 'membership2' );
        
        ob_start();
        ?>
        <div class="wrap ms-wrap">
            <?php
                MS_Helper_Html::settings_header(
                    array(
                        'title' => $title,
                        'title_icon_class' => 'wpmui-fa wpmui-fa-ticket',
                    )
                );
            ?>
            
            <form action="" method="post">
                <?php $members->display(); ?>
            </form>
        </div>
        <?php
        $html = ob_get_clean();

	return apply_filters( 'ms_addon_useractivation_view_list_to_html', $html, $this );
            
    }
    
}