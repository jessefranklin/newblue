<?php
/**
 * Action User admin functions
 * @version 2.0.11
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class evoau_admin{
	public function __construct(){
		add_action('init', array($this, 'admininit'));
		add_action('admin_menu', array( $this,'menu'), 9);
	}

	// admin init
		function admininit(){
			global $eventon_au;
			add_action('eventon_add_meta_boxes', array($this,'evoAU_trigger_meta_box') );
			add_action('eventon_save_meta', array($this,'evoAU_save_meta_box_values'), 10, 2);

			// language
			add_filter('eventon_settings_lang_tab_content', array($this,'evoAU_language_additions'), 10, 1);
			
			// admin styles and scripts
			add_action( 'admin_enqueue_scripts', array($this,'eveoAU_admin_setting_styles') );
			add_action( 'eventon_admin_post_script', array( $this, 'backend_post_scripts' ) ,15);
			add_action( 'admin_enqueue_scripts', array($this,'evoau_admin_scripts' ));

			// other hooks
			add_filter('eventon_core_capabilities', array($this, 'add_new_capability_au'),10, 1);			
			add_action( 'user_row_actions', array( $this,'evoAU_user_row'), 10, 2 );

			// column for events page
			add_filter('evo_event_columns', array($this, 'add_column_title'), 10, 1);
			add_filter('evo_column_type_evoau', array($this, 'column_content'), 10, 1);

			// capabilities
			add_filter( 'map_meta_cap', array($this, 'my_map_meta_cap'), 10, 4 );

			// appearance
			add_filter( 'eventon_appearance_add', array($this,'appearance_settings') , 10, 1);
			add_filter( 'eventon_inline_styles_array',array($this,'dynamic_styles') , 1, 1);
			add_filter( 'evo_styles_primary_font',array($this,'primary_font') ,10, 1);
			add_filter( 'evo_styles_secondary_font',array($this,'secondary_font') ,10, 1);
		}
	// MENUS
		function menu(){
			add_submenu_page( 'eventon', 'Action User', 'Action User', 'manage_eventon', 'action_user', array($this,'evoAU_action_user_fnct') );
		}
			function evoAU_action_user_fnct(){
				require_once('settings_page.php');
				$settings = new evoau_settings();
				$settings->content();
			}
	// appearance inserts
		function appearance_settings($array){
			$new[] = array('id'=>'evoau','type'=>'hiddensection_open','name'=>'ActionUser Styles','display'=>'none');
			$new[] = array('id'=>'evoau','type'=>'fontation','name'=>'Submit Button',
				'variations'=>array(
					array('id'=>'evoau_1', 'name'=>'Background Color','type'=>'color', 'default'=>'237ebd'),
					array('id'=>'evoau_2', 'name'=>'Text Color','type'=>'color', 'default'=>'ffffff')
				)
			);$new[] = array('id'=>'evoau_a1','type'=>'fontation','name'=>'Form',
				'variations'=>array(
					array('id'=>'evoau_a0', 'name'=>'Background Color','type'=>'color', 'default'=>'ffffff'),
					array('id'=>'evoau_a1', 'name'=>'Border Color','type'=>'color', 'default'=>'d9d7d7'),
					array('id'=>'evoau_a2', 'name'=>'Field Label Color','type'=>'color', 'default'=>'808080'),
					array('id'=>'evoau_a3', 'name'=>'Headers Text Color','type'=>'color', 'default'=>'808080'),
					array('id'=>'evoau_a4', 'name'=>'Field Row Background Color (Hover)','type'=>'color', 'default'=>'F9F9F9')					
				)
			);
			$new[] = array('id'=>'evoau','type'=>'hiddensection_close','name'=>'ActionUser Styles');
			return array_merge($array, $new);
		}
		// Add settings to dynamic styles
			function dynamic_styles($_existen){
				$new= array(
					array(
						'item'=>'#eventon_form .submit_row input',
						'multicss'=>array(
							array('css'=>'color:#$', 'var'=>'evcal_gen_btn_fc',	'default'=>'ffffff'),
							array('css'=>'background:#$', 'var'=>'evcal_gen_btn_bgc',	'default'=>'237ebd')
						)
					),array(
						'item'=>'#eventon_form .submit_row input:hover',
						'multicss'=>array(
							array('css'=>'color:#$', 'var'=>'evcal_gen_btn_fcx',	'default'=>'fff'),
							array('css'=>'background-color:#$', 'var'=>'evcal_gen_btn_bgcx',	'default'=>'237ebd')
						)
					),array(
						'item'=>'body #eventon_form p #evoau_submit, body a.evoAU_form_trigger_btn, body .evoau_submission_form .msub_row a, body .evcal_btn.evoau, body .evoau_submission_form.loginneeded .evcal_btn',
						'multicss'=>array(
							array('css'=>'color:#$', 'var'=>'evoau_2',	'default'=>'ffffff'),
							array('css'=>'background-color:#$', 'var'=>'evoau_1',	'default'=>'237ebd')
						)
					),array(
						'item'=>'body .evoau_submission_form',
						'multicss'=>array(
							array('css'=>'border-color:#$', 'var'=>'evoau_a1',	'default'=>'d9d7d7'),
							array('css'=>'background-color:#$', 'var'=>'evoau_a0',	'default'=>'ffffff')
						)
					),array(
						'item'=>'body .evoau_submission_form h2, body .evoau_submission_form h3',
						'css'=>'color:#$', 'var'=>'evoau_a3',	'default'=>'808080'
					),array(
						'item'=>'body .evoau_submission_form p label',
						'css'=>'color:#$', 'var'=>'evoau_a2',	'default'=>'808080'
					),array(
						'item'=>'body #eventon_form .evoau_table .row:hover',
						'css'=>'background-color:#$', 'var'=>'evoau_a4',	'default'=>'F9F9F9'
					)
				);

				return (is_array($_existen))? array_merge($_existen, $new): $_existen;
			}
		// Font families
		function primary_font($str){
			$str .= ',#eventon_form h2, 
				#eventon_form h3,
				#eventon_form p #evoau_submit, 
				a.evoAU_form_trigger_btn, 
				.evoau_submission_form .msub_row a, 
				.row .enterNew,
				.evoau_submission_form .formBtnS';
			return $str;
		}
		function secondary_font($str){
			return $str.',#eventon_form p input, 
				#eventon_form p textarea, 
				#eventon_form p select, 
				#eventon_form p.dropdown_row select,
				.evoau_cat_select_field,
				#eventon_form p select.evoau_location_select,
				#eventon_form p label,
				.evoau_selectmul,
				.row .evoau_img_preview span,
				.evoau_file_field span.evoau_img_btn,
				.eventon_au_form_section.overLay .closeForm';
		}

	// language settings additinos
		function evoAU_language_additions($_existen){
			$evcal_opt = get_option('evcal_options_evcal_1');
			$new_ar = array(
					array('type'=>'togheader','name'=>'ADDON: Action User'),
					array('label'=>'Event Name','name'=>'evoAUL_evn','legend'=>''),
					array('label'=>'Event Sub Title','name'=>'evoAUL_est','legend'=>''),
					array('label'=>'Event Start Date/Time','name'=>'evoAUL_esdt','legend'=>''),
					array('label'=>'Event End Date/Time','name'=>'evoAUL_eedt','legend'=>''),
					array('label'=>'Event Details','name'=>'evcal_evcard_details_au','legend'=>''),
					array('label'=>'Event Color','name'=>'evoAUL_ec','legend'=>''),

					array('label'=>'Event Location Fields','name'=>'evoAU_pseld'),
					array('label'=>'Select Saved Location','name'=>'evoAUL_ssl'),
					array('label'=>'Select from List','name'=>'evoAUL_sfl'),
					array('label'=>'Event Location Name','name'=>'evoAUL_lca'),
					array('label'=>'Event Location Address','name'=>'evoAUL_ln'),
					array('label'=>'Event Location Coordinates (lat,lon Seperated by comma)','name'=>'evoAUL_lcor'),
					array('label'=>'Event Location Link','name'=>'evoAUL_llink'),

					array('label'=>'Select Saved Organizer','name'=>'evoAUL_sso'),
					array('label'=>'Event Host Fields','name'=>'evoAU_pseod'),
					array('label'=>'Event Host','name'=>'evoAUL_eo'),
					array('label'=>'Event Host Contact Information','name'=>'evoAUL_eoc'),
					array('label'=>'Event Host Link','name'=>'evoAUL_eol'),
					array('label'=>'Event Host Address','name'=>'evoAUL_eoa'),

					array('label'=>'Learn More Link','name'=>'evoAUL_lml'),
					array('label'=>'Create New','name'=>'evoAUL_cn'),
					array('label'=>'Edit','name'=>'evoAUL_edit'),
				);

			// event taxnomies upto 5 all active ones only
			$ett_verify = evo_get_ett_count($evcal_opt );
			$_tax_names_array = evo_get_ettNames($evcal_opt);
			$new_ar_1 = array();
			for($x=1; $x< ($ett_verify+1); $x++){
				$ab = ($x==1)? '':'_'.$x;
				$__tax_name = $_tax_names_array[$x];
				$new_ar_1[]= array('label'=>'Select the '.$__tax_name.'','name'=>'evoAUL_stet'.$x,'legend'=>'');
			}

			$new_ar_2 = array(
				array('label'=>'Edit Submitted Event','name'=>'evoAUL_ese','legend'=>''),
				array('label'=>'Event Post Status','var'=>1),
				array('label'=>'Event Image','name'=>'evoAUL_ei','legend'=>''),				
				array('label'=>'Remove Image','var'=>'1'),				
				array('label'=>'All Day Event','name'=>'evoAUL_001','legend'=>''),
				array('label'=>'No End time','name'=>'evoAUL_002','legend'=>''),
				array('label'=>'This is a repeating event','name'=>'evoAUL_ere1'),
				array('label'=>'Daily','name'=>'evoAUL_ere2'),
				array('label'=>'Weekly','name'=>'evoAUL_ere3'),
				array('label'=>'Monthly','name'=>'evoAUL_ere4'),
				array('label'=>'Yearly','name'=>'evoAUL_ere4y'),
				array('label'=>'Event Repeat Type','name'=>'evoAUL_ere5'),
				array('label'=>'Gap Between Repeats','name'=>'evoAUL_ere6'),
				array('label'=>'Number of Repeats','name'=>'evoAUL_ere7'),
				
				array('label'=>'Your Full Name','name'=>'evoAUL_fn','legend'=>''),
				array('label'=>'Your Email Address','name'=>'evoAUL_ea','legend'=>''),
				array('label'=>'Form Human Submission Validation','name'=>'evoAUL_cap','legend'=>''),
				array('label'=>'Select an Image','name'=>'evoAUL_img002','legend'=>''),
				array('label'=>'Image Chosen','name'=>'evoAUL_img001','legend'=>''),
				array('label'=>'Additional Field','name'=>'evoAU_add','legend'=>''),
				array('label'=>'Open in new window','name'=>'evoAUL_lm1'),			
				array('label'=>'or create New (type other categories seperated by commas)','name'=>'evoAUL_ocn'),
				array('label'=>'(Text)','var'=>'1'),
				array('label'=>'(Link)','var'=>'1'),		
				array('label'=>'** Special Event Edit Fields (exclude, feature and cancel event)','var'=>1),
				array('label'=>'Exclude this event from calendar','var'=>1),
				array('label'=>'Feature this event','var'=>1),
				array('label'=>'Cancel this event','var'=>1),		
				array('label'=>'Submit Event','name'=>'evoAUL_se','legend'=>''),
				array('label'=>'Submit another event','var'=>'1'),
				array('label'=>'Update Event','var'=>'1'),
				array('label'=>'Login','name'=>'evoAUL_00l1'),
				array('label'=>'Register','name'=>'evoAUL_00l2'),

				array('label'=>'Date Picker','type'=>'subheader'),
					array('label'=>'Prev','var'=>1),				
					array('label'=>'Next','var'=>1),				
				array('type'=>'togend'),

				array('label'=>'Form field placeholders','type'=>'subheader'),
					array('label'=>'Start Date','name'=>'evoAUL_phsd','legend'=>''),
					array('label'=>'Start Time','name'=>'evoAUL_phst','legend'=>''),
					array('label'=>'End Date','name'=>'evoAUL_phed','legend'=>''),
					array('label'=>'End Time','name'=>'evoAUL_phet','legend'=>''),					
				array('type'=>'togend'),

				array('label'=>'User Interaction values','type'=>'subheader'),
					array('label'=>'Slide Down EventCard','name'=>'evoAUL_ux1','legend'=>''),
					array('label'=>'External Link','name'=>'evoAUL_ux2','legend'=>''),
					array('label'=>'Lightbox popup window','name'=>'evoAUL_ux3'),
					array('label'=>'Type the External Url','name'=>'evoAUL_ux4'),
					array('label'=>'Open as Single Event Page','name'=>'evoAUL_ux4a'),
				array('type'=>'togend'),

				array('label'=>'Form Notification Messages','type'=>'subheader'),
					array('label'=>'You must login to submit events.','name'=>'evoAUL_ymlse','legend'=>''),
					array('label'=>'Required Fields Missing','name'=>'evoAUL_nof1','legend'=>''),
					array('label'=>'Invalid validation code please try again','name'=>'evoAUL_nof2','legend'=>''),
					array('label'=>'Thank you for submitting your event!','name'=>'evoAUL_nof3','legend'=>''),
					array('label'=>'Could not create event post, try again later!','name'=>'evoAUL_nof4','legend'=>''),
					array('label'=>'Bad nonce form verification, try again!','name'=>'evoAUL_nof5','legend'=>''),
					array('label'=>'You can only submit one event!','name'=>'evoAUL_nof6','legend'=>''),
					array('label'=>'Image upload failed!','name'=>'evoAUL_nof7','legend'=>''),
					array('label'=>'Thank you for updating your event!','var'=>1),
				array('type'=>'togend'),
				array('label'=>'Event Manager Section','type'=>'subheader'),
					array('label'=>'My Eventon Events Manager','var'=>'1'),
					array('label'=>'Login required to manage your submitted events','var'=>'1'),
					array('label'=>'Login Now','var'=>'1'),
					array('label'=>'Hello','var'=>'1'),
					array('label'=>'From your event manager dashboard you can view your submitted events and manage them in here','var'=>'1'),
					array('label'=>'Back to my events','var'=>'1'),
					array('label'=>'Event Publish Status','var'=>1),
					array('label'=>'Status','var'=>1),
					array('label'=>'publish','var'=>1),
					array('label'=>'draft  ','var'=>1),
					array('label'=>'Date','var'=>'1'),
					array('label'=>'My Submitted Events','var'=>'1'),
					array('label'=>'You do not have submitted events','var'=>'1'),
					array('label'=>'Featured Event','var'=>'1'),
					array('label'=>'Are you sure you want to delete this event','var'=>1),
					array('label'=>'Yes','var'=>1),
					array('label'=>'No','var'=>1),
					array('label'=>'Previous Events','var'=>1),
					array('label'=>'Next Events','var'=>1),
				array('type'=>'togend'),
			);

			$endAr = array(array('type'=>'togend'));

			// hook for addons
			$new_ar = apply_filters('eventonau_language_fields', array_merge($new_ar, $new_ar_1, $new_ar_2));

			return (is_array($_existen))? array_merge($_existen, $new_ar, $endAr): $_existen;
		}
	
	// USERS page: Add capabilities edit button each users line
		function evoAU_user_row($actions, $user)  {
			global $pagenow;
			if ($pagenow == 'users.php') {				
				if (current_user_can( 'manage_eventon' )) {
				  $actions['evo_capabilities'] = '<a href="' . 
					wp_nonce_url("admin.php?page=action_user&tab=evoau_2&"."object=user&amp;user_id={$user->ID}", "evo_user_{$user->ID}") . 
					'">' . __('EventON Capabilities', 'eventon') . '</a>';
				}      
			}
			return $actions;
		}
		// UPDATE user/role capabilities
			function update_role_caps($ID, $type='role', $action=''){
				global $_POST;
				
				$caps = eventon_get_core_capabilities();
				
				if($type=='role'){
					global $wp_roles;
					
					$current_role_caps = $wp_roles->get_role($ID);		
					$cur_role_caps = ($current_role_caps->capabilities);
					
					foreach($caps as $capgroupf=>$capgroup){			
						foreach($capgroup as $cap){
							
							if(!isset($_POST[$cap])) continue;
							
							// add cap
							// If capability exist currently
							if(array_key_exists($cap, $cur_role_caps)){ 
								if(isset($_POST[$cap]) && $_POST[$cap]=='no'){
									$wp_roles->remove_cap( $ID, $cap );
								}
							}else{// if capability doesnt exists currently
								if(isset($_POST[$cap]) && $_POST[$cap]=='yes'){
									$wp_roles->add_cap( $ID, $cap );
								}
							}					
						}
					}		
				}else if($type=='user'){
					$currentuser = new WP_User( $ID );
					$cur_role_caps = $currentuser->allcaps;
					
					foreach($caps as $capgroupf=>$capgroup){			
						foreach($capgroup as $cap){					
							// add cap
							// If capability exist currently
							if(array_key_exists($cap, $cur_role_caps)){ 
								if($_POST[$cap]=='no'){
									$currentuser->remove_cap( $cap );
								}
							}else{// if capability doesnt exists currently
								if($_POST[$cap]=='yes'){
									$currentuser->add_cap( $cap );
								}
							}					
						}
					}
				}
			}
		// save user specific capabilities
			public function my_map_meta_cap($caps, $cap, $user_id, $args ) {

				if ( ('edit_eventon' == $cap || 'delete_eventon' == $cap || 'read_eventon' == $cap ) && !empty($args[0])) {
					$post = get_post( $args[0] );
					$post_type = get_post_type_object( $post->post_type );

					$caps = array();

					if ( 'edit_eventon' == $cap ) {
						if ( $user_id == $post->post_author )
							$caps[] = $post_type->cap->edit_posts;
						else
							$caps[] = $post_type->cap->edit_others_posts;
					}

					elseif ( 'delete_eventon' == $cap ) {
						if ( $user_id == $post->post_author)
							$caps[] = $post_type->cap->delete_posts;
						else
							$caps[] = (!empty($post_type->cap->delete_others_posts)? 
								$post_type->cap->delete_others_posts:null);
					}

					elseif ( 'read_eventon' == $cap ) {

						if ( 'private' != $post->post_status )
							$caps[] = 'read';
						elseif ( $user_id == $post->post_author )
							$caps[] = 'read';
						else
							$caps[] = $post_type->cap->read_private_posts;
					}
				}

				/* Return the capabilities required by the user. */
				return $caps;
			}
			
	// ADMIN stylesheet
		function eveoAU_admin_setting_styles(){
			global $eventon_au;
			wp_enqueue_style( 'au_backend_settings',$eventon_au->plugin_url.'/assets/au_styles_settings.css');
		}
		function backend_post_scripts(){
			global $eventon_au;
			wp_enqueue_script('jquery-form');
			wp_enqueue_script( 'evo_au_backend',$eventon_au->plugin_url.'/assets/js/au_script_b.js',array('jquery'),$eventon_au->version,true);
			wp_localize_script( 'evo_au_backend', 'evoau_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
		}
		function evoau_admin_scripts(){
			global $pagenow, $eventon_au;
			
			if($pagenow=='admin.php' && $_GET['page']=='action_user'){			
				wp_register_script( 'evo_au_backend_admin',$eventon_au->plugin_url.'/assets/js/au_script_b_admin.js',array('jquery'),'1.0',true);
				wp_localize_script( 'evo_au_backend_admin', 'the_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));	
				wp_enqueue_script('evo_au_backend_admin');
			}
		}

	// ADD meta box on events page
		function evoAU_trigger_meta_box(){	
			// restrict access to user permission set box only to those can manage eventon
			add_meta_box('ajdeevcal_mb_au','Action User',  array($this,'evoAU_meta_box'),'ajde_events', 'side', 'low');	
		}

	/* Action User META BOX for events post page*/
		function evoAU_meta_box(){
			global $eventon, $post; 

			//testing
			global $eventon_au;
			//echo $eventon_au->frontend->_get_email_body('test',2346);

			if(current_user_can('manage_eventon') || current_user_can('assign_users_to_events')):
			
			// Lightbox Hookup
				global $ajde;
				echo $ajde->wp_admin->lightbox_content(array(
					'class'=>'evoau_lightbox_assign', 
					'content'=> "<p class='evo_lightbox_loading'></p>", 
					'title'=>__('Assign users to this event','eventon'),
					'type'=>'padded'
					)
				);
								
			// The actual fields for data entry
			$p_id = $post->ID;
			$pmv = get_post_custom($p_id);
			
			$saved_users = wp_get_object_terms($p_id, 'event_users', array('fields'=>'slugs'));
			$saved_users = (!empty($saved_users))? $saved_users:null;
			
			//$all_users = get_users();			
			$assigned_users = array();	

			// Get Assigned users information
				if(is_array($saved_users)  && !empty($saved_users)){
					if( in_array('all', $saved_users) ){
						$assigned_users[] = array('all', 'All Users');
					}else{

						foreach($saved_users as $UID){
							$udata = get_userdata($UID);
							$assigned_users[] = array($UID, $udata->display_name);
						}
						
					}
				}				
		?>
			
			<!-- disable front end editting -->
			<p class='yesno_leg_line' style='padding-top:0px'>
				<?php 	
					$evoau_disableEditing = (!empty($pmv['evoau_disableEditing']))?
						$pmv['evoau_disableEditing'][0]:null;
					echo eventon_html_yesnobtn(
					array(
						'id'=>'evoau_disableEditing', 
						'var'=>$evoau_disableEditing,
						'input'=>true,
						'label'=>__('Disable frontend editing','eventon_cd'),
						'guide'=>__('This will disable users from editing this event on frontend event manager page. This value will override editing value saved in actionUser settings'),
						'guide_position'=>'L',
					));
				?>	
			</p>
			<div class="evoau_assign_users" style='margin-bottom:10px;'>
				<?php
					echo "<div class='evoau_assigned_users_in'>";
					if(!empty($assigned_users)){
						echo "<h4>".__('Users Assigned to this Event','eventon')."</h4>";
						echo "<div class='EVOAU_assigned_users_list'>";
						foreach($assigned_users as $user){
							echo "<p><i>{$user[1]} ({$user[0]})</i></p>";
						}
						echo "</div>";
					}else{
						echo "<p>".__('You can assign users to this event and build calendars with events from only those users.','eventon')." <a href='http://www.myeventon.com/documentation/assign-users-events/' target='_blank'>".__('Learn More','eventon')."</a></p><br/>";
					}
					echo "</div>";
				?>
				<input id='EVOAU_assigned_users' type='hidden' name='_evoau_assigned_users' value=''/>
				
				<?php if( !empty($post->post_author)):?>
					<p><b><?php _e('Event Author','eventon');?>:</b> <?php echo get_the_author_meta('display_name',$post->post_author);?></p>
				<?php endif;?>
			</div>
			
			<?php do_action('evoau_assigninfo_display', $p_id);?>
			
			<p><a class='button ajde_popup_trig evoau_load_lightbox_content' data-eventid='<?php echo $p_id;?>' data-popc='evoau_lightbox_assign' ><?php _e('Manage Assigned Users','eventon');?></a></p>
				
			<?php 
				// if submitters name present
				if(!empty($pmv['_submitter_name']) && !empty($pmv['_submitter_email'])):?>
					<p><i><?php _e('Event submitted by','eventon');?>: <b><?php echo $pmv['_submitter_name'][0]?> (<?php echo $pmv['_submitter_email'][0];?>)</b></i></p>
				<?php endif;?>
			
			<?php else:
				echo "<p>".__('You do not have permission to edit this section!','eventon')."</p>";
			endif;

			// additional private notes to admin
				if( (current_user_can('manage_eventon') || current_user_can('view_private_event_submission_notes'))  && !empty($pmv['evcalau_notes']) ){
					$notes = trim($pmv['evcalau_notes'][0]);
					if(!empty($notes))
						echo "<p class='evoau_private_note'><span><em>".__('Private notes','eventon')."</em>{$pmv['evcalau_notes'][0]}</span><p>";
				}
		}
			
	// SAVE meta box values for user assignments
		function evoAU_save_meta_box_values($fields, $post_id){	
			if(isset($_POST['evoau_disableEditing']))
				update_post_meta($post_id,'evoau_disableEditing',$_POST['evoau_disableEditing']);
			
		}

	// add a new capability to be able to manage eventon user capabilities
		function add_new_capability_au($caps){
			$new_caps = $caps;			
			$new_caps[] = 'manage_eventon_user_capabilities';			
			$new_caps[] = 'assign_users_to_events';			
			$new_caps[] = 'view_private_event_submission_notes';	
			$new_caps[] = 'submit_new_events_from_submission_form';	
			return $new_caps;
		}	

	// return HTML content for eventON role editor admin settings
		// type = role, user
		function get_cap_list_admin($ID, $type='role'){
			
			$content = $content_l = $content_r ='';	
			$count=1;
			if($type =='role'){
				global $wp_roles;
				$wp_roles = new WP_Roles();
									
				$current_role_caps = $wp_roles->get_role($ID);	
				//print_r($current_role_caps);
				$cur_role_caps = ($current_role_caps->capabilities);			
				
			}else if($type=='user'){
				$currentuser = new WP_User( $ID );
				$cur_role_caps = $currentuser->allcaps;
			}
			
			
			$caps = eventon_get_core_capabilities();
			foreach($caps as $capgroupf=>$capgroup){
				
				foreach($capgroup as $cap){
					if(in_array( $cap, array('delete_eventon','publish_eventon','edit_eventon')) ) continue;

					$rowcap = $cap;
					
					if($capgroupf=='core'){
						$cap = str_replace('eventon','eventon Settings', $cap);
					}else{
						$cap = str_replace('eventon','event', $cap);
					}
					
					$human_nam = ucwords(str_replace('_',' ',$cap));
					
					$yesno_val = ($ID=='administrator')? 'yes':((isset($cur_role_caps[$rowcap]))? 'yes':'no');
					$disabled = ($ID=='administrator')?'disable':null;
					
					$yesno_btn = eventon_html_yesnobtn(array('var'=>$yesno_val));

					$content= '<p class="yesno_row">'.$yesno_btn.'<input type="hidden" name="'.$rowcap.'" value="'.$yesno_val.'"><span class="field_name">'.$human_nam.'</span></p>';
					
					if($count >10){
						$content_r .=$content;
					}else{
						$content_l .=$content;
					}
					
					$count++;
				}
			}
			
			$content = "<table width='100%' ><tr><td valign='top'>".$content_l."</td><td valign='top'>".$content_r."</td></tr></table>";
			
			return $content;
		}

	// Assigned users for column for events
	// @version 1.8
		function add_column_title($columns){
			$columns['evoau']= 'Assigned Users';
			return $columns;
		}
		function column_content($post_id){

			$output = __('None','eventon');

			$saved_users = wp_get_object_terms($post_id, 'event_users', array('fields'=>'slugs'));
			$saved_users = (!empty($saved_users))? $saved_users:null;

			if(!empty($saved_users) && is_array($saved_users)){
				$output=array();
				foreach($saved_users as $user){
					$output[]= ($user=='all')? __('All Users','eventon'):
						get_the_author_meta('display_name', $user);
				}
				$output = implode(', ', $output);
			}

			$output = apply_filters('evoau_assigned_users_column', $output,  $post_id);

			return $output;
		}
}