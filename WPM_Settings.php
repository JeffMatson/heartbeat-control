<?php
/**
 * Contains the Heartbeat_Control\WPM_Settings class.
 *
 * @package Heartbeat_Control
 */

namespace Heartbeat_Control;

defined('ABSPATH') || die('Cheatin\' uh?');

/**
 * Primary settings page class.
 */
class WPM_Settings {

	protected $plugins_block = array();

	/**
	 * WPM_Settings constructor.
	 * @return void
	 */
	public function __construct(){
		add_action( 'cmb2_render_slider', array( $this, 'render_slider_field' ), 10, 5 );

		//we need this objects to declare there controller right now.
		$imagify_partner = new Imagify_Partner( 'heartbeat-control' );
		$imagify_partner->init();
		$this->plugins_block = array(
			'rocket-lazy-load' 	=> new Plugin_Card_Helper( array( 'plugin_slug' => 'rocket-lazy-load' ) ),
			'wp-rocket'			=> new Plugin_Card_Helper( array( 'plugin_slug' => 'wp-rocket' ) ),
			'imagify'			=> new Plugin_Card_Helper( array( 'plugin_slug' => 'imagify' ), array(
				'imagify_partner' => $imagify_partner,
			) ),
		);
	}

	/**
	 * slider field render.
	 * @return void
	 */
	public function render_slider_field( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		echo '<div class="slider-field"></div>';
		echo $field_type_object->input( array(
			'type'       => 'hidden',
			'class'      => 'slider-field-value',
			'readonly'   => 'readonly',
			'data-start' => absint( $field_escaped_value ),
			'data-min'   => intval( $field->min() ),
			'data-max'   => intval( $field->max() ),
			'data-step'  => intval( $field->step() ),
			'desc'       => '',
		) );
		echo '<span class="slider-field-value-display">' . esc_html( $field->value_label() ) . ' <span class="slider-field-value-text"></span></span>';
		$field_type_object->_desc( true, true );
	}

	/**
	 * option admin page controller
	 * @param $hookup CMB2_hookup
	 * @return void
	 */
	public function admin_controller_options( $hookup ){
		$cmb_form = cmb2_metabox_form( $hookup->cmb, $hookup->cmb->cmb_id, array(
			'echo'		 	=> false,
			'save_button'	=> __( 'Save changes', 'heartbeat-control' )
		) );
		$plugins_block = $this->plugins_block;
		$asset_image_url = HBC_PLUGIN_URL.'assets/img/';
		$notices = Notices::get_instance();
		include( HBC_PLUGIN_PATH.'views/admin-page.php' );
	}

	/**
	 * option admin page enqueue script and style
	 * @param $hook string, use for context validation
	 * @return void
	 */
	public function enqueue_scripts( $hook ){
		if ( $hook !== 'settings_page_heartbeat_control_settings' ){ return; }
		wp_register_script( 'hbc_admin_script', HBC_PLUGIN_URL.'assets/js/script.js', array( 'jquery', 'jquery-ui-slider' ), '1.0.0' );
		wp_enqueue_script( 'hbc_admin_script' );
		wp_register_style( 'slider_ui', '//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.min.css', array(), '1.0' );
		wp_enqueue_style( 'slider_ui' );
		wp_register_style( 'hbc_admin_style', HBC_PLUGIN_URL.'assets/css/style.min.css' , array(), '1.0' );
		wp_enqueue_style( 'hbc_admin_style' );

	}

	/**
	 * declare cmb2 metaboxes
	 * @return void
	 */
	public function init_metaboxes(){
		add_action('admin_enqueue_scripts', array( $this, 'enqueue_scripts' ));
		add_action( 'cmb2_save_options-page_fields' , function( $object_id, $cmb_id, $updated, $t ){
			if( 'heartbeat_control_settings' === $object_id && $updated ){
				$notices = Notices::get_instance();
				$notices->append( 'success', __( 'Your changes have been saved successfully !', 'heartbeat-control' ) );
			}
		}, 10, 4 );

		$behavior = array(
			'name'    => __( 'Heartbeat Behavior', 'heartbeat-control' ),
			'id'      => 'heartbeat_control_behavior',
			'type'    => 'radio_inline',
			'default' => 'allow',
			'classes' => 'heartbeat_behavior',
			'options' => array(
				'allow'   => __( 'Allow Heartbeat', 'heartbeat-control' ),
				'disable' => __( 'Disable Heartbeat', 'heartbeat-control' ),
				'modify'  => __( 'Modify Heartbeat', 'heartbeat-control' ),
			),
		);

		$frequency = array(
			'name'    => __( 'Override Heartbeat frequency', 'heartbeat-control' ),
			'id'      => 'heartbeat_control_frequency',
			'type'    => 'slider',
			'min'     => '15',
			'step'    => '1',
			'max'     => '300',
			'default' => '15',
			'classes' => 'heartbeat_frequency',
		);

		$cmb_options = new_cmb2_box(array(
			'id'			=> 'heartbeat_control_settings',
			'title'			=> __( 'Heartbeat Control', 'heartbeat-control' ),
			'object_types' 	=> array( 'options-page' ),
			'option_key'	=> 'heartbeat_control_settings',
			'capability'	=> 'manage_options',
			'parent_slug'	=> 'options-general.php',
			'display_cb'	=> array( $this, 'admin_controller_options' ),
		));

		$dash_group = $cmb_options->add_field( array(
			'id'          	=> 'rules_dash',
			'type'        	=> 'group',
			'repeatable'	=> false,
			'options'     	=> array(
				'group_title'	=> '<span class="dashicons dashicons-dashboard"></span> '.__( 'WordPress Dashboard', 'heartbeat-control' ),
			),
		) );
		$cmb_options->add_group_field( $dash_group, $behavior );
		$cmb_options->add_group_field( $dash_group, $frequency );

		$front_group = $cmb_options->add_field( array(
			'id'          	=> 'rules_front',
			'type'			=> 'group',
			'repeatable'	=> false,
			'options'     	=> array(
				'group_title'	=> '<span class="dashicons dashicons-admin-appearance"></span> '.__( 'Frontend', 'heartbeat-control' ),
			),
		) );
		$cmb_options->add_group_field( $front_group, $behavior );
		$cmb_options->add_group_field( $front_group, $frequency );

		$editor_group = $cmb_options->add_field( array(
			'id'          	=> 'rules_editor',
			'type'        	=> 'group',
			'repeatable'	=> false,
			'options'    	=> array(
				'group_title'	=> '<span class="dashicons dashicons-admin-post"></span> '.__( 'Post editor', 'heartbeat-control' ),
			),
		) );
		$cmb_options->add_group_field( $editor_group, $behavior );
		$cmb_options->add_group_field( $editor_group, $frequency );

	}

}
