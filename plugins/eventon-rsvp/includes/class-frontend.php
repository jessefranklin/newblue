<?php
/**
 * eventon rsvp front end class
 *
 * @author 		AJDE
 * @category 	Admin
 * @package 	eventon-rsvp/classes
 * @version     2.5.7
 */
class evors_front{
	public $rsvp_array = array('y'=>'yes','m'=>'maybe','n'=>'no');
	public $rsvp_array_ = array('y'=>'Yes','m'=>'Maybe','n'=>'No');
	public $evors_args;
	public $optRS;
	public $addFields;
	public $showRSVPform = false;

	public $rsvp_option_count = 0;

	public $currentlang;

	function __construct(){
		global $eventon,$eventon_rs;

		$this->evoopt1 = evo_get_options('1');

		$this->addFields = apply_filters('evors_field_count',5);
		add_action('evo_addon_styles', array($this, 'styles') );

		include_once('class-functions.php');
		$this->functions = new evorsvp_functions();

		add_filter('eventon_eventCard_evorsvp', array($this, 'frontend_box'), 10, 2);
		add_filter('eventon_eventcard_array', array($this, 'eventcard_array'), 10, 4);
		add_filter('evo_eventcard_adds', array($this, 'eventcard_adds'), 10, 1);

		// event top inclusion
		add_filter('eventon_eventtop_one', array($this, 'eventop'), 10, 3);
		add_filter('evo_eventtop_adds', array($this, 'eventtop_adds'), 10, 1);
		add_filter('eventon_eventtop_evors', array($this, 'eventtop_content'), 10, 2);			
		//add_action( 'wp_enqueue_scripts', array( $this, 'load_styles' ), 10 );	
		
		// scripts and styles 
			add_action( 'init', array( $this, 'register_styles_scripts' ) ,15);
			add_action( 'eventon_enqueue_styles', array($this,'print_styles' ));	
			add_action( 'eventon_enqueue_scripts', array($this,'print_scripts' ));	

		$this->optRS = $eventon_rs->evors_opt;
		$this->opt2 = $eventon_rs->opt2;

		add_filter('evo_frontend_lightbox', array($this, 'lightbox'),10,1);

		// event top above title
		add_filter('eventon_eventtop_abovetitle', array($this,'eventtop_above_title'),10, 2);
	}

	//	STYLES: for the tab page 
		public function register_styles_scripts(){
			global $eventon_rs;
			
			if(is_admin()) return false;
			
			$evOpt = evo_get_options('1');
			if( evo_settings_val('evcal_concat_styles',$this->evoopt1, true))
				wp_register_style( 'evo_RS_styles',$eventon_rs->assets_path.'RS_styles.css', '', $eventon_rs->version);
			
			wp_register_script('evo_RS_script',$eventon_rs->assets_path.'RS_script.js', 
				array('jquery','jquery-ui-core'), 
				$eventon_rs->version, true );

			wp_localize_script( 
				'evo_RS_script', 
				'evors_ajax_script', 
				array( 
					'ajaxurl' => admin_url( 'admin-ajax.php' ) , 
					'postnonce' => wp_create_nonce( 'evors_nonce' )
				)
			);
		}
		public function print_scripts(){
			wp_enqueue_script('evo_RS_ease');	
			//wp_enqueue_script('evo_RS_mobile');	
			wp_enqueue_script('jquery-form');	
			wp_enqueue_script('evo_RS_script');	
		}
		function print_styles(){	wp_enqueue_style( 'evo_RS_styles');		}
		function styles(){
			global $eventon_rs;
			ob_start();
			include_once($eventon_rs->plugin_path.'/assets/RS_styles.css');
			echo ob_get_clean();
		}

	// EVENTTOP inclusion
		public function eventop($array, $pmv, $vals){
			$array['evors'] = array(	'vals'=>$vals,	);
			return $array;
		}		
		function eventtop_adds($array){
			$array[] = 'evors';
			return $array;
		}
		public function eventtop_content($object, $helpers){
			global $eventon;

			$this->print_scripts();
			$output = '';
			
			// Initial Values
				$emeta = get_post_custom($object->vals['eventid']);
				$unixTime = $this->get_correct_eventTime($emeta, $object->vals['ri']);
						$row_endTime = $unixTime['end'];

			// check if RSVP info is ok to show
				if(!$this->functions->show_rsvp_still($row_endTime, $emeta) || $this->functions->close_rsvp_beforex($unixTime['start'], $emeta) ) return;

						
			$lang = $this->get_local_lang();
			$this->currentlang = $lang;
			$opt = $this->opt2;

			// logged-in user RSVPing with one click
			$output .= $this->get_eventtop_your_rsvp($emeta, $object->vals['ri'], $object->vals['eventid']);

			// get the eventtop data values
			$output .= $this->get_eventtop_data($emeta, $object->vals['ri'], $object->vals['eventid']);

			//construct HTML
			if(!empty($output)){
				$output = "<span class='evcal_desc3_rsvp'>".$output."</span>";
			}
			
			return $output;
			
		}

