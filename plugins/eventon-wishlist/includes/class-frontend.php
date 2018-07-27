<?php
/**
 * front end
 * @version 0.1
 */
class evowi_frontend{

	public function __construct(){

		$this->fnc = new evowi_fnc();

		// wishlisted events
		$this->user_wishlist = $this->fnc->user_wishlist();

		$wishlist_events = get_option('_evo_wishlist');
		//print_r($wishlist_events);
		//delete_option('_evo_wishlist');


		add_action( 'init', array( $this, 'register_styles_scripts' ) , 15);
		add_action('evo_addon_styles', array($this, 'styles') );

		// shortcode additions
		add_filter('eventon_shortcode_defaults', array($this,'add_shortcode_defaults'), 10, 1);
		add_filter('eventon_shortcode_popup',array($this,'shortcode_options'), 10, 1);
		add_shortcode('add_eventon_wishlist_manager', array($this,'wishlist_manager'));
		add_filter('eventon_calhead_shortcode_args', array($this,'cal_head_args'), 10, 2);

		// eventtop inclusion
		add_filter('eventon_eventtop_one', array($this, 'eventop'), 10, 3);
		add_filter('evo_eventtop_adds', array($this, 'eventtop_adds'), 10, 1);
		add_filter('eventon_eventtop_evowi', array($this, 'eventtop_content'), 10, 2);

		add_filter('evo_frontend_lightbox', array($this, 'lightbox'),10,1);


	}

