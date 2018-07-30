<?php
/*
Plugin Name: FLN New Blue Connect Test
Version:     1.0
Description: This plugin is to test the New Blue Connect Site.
Author:      Andy Stubbs
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	exit();
}

class FlnNewBlueConnectTest {

	public function __construct() {
		add_shortcode( 'fln-test-new-blue-connect-ajax', array( $this, 'test_new_blue_connect_ajax' ) );
		add_action( 'wp_ajax_fln_get_super_groups', array( $this, 'fln_ajax_get_super_groups' ) );
		add_action( 'wp_ajax_fln_get_sites', array( $this, 'fln_ajax_get_sites' ) );
		add_action( 'wp_ajax_fln_get_employees_by_site', array( $this, 'fln_ajax_get_employees_by_site' ) );
		add_action( 'wp_ajax_fln_get_employees_by_bu', array( $this, 'fln_ajax_get_employees_by_bu' ) );
		add_action( 'wp_ajax_fln_invite_guests', array( $this, 'fln_ajax_invite_guests' ) );
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

	public function fln_ajax_get_employees_by_bu() {
		if( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'You do not have access to this feature.' );
			return;
		}
		$bu = $_POST[ 'bu' ];

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

$flnNewBlueConnectTest = new FlnNewBlueConnectTest();