		// GET the event top data values
		function get_eventtop_your_rsvp($emeta='',$ri, $eventid){
			$existing_rsvp_status = false;
			$output = '';
			if(evo_settings_check_yn($this->optRS, 'evors_eventop_rsvp')){	
				if(is_user_logged_in()){
					global $current_user;
					wp_get_current_user();
					$this_user_id = ($current_user->ID!= '0' )? $current_user->ID:'na';

					$unixTime = $this->get_correct_eventTime($emeta, $ri);
						$row_endTime = $unixTime['end'];
					
					// Initial values
						$existing_rsvp_status = $this->functions->get_user_rsvp_status($current_user->ID, $eventid, $ri, $emeta);
						$closeRSVPbeforeX = $this->functions->close_rsvp_beforex($unixTime['start'], $emeta);
						$can_still_rsvp = $this->functions->can_still_rsvp($row_endTime, $emeta);
						$remaining_rsvp = $this->functions->remaining_rsvp($emeta, $ri, $eventid);
						$lang = $this->get_local_lang();

					if($remaining_rsvp==true || $remaining_rsvp >0){
						// if loggedin user have not rsvp-ed yet
						if(!$existing_rsvp_status && !$closeRSVPbeforeX && $can_still_rsvp){
							$TEXT = eventon_get_custom_language($this->opt2, 'evoRSL_001','RSVP to event', $lang);
							$output .=  "<span class='evors_eventtop_section evors_eventtop_rsvp evors_eventop_rsvped_data' data-eid='{$eventid}' data-ri='{$ri}'data-uid='{$this_user_id}' data-lang='{$lang}'>".$TEXT. $this->get_rsvp_choices($this->opt2, $this->optRS)."</span>";
						}else{
						// user has rsvp-ed already
							$TEXT = evo_lang('You have already RSVP-ed', $lang);
							$output .="<span class='evors_eventtop_section evors_eventtop_rsvp evors_eventop_rsvped_data'>{$TEXT}: <em class='evors_rsvped_status_user'>".$this->get_rsvp_status($existing_rsvp_status)."</em></span>";
						}
					}
				}							
			}
			return $output;
		}

		function get_eventtop_data($emeta='', $ri, $eventid){
			
			// initial values
			$lang = $this->get_local_lang();
			$opt = $this->opt2;
			$output = '';
			$emeta = empty($emeta)? get_post_custom($eventid): $emeta;

			// show attending count 
				$attending_html = '';
				if(evo_settings_check_yn($this->optRS, 'evors_eventop_attend_count')){
					// correct language text for based on count coming to event
						$lang_str =  array(
							'0'=>'Be the first to RSVP',
							'1'=>'Guest is attending',
							'2'=>'Guests are attending',
						);
					
					$yes_count = $this->functions->get_rsvp_count($emeta, 'y', $ri);

					// correct language string
					$__count_lang = evo_lang($lang_str['0'], $lang);
					if( $yes_count == 1) $__count_lang = evo_lang($lang_str['1'], $lang);
					if( $yes_count > 1 ) $__count_lang = evo_lang($lang_str['2'], $lang);

					
					$attending_html .= "<span class='evors_eventtop_section evors_eventtop_data count_$yes_count'>".($yes_count>0? '<em>'.$yes_count.'</em> ':'').$__count_lang."</span>";
				}
				// show not attending count
				if(evo_settings_check_yn($this->optRS, 'evors_eventop_notattend_count')){
					// correct language text for based on count coming to event
						$lang_str = array(
							'1'=>'Guest is not attending',
							'2'=>'Guests are not attending',
						);
					
					$no_count = $this->functions->get_rsvp_count($emeta, 'n', $ri);

					if($no_count >0){

						if($no_count == 1) $__count_lang = evo_lang($lang_str['1'], $lang);
						if($no_count > 1) $__count_lang = evo_lang($lang_str['2'], $lang);
						
						$attending_html .= "<span class='evors_eventtop_section evors_eventtop_data count_$no_count evors_eventtop_notattending_count'><em>".$no_count.'</em> '.$__count_lang."</span>";
					}					
					
				}

			// show remainging count 
				$count_html = '';
				if( evo_settings_check_yn($this->optRS,'evors_eventop_remaining_count')){
					// /print_r($object);
					$remaining_rsvp = $this->functions->remaining_rsvp($emeta, $ri, $eventid);

					if(!$remaining_rsvp){
						$count_html .= "<span class='evors_eventtop_section evors_eventtop_data remaining_count'>".evo_lang_get( 'evoRSL_002c','No more spots left!', $lang, $opt)."</span>";
					// no capacity set
					}elseif($remaining_rsvp == 'nocap'){
						$count_html .= "<span class='evors_eventtop_section evors_eventtop_data remaining_count'>".evo_lang_get( 'evoRSL_002bb','Spaces Still Available', $lang, $opt)."</span>";
					}else{							
						$count_html .= "<span class='evors_eventtop_section evors_eventtop_data remaining_count'><em>".($remaining_rsvp>0?$remaining_rsvp.' ':'na').'</em>'.evo_lang_get('evoRSL_002b','Spots remaining', $lang, $opt)."</span>";
					}
				}

				if(!empty($attending_html) || !empty($count_html) )
					$output = '<span class="evors_eventtop_section_data'.(empty($attending_html)?' sinval':'').'">'.$attending_html.$count_html .'</span>';
				return $output;
		}

	// ABOVE title - sold out tag
		function eventtop_above_title($var, $object){
			$epmv = $object->evvals;

			// dismiss if set in ticket settings not to show sold out tag on eventtop
			if(!empty($this->optRS['evors_eventop_soldout_hide']) && $this->optRS['evors_eventop_soldout_hide']=='yes') return $var;
			
			$remaining_rsvp = $this->functions->remaining_rsvp($epmv, $object->ri, $object->eventid);

			// Initial Check
			if(!$this->functions->is_rsvp_active($epmv)) return $var;

			$row_endTime = $this->get_correct_event_end_time($epmv, $object->ri);

			// check if users can still RSVP to events
			if($remaining_rsvp !='nocap' && $remaining_rsvp<1){
				return "<span class='eventover'>".evo_lang('No more spaces left', '',$this->opt2)."</span>";
			}elseif(!$this->functions->can_still_rsvp($row_endTime, $epmv) ){
				return "<span class='eventover'>".evo_lang('Event Over', '',$this->opt2)."</span>";
			}
		}

