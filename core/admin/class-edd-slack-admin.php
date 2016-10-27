<?php
/**
 * The admin settings side to EDD Slack
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/admin
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Admin {

    /**
	 * EDD_Slack_Admin constructor.
	 *
	 * @since 1.0.0
	 */
    function __construct() {

        // Register Settings Section
        add_filter( 'edd_settings_sections_extensions', array( $this, 'settings_section' ) );

        // Register Settings
        add_filter( 'edd_settings_extensions', array( $this, 'settings' ) );

        // Enqueue CSS/JS on our Admin Settings Tab
        add_action( 'edd_settings_tab_top_extensions_edd-slack-settings', array( $this, 'admin_settings_scripts' ) );
        
        add_action( 'edd_slack_post_id', array( $this, 'post_id_field' ) );

    }
    
    /**
    * Register Our Settings Section
    * 
    * @access       public
    * @since        1.0.0
    * @param        array $sections EDD Settings Sections
    * @return       array Modified EDD Settings Sections
    */
    public function settings_section( $sections ) {

        $sections['edd-slack-settings'] = __( 'Slack', EDD_Slack_ID );

        return $sections;

    }

    /**
    * Adds new Settings Section under "Extensions". Throws it under Misc if EDD is lower than v2.5
    * 
    * @access      public
    * @since       1.0.0
    * @param       array $settings The existing EDD settings array
    * @return      array The modified EDD settings array
    */
    public function settings( $settings ) {
        
        // Initialize repeater
        $repeater_values = array();
        $fields = EDDSLACK()->get_notification_fields();
        
        $feeds = get_posts( array(
            'post_type'   => 'edd-slack-rbm-feed',
            'numberposts' => -1,
            'order'       => 'ASC',
        ) );
        
        if ( ! empty( $feeds ) && ! is_wp_error( $feeds ) ) {
            
            foreach ( $feeds as $feed ) {
                
                $value = array(
                    'admin_title'  => get_the_title( $feed->ID ), // The first element in this Array is used for the Collapsable Title
                    'post_id'      => $feed->ID,
                );
                
                // Conditionally Hide certain fields
                $trigger = get_post_meta( $feed->ID, 'edd_slack_rbm_feed_trigger', true );
                $trigger = ( $trigger ) ? $trigger : 0;
                
                foreach ( $fields as $field_id => $field ) {
                    
                    if ( $field_id == 'post_id' || $field_id == 'admin_title' ) continue; // We don't need to do anything special with these
                    
                    $value[ $field_id ] = get_post_meta( $feed->ID, "edd_slack_rbm_feed_$field_id", true );
                    
                    if ( $field_id = 'replacement_hints' ) {
                        
                        $value[ $field_id ] = $trigger;
                        
                    }
                    
                }
                
                $repeater_values[] = $value;
                
            }
            
        }

        $edd_slack_settings = array(
            array(
                'type' => 'text',
                'name' => _x( 'Default Webhook URL', 'Default Webhook URL Label', EDD_Slack_ID ),
                'id' => 'slack_webhook_default',
            ),
            array(
                'type' => 'text',
                'name' => _x( 'Timestamp Format', 'Timestamp Format Label', EDD_Slack_ID ),
                'id' => 'slack_timestamp_format',
                'std' => 'm/d/Y @ g:i A',
                'desc' => _x( '<a href="//php.net/manual/en/function.date.php" target="_blank">Click Here</a> for Format Options. This applies to all %timestamp% Replacements', 'Timestamp Format Help Text', EDD_Slack_ID ),
            ),
            array(
                'type' => 'rbm_repeater',
                'id' => 'slack_notifications',
                'input_name' => 'edd_slack_rbm_feeds',
                'name' => _x( 'Slack Notifications', 'Slack Notifications Repeater Label', EDD_Slack_ID ),
                'std' => $repeater_values,
                'sortable' => false,
                'collapsable' => true,
                'layout' => 'row',
                'add_item_text' => _x( 'Add Slack Notification', 'Add Slack Notification Button', EDD_Slack_ID ),
                'delete_item_text' => _x( 'Delete Slack Notification', 'Delete Slack Notification Button', EDD_Slack_ID ),
                'collapsable_title' => _x( 'New Slack Notification', 'New Slack Notification Header', EDD_Slack_ID ),
                'fields' => $fields,
            ),
        );

        // If EDD is at version 2.5 or later...
        if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
            // Place the Settings in our Settings Section
            $edd_slack_settings = array( 'edd-slack-settings' => $edd_slack_settings );
        }

        return array_merge( $settings, $edd_slack_settings );

    }

    /**
     * Enqueue our CSS/JS on our Admin Settings Tab
     * 
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function admin_settings_scripts() {

        wp_enqueue_style( EDD_Slack_ID . '-admin' );
        wp_enqueue_script( EDD_Slack_ID . '-admin' );

    }

}