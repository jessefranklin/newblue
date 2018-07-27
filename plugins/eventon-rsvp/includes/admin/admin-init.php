<?php
/**
 * 
 * Admin settings class
 *
 * @author 		AJDE
 * @category 	Admin
 * @package 	eventon-rsvp/classes
 * @version     2.5.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class evorsvp_admin{
	
	public $optRS;
	function __construct(){
		add_action('admin_init', array($this, 'evoRS_admin_init'));
		include_once('evo-rsvp.php');
		include_once('evo-rsvp_meta_boxes.php');

		add_filter( 'eventon_appearance_add', array($this, 'appearance_settings' ), 10, 1);
		add_filter( 'eventon_inline_styles_array',array($this, 'evoRS_dynamic_styles') , 10, 1);
		add_filter( 'evo_styles_primary_font',array($this,'primary_font') ,10, 1);
		add_filter( 'evo_styles_secondary_font',array($this,'secondary_font') ,10, 1);

		// eventtop
		//add_action('eventon_eventop_fields', array($this,'eventtop_option'), 10, 1);
		add_action( 'admin_menu', array( $this, 'menu' ),9);

		// delete rsvp
		add_action('wp_trash_post',array($this,'trash_rsvp'),1,1);
		add_action('publish_to_trash',array($this,'trash_rsvp'),1,1);
		add_action('draft_to_trash',array($this,'trash_rsvp'),1,1);
		//add_action('trash_post',array($this,'trash_rsvp'),1,1);

		// duplicating event
		add_action('eventon_duplicate_product',array($this,'duplicate_event'), 10, 2);
		add_action('eventon_duplicate_event_exclude_meta',array($this,'exclude_duplicate_fields'), 10, 1);

		// troubleshooting info
		add_filter('eventon_troubleshooter', array($this,'troubleshooting'), 10, 1);
	}

	// INITIATE
		function evoRS_admin_init(){

			// icon
			add_filter( 'eventon_custom_icons',array($this, 'custom_icons') , 10, 1);

			// eventCard inclusion
			add_filter( 'eventon_eventcard_boxes',array($this,'add_toeventcard_order') , 10, 1);


			global $pagenow, $typenow, $wpdb, $post;	
			
			if ( $typenow == 'post' && ! empty( $_GET['post'] ) && $post){
				$typenow = $post->post_type;
			} elseif ( empty( $typenow ) && ! empty( $_GET['post'] ) ) {
		        $typenow = get_post_type( $_GET['post'] );
		    }
			
			if ( $typenow == '' || $typenow == "ajde_events" || $typenow =='evo-rsvp') {

				// Event Post Only
				$print_css_on = array( 'post-new.php', 'post.php' );

				foreach ( $print_css_on as $page ){
					add_action( 'admin_print_styles-'. $page, array($this,'evoRS_event_post_styles' ));		
				}
			}

			// include rsvp id in the search
			if($typenow =='' || $typenow == 'evo-rsvp'){
				// Filter the search page
				add_filter('pre_get_posts', array($this, 'evors_search_pre_get_posts'));		
			}

			if($pagenow == 'edit.php' && $typenow == 'evo-rsvp'){
				add_action( 'admin_print_styles-edit.php', array($this, 'evoRS_event_post_styles' ));	
			}

			// settings
			add_filter('eventon_settings_tabs',array($this, 'evoRS_tab_array' ),10, 1);
			add_action('eventon_settings_tabs_evcal_rs',array($this, 'evoRS_tab_content' ));		
		}

	// other hooks
		function evors_search_pre_get_posts($query){
		    // Verify that we are on the search page that that this came from the event search form
		    if($query->query_vars['s'] != '' && is_search())
		    {
		        // If "s" is a positive integer, assume post id search and change the search variables
		        if(absint($query->query_vars['s']) ){
		            // Set the post id value
		            $query->set('p', $query->query_vars['s']);

		            // Reset the search value
		            $query->set('s', '');
		        }
		    }
		}		

		function evoRS_event_post_styles(){
			global $eventon_rs;
			wp_enqueue_style( 'evors_admin_post',$eventon_rs->assets_path.'admin_evors_post.css','',$eventon_rs->version);
			wp_enqueue_script( 'evors_admin_post_script',$eventon_rs->assets_path.'RS_admin_script.js',array(), $eventon_rs->version);
			wp_localize_script( 
				'evors_admin_post_script', 
				'evors_admin_ajax_script', 
				array( 
					'ajaxurl' => admin_url( 'admin-ajax.php' ) , 
					'postnonce' => wp_create_nonce( AJDE_EVCAL_BASENAME )
				)
			);
		}
		function add_toeventcard_order($array){
			$array['evorsvp']= array('evorsvp',__('RSVP Event Box','eventon'));
			return $array;
		}

		function custom_icons($array){
			$array[] = array('id'=>'evcal__evors_001','type'=>'icon','name'=>'RSVP Event Icon','default'=>'fa-envelope');
			return $array;
		}
		// event top option for RSVP
		function eventtop_option($array){
			$array['rsvp_options'] = __('RSVP Info (Remaing Spaces & eventtop RSVP)','eventon');
			return $array;
		}
		// EventON settings menu inclusion
		function menu(){
			add_submenu_page( 'eventon', 'RSVP', __('RSVP','eventon'), 'manage_eventon', 'admin.php?page=eventon&tab=evcal_rs', '' );
		}
	// appearance
		function appearance_settings($array){
			
			$new[] = array('id'=>'evors','type'=>'hiddensection_open','name'=>'RSVP Styles', 'display'=>'none');
			$new[] = array('id'=>'evors','type'=>'fontation','name'=>'RSVP Buttons',
				'variations'=>array(
					array('id'=>'evoRS_1', 'name'=>'Border Color','type'=>'color', 'default'=>'cdcdcd'),
					array('id'=>'evoRS_2', 'name'=>'Background Color','type'=>'color', 'default'=>'ffffff'),
					array('id'=>'evoRS_2t', 'name'=>'Text Color','type'=>'color', 'default'=>'808080'),
					array('id'=>'evoRS_3', 'name'=>'Background Color (Hover)','type'=>'color', 'default'=>'fddfa6'),
					array('id'=>'evoRS_3t', 'name'=>'Text Color (Hover)','type'=>'color', 'default'=>'ffffff')	
				)
			);
			$new[] = array('id'=>'evors','type'=>'fontation','name'=>'Count Number (EventTop)',
				'variations'=>array(
					array('id'=>'evoRScc_3', 'name'=>'Font Color','type'=>'color', 'default'=>'808080'),
					array('id'=>'evoRScc_4', 'name'=>'Background Color','type'=>'color', 'default'=>'fafafa'),	
					array('id'=>'evoRScc_5', 'name'=>'Border Color (on EventTop)','type'=>'color', 'default'=>'808080')
				)
			);
			$new[] = array('id'=>'evors','type'=>'fontation','name'=>'RSVP Form',
				'variations'=>array(
					array('id'=>'evoRS_4', 'name'=>'Background Color','type'=>'color', 'default'=>'9AB37F'),
					array('id'=>'evoRS_5', 'name'=>'Font Color','type'=>'color', 'default'=>'ffffff'),
					array('id'=>'evoRS_7', 'name'=>'Button Color','type'=>'color', 'default'=>'ffffff'),	
					array('id'=>'evoRS_8', 'name'=>'Button Text Color','type'=>'color', 'default'=>'9AB37F'),		
					array('id'=>'evoRS_8z', 'name'=>'Selected RSVP option button font color','type'=>'color', 'default'=>'9AB37F'),		
				)
			);
			$new[] = array('id'=>'evors','type'=>'fontation','name'=>'RSVP Form Fields',
				'variations'=>array(
					array('id'=>'evoRS_ff', 'name'=>'Font Color','type'=>'color', 'default'=>'ffffff'),
					array('id'=>'evoRS_ff2', 'name'=>'Placeholder Text Color','type'=>'color', 'default'=>'d5e4c5'),
				)
			);
			$new[] = array('id'=>'evors','type'=>'fontation','name'=>'RSVP Form Submit Button',
				'variations'=>array(
					array('id'=>'evoRS_12', 'name'=>'Font Color','type'=>'color', 'default'=>'9AB37F'),
					array('id'=>'evoRS_12H', 'name'=>'Background Color','type'=>'color', 'default'=>'ffffff'),
				)
			);
			$new[] = array('id'=>'evors','type'=>'fontation','name'=>'Guest List',
				'variations'=>array(
					array('id'=>'evoRS_9', 'name'=>'Guest Buble Background Color','type'=>'color', 'default'=>'A7A7A7'),
					array('id'=>'evoRS_10', 'name'=>'Guest Buble Font Color','type'=>'color', 'default'=>'ffffff'),
					array('id'=>'evoRS_11', 'name'=>'Section Background Color','type'=>'color', 'default'=>'ececec'),						
				)
			);
			
			$new[] = array('id'=>'evors','type'=>'hiddensection_close',);

			return array_merge($array, $new);
		}

		function evoRS_dynamic_styles($_existen){
			$new= array(
				array(
					'item'=>'#evorsvp_form #submit_rsvp_form',
					'multicss'=>array(
						array('css'=>'color:#$', 'var'=>'evoRS_12',	'default'=>'9AB37F'),
						array('css'=>'background-color:#$', 'var'=>'evoRS_12H',	'default'=>'ffffff'),
					)
				),array(
					'item'=>'.evcal_desc .evcal_desc3 .evors_eventtop_data em',
					'multicss'=>array(
						array('css'=>'color:#$', 'var'=>'evoRScc_3',	'default'=>'808080'),
						array('css'=>'background-color:#$', 'var'=>'evoRScc_4',	'default'=>'fafafa'),
						array('css'=>'border-color:#$', 'var'=>'evoRScc_5',	'default'=>'808080'),
					)
				),
				array(
					'item'=>'.evors_whos_coming span',
					'multicss'=>array(
						array('css'=>'background-color:#$', 'var'=>'evoRS_9',	'default'=>'A7A7A7'),
						array('css'=>'color:#$', 'var'=>'evoRS_10',	'default'=>'ffffff'),						
					)
				),
				array('item'=>'.evcal_evdata_row .evors_section.evors_guests_list','css'=>'background-color:#$', 'var'=>'evoRS_11',	'default'=>'ececec'),
				array(
					'item'=>'#evorsvp_form a.submit_rsvp_form',
					'multicss'=>array(
						array('css'=>'background-color:#$', 'var'=>'evoRS_7',	'default'=>'ffffff'),
						array('css'=>'color:#$', 'var'=>'evoRS_8',	'default'=>'9AB37F'),
					)
				),array(
					'item'=>'.evo_lightbox_body #evorsvp_form .rsvp_status span.set',
					'css'=>'color:#$', 'var'=>'evoRS_8z',	'default'=>'9AB37F'
				),array(
					'item'=>'.evors_lightbox_body #evorsvp_form .form_row select, .evors_lightbox_body #evorsvp_form .form_row input',
					'css'=>'color:#$', 'var'=>'evoRS_ff',	'default'=>'ffffff'
				),
				
				array('item'=>'.evors_lightbox_body #evorsvp_form .form_row input::-webkit-input-placeholder','css'=>'color:#$', 'var'=>'evoRS_ff2',	'default'=>'141412'),
				array('item'=>'.evors_lightbox_body #evorsvp_form .form_row input:-moz-input-placeholder','css'=>'color:#$', 'var'=>'evoRS_ff2',	'default'=>'141412'),
				array('item'=>'.evors_lightbox_body #evorsvp_form .form_row input::-moz-input-placeholder','css'=>'color:#$', 'var'=>'evoRS_ff2',	'default'=>'141412'),
				array('item'=>'.evors_lightbox_body #evorsvp_form .form_row input:-ms-input-placeholder','css'=>'color:#$', 'var'=>'evoRS_ff2',	'default'=>'141412'),
				array(
					'item'=>'.evors_submission_form, .evors_lightbox_body #evorsvp_form h3',
					'css'=>'color:#$', 'var'=>'evoRS_5',	'default'=>'ffffff'
				),array(
					'item'=>'.evors_lightbox .evo_lightbox_body.evo_lightbox_body',
					'css'=>'background-color:#$', 'var'=>'evoRS_4',	'default'=>'9AB37F'
				),array(
					'item'=>'.evoRS_status_option_selection span:hover, body .eventon_list_event .evcal_list_a .evors_eventtop_rsvp span:hover',
					'css'=>'background-color:#$', 'var'=>'evoRS_3',	'default'=>'ffffff'
				),array(
					'item'=>'.evoRS_status_option_selection span, 
						.evors_rsvped_status_user, 
						.evors_change_rsvp span.change',
					'multicss'=>array(
						array('css'=>'color:#$', 'var'=>'evoRS_2t','default'=>'808080'),
						array('css'=>'border-color:#$', 'var'=>'evoRS_1','default'=>'cdcdcd'),
						array('css'=>'background-color:#$', 'var'=>'evoRS_2','default'=>'ffffff')
					)	
				),array(
					'item'=>'.evoRS_status_option_selection span:hover, 
						.evoRS_status_option_selection span.set, 
						.evors_change_rsvp span.change:hover',
					'multicss'=>array(
						array('css'=>'color:#$', 'var'=>'evoRS_3t','default'=>'ffffff'),
						array('css'=>'background-color:#$', 'var'=>'evoRS_3','default'=>'fddfa6')
					)	
				),				
			);			

			return (is_array($_existen))? array_merge($_existen, $new): $_existen;
		}
		// Font families
		function primary_font($str){
			$str .= ',.evcal_evdata_row .evors_stat_data p em,
			.evors_submission_form, .evors_lightbox_body #evorsvp_form h3,
			.evcal_desc .evcal_desc3 .evors_eventtop_data em,
			.eventon_rsvp_rsvplist p em.event_data span a,
			.eventon_rsvp_rsvplist p span.rsvpstatus,
			.eventon_rsvp_rsvplist p a.update_rsvp';
			return $str;
		}
		function secondary_font($str){
			return $str.',.evors_change_rsvp span.change,
			.evo_popin .evcal_eventcard p.evors_whos_coming_title,
			.eventon_list_event .evcal_evdata_row p.evors_whos_coming_title';
		}

	
	// TABS SETTINGS
		function evoRS_tab_array($evcal_tabs){
			$evcal_tabs['evcal_rs']='RSVP';		
			return $evcal_tabs;
		}

		function user_roles(){
			$roles = array();
			foreach(get_editable_roles() as $role_name => $role_info){
				$roles[$role_name ] = translate_user_role($role_info['name']) ;
			}
			return $roles;
		}

		function evoRS_tab_content(){
			global $eventon;
			$eventon->load_ajde_backender();			
		?>
			<form method="post" action=""><?php settings_fields('evoau_field_group'); 
					wp_nonce_field( AJDE_EVCAL_BASENAME, 'evcal_noncename' );?>
			<div id="evcal_csv" class="evcal_admin_meta">	
				<div class="evo_inside">
				<?php

					$site_name = get_bloginfo('name');
					$site_email = get_bloginfo('admin_email');

					$cutomization_pg_array = apply_filters('evors_settings_fields',array(
						array(
							'id'=>'evoRS1','display'=>'show',
							'name'=>'General RSVP Settings',
							'tab_name'=>'General',
							'fields'=>array(
								array('id'=>'evors_onlylogu','type'=>'yesno',
									'name'=>'Allow only logged-in users to submit RSVP',
									'afterstatement'=>'evors_onlylogu',
									'legend'=>'If a custom login URL is set via eventon settings that will be used for users to login to RSVP'
								),
									array('id'=>'evors_onlylogu','type'=>'begin_afterstatement'),
									array('id'=>'evors_rsvp_roles',
										'type'=>'checkboxes',
										'name'=>'Select only certain user roles with RSVPing capabilities (If not selected all logged-in users can RSVP)',
										'options'=>$this->user_roles(),
									),									
									array('id'=>'evors_onlylogu','type'=>'end_afterstatement'),	

								array('id'=>'evors_prefil',
									'type'=>'yesno',
									'name'=>'Pre-fill fields  if user is already logged-in (eg. first name, last name, email)',
									'legend'=>'If this option is activated, form will pre-fill fields (name & email) for logged-in users.'
								),
								array('id'=>'evors_prefil_block','type'=>'yesno','name'=>'Activate uneditable pre-filled fields','legend'=>'This will disable editing pre-filled data fields, when fields are pre-filled with loggedin user data eg. first name, last name, email.'),

								array('id'=>'evors_allow_past','type'=>'yesno','name'=>'Allow RSVP-ing to past events','legend'=>'When event end date is past current time RSVP will auto disable, but if this is set users will still be able to RSVP.'),

								array('id'=>'evors_orderby','type'=>'dropdown','name'=>'Order Attendees by ','legend'=>'Which field to use for ordering attendees in backend and frontend. If users are not entering last name first name would be a wise option for ordering.','options'=>array('def'=>'Last Name','fn'=>'First Name')),

								array('id'=>'evors_guestlist','type'=>'dropdown','name'=>'Show guest list as ','legend'=>'Whether to show full names or initials in event card for guest list - whos coming.','options'=>array('def'=>'Initials','fn'=>'Full Name')),
								
								array('id'=>'evors_guest_link','type'=>'yesno','name'=>'Link guests to matching user profile','legend'=>'Link guest name to user profile pages. This feature is only available for loggedin guests.', 'afterstatement'=>'evors_guest_link'),
									array('id'=>'evors_guest_link','type'=>'begin_afterstatement'),
									array('id'=>'evors_profile_link_structure','type'=>'text','name'=>'Custom Link structure for the guest user profile page link (This is appended to your base website URL)','default'=>'/profile/?user_id={user_id}'),
									array('id'=>'note','type'=>'note','name'=>'You can use <code>{user_id}</code> in your link structure, which will be replaced with dynamic value. The above link structure must not contain your base website URL.<br/>
										NOTE: If you are using buddypress profiles you do not need to fill custom link structure.'),
									array('id'=>'evors_guest_link','type'=>'end_afterstatement'),	

								array('id'=>'evors_nonce_disable',
									'type'=>'yesno',
									'name'=>'Disable Nonce verification check upon new RSVP submission',
									'legend'=>'Enabling this will stop checking for nonce verification upon new RSVP submission.'
								),array('id'=>'evors_incard_form',
									'type'=>'yesno',
									'name'=>'Show RSVP form within EventCard instead of lightbox',
									'legend'=>'This will open all RSVP forms inside the EventCard as oppose to lightbox RSVP form.'
								),
								
								
								array('id'=>'evors_eventop','type'=>'subheader','name'=>'EventTop Data for RSVP.'),
									array('id'=>'evors_eventop_rsvp','type'=>'yesno','name'=>'Activate RSVPing with one-click from eventTop ONLY for logged-in users','legend'=>'This will show the normal RSVP option buttons for a logged-in user to RSVP to the event straight from the eventtop. This method will only capture user name, email and rsvp status only'),
									array('id'=>'evors_eventop_attend_count',
										'type'=>'yesno',
										'name'=>'Show attending guest count',
										'legend'=>'Show the attending guest count for an event on eventTOP'
									),array('id'=>'evors_eventop_notattend_count',
										'type'=>'yesno',
										'name'=>'Show not attending guest count',
										'legend'=>'This will show the count of guest not attending the event on eventTOP'
									),
									array('id'=>'evors_eventop_remaining_count',
										'type'=>'yesno',
										'name'=>'Show remaining spaces count',
										'legend'=>'Show the remaining spaces for this event on eventTOP'
									),
									array('id'=>'evors_eventop_soldout_hide',
										'type'=>'yesno',
										'name'=>'Do NOT show eventtop "RSVP Closed" or "No more spaces left" tag above event title, when rsvps are closed.'
									),


									array('id'=>'evors_eventop','type'=>'note','name'=>'NOTE: You can download all RSVPs for an event as CSV file from the event edit page under RSVP settings box.'),
									array('id'=>'evors_eventop','type'=>'customcode','code'=>'<a href="'.get_admin_url('','/admin.php?page=eventon&tab=evcal_5').'" class="evo_admin_btn btn_triad">RSVP Troubleshoot</a>'),

								array('id'=>'evors_eventop','type'=>'subheader','name'=>'ActionUser Event Manager RSVP settings'),
									array('id'=>'evorsau_csv_download',
										'type'=>'yesno',
										'name'=>'Allow front-end download of attendees list as CSV file',
										'legend'=>'With this loggedin users can download event attendees list as CSV from action user event manager.'
									),
									array('id'=>'evorsau_check_guest',
										'type'=>'yesno',
										'name'=>'Allow front-end checking guests',
										'legend'=>'This will allow loggedin users to check in guests from action user event manager.'
									)


								
						)),array(
							'id'=>'evoRS2','display'=>'',
							'name'=>'Email Templates',
							'tab_name'=>'Emails','icon'=>'envelope',
							'fields'=>array(
								array('id'=>'evors_disable_emails','type'=>'yesno','name'=>'Disable sending all emails'),
								array('id'=>'evors_notif','type'=>'yesno','name'=>'Receive email notifications upon new RSVP receipt','afterstatement'=>'evors_notif'),
								array('id'=>'evors_notif','type'=>'begin_afterstatement'),	

									array('id'=>'evcal_fcx','type'=>'note','name'=>'You can also set additional email addresses to receive notifications on each event edit page'),
									array('id'=>'evors_notfiemailfromN','type'=>'text','name'=>'"From" Name','default'=>$site_name),
									array('id'=>'evors_notfiemailfrom','type'=>'text','name'=>'"From" Email Address' ,'default'=>$site_email),
									array('id'=>'evors_notfiemailto','type'=>'text','name'=>'"To" Email Address' ,'default'=>$site_email),

									array('id'=>'evors_notfiesubjest','type'=>'text','name'=>'Email Subject line','default'=>'New RSVP Notification'),
									array('id'=>'evors_notfiesubjest_update','type'=>'text','name'=>'Email Subject line (update)','default'=>'Update RSVP Notification'),
									array('id'=>'evcal_fcx','type'=>'subheader','name'=>'HTML Template'),
									array('id'=>'evcal_fcx','type'=>'note','name'=>'To override and edit the email template copy "eventon-rsvp/templates/notification_email.php" to  "yourtheme/eventon/templates/email/rsvp/notification_email.php.'),
								array('id'=>'evors_notif','type'=>'end_afterstatement'),

								array('id'=>'evors_digest','type'=>'yesno','name'=>'Receive daily digest emails for events (BETA)','afterstatement'=>'evors_digest'),
								array('id'=>'evors_digest','type'=>'begin_afterstatement'),	

									array('id'=>'evcal_fcx','type'=>'note','name'=>'NOTE: You can set which events with RSVP to receive the digest emails for, from the event edit page itself. Important: the scheduled daily email will only get sent out once someone visit your website.'),
									array('id'=>'evors_digestemail_fromN','type'=>'text','name'=>'"From" Name','default'=>$site_name),
									array('id'=>'evors_digestemail_from','type'=>'text','name'=>'"From" Email Address' ,'default'=>$site_email),
									array('id'=>'evors_digestemail_to','type'=>'text','name'=>'"To" Email Address' ,'default'=>$site_email),

									array('id'=>'evors_digestemail_subjest','type'=>'text','name'=>'Email Subject line','default'=>'Digest Email for {event-name}'),
									
									array('id'=>'evcal_fcx','type'=>'subheader','name'=>'HTML Template'),
									array('id'=>'evcal_fcx','type'=>'note','name'=>'To override and edit the email template copy "eventon-rsvp/templates/digest_email.php" to  "yourtheme/eventon/templates/email/rsvp/digest_email.php.'),
								array('id'=>'evors_digest','type'=>'end_afterstatement'),


								array('id'=>'evors_notif_e','type'=>'subheader','name'=>'Send out RSVP email confirmations to attendees'),								
								array('id'=>'evors_notfiemailfromN_e','type'=>'text','name'=>'"From" Name','default'=>$site_name),
								array('id'=>'evors_notfiemailfrom_e','type'=>'text','name'=>'"From" Email Address' ,'default'=>$site_email),

								array('id'=>'evors_notfiesubjest_e','type'=>'text','name'=>'Email Subject line','default'=>'RSVP Confirmation'),
								
								array('id'=>'evors_contact_link','type'=>'text','name'=>'Contact for help link' ,'default'=>site_url(), 'legend'=>'This will be added to the bottom of RSVP confirmation email sent to attendee'),

								array('id'=>'evcal_fcx','type'=>'subheader','name'=>'HTML Template'),
								array('id'=>'evcal_fcx','type'=>'note','name'=>'To override and edit the email templates, copy default email templates from "eventon-rsvp/templates/" to  "yourtheme/eventon/templates/email/rsvp/ folder.'),
								/*array('id'=>'evors_3_000','type'=>'subheader','name'=>'Preview email templates'),
								array('id'=>'evors_3_000','type'=>'customcode','name'=>'Preview Emails','code'=>$this->__evors_settings_part_preview_email()
								),*/

						)),array(
							'id'=>'evoRS3','display'=>'',
							'name'=>'RSVP form fields',
							'tab_name'=>'RSVP Form','icon'=>'inbox',
							'fields'=>$this->rsvp_form_fields()													
						)
					));							
					$eventon->load_ajde_backender();	
					$evcal_opt = get_option('evcal_options_evcal_rs'); 
					print_ajde_customization_form($cutomization_pg_array, $evcal_opt);	
				?>
			</div>
			</div>
			<div class='evo_diag'>
				<input type="submit" class="evo_admin_btn btn_prime" value="<?php _e('Save Changes') ?>" /><br/><br/>
				<a target='_blank' href='http://www.myeventon.com/support/'><img src='<?php echo AJDE_EVCAL_URL;?>/assets/images/myeventon_resources.png'/></a>
			</div>			
			</form>	
		<?php
		}

		// RSVP form fields
		function rsvp_form_fields(){
			global $eventon_rs;

			$fields = array(
				array('id'=>'evors_selection','type'=>'checkboxes','name'=>'Select RSVP status options for selection. <br/><b>NOTE:</b> Yes value is required. No value will show on change RSVP form regardless to allow users to cancel their reservation.', 
					'options'=>array(
						'm'=>'Maybe','n'=>'No',
				)),
				array('id'=>'evors_onlylog_chg','type'=>'yesno','name'=>'Allow only logged-in users see \'Change RSVP\' option','legend'=>'This will only show change RSVP options for the users that have loggedin to your site.','afterstatement'=>'evors_onlylog_chg'),
					array('id'=>'evors_onlylog_chg','type'=>'begin_afterstatement'),	
						array('id'=>'evors_change_hidden','type'=>'yesno','name'=>'Show \'Change RSVP\' option only for the users who have rsvp-ed for the event'),
					array('id'=>'evors_onlylog_chg','type'=>'end_afterstatement'),
				array('id'=>'evors_hide_change','type'=>'yesno','name'=>'Hide \'Change RSVP\' button','legend'=>'This will hide the Change rsvp button from eventcard, will override any other Change RSVP button options'),
				
				array('id'=>'evors_ffields','type'=>'checkboxes','name'=>'Select RSVP form fields to show in the form. <i>(** First , Last names, and Email are required)</i>',
					'options'=>array(
						'phone'=>'Phone Number',
						'count'=>'RSVP Count -- (If unckecked system will count as 1 RSVP)',
						'updates'=>'Receive Updates About Event -- (Acknoledge Checkbox field)',
						'names'=>'Other Guest Names -- (if RSVP count if more than 1)',
						'additional'=>'Additional Notes Field -- (visible only for NO option)',
						'captcha'=>'Verification Code'
				)),	
				array('id'=>'evors_hide_change','type'=>'note','name'=>'NOTE: Additional notes field will only show when a guest select NO as RSVP status.'),
				
				array('id'=>'evors_hide_change','type'=>'subheader','name'=>'Other Form Field Options'),
				
				array('id'=>'evors_terms','type'=>'yesno','name'=>'Activate Terms & Conditions for form','afterstatement'=>'evors_terms'),
					array('id'=>'evors_terms','type'=>'begin_afterstatement'),		
					array('id'=>'evors_terms_link','type'=>'text','name'=>'Link to Terms & Conditions'),
					array('id'=>'evors_terms_text','type'=>'note','name'=>'Text Caption for Terms & Conditions can be edited from EventON > Language > EventON RSVP'),
					array('id'=>'evors_terms','type'=>'end_afterstatement'),
			);

			// additional fields
				$field_additions = array();
				for($x=1; $x<= $eventon_rs->frontend->addFields; $x++){
					$field_additions =array(
						array('id'=>'evors_addf'.$x,'type'=>'yesno','name'=>'Additional Field #'.$x,'afterstatement'=>'evors_addf'.$x),
						array('id'=>'evors_addf'.$x,'type'=>'begin_afterstatement'),								
						array('id'=>'evors_addf'.$x.'_1','type'=>'text','name'=>'Field Name'),
						array('id'=>'evors_addf'.$x.'_2','type'=>'dropdown','name'=>'Field Type','options'=> $this->_custom_field_types()),
						array('id'=>'evors_addf'.$x.'_4','type'=>'text','name'=>'Option Values (only for Drop Down field)','default'=>'eg. cats,dogs','legend'=>'Only set these values for field type = drop down. If these values are not provided for drop down field type it will revert as text field.'),
						array('id'=>'evors_addf'.$x.'_3','type'=>'yesno','name'=>'Required Field'),
						array('id'=>'evors_addf'.$x,'type'=>'end_afterstatement'),
					);
					$fields = array_merge($fields,$field_additions);
				}
			return $fields;
		}		

		// return an array list of supported different field types
		function _custom_field_types(){
			return apply_filters('evors_additional_field_types', array(
				'text'=>'Single Line Input Text Field', 
				'dropdown'=>'Drop Down Options', 
				'textarea'=>'Multiple Line Text Box',
				'html'=>'Basic Text Line'
				)
			);
		}
		function __evors_settings_part_preview_email(){
			ob_start();
			echo "<a href='".get_admin_url()."admin.php?page=eventon&tab=evcal_rs&action=confirmation#evoRS2' class='evo_admin_btn btn_triad'>Confirmation Email</a> <a href='".get_admin_url()."admin.php?page=eventon&tab=evcal_rs&action=notification#evoRS2' class='evo_admin_btn btn_triad'>Notification Email</a>";
			
			if(!empty($_GET['action'])){
				echo $this->get_email_preview($_GET['action']);
			}
			return ob_get_clean();
		}
		// preview of emails that are sent out
		function get_email_preview( $type){
			global $eventon_rs;

			$email = $eventon_rs->frontend->get_email_data(array(
					'e_id'=>'934',
					'email'=>'test@msn.com',
					'rsvp_id'=>'100'
				),$type
			);		
			$email['preview']= 'yes';	
			return $eventon_rs->helper->send_email($email);
		}
	
	// duplicate event
		function duplicate_event($new_event_id, $old_event){
			global $eventon_rs;
			$eventon_rs->frontend->functions->sync_rsvp_count($new_event_id);
			delete_post_meta($new_event_id, 'ri_count_rs');// clear ri count
		}
		// exclude event meta fields from duplication
			function exclude_duplicate_fields($fields){
				$fields[] = 'evors_data';
				return $fields;
			}

	// trash rsvp
		public function trash_rsvp($post_id){
			if( empty($post_id)) return;
			
			$post = get_post($post_id);

			if ( 'evo-rsvp' != $post->post_type)	return;
			
       		$data = '';

       		global $eventon_rs;
       		$PMV = get_post_custom($post_id);

       		$data .= '2';

       		$event_id = !empty($PMV['e_id'])? $PMV['e_id'][0]: false;

       		$repeat_interval = !empty($PMV['repeat_interval'])? $PMV['repeat_interval'][0]:0;
       		$rsvp_status = !empty($PMV['rsvp'])? $PMV['rsvp'][0]:0;
       		
       		// if the userid is present for this RSVP
       		if(!empty($PMV['userid']) && !empty($PMV['e_id'])){
	       		$eventon_rs->frontend->functions->trash_user_rsvp($PMV['userid'][0], $PMV['e_id'][0], $repeat_interval);
	       	}

	       	// if repeating event - sync remainging repeat count
	       		if($repeat_interval){
	       			$eventon_rs->frontend->functions->adjust_ri_count(
	       				$event_id, 
	       				$repeat_interval, 
	       				$rsvp_status, 
	       				$adjust='reduce'
	       			);
	       		}

	       	// sync count
	       	if($event_id){
	       		$data .= '1 '.$event_id;
	       		$eventon_rs->frontend->functions->sync_rsvp_count($event_id);
	       	}

	       	//update_post_meta(1,'aa',$data);
		}

	// troubleshooting
		function troubleshooting($array){
			$newarray['RSVP Addon'] = array(
				'RSVP is not showing on eventcard'=>'Once you have activated RSVP for an event go to <b>myEventON Settings > EventCard > Re-arrange event data boxes</b> and make sure RSVP Event Box is checked and positioned correct. You can also move it up and down to make sure its registered. <b>Save Changes</b> This should make the RSVP box show up on eventCard.'
			);
			return array_merge($array, $newarray);
		}

}

new evorsvp_admin();