	// RSVP EVENTCARD form HTML
		// add RSVP box to front end
			function frontend_box($object, $helpers){
				global $eventon_rs;	

				// INITIAL VALUES
					$event_pmv = get_post_custom($object->event_id);
					$optRS = $this->optRS;
					$is_user_logged_in = is_user_logged_in();
										
					// set language
						$lang = $this->get_local_lang();

					$text_event_title = get_the_title($object->event_id);
					
					// loggedin user
						$currentUserID = 	$this->functions->current_user_id();	
							$currentUserID = $currentUserID!=0? $currentUserID:'';
						$currentUserRSVP = $this->functions->get_userloggedin_user_rsvp_status($object->event_id, $object->__repeatInterval, $event_pmv);
					// event end time 
						$unixTime = $this->get_correct_eventTime($event_pmv, $object->__repeatInterval);
						$row_endTime = $unixTime['end'];

					// check if RSVP is ok to show
					if(!$this->functions->is_rsvp_active($event_pmv)) return;
					
					$closeRSVPbeforeX = $this->functions->close_rsvp_beforex($unixTime['start'], $event_pmv);
					$can_still_rsvp = $this->functions->can_still_rsvp($row_endTime, $event_pmv);
								
					
				// if only loggedin users can see rsvp form
					if( evo_settings_val('evors_onlylogu', $optRS) && !$is_user_logged_in || 
						!$this->functions->user_need_loggedin_to_rsvp($event_pmv) 
					){

						return $this->rsvp_for_none_loggedin($helpers, $object);
						return;	// not proceeding forward from here
					}elseif(evo_settings_val('evors_onlylogu', $optRS)){
					// if user is loggedin
						$can_user_rsvp = $this->functions->can_user_rsvp();

						if(!$can_user_rsvp){
							return $this->rsvp_not_for_userrole($helpers, $object);
							return;
						}
					}
							
				// show rsvp count
					if( evo_meta_yesno($event_pmv, 'evors_show_rsvp', 'yes', true, false) ){
						$countARR = array(
							'y' => (' ('.$this->functions->get_rsvp_count($event_pmv,'y',$object->__repeatInterval).')'),
							'n' => (' ('.$this->functions->get_rsvp_count($event_pmv,'n',$object->__repeatInterval).')'),
							'm' => (' ('.$this->functions->get_rsvp_count($event_pmv,'m',$object->__repeatInterval).')'),
						);
					}else{	$countARR = array();}

					$remaining_rsvp = $this->functions->remaining_rsvp($event_pmv, $object->__repeatInterval, $object->event_id);
					$precapVal = $this->functions->is_per_rsvp_max_set($event_pmv);

				// get options array
					$opt = $helpers['evoOPT2'];
					$fields_options = 	(!empty($optRS['evors_ffields']))?$optRS['evors_ffields']:false;

				// change rsvp button
					$_txt_changersvp = eventon_get_custom_language($opt, 'evoRSL_005a','Change my RSVP');
					$changeRSVP = (!empty($optRS['evors_hide_change']) && $optRS['evors_hide_change']=='yes')?'': "<span class='change' data-val='ch'>".$_txt_changersvp."</span>";

				$this->print_scripts();
				ob_start();

				
					echo  "<div class='evorow evcal_evdata_row bordb evcal_evrow_sm evo_metarow_rsvp".$helpers['end_row_class']."' data-rsvp='' data-event_id='".$object->event_id."'>
							<span class='evcal_evdata_icons'><i class='fa ".get_eventON_icon('evcal__evors_001', 'fa-envelope',$helpers['evOPT'] )."'></i></span>
							<div class='evcal_evdata_cell'>							
								<h3 class='evo_h3'>".eventon_get_custom_language($opt, 'evoRSL_001','RSVP to event')."</h3>";

						// subtext for rsvp section
						$subtext = '';
						if(!$can_still_rsvp || $closeRSVPbeforeX){
							$subtext = eventon_get_custom_language($opt, 'evoRSL_002d',"RSVPing is closed at this time.");
						}else{
							if(!$currentUserRSVP )
								$subtext = eventon_get_custom_language($opt, 'evoRSL_002','Make sure to RSVP to this amazing event!');
						}

						// subtitle text
						if(!empty($subtext))
							echo "<div class='evors_section evors_subtext'><p class='evo_data_val'>".$subtext."</p></div>";
						
						// RSVPing allowed and spaces left
						$eventtop_rsvp = (!empty($this->optRS['evors_eventop_rsvp']) && $this->optRS['evors_eventop_rsvp']=='yes')? true:false;
					 	
					 	// there are RSVP spots remaining OR user loggedin
							if( ($remaining_rsvp==true || $remaining_rsvp >0) || $currentUserRSVP ){

								$data_array_string = $this->event_rsvp_data(
									$object->event_id,
									$object->__repeatInterval,
									$event_pmv,
									true
								);

								echo "<div class='evoRS_status_option_selection' ".$data_array_string.">";

								// if User already RSVPED
								if($currentUserRSVP){
									echo "<p class='nobrbr loggedinuser' data-uid='{$currentUserID}' data-eid='{$object->event_id}' data-ri='{$object->__repeatInterval}'>";
									echo evo_lang('You have already RSVP-ed').": <em class='evors_rsvped_status_user'>".$this->get_rsvp_status($currentUserRSVP)."</em> ";
									echo "</p>";

								}elseif(!$closeRSVPbeforeX && $can_still_rsvp){// have no RSVPed yet
									$content = $this->get_rsvp_choices($opt, $optRS, $countARR);
									echo "<p class='".($this->rsvp_option_count==1?'sin':'')."'>". $content ."</p>";
								}							
								echo "</div>";
							}

						?>
						<div class="evors_incard_form"></div>
						<?php

						echo "<div class='evors_information'>";
						echo $this->get_eventcard_rsvp_html($object->event_id, $object->__repeatInterval, $event_pmv);
						echo "</div>";

						// change RSVP status section							
							if( $this->functions->show_change_rsvp($this->optRS,$currentUserRSVP, $is_user_logged_in ) ){
								$user_id = ($is_user_logged_in)? $currentUserID:'na';

								$data_array_string = $this->event_rsvp_data(
									$object->event_id,
									$object->__repeatInterval,
									$event_pmv,
									true
								);

								echo "<div class='evors_section evors_change_rsvp'>
									<p class='evors_whos_coming_title' ".$data_array_string." >".
										'<span class="evors_change_rsvp_label">'.evo_lang_get('evoRSL_002a2','Can not make it to this event?', $lang, $opt) . '</span>'
										."<span class='change evors_change_rsvp_trig' data-val='".($currentUserRSVP?'chu':'ch')."'>".$_txt_changersvp."</span>
									</p></div>";
							}
						// additional information to rsvped logged in user
							if(!empty($event_pmv['evors_additional_data']) && $currentUserRSVP){
								echo "<div class='evors_additional_data'>";
								echo "<h3 class='evo_h3 additional_info'>".evo_lang('Additional Information', $lang, $opt)."</h3>";
								echo "<p class='evo_data_val'>".$event_pmv['evors_additional_data'][0]."</p>";
								echo "</div>";
							}

						//echo "</div><div class='evorsvp_eventcard_column'>";
						//echo "</div>";
								
						echo "</div>".$helpers['end'];
						echo "</div>";							

				return ob_get_clean();
			}
 

