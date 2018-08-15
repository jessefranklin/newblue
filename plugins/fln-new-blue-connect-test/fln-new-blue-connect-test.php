<?php
/*
Plugin Name: FLN New Blue Connect Test
Version:     1.1
Description: This plugin is to test the New Blue Connect Site.
Author:      Andy Stubbs
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	exit();
}

function fln_new_blue_connect_test_log( $message ) {
	date_default_timezone_set( 'America/Los_Angeles' );
	$stamp = date('Y-m-d h:i:sa');
	if( is_array( $message ) || is_object( $message ) ) {
		$message = print_r( $message, true );
	}
	$message = "[$stamp] $message" . "\n";
	$fileName = plugin_dir_path( __FILE__ ) . 'log.txt';
	if( file_exists( $fileName ) ) {
		$message = file_get_contents( $fileName ) . $message . "\n";
	}
	file_put_contents( $fileName, $message );
}

function fln_new_blue_connect_test_install() {
	global $wpdb;

	//Create the invites table
	$table_name = $wpdb->prefix . 'fln_nbc_invites';
	fln_new_blue_connect_log( $table_name );
	$sql = "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_NAME = '$table_name'";
	fln_new_blue_connect_log( $sql );
	$count = $wpdb->get_var( $sql );
	fln_new_blue_connect_log( $count );
	if( $count == 0 ) {
		$sql = "CREATE TABLE $table_name (
				id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				event_id MEDIUMINT(9) NOT NULL,
				email VARCHAR(254) NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";
		fln_new_blue_connect_log( $sql );
		$wpdb->query( $sql );
	}
}

class FlnNewBlueConnectTest {

	public function __construct() {
		add_shortcode( 'fln-test-new-blue-connect-ajax', array( $this, 'test_new_blue_connect_ajax' ) );
		add_shortcode( 'fln-test-is-user-invited', array( $this, 'test_is_user_invited' ) );
		add_action( 'wp_ajax_fln_get_super_groups', array( $this, 'fln_ajax_get_super_groups' ) );
		add_action( 'wp_ajax_fln_get_sites', array( $this, 'fln_ajax_get_sites' ) );
		add_action( 'wp_ajax_fln_get_employees_by_site', array( $this, 'fln_ajax_get_employees_by_site' ) );
		add_action( 'wp_ajax_fln_get_employees_by_bu', array( $this, 'fln_ajax_get_employees_by_bu' ) );
		add_action( 'wp_ajax_fln_invite_guests', array( $this, 'fln_ajax_invite_guests' ) );
	}

	public function is_user_invited( $email, $event_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fln_nbc_invites';
		$safe_email = sanitize_email( $email );
		$safe_event_id = ( int )$event_id;
		$sql = "SELECT COUNT(*) FROM $table_name WHERE event_id = '$safe_event_id' AND email = '$safe_email'";
		$this->log( $sql );
		$count = $wpdb->get_var( $sql );
		return $count > 0;
	}

	public function test_is_user_invited( $atts ) {
		$msg = 'Is user ' . $atts[ 'email' ] . ' invited to ' . $atts[ 'event_id' ] . 
			'. Expected result is ' . $atts[ 'expected' ] . '. Result: ';
		if( $this->is_user_invited( $atts[ 'email' ], $atts[ 'event_id' ] ) ) {
			$msg .= '1. ';
			if( $atts[ 'expected' ] == 1 ) {
				$msg .= '<b>Success!</b>';
			}
		} else {
			$msg .= '0. ';
			if( $atts[ 'expected' ] == 0 ) {
				$msg .= '<b>Success!</b>';
			}
		}
		return $msg;
	}

	public function test_new_blue_connect_ajax() {
		
		if( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		return '' . 
			'<script>var ajax_url = "'. admin_url( 'admin-ajax.php' ) . '"; </script>' .
			file_get_contents( plugin_dir_path( __FILE__ ) . 'test-ajax.html' );
	}

	public function fln_ajax_invite_guests() {
		if( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'You do not have access to this feature.' );
			return;
		}
		
		$event_data = $_POST[ 'event_data' ];
		//$this->log( $event_data );
		$event_id = ( int )$event_data[ 'event_id' ];

		if( $event_data[ 'group' ] ) {
			$this->log( 'Sending emails to group' );
			foreach( $event_data[ 'group' ] as $group ) {
				$group_name = sanitize_text_field( $group );
				$this->log( $group_name );

				//Add emails to track invitees to an event
				$emp_data = $this->get_employees_by_bu( $group_name );
				$this->log( $emp_data );
				$custom_list = array();
				foreach( $emp_data as $emp ) {
					$custom_list[] = $emp->email;
				}
				$this->add_emails_to_invitees_database( $custom_list, $event_id );
			}
		}
		if( $event_data[ 'custom_list' ] ) {
			$this->log( 'Sending emails to custom list' );
			$unclean_custom_list = preg_split( '/\n|\r\n?/', $event_data[ 'custom_list' ] );
			$custom_list = array();
			foreach( $unclean_custom_list as $email ) {
				$clean_email = sanitize_email( $email );
				if( ! empty( $clean_email ) ) {
					$custom_list[] = $clean_email;
				}
			}
			$this->log( count( $custom_list ) );

			//Add emails to track invitees to an event
			$this->add_emails_to_invitees_database( $custom_list, $event_id );
		}
		wp_send_json_success( 'Emails sent successfully.' );
	}

	public function fln_ajax_get_super_groups() {
		if( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'You do not have access to this feature.' );
			return;
		}
		$entries = array();
		$entry = new stdClass();
		$entry->bu = 'TECHNOLOGY MANUFACTURING SGROUP';
		$entries[] = $entry;
		
		$entry = new stdClass();
		$entry->bu = 'SILICON ENGINEERING SUPERGROUP';
		$entries[] = $entry;
		
		$entry = new stdClass();
		$entry->bu = 'DCG SUPERGROUP';
		$entries[] = $entry;
		
		$entry = new stdClass();
		$entry->bu = 'COMM & DEVICES SUPERGROUP';
		$entries[] = $entry;
		
		$entry = new stdClass();
		$entry->bu = 'NVM SOLUTIONS SUPER GROUP';
		$entries[] = $entry;
		
		wp_send_json( $entries );
	}

	public function fln_ajax_get_sites() {
		if( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'You do not have access to this feature.' );
			return;
		}
		$entries = array();
		
		$entry = new stdClass();
		$entry->site = 'RA';
		$entries[] = $entry;
		
		$entry = new stdClass();
		$entry->site = 'JF';
		$entries[] = $entry;
		
		$entry = new stdClass();
		$entry->site = 'PG';
		$entries[] = $entry;
		
		$entry = new stdClass();
		$entry->site = 'FM';
		$entries[] = $entry;
		
		$entry = new stdClass();
		$entry->site = 'MI';
		$entries[] = $entry;
		
		wp_send_json( $entries );
	}

	private function get_employees_by_bu( $bu ) {
		$entries = array();
		
		$entry = new stdClass();
		$entry->id = 89704;
		$entry->idsid = 'mffallas';
		$entry->name = 'Fallas navarrete, Moises F';
		$entry->bu = $bu;
		$entry->site = 'CR';
		$entry->email = 'moises.f.fallas.navarrete@intel.com';
		$entries[] = $entry;
		
		$entry = new stdClass();
		$entry->id = 56865;
		$entry->idsid = 'laykeene';
		$entry->name = 'New, Lay Kee';
		$entry->bu = $bu;
		$entry->site = 'PG';
		$entry->email = 'lay.kee.new@intel.com';
		$entries[] = $entry;
		
		$entry = new stdClass();
		$entry->id = 54710;
		$entry->idsid = 'mlsorbel';
		$entry->name = 'Sorbel, Marie L';
		$entry->bu = $bu;
		$entry->site = 'RA';
		$entry->email = 'marie.l.sorbel@intel.com';
		$entries[] = $entry;

		return $entries;
	}

	public function fln_ajax_get_employees_by_bu() {
		if( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'You do not have access to this feature.' );
			return;
		}
		$bu = $_POST[ 'bu' ];

		$entries = $this->get_employees_by_bu( $bu );
		
		wp_send_json( $entries );
	}

	public function fln_ajax_get_employees_by_site() {
		if( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'You do not have access to this feature.' );
			return;
		}
		$site_code = $_POST[ 'site' ];
		
		$entries = array();
		
		$entry = new stdClass();
		$entry->id = 89704;
		$entry->idsid = 'mffallas';
		$entry->name = 'Fallas navarrete, Moises F';
		$entry->bu = 'INFORMATION TECHNOLOGY SGRP';
		$entry->site = $site_code;
		$entry->email = 'moises.f.fallas.navarrete@intel.com';
		$entries[] = $entry;
		
		$entry = new stdClass();
		$entry->id = 56865;
		$entry->idsid = 'laykeene';
		$entry->name = 'New, Lay Kee';
		$entry->bu = 'ALTERA SUPER GROUP';
		$entry->site = $site_code;
		$entry->email = 'lay.kee.new@intel.com';
		$entries[] = $entry;
		
		$entry = new stdClass();
		$entry->id = 54710;
		$entry->idsid = 'mlsorbel';
		$entry->name = 'Sorbel, Marie L';
		$entry->bu = 'CORE & VIS COMPUTE SUPER GROUP';
		$entry->site = $site_code;
		$entry->email = 'marie.l.sorbel@intel.com';
		$entries[] = $entry;
		
		wp_send_json( $entries );
	}

	private function add_emails_to_invitees_database( $email_list, $event_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fln_nbc_invites';
		$format = array( '%d', '%s' );
		foreach( $email_list as $email ) {
			$data = array( 'event_id' => $event_id, 'email' => $email );
			$wpdb->insert( $table_name, $data, $format );
		}
	}

	private function log( $message ) {
		date_default_timezone_set( 'America/Los_Angeles' );
		$stamp = date('Y-m-d h:i:sa');
		if( is_array( $message ) || is_object( $message ) ) {
			$message = print_r( $message, true );
		}
		$message = "[$stamp] $message" . "\n";
		$fileName = plugin_dir_path( __FILE__ ) . 'log.txt';
		if( file_exists( $fileName ) ) {
			$message = file_get_contents( $fileName ) . $message . "\n";
		}
		file_put_contents( $fileName, $message );
	}
}

$flnNewBlueConnect = new FlnNewBlueConnectTest();