	//	Styles for the tab page
		function styles(){
			global $evowi;
			ob_start();
			include_once($evowi->plugin_path.'/assets/evowi_style.css');
			echo ob_get_clean();
		}
		public function register_styles_scripts(){

			if(is_admin()) return false;

			global $evowi, $eventon;

			// Load dailyview styles conditionally
			$evOpt = evo_get_options('1');
			if( evo_settings_val('evcal_concat_styles',$evOpt, true))
				wp_register_style( 'evowi_styles',$evowi->assets_path.'evowi_style.css');

			wp_register_script('evowi_script',$evowi->assets_path.'evowi_script.js', array('jquery'), $evowi->version, true );
			wp_localize_script(
				'evowi_script',
				'evowi_ajax_script',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ) ,
					'postnonce' => wp_create_nonce( 'evowi_nonce' )
				)
			);

			add_action( 'wp_enqueue_scripts', array($this,'print_styles' ));
		}
		function print_styles(){
			wp_enqueue_style( 'evowi_styles');
			wp_enqueue_script('evowi_script');
		}

	// Show add to wish list element
		// event top inclusion
		public function eventop($array, $pmv, $vals){
			$array['evowi'] = array(
				'vals'=>$vals,
				'pmv'=>$pmv,
			);
			return $array;
		}
		public function eventtop_content($object, $helpers){
			global $eventon;

			// if wish list is not enabled for calednar
			// if(!evo_settings_check_yn($eventon->evo_generator->shortcode_args, 'wishlist')) return;
			//
			// $this->print_styles();
			//
			// $output = '';

			//print_r($object);
			$data = "data-ei='{$object->vals['eventid']}' data-ri='{$object->vals['ri']}' data-pl='". get_page_link() ."'";
			$evOpt = evo_get_options('1');


			if( $this->fnc->is_event_wishlisted($object->vals['eventid'], $object->vals['ri'], $this->user_wishlist) ){
				$output .= "<span class='evowi wishlisted' $data>
					<span class='evowi_wi_area'>
						<i class='fa ".get_eventON_icon('evcal_evowi_001', 'fa-heart',$evOpt )."'></i>
						<em>".$this->fnc->get_wishlist_count($object->vals['eventid'], $object->vals['ri'])."</em>
					</span>".evo_lang('Unlike')."</span>";
			}else{
				$output .= "<span class='evowi notlisted' $data>
					<span class='evowi_wi_area'>
						<i class='fa ".get_eventON_icon('evcal_evowi_002', 'fa-heart-o',$evOpt )."'></i>
						<em>".$this->fnc->get_wishlist_count($object->vals['eventid'], $object->vals['ri'])."</em>
					</span>".evo_lang('Like')."</span>";
			}

			return $output;
		}

		// event card inclusion functions
			function eventtop_adds($array){
				$array[] = 'evowi';
				return $array;
			}

		// lightbox
			function lightbox($array){
				$array['evowl_lightbox']= array(
					'id'=>'evowl_lightbox',
					'CLclosebtn'=> 'evowl_lightbox',
					'CLin'=> 'evowl_lightbox_body',
					'content'=>'<p class="evoloading loading_content"></p>'
				);
				return $array;
			}

	// wishlist manager
		function wishlist_manager($atts){
			global $eventon, $evowi;

			$this->lang = (!empty($atts['lang']))? $atts['lang']:'L1';

			// loading child templates
				$file_name = 'wishlist-manager.php';
				$paths = array(
					0=> TEMPLATEPATH.'/'.$eventon->template_url.'wishlist/',
					1=> STYLESHEETPATH.'/'.$eventon->template_url.'wishlist/',
					2=> $evowi->plugin_path.'/templates/',
				);

				foreach($paths as $path){
					if(file_exists($path.$file_name) ){
						$template = $path.$file_name;
						break;
					}
				}

			ob_start();
			require_once($template);
			return ob_get_clean();
		}

	// shortcode
		function shortcode_content($atts){
			// add el scripts to footer
			add_action('wp_footer', array($this, 'print_styles'));


			// connect to support arguments
			global $eventon;
			$supported_defaults = $eventon->evo_generator->get_supported_shortcode_atts();

			$args = (sizeof($atts)>0 && is_array($atts) ) ?
				array_merge( $supported_defaults, $atts ):
				$supported_defaults;

			return $this->generate_calendar($args);
		}

		function generate_calendar($args){
			if($this->user_wishlist){

				//print_r($this->user_wishlist);
				global $eventon;

				$args['el_type'] = 'ue';

				$this->only_wi_actions();

				// CUT OFF time calculation

					// reset arguments
					$current_timestamp = current_time('timestamp');
					$args['fixed_date']= $args['fixed_month']= $args['fixed_year']='';
					$args['wishlist'] = 'yes';

				// restrained time unix
					$number_of_months = (!empty($args['number_of_months']))? (int)($args['number_of_months']):0;
					$month_dif = (isset($args['el_type']) && $args['el_type']=='ue')? '+':'-';
					$unix_dif = strtotime($month_dif.($number_of_months-1).' months', $current_timestamp);

					$restrain_monthN = ($number_of_months>0)?
						date('n',  $unix_dif):
						date('n',$current_timestamp);

					$restrain_year = ($number_of_months>0)?
						date('Y', $unix_dif):
						date('Y',$current_timestamp);

				// upcoming events list
					if(isset($args['el_type']) && $args['el_type']=='ue'){
						$restrain_day = date('t', mktime(0, 0, 0, $restrain_monthN+1, 0, $restrain_year));
						$__focus_start_date_range = $current_timestamp;
						$__focus_end_date_range =  mktime(23,59,59,($restrain_monthN),$restrain_day, ($restrain_year));

					}else{// past events list

						if(!empty($args['event_order']))
							$args['event_order']='ASC';

						$args['hide_past']='no';

						$__focus_start_date_range =  mktime(0,0,0,($restrain_monthN),1, ($restrain_year));
						$__focus_end_date_range = $current_timestamp;
					}


				// Add extra arguments to shortcode arguments
				$new_arguments = array(
					'focus_start_date_range'=>$__focus_start_date_range,
					'focus_end_date_range'=>$__focus_end_date_range,
				);

				//print_r($args);
				$args = (!empty($args) && is_array($args))?
					wp_parse_args($new_arguments, $args): $new_arguments;


				// PROCESS variables
				$args__ = $eventon->evo_generator->process_arguments($args);
				$this->shortcode_args=$args__;


				$eventon->evo_generator->events_processed = array();

				// ==================
				$content =$eventon->evo_generator->calendar_shell_header(
					array(
						'month'=>$restrain_monthN,'year'=>$restrain_year,
						'date_header'=>false,
						'sort_bar'=>true,
						'date_range_start'=>$__focus_start_date_range,
						'date_range_end'=>$__focus_end_date_range,
						'title'=>$args['title'],
						'send_unix'=>true
					)
				);

				// GET events list array
				$event_list_array = $eventon->evo_generator->evo_get_wp_events_array(
					array(
						'post__in'=> $this->fnc->get_user_wishlist_events_array( $this->user_wishlist )
					)
					, $args, $eventon->evo_generator->cal_filters);

				// filter for only wishlisted events between repeat instances
				foreach($event_list_array as $key=>$event){
					$ri = !empty($event['event_repeat_interval'])? $event['event_repeat_interval']: '0';
					if(!in_array($event['event_id'].'-'.$ri, $this->user_wishlist)){
						unset($event_list_array[$key]);
					}
				}

				// Events separated by month titles
				if(!empty($args['sep_month']) && $args['sep_month']=='yes' && $args['number_of_months']>1 ){

					$content.= $eventon->evo_generator->separate_eventlist_to_months($event_list_array, $args['event_count'], $args);


				}else{ // return only individual events
					// pre filter events: featured events to top & pagination cutoffs
					$months_event_array = $eventon->evo_generator->prefilter_events($event_list_array);

					// GET: eventTop and eventCard for each event in order
					$months_event_array = $eventon->evo_generator->generate_event_data(
						$months_event_array, 	$args['focus_start_date_range']
					);

					$content .= $eventon->evo_generator->evo_process_event_list_data($months_event_array, $args);
				}

				$content .=$eventon->evo_generator->calendar_shell_footer();

				$this->remove_only_wi_actions();

				return  $content;
			}else{

			}
		}

		public function only_wi_actions(){
			add_filter('eventon_cal_class', array($this, 'eventon_cal_class'), 10, 1);
		}
		public function remove_only_wi_actions(){
			//add_filter('eventon_cal_class', array($this, 'remove_eventon_cal_class'), 10, 1);
			remove_filter('eventon_cal_class', array($this, 'eventon_cal_class'));
		}
		// add class name to calendar header for DV
		function eventon_cal_class($name){
			$name[]='evoWI';
			return $name;
		}
		// add class name to calendar header for DV
		function remove_eventon_cal_class($name){
			if(($key = array_search('evoWI', $name)) !== false) {
			    unset($name[$key]);
			}
			return $name;
		}


		function cal_head_args($array, $arg=''){
			if( !empty($arg['wishlist'])) $array['wishlist']=$arg['wishlist'];
			return $array;
		}
		function add_shortcode_defaults($arr){
			return array_merge($arr, array(
				'wishlist'=>'no',
				'title'=>'',
			));
		}
		function shortcode_options($shortcode_array){

			$shortcode_array[0]['variables'][] = array(
				'name'=> 'Allow loggedin visitors to add events to wishlist',
				'type'=>'YN',
				'default'=>'no',
				'var'=>'wishlist',
				'guide'=>'This will allow loggedin visitors to add events to wishlist '
			);

			$shortcode_array[1]['variables'][] = array(
				'name'=> 'Allow loggedin visitors to add events to wishlist',
				'type'=>'YN',
				'default'=>'no',
				'var'=>'wishlist',
				'guide'=>'This will allow loggedin visitors to add events to wishlist '
			);


			// wish list events only
			$new_shortcode_array = array(
				array(
					'id'=>'s_WI',
					'name'=>'Wishlist Events Manager',
					'code'=>'add_eventon_wishlist_manager',
					'variables'=>array(

					)
				)
			);
			return array_merge($shortcode_array, $new_shortcode_array);
			//print_r($shortcode_array);

		}

}