			// get all the data values pertaining to event
			// return array or string
				function event_rsvp_data($event_id, $ri, $pmv='', $string = false){
					
					// pre calculations
					$remaining_rsvp = $this->functions->remaining_rsvp($pmv, $ri, $event_id);
					$precapVal = $this->functions->is_per_rsvp_max_set($pmv);
					$currentUserID = 	$this->functions->current_user_id();	
							$currentUserID = $currentUserID!=0? $currentUserID:'';
					$lang = $this->get_local_lang();

					$pmv = empty($pmv)? get_post_meta($event_id): $pmv;

					$data_array = array();
					$data_array['etitle'] = get_the_title($event_id);
					$data_array['eid'] = $event_id;
					$data_array['ri'] = $ri;
					$data_array['cap'] = (is_int($remaining_rsvp)? $remaining_rsvp:'na');
					$data_array['precap'] = $precapVal;
					$data_array['uid'] = $currentUserID;
					$data_array['prefill'] = $currentUserID;
					$data_array['lang'] = $lang;
					$data_array['incardform'] =  ($this->functions->inCard_form($this->optRS, $pmv)?'yes':'no');

					return ($string)?
						$this->_get_data_attrs($data_array):
						$data_array;

				}

			// for not loggedin users
				function rsvp_for_none_loggedin($helpers, $object){
					global $eventon;
					$lang = (!empty($eventon->evo_generator->shortcode_args['lang'])? $eventon->evo_generator->shortcode_args['lang']:'L1');
					ob_start();
					echo  "<div class='evorow evcal_evdata_row bordb evcal_evrow_sm evo_metarow_rsvp".$helpers['end_row_class']."' data-rsvp='' data-event_id='".$object->event_id."'>
								<span class='evcal_evdata_icons'><i class='fa ".get_eventON_icon('evcal__evors_001', 'fa-envelope',$helpers['evOPT'] )."'></i></span>
								<div class='evcal_evdata_cell'>							
									<h3 class='evo_h3'>".eventon_get_custom_language($helpers['evoOPT2'], 'evoRSL_001','RSVP to event')."</h3>";
							
							$txt_1 = evo_lang('You must login to RSVP for this event',$lang, $helpers['evoOPT2']);
							$txt_2 = evo_lang('Login Now',$lang, $helpers['evoOPT2']);
							echo "<p>{$txt_1} ";

							$login_link = wp_login_url(get_permalink());

							// check if custom login lin kprovided
								if(!empty($this->evoopt1['evo_login_link']))
									$login_link = $this->evoopt1['evo_login_link'];

							echo apply_filters('evo_login_button',"<a class='evors_loginnow_btn evcal_btn' href='".$login_link ."'>{$txt_2}</a>", $login_link, $txt_2);
							echo "</p>";
					echo "</div></div>";
					return ob_get_clean();
				}

