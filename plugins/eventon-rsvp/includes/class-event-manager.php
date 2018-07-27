<?php
/**
 * RSVP Event Manager class
 *
 * @author 		AJDE
 * @category 	Admin
 * @package 	eventon-rsvp/classes
 * @version     2.5.3
 */
class evors_event_manager{

	// user RSVP manager
		function user_rsvp_manager($atts){
			global $eventon_rs, $eventon;

			add_action( 'wp_footer', array( $this, 'footer_code' ) ,15);
			
			$eventon->evo_generator->process_arguments($atts);

			$eventon_rs->frontend->register_styles_scripts();
			$eventon_rs->frontend->print_scripts();
			
			// intial variables
			$current_user = get_user_by( 'id', get_current_user_id() );
			$USERID = is_user_logged_in()? get_current_user_id(): false;
			$current_page_link = get_page_link();

			// loading child templates
				$file_name = 'rsvp_user_manager.php';
				$paths = array(
					0=> TEMPLATEPATH.'/'.$eventon->template_url.'rsvp/',
					1=> $eventon_rs->plugin_path.'/templates/',
				);

				foreach($paths as $path){	
					if(file_exists($path.$file_name) ){	
						$template = $path.$file_name;	
						break;
					}
				}

			require_once($template);
		}

		function footer_code(){
			add_filter('evo_frontend_lightbox', array($this, 'lightbox'),10,1);
			global $eventon;

			$eventon->frontend->footer_code();
		}
		function lightbox($array){
			global $eventon_rs;
			$eventon_rs->frontend->lightbox($array);
		}

	// get events for a user
		function get_user_events($userid){
			global $eventon_rs;

			$rsvps = new WP_Query(array(
				'posts_per_page'=>-1,
				'post_type' => 'evo-rsvp',
				'meta_query' => array(
					array('key' => 'userid','value' => $userid)
				),
				//'meta_key'=>'last_name',
				'orderby'=>'post_date'
			));
			$userRSVP = array();

			ob_start();
			if($rsvps->have_posts()):					

				$datetime = new evo_datetime();
				$format = get_option('date_format');
				$currentTime = current_time('timestamp');

				$content = array();

				while( $rsvps->have_posts() ): $rsvps->the_post();
					$_id = $rsvps->post->ID;
					$pmv = get_post_meta($_id);
					$checkin_status = (!empty($pmv['status']))? $pmv['status'][0]:'check-in'; // checkin status

					$e_id = (!empty($pmv['e_id']))? $pmv['e_id'][0]:false;

					if(!$e_id) continue;
					if(empty($pmv['rsvp']) ) continue; // if there are no RSVP info
					$epmv = get_post_custom($e_id);					

					$rsvp = (!empty($pmv['rsvp'])? 	$eventon_rs->frontend->get_rsvp_status($pmv['rsvp'][0]):'');
					$RI = (!empty($pmv['repeat_interval'])?$pmv['repeat_interval'][0]:'');

					// get time values from eventon data class
						$time = $datetime->get_correct_event_repeat_time($epmv, $RI, $format);
						$cleanTime = $datetime->get_formatted_smart_time($time['start'], $time['end'], $epmv);
					
					$link = get_permalink($e_id);
					$link = $link.( strpos($link, '?')?'&ri='.$RI:'?ri='.$RI);

					$remaining_rsvp = $eventon_rs->functions->remaining_rsvp($epmv, $RI, $e_id);

					// individual event class values
						$p_classes = array();
						$p_classes[] = ($time['start']>=$currentTime)?'':'pastevent';
						$p_classes[] = $checkin_status;
					
					$output = '';
					$output.= "<p id='rsvp_event_{$e_id}' class='rsvpmanager_event ".(count($p_classes)>0? implode(' ', $p_classes):'')."'>" . evo_lang('RSVP ID'). ": <b>#".$_id."</b> <span class='rsvpstatus status_{$rsvp}'>{$rsvp}</span>
						<em class='checkin_status'>". $eventon_rs->frontend->get_checkin_status($checkin_status)."</em><br/>
						<em class='count'>".(!empty($pmv['count'])? $pmv['count'][0]:'-')."</em>
						<em class='event_data' >
							<span style='font-size:18px;'><a href='".$link."'>".get_the_title($e_id)."</a></span>
							<span class='event_time'>".evo_lang('Time').": ".$cleanTime."</span>
							</em>";

					// if the event is current event, allow updating rsvp status
					$output.= ($time['start']>=$currentTime)? 
						"<span class='action' data-cap='".(is_int($remaining_rsvp)? $remaining_rsvp:'na')."' data-etitle='".get_the_title($e_id)."' data-precap='".$eventon_rs->functions->is_per_rsvp_max_set($epmv)."' data-uid='{$userid}' data-rsvpid='{$_id}' data-eid='{$e_id}' data-ri='{$RI}' ><a class='update_rsvp' data-val='chu'>".evo_lang('Update')."</a></span>":'';
					$output.= "</p>";

					// based on live or past event arrange rsvped events
						if($time['start']>=$currentTime){
							$content['live'][] = $output;
						}else{							
							$content['past'][] = $output;
						}					
				endwhile;

				// print out output
					echo !empty($content['live'])? implode('',$content['live']):'';
					echo !empty($content['past'])? implode('',$content['past']):'';

			endif;
			wp_reset_postdata();
			return ob_get_clean();
		}
}