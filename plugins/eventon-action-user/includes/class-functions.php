<?php
/**
 * ActionUser front and back end functions
 * @version  2.0.14
 */
class evoau_functions{
	public function __construct(){
		
	}

	// event manager related
		// output a hidden wp_editor
			function print_hidden_editor(){
				?><div id='evoau_editor' style='display:none'><?php wp_editor('', 'id',  array('textarea_rows' => 3)); ?></div><?php
			}
		// Can a event be editable
			function can_edit_event($eventid, $epmv=''){

				$opt = get_option('evcal_options_evoau_1');
				
				// does settings allow editing
				if( evo_settings_check_yn($opt,'evo_auem_editing') ){
					// check if editing allowed per each event post
					if(!empty($epmv)){						
						return evo_check_yn($epmv, 'evoau_disableEditing')? false: true;
					}else{
						$editable = get_post_meta($eventid, 'evoau_disableEditing', true);
						return (!empty($editable) && $editable=='yes')? false:true;
					}

				}else{
					return false;
				}

			}
		// can user edit event
			function can_currentuser_edit_event($event_id, $epmv=''){
				$opt = get_option('evcal_options_evoau_1');
				// if editing disabled 
				if( !$this->can_edit_event($event_id, $epmv) ) return false;

				global $current_user;
				$event_author = get_post_field ('post_author', $event_id);

				// if user have permission to edit events
				if( current_user_can('edit_others_eventons', $event_id) ){
					//echo '1';
					return true;
				
				// if user is post author
				}elseif($event_author == $current_user->ID){
					//echo '2';
					return true;

				// if user is assigned to event and assigned user can edit events	
				}elseif(					
					evo_settings_check_yn($opt, 'evoau_assigned_editing') &&
					$this->user_assigned_toevent($event_id, $current_user->ID )
				){
					//echo '3';
					return true;
				}
			}

		// can user delete events
			function can_currentuser_delete_event($event_id){
				$opt = get_option('evcal_options_evoau_1');
				// if deleting disabled 
				if( !evo_settings_check_yn($opt,'evo_auem_deleting') ) return false;

				global $current_user;
				$event_author = get_post_field ('post_author', $event_id);
				
				// if user have permission to delete events
				// /if( current_user_can('delete_post', $event_id) ){
				if( current_user_can('delete_others_eventons', $event_id) ){
					return true;

				// if user is the post author 
				}elseif( $event_author == $current_user->ID ){
					return true;
				
				// if user is assigned to event and assigned users can delete events
				}elseif(
					evo_settings_check_yn($opt, 'evoau_assigned_deleting') &&
					$this->user_assigned_toevent($event_id, $current_user->ID )
				){
					return true;
				}

			}

		// check if the user ID is assigned to event
			function user_assigned_toevent($event_id, $userid){
				$r = is_object_in_term( $event_id, 'event_users', $userid );

				$saved_users = wp_get_object_terms( $event_id, 'event_users', array( 'fields'=>'slugs') );
				if(is_array($saved_users)  && !empty($saved_users)){
					if( in_array('all', $saved_users) ){
						return true;
					}else{
						$all_users = get_users();	
						foreach($all_users as $uu){
							if( in_array($uu->ID, $saved_users)){
								return true;
							}
						}
					}
				}
				return false;
			}

		// trash event
			function trash_event($eid){
				return wp_trash_post($eid);
			}
		// get url with variables added
			public function get_custom_url($baseurl, $args){
				$str = '';
				foreach($args as $f=>$v){ $str .= $f.'='.$v. '&'; }
				if(strpos($baseurl, '?')!== false){
					return $baseurl.'&'.$str;
				}else{
					return $baseurl.'?'.$str;
				}
			}