			// Do not have permission to RSVP
				function rsvp_not_for_userrole($helpers, $object){
					global $eventon;
					$lang = (!empty($eventon->evo_generator->shortcode_args['lang'])? $eventon->evo_generator->shortcode_args['lang']:'L1');
					ob_start();
					echo  "<div class='evorow evcal_evdata_row bordb evcal_evrow_sm evo_metarow_rsvp".$helpers['end_row_class']."' data-rsvp='' data-event_id='".$object->event_id."'>
								<span class='evcal_evdata_icons'><i class='fa ".get_eventON_icon('evcal__evors_001', 'fa-envelope',$helpers['evOPT'] )."'></i></span>
								<div class='evcal_evdata_cell'>							
									<h3 class='evo_h3'>".eventon_get_custom_language($helpers['evoOPT2'], 'evoRSL_001','RSVP to event')."</h3>";
							
							$txt_1 = evo_lang('You do not have permission to RSVP to this event!',$lang, $helpers['evoOPT2']);
							
							echo "<p>{$txt_1}  ";
							echo "</p>";
							
					echo "</div></div>";
					return ob_get_clean();
				}
		
		// RSVP details for eventCard
			function get_eventcard_rsvp_html($eventid, $ri){
				global $eventon_rs;
				$opt = $this->opt2;

				$pmv = get_post_custom($eventid);

				$lang = $this->get_local_lang();

				$currentUserRSVP = $eventon_rs->functions->get_userloggedin_user_rsvp_status($eventid, $ri, $pmv);
				$remaining_rsvp = $eventon_rs->functions->remaining_rsvp($pmv, $ri, $eventid);

				$unixTime = $this->get_correct_eventTime($pmv, $ri);
				$row_endTime = $unixTime['end'];

				$closeRSVPbeforeX = $eventon_rs->functions->close_rsvp_beforex($unixTime['start'], $pmv);
				$can_still_rsvp = $eventon_rs->functions->can_still_rsvp($row_endTime, $pmv);
				$show_remainingrsvp_onCard = evo_check_yn($pmv, 'evors_capacity_show');


				ob_start();

				// spots remaining
					$spots_remaining_HTML = '';
					if(!$closeRSVPbeforeX && $can_still_rsvp){
						$spots_remaining_HTML .= "<div class='evors_section evors_remaining_spots'>";
						if(!$remaining_rsvp){
							$spots_remaining_HTML .= "<p class='remaining_count no_spots_left'><em class='nospace'>".evo_lang('Filled')."</em>".evo_lang_get( 'evoRSL_002c','No more spots left!', $lang, $opt)."</p>";
						}elseif($remaining_rsvp=='nocap' ){
							$spots_remaining_HTML .= "<p class='remaining_count'><em class='space'>".evo_lang('Open')."</em>".evo_lang_get( 'evoRSL_002bb','Spaces Still Available', $lang, $opt)."</p>";
						}else{
							if($show_remainingrsvp_onCard)
								$spots_remaining_HTML .= "<p class='remaining_count'><em>".$remaining_rsvp  ."</em> ".evo_lang_get('evoRSL_002b','Spots remaining', $lang, $opt)."</p>";
						}
						$spots_remaining_HTML .= "</div>";
					}

				// minimum capacity event happening
					$min_needed_HTML = '';
					if(!empty($pmv['evors_min_cap']) && $pmv['evors_min_cap'][0]=='yes' && !empty($pmv['evors_min_count']) ){
						$output = '';
						$minCap = (int)$pmv['evors_min_count'][0];
						$coming = $this->functions->get_rsvp_count($pmv,'y',$ri);
						if($coming>=$minCap){
							$output = evo_lang('Event is happening for certain');
						}else{
							$need = $minCap - $coming;
							$output = '<em>'.$need.'<i>'.evo_lang('rsvps').'</i></em>';
							$output .= str_replace('-count-', '', evo_lang('Needed for the event to happen') );
						}
						if(!empty($output)){
							$min_needed_HTML = "<div class='evors_section evors_mincap ".(empty($spots_remaining_HTML)?'nosr ':''). ($coming>=$minCap? 'happening':'nothappening')."'><p class='evo_data_val'>".$output."</p></div>";
						}
					}

					if(!empty($spots_remaining_HTML) || $min_needed_HTML){
						echo "<div class='evors_stat_data'>";
						echo $spots_remaining_HTML.$min_needed_HTML;
						echo "<div class='clear'></div></div>";
					}
				// Guest List
					if($this->functions->show_whoscoming($pmv)){	
						// check if only rsvped users can see guest list
						if( $this->functions->can_show_guestList($pmv, $currentUserRSVP)){

							$attendee_icons = $this->GET_attendees_icons($eventid, $ri);
							if($attendee_icons){
								echo "<div class='evors_section evors_guests_list'>";
								echo "<p class='evors_whos_coming_title'>".evo_lang_get('evoRSL_002a','Guests List', $lang, $opt).' <em>('.evo_lang_get('evoRSL_002a1','Attending', $lang, $opt).' <i>'.$this->functions->get_rsvp_count($pmv,'y',$ri)."</i>)</em></p>
									<p class='evors_whos_coming'><em class='tooltip'></em>". $attendee_icons."</p>";
								echo "</div>";
							}
						}
					}

				// List of people not coming
					if($this->functions->show_whosnotcoming($pmv)){	
						// check if only rsvped users can see guest list
						if( $this->functions->can_show_notcomingList($pmv, $currentUserRSVP)){

							$attendee_icons = $this->GET_attendees_icons($eventid, $ri, 'n');
							if($attendee_icons){
								echo "<div class='evors_section evors_guests_list evors_notcoming_list'>";
								echo "<p class='evors_whos_coming_title'>".evo_lang('List of guests not attending to this event', $lang, $opt).' <em>('.evo_lang('Not Attending', $lang, $opt).' <i>'.$this->functions->get_rsvp_count($pmv,'n',$ri)."</i>)</em></p>
									<p class='evors_whos_coming'><em class='tooltip'></em>". $attendee_icons."</p>";
								echo "</div>";
							}
						}
					}

				return ob_get_clean();
			}

			// need removed after evo 2.5 compatibility
			function _get_data_attrs($array){
				if(!function_exists('EVO_get_data_attrs')){
					$output = '';
					foreach($array as $key=>$val){
						$output .= 'data-'.$key.'="'.$val .'" ';
					}
					return $output;
				}else{
					return EVO_get_data_attrs($array);
				}
			}
		
		// EventON lightbox Call
			function lightbox($array){
				$array['evors_lightbox']= array(
					'id'=>'evors_lightbox',
					'CLclosebtn'=> 'evors_lightbox',
					'CLin'=> 'evors_lightbox_body',
				);
				return $array;
			}

		// save a cookie for RSVP
			function set_user_cookie($args){
				//$ip =$this->get_client_ip();
				$cookie_name = 'evors_'.$args['email'].'_'.$args['e_id'].'_'.$args['repeat_interval'];
				$cookie_value = 'rsvped';
				setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");
			}
			function check_user_cookie($userid, $eventid){
				$cookie_name = 'evors_'.$eventid.'_'.$userid;
				if(!empty($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name]=='rsvped'){
					return true;
				}else{
					return false;
				}
			}
		// get form messages html
			function get_form_message($code='', $lang=''){
				$lang = empty($lang) ? $this->get_local_lang() : $lang;
				$opt = $this->opt2;
				$array =  array(
					'err'=>eventon_get_custom_language($opt, 'evoRSL_013','Required fields missing',$lang),
					'err2'=>eventon_get_custom_language($opt, 'evoRSL_014','Invalid email address',$lang),
					'err3'=>eventon_get_custom_language($opt, 'evoRSL_015','Please select RSVP option',$lang),
					'err4'=>eventon_get_custom_language($opt, 'evoRSL_016','Could not update RSVP, please contact us.',$lang),
					'err5'=>eventon_get_custom_language($opt, 'evoRSL_017','Could not find RSVP, please try again.',$lang),
					'err6'=>eventon_get_custom_language($opt, 'evoRSL_017x','Invalid Validation code.',$lang),
					'err7'=>eventon_get_custom_language($opt, 'evoRSL_017y','Could not create a RSVP please try later.',$lang),
					'err8'=>eventon_get_custom_language($opt, 'evoRSL_017z1','You can only RSVP once for this event.',$lang),
					'err9'=>eventon_get_custom_language($opt, 'evoRSL_017z2','Your party size exceed available space.',$lang),
					'err10'=>eventon_get_custom_language($opt, 'evoRSL_017z3','Your party size exceed allowed space per RSVP.',$lang),
					'succ'=>eventon_get_custom_language($opt, 'evoRSL_018','Thank you for submitting your rsvp',$lang),
					'succ_n'=>eventon_get_custom_language($opt, 'evoRSL_019','Sorry to hear you are not going to make it to our event.',$lang),
					'succ_m'=>eventon_get_custom_language($opt, 'evoRSL_020','Thank you for updating your rsvp',$lang),
					'succ_c'=>eventon_get_custom_language($opt, 'evoRSL_021','Great! we found your RSVP!',$lang),
				);				
				return (!empty($code))? $array[$code]: $array;
			}
			function get_form_msg($lang){
				$str='';
				$ar = array('codes'=> $this->get_form_message('', $lang) );
				return "<div class='evors_msg_' style='display:none'>". json_encode($ar)."</div>";
			}
		// GET attendees icons
			function GET_attendees_icons($eventID, $ri, $list_type='y'){
				$list = $this->functions->GET_rsvp_list($eventID, $ri);
				$output = array();

				$guestListInitials = (!empty($this->optRS['evors_guestlist']) && $this->optRS['evors_guestlist']!='fn')? true: false;

				//$LINKGUEST = (!empty($this->optRS['evors_guest_link']) && $this->optRS['evors_guest_link'] == 'yes')? true: false;

				$LINKGUEST = evo_settings_check_yn($this->optRS, 'evors_guest_link');
				$LINKstructure = !empty($this->optRS['evors_profile_link_structure'])?$this->optRS['evors_profile_link_structure']:false ;
				$site_url = get_site_url();

				if(!empty($list[ $list_type ])){
					foreach($list[ $list_type ] as $field=>$value){
						//$gravatar_link = 'http://www.gravatar.com/avatar/' . md5($value['email']) . '?s=32';
						
						$LINK = 'na';
						$initials = ($guestListInitials)?
							substr($value['fname'], 0, 1).substr($value['lname'], 0, 1):
							$value['fname'].' '.$value['lname'];
						$spaces = $value['count'];

						if(empty($initials)) continue;

						// link to profile - if custom link structure is given use that instead of buddypress link
							if($LINKGUEST && $value['userid'] != 'na' && !empty($value['userid'])){
								
								if($LINKstructure){
									$link_append = str_replace('{user_id}', $value['userid'], $LINKstructure);
									$LINK = $site_url . $link_append;
								}elseif(function_exists('bp_core_get_user_domain')){
									$LINK = bp_core_get_user_domain( (int)$value['userid'] );
								}
							}

						$output[$value['email']] = apply_filters('evors_guestlist_guest',"<span class='".($guestListInitials? 'initials':'fullname')."' data-name='{$value['fname']} {$value['lname']}' data-link='{$LINK}' data-uid='". (!empty($value['userid'])? $value['userid']:'-')."'>{$initials}". ($spaces>1? '<i>+'.($spaces-1).'</i>':'' )."</span>", 
							$value
						);
					}
				}

				if(count($output)<1) return false;

				return implode('', $output);
			}
		// GET rsvp status selection HTML
			function get_rsvp_choices($opt2, $optRS, $countARR='', $setchoice='', $formtype=''){
				$selection = (!empty($optRS['evors_selection']))? $optRS['evors_selection']: true;
				$selOpt = array(
					'y'=>array('Yes', 'evoRSL_003'),
					'n'=>array('No', 'evoRSL_005'),
					'm'=>array('Maybe', 'evoRSL_004'),
				);

				$content ='';
				$lang = $this->get_local_lang();

				//if(!is_array($selection)) return false;

				$rsvp_option_count = 0;
				foreach($selOpt as $field=>$value){

					if(is_array($selection) &&  in_array($field, $selection) || $field=='y' || ($field=='n' && !empty($formtype) && $formtype!='submit')  
					){
						$selCount = (!is_array($selection))? 'one ': '';
						$count = (!empty($countARR) && $countARR[$field] != ' (0)')? $countARR[$field]: null;
						$setChoice = (!empty($setchoice) && $setchoice==$field)?'set':'';

						$content .= "<span data-val='{$field}' class='evors_choices {$selCount}{$setChoice}'>".eventon_get_custom_language($opt2, $value[1],$value[0], $lang).$count."</span>";
						$rsvp_option_count++;
					}
				}

				$this->rsvp_option_count = $rsvp_option_count;
				return $content;
			}
		// add eventon rsvp event card field to filter
			function eventcard_array($array, $pmv, $eventid, $__repeatInterval){
				$array['evorsvp']= array(
					'event_id' => $eventid,
					'value'=>'tt',
					'__repeatInterval'=>(!empty($__repeatInterval)? $__repeatInterval:0)
				);
				return $array;
			}
			function eventcard_adds($array){
				$array[] = 'evorsvp';
				return $array;
			}