		// Get the back to event manager link
			function get_backlink($current_page_link){
				$parsed_url = parse_url($current_page_link);
				
				if( array_key_exists( 'query', $parsed_url )) {
			        $query_portion = $parsed_url['query'];
			    }else{
			    	return $current_page_link;
			    }

			    parse_str( $query_portion, $query_array );

			    $evoau_vars = apply_filters('evoau_event_manager_backlink_vars', array('action','eid'));
			    foreach($query_array as $key=>$value){
			    	if(in_array($key, $evoau_vars)){
			    		unset($query_array[$key]);
			    	}
			    }

			    $q = ( count( $query_array ) === 0 ) ? '' : '?';
			    $url =  $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'] . $q . http_build_query( $query_array );

			    return $url;
			}

// GET EVENTS
	// get paged events
		function get_paged_events($events, $atts){

			if(!$atts || empty($atts)) $atts = array();
			
			$page = (int)$atts['page'];
			$paginate = false;
			$count_start = $count_end = false;

			// if pagination
			if(isset($atts['pagination']) && $atts['pagination'] == 'yes'){
								
				$count_start = ($page==0)? 1: $atts['events_per_page'] * ($page-1)+1;
				$count_end = ($page==0)? $atts['events_per_page']:  $atts['events_per_page'] *$page;
				$paginate = true;
			}

			$count =1;
			ob_start();

			foreach($events as $event_id=>$evv){
				
				if($paginate && ($count < $count_start || $count >$count_end ) ){
					$count++;  continue;
				}

				echo $this->gethtml_event_row_event($event_id, $evv);
				$count ++;			
			}

			return ob_get_clean();
		}

		function get_next_pagination_page($atts){
			$current_page = isset($atts['page'])? $atts['page']: 1;			
			$direction = $atts['direction'];

			// if no direction
			if($direction == 'none') return $current_page;

			$all_events = $atts['total_events'];
			$events_per_page = $atts['events_per_page'];

			$max_pages = ceil($all_events/$events_per_page);

			$next_page = ($direction=='next')? $current_page+1: $current_page -1;
			$next_page = ($next_page<1)? 1: $next_page;

			if( $next_page > $max_pages) $next_page = $current_page;

			
			return $next_page;
		}

		// get event row HTML for event manager
			function gethtml_event_row_event($event_id, $data){
				ob_start();

				$evoDateTime = new evo_datetime();

				// initial values
				$DateTime = '';
				$ePmv = get_post_custom($event_id);
				$can_user_edit_event = $this->can_currentuser_edit_event($event_id, $ePmv);
				
				$EVENT_SUBMISSION_FORMAT = 'unpaid_submission';
				if(!empty($ePmv['_evoaup_event_type'])) $EVENT_SUBMISSION_FORMAT = 'regular';
				if(!empty($ePmv['_evoaup_submission_level'])) $EVENT_SUBMISSION_FORMAT = 'level_based';
				

				// edit button html
					$edit_html = (!$can_user_edit_event)? '':
						"<a class='fa fa-pencil editEvent' data-eid='{$event_id}' data-sformat='{$EVENT_SUBMISSION_FORMAT}'></a>";
				
				// delete button html
					$delete_html = (!$this->can_currentuser_delete_event($event_id, $ePmv))?
						'':"<a class='fa fa-trash deleteEvent' data-eid='{$event_id}'></a>";

				// Event Date	  
					$startUNIX = !empty($ePmv['evcal_srow'])? $ePmv['evcal_srow'][0]:false;
					$eUnix = !empty($ePmv['evcal_erow'])? $ePmv['evcal_erow'][0]: $startUNIX;
					if($startUNIX)
						$DateTime = $evoDateTime->get_formatted_smart_time($startUNIX, $eUnix, $ePmv);
					//$time = date_i18n(get_option('date_format').' '.get_option('time_format'), $ePmv);
				
				// Link
					$link = get_permalink($event_id);

				// if event is featured
					$feature_event_tag = evo_check_yn($ePmv, '_featured')? "<span class='evoauem_event_tag evo_event_tag_primary'><i>". evo_lang('Featured Event') ."</i></span>":'';

				echo "<div class='evoau_manager_row evoau_em_{$event_id}' >";
				echo "<p>". $feature_event_tag. "<subtitle><a href='{$link}'>".$data[0]."</a></subtitle>";
					do_action('evoau_manager_row_title', $event_id, $ePmv );
				echo "</p>";
				
				echo "{$edit_html} {$delete_html}
					<span>".evo_lang('Status').": <em>". evo_lang($data[1])."</em></span>
					<span>".evo_lang('Date').": <em>{$DateTime}</em></span>";

				// pluggable
				do_action('evoau_manager_row', $event_id, $ePmv, $can_user_edit_event );

				echo "</div>";
				return ob_get_clean();
			}

		// language
			function get_lang($text, $lang='L1'){
				global $eventon_au;
				$lang = !empty($eventon_au->frontend->lang)?$eventon_au->frontend->lang: $lang ;
				return evo_lang($text, $lang, $eventon_au->frontend->evoau_opt_2);
			}
}