	// SAVE new RSVP
		function _form_save_rsvp($args){
			global $eventon_rs;
			$status = 0;
			
			// add new rsvp
			if($created_rsvp_id = $this->create_post() ){

				//$pmv = get_post_meta($args['e_id']);				
				$_count = (empty($args['count']))?1: $args['count'];
				$_count = (int)$_count;					

				// save rsvp data		
				if(!empty($args['first_name']))						
						$this->create_custom_fields($created_rsvp_id, 'first_name', $args['first_name']);
				if(!empty($args['last_name']))
					$this->create_custom_fields($created_rsvp_id, 'last_name', $args['last_name']);

				if(!empty($args['email']))
					$this->create_custom_fields($created_rsvp_id, 'email', $args['email']);

				if(!empty($args['phone']))		
					$this->create_custom_fields($created_rsvp_id, 'phone', $args['phone']);		

				$this->create_custom_fields($created_rsvp_id, 'rsvp', $args['rsvp']); // y n m	
				if(!empty($args['updates']))
					$this->create_custom_fields($created_rsvp_id, 'updates', $args['updates']);	
				$this->create_custom_fields($created_rsvp_id, 'count', $_count);	
				$this->create_custom_fields($created_rsvp_id, 'e_id', $args['e_id']);

				$__repeat_interval = (isset($args['repeat_interval']))? $args['repeat_interval']: '0';
				$this->create_custom_fields($created_rsvp_id, 'repeat_interval', $__repeat_interval);

				// save additional guest names
					if(!empty($args['names'])){
						$this->create_custom_fields($created_rsvp_id, 'names', $args['names']);
					}

				// save additional form fields
					$optRS = $this->optRS;
					for($x=1; $x<=$this->addFields; $x++){
						if(evo_settings_val('evors_addf'.$x, $optRS) && !empty($optRS['evors_addf'.$x.'_1'])  ){
							$value = (!empty($args['evors_addf'.$x.'_1']))? $args['evors_addf'.$x.'_1']: '-';
							$this->create_custom_fields($created_rsvp_id, 'evors_addf'.$x.'_1', $value);
						}
					}

				// save loggedin user ID if prefill fields for loggedin enabled
					$prefill_enabled = (!empty($optRS['evors_prefil']) && $optRS['evors_prefil']=='yes')? true:false;

					if( $prefill_enabled || !empty($args['uid'])){
						$loggedinUserID = $this->functions->get_current_userid();

						// user ID if provided or find loggedin user id
						$CURRENT_user_id = !empty($args['uid'])? $args['uid']: $loggedinUserID;
						$this->create_custom_fields($created_rsvp_id, 'userid',$CURRENT_user_id);

						// add user meta
						//$this->functions->add_user_meta($CURRENT_user_id, $args['e_id'], $__repeat_interval, $args['rsvp']);
						$this->functions->save_user_rsvp_status($CURRENT_user_id, $args['e_id'], $__repeat_interval, $args['rsvp']);
					}

				// submission status
					$this->create_custom_fields($created_rsvp_id, 'submission_status', 'confirmed');
					$this->create_custom_fields($created_rsvp_id, 'status', 'check-in');

				$args['rsvp_id'] = $created_rsvp_id;

				// SYNC event's rsvp counts
				$this->functions->sync_rsvp_count($args['e_id']);

				
				// send out email confirmation
				if($args['rsvp']!='n'){	
					$eventon_rs->email->send_email($args);
				}
				
				// $eventon_rs->email->send_email($args,'notification');
                if(get_post_meta($args['e_id'], 'evors_notify_event_author', true) == 'yes'){
                        $eventon_rs->email->send_email($args,'notification');
                }

				$status = $created_rsvp_id;

				do_action('evors_new_rsvp_saved', $created_rsvp_id, $args);

			}else{	
				$status = 7; // new rsvp post was not created
			}
		
			return $status;
		}
	
	// RETURN corected event end time for repeat interval
		function get_correct_event_end_time($e_pmv, $__repeatInterval){
			$datetime = new evo_datetime();
			return $datetime->get_int_correct_event_time($e_pmv, $__repeatInterval, 'end');	
	    }
	    function get_correct_eventTime($e_pmv, $__repeatInterval){
			$datetime = new evo_datetime();
			return $datetime->get_correct_event_repeat_time($e_pmv, $__repeatInterval);	
	    }
	    function get_adjusted_event_formatted_times($e_pmv, $repeat_interval=''){
	    	$datetime = new evo_datetime();
	    	return $datetime->get_correct_formatted_event_repeat_time($e_pmv,$repeat_interval );
	    }
		
	// SUPPORT functions	
		// RETURN: language
			function lang($variable, $default_text, $lang=''){
				global $eventon_rs;
				return $eventon_rs->lang($variable, $default_text, $lang);
			}
			function get_local_lang(){

				$lang = EVORS()->l;

				if(!empty($this->currentlang)) return $this->currentlang;

				if( !empty(EVO()->evo_generator->shortcode_args['lang']))
					$lang = EVO()->evo_generator->shortcode_args['lang'];

				return $lang;
			}

		// function replace event name from string
			function replace_en($string, $eventTitle=''){
				return (empty($eventTitle))?
					str_replace('[event-name]', "<span class='eventName'>Event Name</span>", $string):
					str_replace('[event-name]', $eventTitle, $string);
			}
		// get proper rsvp status name I18N
			public function get_checkin_status($status, $lang='', $evopt=''){
				$evopt = $this->opt2;
				$lang = (!empty($lang))? $lang : 'L1';

				if($status=='check-in'){
					return (!empty($evopt[$lang]['evoRSL_003x']))? $evopt[$lang]['evoRSL_003x']: 'check-in';
				}else{
					return (!empty($evopt[$lang]['evoRSL_003y']))? $evopt[$lang]['evoRSL_003y']: 'checked';
				}
			}
			public function get_trans_checkin_status($lang=''){
				$evopt = $this->opt2;
				$lang = (!empty($lang))? $lang : 'L1';

				return array(
					'check-in'=>(!empty($evopt[$lang]['evoRSL_003x'])? $evopt[$lang]['evoRSL_003x']: 'check-in'),
					'checked'=>(!empty($evopt[$lang]['evoRSL_003y'])? $evopt[$lang]['evoRSL_003y']: 'checked'),
				);
			}

		// Internationalization rsvp status yes, no, maybe
			public function get_rsvp_status($status, $lang=''){
				if(empty($status)) return;

				$opt2 = $this->opt2;
				$_sta = array(
					'y'=>array('Yes', 'evoRSL_003'),
					'n'=>array('No', 'evoRSL_005'),
					'm'=>array('Maybe', 'evoRSL_004'),
				);

				$lang = (!empty($lang))? $lang : (!empty($this->currentlang)? $this->currentlang: 'L1');
				return $this->lang($_sta[$status][1], $_sta[$status][0], $lang);
			}
		function create_post() {
			
			$type = 'evo-rsvp';
	        $valid_type = (function_exists('post_type_exists') &&  post_type_exists($type));

	        if (!$valid_type) {
	            $this->log['error']["type-{$type}"] = sprintf(
	                'Unknown post type "%s".', $type);
	        }
	       
	        $title = 'RSVP '.date('M d Y @ h:i:sa', time());
	        $author = ($this->get_author_id())? $this->get_author_id(): 1;

	        $new_post = array(
	            'post_title'   => $title,
	            'post_status'  => 'publish',
	            'post_type'    => $type,
	            'post_name'    => sanitize_title($title),
	            'post_author'  => $author,
	        );
	       
	        // create!
	        $id = wp_insert_post($new_post);
	       
	        return $id;
	    }
		function create_custom_fields($post_id, $field, $value) {       
	        add_post_meta($post_id, $field, $value);
	    }
	    function update_custom_fields($post_id, $field, $value) {       
	        update_post_meta($post_id, $field, $value);
	    }
    	function get_author_id() {
			$current_user = wp_get_current_user();
	        return (($current_user instanceof WP_User)) ? $current_user->ID : 0;
	    }	
	    function get_event_post_date() {
	        return date('Y-m-d H:i:s', time());        
	    }
	    // return sanitized additional rsvp field option values
	    function get_additional_field_options($val){
	    	$OPTIONS = stripslashes($val);
			$OPTIONS = str_replace(', ', ',', $OPTIONS);
			$OPTIONS = explode(',', $OPTIONS);
			$output = false;
			foreach($OPTIONS as $option){
				$slug = str_replace(' ', '-', $option);
				$output[$slug]= $option;
			}
			return $output;
	    }
}
