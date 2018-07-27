<?php
/**
 * RSVP frontend supporting functions
 * @version  0.2
 */
class evorsvp_functions{

	private $UMV = 'eventon_rsvp_user';

	// loggedin  user
		// version 2 of user rsvp data functions
			function print_debug($eventid){
				$data = get_post_meta($eventid, 'evors_data', true);
				//print_r($data);
			}
			function save_user_rsvp_status($userid, $eventid, $ri=0, $rsvp_status, $eventpmv = ''){
				$rsvp_data = (!empty($eventpmv) && isset($eventpmv['evors_data']))? 
					unserialize($eventpmv['evors_data'][0]): get_post_meta($eventid, 'evors_data', true);

				// not the first itme doing this
				if(!empty($rsvp_data)){
					$rsvp_data[$userid][$ri] = $rsvp_status;
					update_post_meta($eventid, 'evors_data', $rsvp_data);
				}else{ // first time
					$rsvp_data = array();
					$rsvp_data[$userid][$ri] = $rsvp_status;
					add_post_meta($eventid, 'evors_data', $rsvp_data);
				}
			}
			function get_user_rsvp_status($userid, $eventid, $ri=0, $eventpmv = ''){
				$rsvp_data = (!empty($eventpmv) && isset($eventpmv['evors_data']))? 
					unserialize($eventpmv['evors_data'][0]): get_post_meta($eventid, 'evors_data', true);

				if(empty($rsvp_data)){
					return false;
				}else{
					return (isset($rsvp_data[$userid][$ri]))? $rsvp_data[$userid][$ri]: false;
				}
			}
			function trash_user_rsvp($userid, $eventid, $ri=0, $eventpmv = ''){
				$rsvp_data = (!empty($eventpmv) && isset($eventpmv['evors_data']))? 
					unserialize($eventpmv['evors_data'][0]): get_post_meta($eventid, 'evors_data', true);

				if(empty($rsvp_data)) return;

				if(empty($rsvp_data[$userid][$ri])) return;

				unset($rsvp_data[$userid][$ri]);
				update_post_meta($eventid,'evors_data', $rsvp_data);
			}
			// for all rsvped for an event sync user rsvp status
			function sync_users_to_rsvps($eventid){

				// build a new array of users that rsvped to this event repeat instance
				$rsvpUserArray = array();
				$rsvps = new WP_Query( array(
					'posts_per_page'=>-1,
					'post_type' => 'evo-rsvp',
					'meta_query' => array(
						array('key' => 'e_id','value' => $eventid),
					),
				));
				while($rsvps->have_posts()): $rsvps->the_post();
					$rsvpPMV = get_post_custom($rsvps->post->ID);

					if(empty($rsvpPMV['userid'])) continue;
					if(empty($rsvpPMV['rsvp'])) continue;
					$ri = (!empty($rsvpPMV['repeat_interval'])? $rsvpPMV['repeat_interval'][0]:0);

					$rsvpUserArray[$rsvpPMV['userid'][0]][$ri] = $rsvpPMV['rsvp'][0];
				endwhile;

				//print_r($rsvpUserArray);

				update_post_meta($eventid,'evors_data', $rsvpUserArray);
				
				wp_reset_postdata();
			}
			function get_userloggedin_user_rsvp_status($eid, $ri=0, $eventpmv = ''){
				if(is_user_logged_in()){					
					return $this->get_user_rsvp_status($this->current_user_id(),$eid, $ri, $eventpmv );
				}else{return false;}
			}
			function current_user_id(){
				return get_current_user_id();
			}	

			// check if user have to be loggedin to RSVP and if user role rsvp restrictions activated and met
			function user_need_loggedin_to_rsvp($ePMV){

				$is_user_logged_in = is_user_logged_in();
				$current_user_id = $this->current_user_id();

				// get current user role
				

				// check if event specify whether use have to be loggedin
				if(!empty($ePMV['evors_only_loggedin']) && $ePMV['evors_only_loggedin'][0]=='yes' && !$is_user_logged_in)
					return false;

				return true;
			}

			// check is user role restrictions enable and current user have permission to RSVP
			// user is loggedin at this point
			function can_user_rsvp(){
				global $eventon_rs;

				if(!is_user_logged_in()) return false;

				$roles = !empty($eventon_rs->evors_opt['evors_rsvp_roles'])? $eventon_rs->evors_opt['evors_rsvp_roles']: false;

				// if specific user roles were not set
				if(!$roles)  return true;

				// check if current use in the specified user role
				if($roles){
					$user = wp_get_current_user();
					
					foreach($user->roles as $role){
						if(in_array($role, $roles)){
							return true;
						}
					}
				}
				return false;
			}



		// wait list functions
			function add_user_to_waitlist($userid, $eventid, $ri){
				$eventwaitlist = get_post_meta($eventid, 'evors_waitlist', true);
				// not the first itme doing this
				if(!empty($eventwaitlist) && isset($eventwaitlist[$ri]) ){
					if(!in_array($userid, $eventwaitlist[$ri])){
						$eventwaitlist[$ri][] = $userid;
						update_post_meta($eventid, 'evors_waitlist', $eventwaitlist);
					}else{
						return false;
					}					
				}else{
					$eventwaitlist[$ri][] = $userid;
					add_post_meta($eventid, 'evors_waitlist', $eventwaitlist);
				}
			}
			// move user out of wait list and into event rsvp list
			function move_user_to_event($userid, $eventid, $ri, $rsvp_status){
				$eventwaitlist = get_post_meta($eventid, 'evors_waitlist', true);
				if(!empty($eventwaitlist) && isset($eventwaitlist[$ri]) && in_array($userid, $eventwaitlist[$ri])){
					$key = array_search($userid, $eventwaitlist[$ri]);
					unset($eventwaitlist[$ri][$key]);

					// move user to event rsvp list
					$this->save_user_rsvp_status($userid, $eventid, $ri, $rsvp_status);
				}else{
					return false;
				}
			}

		// update to new version
			function update_to_new_system(){
				$user_data = get_option($this->UMV);
				foreach($user_data as $userid=>$data){
					foreach($data as $eventid=>$datar){
						foreach($datar as $ri=>$rsvp){
							$this->save_user_rsvp_status($userid, $eventid, $ri, $rsvp);
						}
					}
				}
				update_option('evors_update', 'true');
			}

		/*
			function add_user_meta($uid, $eid, $ri, $rsvp){
				$user_data = get_option($this->UMV);
				if(!empty($user_data) && !is_array($rsvp)){
					$user_data[$uid][$eid][$ri] = $rsvp;
					update_option($this->UMV, $user_data);
				}else{
					$user_data[$uid][$eid][$ri] = $rsvp;
					add_option($this->UMV, $user_data);
				}			
			}
			// GET user rsvp status by user id
				function get_user_rsvp_status($uid, $eid, $ri='0'){
					$user_data = get_option($this->UMV);
					//print_r($user_data);
					if(!empty($user_data)){
						return !empty($user_data[$uid][$eid][$ri])? $user_data[$uid][$eid][$ri]: false;
					}else{
						return false;
					}
				}
						
				function trash_user_rsvp($uid, $eid, $ri='0'){
					if($uid){
						$user_data = get_option($this->UMV);
						if(empty($user_data)) return;

						if(empty($user_data[$uid][$eid][$ri])) return;

						unset($user_data[$uid][$eid][$ri]);
						update_option($this->UMV, $user_data);
					}
				}
		*/
	
		// CHECK if user rsvped already
			function has_user_rsvped($post){
				$eventid = (!empty($post['e_id'])? $post['e_id']: (!empty($post['eventid'])? $post['eventid']:''));
				$repeat_interval = !empty($post['repeat_interval'])? $post['repeat_interval']:0;
				$rsvped = new WP_Query( array(
					'posts_per_page'=>-1,
					'post_type' => 'evo-rsvp',
					'meta_query' => array(
						array('key' => 'email','value' => $post['email']),
						array('key' => 'e_id','value' => $eventid),
						array('key' => 'repeat_interval','value' => $repeat_interval),
					),
				));
				return ($rsvped->have_posts())? $rsvped->post->ID: false;
			}
			function has_loggedin_user_rsvped(){
				$eventid = (!empty($post['e_id'])? $post['e_id']: (!empty($post['eventid'])? $post['eventid']:''));
				$repeat_interval = !empty($post['repeat_interval'])? $post['repeat_interval']:0;
				$rsvped = new WP_Query( array(
					'posts_per_page'=>-1,
					'post_type' => 'evo-rsvp',
					'meta_query' => array(
						array('key' => 'e_id','value' => $eventid),
						array('key' => 'repeat_interval','value' => $repeat_interval),
						array('key' => 'userid','value' => $post['uid']),
					),
				));

				$rsvp = false;
				if($rsvped->have_posts() && $rsvped->found_posts==1){
					while($rsvped->have_posts()): $rsvped->the_post();
						$rsvp = get_post_meta($rsvped->post->ID, 'rsvp',true);
					endwhile;
				}
				wp_reset_postdata();

				return $rsvp;
			}
	
	// RSVP post related
		// RETURN: remaining RSVP adjsuted for Repeat intervals
			function remaining_rsvp($event_pmv, $ri = 0, $event_id=''){
				// get already RSVP-ed count
				$yes = (!empty($event_pmv['_rsvp_yes']))? $event_pmv['_rsvp_yes'][0]:0;
				$maybe = (!empty($event_pmv['_rsvp_maybe']))? $event_pmv['_rsvp_maybe'][0]:0;

				// if capacity limit set for rsvp 
				if(!empty($event_pmv['evors_capacity']) && $event_pmv['evors_capacity'][0]=='yes'){
					// if capacity calculated per each repeat instance
					if($this->is_ri_count_active($event_pmv)){		
						$ri_capacity = unserialize($event_pmv['ri_capacity_rs'][0]);			
						$ri_count = !empty($event_pmv['ri_count_rs'])? unserialize($event_pmv['ri_count_rs'][0]):null;	

						if(empty($ri_capacity[$ri])) return 0;

						// if count not saved
						if(empty($ri_count)){
							$this->update_ri_count($event_id, $ri, 'y', $yes);
							$this->update_ri_count($event_id, $ri, 'm', $maybe);
						}	
						$count = (!empty($ri_count))? (!empty($ri_count[$ri]['y'])? $ri_count[$ri]['y']:0)+
							(!empty($ri_count[$ri]['m'])? $ri_count[$ri]['m']:0)
							:($yes+$maybe);

						return $ri_capacity[$ri] - $count;
					
					// not repeating event
					}elseif(!empty($event_pmv['evors_capacity_count'])	){
						$capacity = (int)$event_pmv['evors_capacity_count'][0];
						$remaining =  $capacity - ( $yes + $maybe);
						return ($remaining>0)? $remaining: false;
					}elseif($event_pmv['evors_capacity'][0]=='no' || empty($event_pmv['evors_capacity_count'])){
						return true;
					}
				}else{
				// set capacity limit is NOT set
					return 'nocap';
				}
			}
		// return total capacity for events adjusted for repeat intervals
			function get_total_adjusted_capacity($eid, $ri=0, $epmv=''){
				$epmv = (!empty($epmv))? $epmv: get_post_meta($eid);

				$setCap = (!empty($epmv['evors_capacity']) && $epmv['evors_capacity'][0]=='yes' )? true:false;
				$setCapVal = (!empty($epmv['evors_capacity_count']) )? $epmv['evors_capacity_count'][0]:false;
				$managRIcap = (!empty($epmv['_manage_repeat_cap_rs']) && $epmv['_manage_repeat_cap_rs'][0]=='yes')? true:false;
				$riCap = (!empty($epmv) && !empty($epmv['ri_capacity_rs']))? 
					unserialize($epmv['ri_capacity_rs'][0]):false;

				// if managing capacity per each ri
				if($managRIcap && $riCap){
					return !empty($riCap[$ri])? $riCap[$ri]:0;
				// if total capacity limit for event
				}elseif($setCap && $setCapVal){
					return ($setCapVal)? $setCapVal: 0;
				}else{
					return 0;
				}
				
			}		

		// GET RSVP attendee list as ARRAY
			function GET_rsvp_list($eventID, $ri=''){
				global $eventon_rs;

				$event_pmv = get_post_custom($eventID);
				$ri_count_active = $this->is_ri_count_active($event_pmv);
				$guestsAR = array('y'=>array(),'m'=>array(),'n'=>array());

				$metaKey = (!empty($eventon_rs->evors_opt['evors_orderby']) && $eventon_rs->evors_opt['evors_orderby']=='fn')? 'first_name':'last_name';

				$wp_args = array(
					'posts_per_page'=>-1,
					'post_type' => 'evo-rsvp',
					'meta_query' => apply_filters('evors_guest_list_metaquery', array(
						array('key' => 'e_id','value' => $eventID)
					)),
					'meta_key'=>$metaKey,
					'orderby'=>array('meta_value'=>'DESC','title'=>'ASC')
				);

				//print_r($wp_args);
	
				$guests = new WP_Query( $wp_args );

				if($guests->have_posts()):
					while( $guests->have_posts() ): $guests->the_post();
						$_id = get_the_ID();
						$pmv = get_post_meta($_id);
						$_status = (!empty($pmv['status']))? $pmv['status'][0]:'check-in';
						$rsvp = (!empty($pmv['rsvp']))? $pmv['rsvp'][0]:false;
						$e_id = (!empty($pmv['e_id']))? $pmv['e_id'][0]:false;

						if(!$rsvp) continue;
						if(!$e_id || $e_id!=$eventID) continue;

						if(							
							$ri == 'all' || 
							(empty($pmv['repeat_interval']) && $ri == '0') ||
							(!empty($pmv['repeat_interval']) && $pmv['repeat_interval'][0]==$ri)

						){
							$lastName = isset($pmv['last_name'])? $pmv['last_name'][0]:'';
							$firstName = isset($pmv['first_name'])? $pmv['first_name'][0]:'';
							$guestsAR[$rsvp][$_id] = array(
								'fname'=> $firstName,
								'lname'=> $lastName,
								'name'=> $lastName.(!empty($lastName)?', ':'').$firstName,
								'email'=> $pmv['email'][0],
								'phone'=> (!empty($pmv['phone'])?$pmv['phone'][0]:''),
								'status'=>$_status,
								'count'=>$pmv['count'][0],						
								'userid'=>  (!empty($pmv['uid'])? $pmv['uid'][0]: (!empty($pmv['userid'])? $pmv['userid'][0]: 'na')),
								'names'=>  (!empty($pmv['names'])? unserialize($pmv['names'][0]) :'na'),
								'rsvpid'=>  $_id
							);
						}

					endwhile;
				endif;
				wp_reset_postdata();
				return array('y'=>$guestsAR['y'], 'm'=>$guestsAR['m'], 'n'=>$guestsAR['n']);
			}

		// GET repeat interval RSVP count
			function get_ri_count($rsvp, $ri=0, $event_pmv=''){
				$ri_count = (!empty($event_pmv) && !empty($event_pmv['ri_count_rs']))? 
					unserialize($event_pmv['ri_count_rs'][0]):false;
				if(!$ri_count) return 0;
				return !empty($ri_count[$ri][$rsvp])? $ri_count[$ri][$rsvp]:0;
			}
		// GET rsvp (remaining) count RI or not
			function get_rsvp_count($event_pmv, $rsvp, $ri=0){
				if($this->is_ri_count_active($event_pmv)){
					return $this->get_ri_count($rsvp, $ri, $event_pmv);
				}else{
					global $eventon_rs;
					return !empty($event_pmv['_rsvp_'.$eventon_rs->rsvp_array[$rsvp]])? 
						$event_pmv['_rsvp_'.$eventon_rs->rsvp_array[$rsvp]][0]:0;
				}				
			}
			function get_ri_remaining_count($rsvp, $ri=0, $ricount, $eventpmv){
				$openCount = (int)$this->get_ri_count($rsvp, $ri, $eventpmv);
				return $ricount - $openCount;
			}
			// GET rsvp count for given rsvp type
			function get_event_rsvp_count($event_id, $rsvp_type, $event_pmv=''){
				$event_pmv = (!empty($event_pmv))? $event_pmv: get_post_meta($event_id);
				return (!empty($event_pmv['_rsvp_'.$rsvp_type]))? $event_pmv['_rsvp_'.$rsvp_type][0]:'0';
			}


		// UPDATE repeat interval RSVP count
		// rsvp_status = y,n
			function update_ri_count($event_id, $ri, $rsvp_status, $count){
				$ri_count = get_post_meta($event_id, 'ri_count_rs', true);
				$ri_count = !empty($ri_count)? $ri_count: array();
				$ri_count[$ri][$rsvp_status] = $count;
				update_post_meta($event_id, 'ri_count_rs', $ri_count);
			}
			function adjust_ri_count($event_id, $ri, $rsvp_status, $adjust='reduce'){
				$ri_count = get_post_meta($event_id, 'ri_count_rs', true);
				$ri_count = !empty($ri_count)? $ri_count: array();				

				// if data already exist 
				if(sizeof($ri_count)>0 && !empty($ri_count[$ri][$rsvp_status])){
					$old_count = (int)$ri_count[$ri][$rsvp_status];
					$new_count = $adjust=='reduce'? $old_count-1: $old_count+1;
					$ri_count[$ri][$rsvp_status] = $new_count;
				}else{// 
					$new_count = $adjust=='reduce'? 0: 1;
					$ri_count[$ri][$rsvp_status] = $count;
				}
				
				update_post_meta($event_id, 'ri_count_rs', $ri_count);
			}

		// Update a RSVP post
			public function _form_update_rsvp($post){
				global $eventon_rs;
				$repeat_interval = !empty($post['repeat_interval'])? $post['repeat_interval']:0;

				// update each fields
				foreach($post as $field=>$value){
					if(in_array($field, array( 'action','evors_nonce','_wp_http_referer','formtype','lang'))) continue;

					if($field=='names' && !empty($_POST['names'])){
						$value = array_unique(array_filter($_POST['names']));
					}
					update_post_meta($post['rsvpid'], $field, $value);
				}
				// update usermeta
				if(isset($post['uid']) && isset($post['e_id'])){
					$this->save_user_rsvp_status($post['uid'], $post['e_id'],$repeat_interval , $post['rsvp']);
				}

				// send confirmation email
				// if status is not same as original status then send emails
				if(!empty($post['original_status']) && $post['original_status'] != $post['rsvp']){
					$post['rsvp_id'] = $post['rsvpid'];
					$post['emailtype'] = 'update';
					$eventon_rs->email->send_email($post);
					$eventon_rs->email->send_email($post, 'notification');
				}				

				// pluggable action
				do_action('evors_rsvp_updated',$post);

				// sync rsvp count after update
				$this->sync_rsvp_count($post['e_id']);
				return true;
			}

		// find a RSVP
			public function find_rsvp($rsvpid, $fname, $eid){
				$rsvp = get_post($rsvpid);
				if($rsvp){
					$rsvp_meta = get_post_custom($rsvpid);

					// check if first name and event id
					return ($fname == $rsvp_meta['first_name'][0] && $eid == $rsvp_meta['e_id'][0])? array('rsvp'=>$rsvp_meta['rsvp'][0], 'count'=>$rsvp_meta['count'][0]): false;
				}else{ return false;}
			}
		
		// return total RSVP count for an event
			function total_rsvp_counts($ePMV){
				$rsvp_count = array('y'=>0,'n'=>0,'m'=>0);

				if(!empty($ePMV['_rsvp_yes'])) $rsvp_count['y']= $ePMV['_rsvp_yes'][0];
				if(!empty($ePMV['_rsvp_no'])) $rsvp_count['n']= $ePMV['_rsvp_no'][0];
				if(!empty($ePMV['_rsvp_maybe'])) $rsvp_count['m']= $ePMV['_rsvp_maybe'][0];

				return $rsvp_count;
			}
		// get RSVP post
			function get_rsvp_post($eid, $ri=0, $args=''){

				$args = empty($args)? array() : $args;
				$args = array_merge(array(
					'uid'=>'', 'email'=>'','rsvpid'=>''
					), $args
				);

				$ri = (empty($ri)? '': $ri);
				
				$metaQuery = array();
				if(!empty($args['rsvpid'])){
					$rsvp = new WP_Query(array(
						'p' => $args['rsvpid']
					));
				}else{
					if(!empty($args['uid']))
						$metaQuery[] = array('key' => 'userid','value'=> $args['uid']);

					if(!empty($args['email']))
						$metaQuery[] = array('key' => 'email','value'=> $args['email']);

					$metaQuery[] = array('key' => 'e_id','value'=> $eid);

					if( $ri != 0 && !empty($ri) ){
						$metaQuery[] = array('key' => 'repeat_interval','value'=> $ri);
					}

					$query = array(
						'post_type'=>'evo-rsvp',
						'meta_query' => $metaQuery,
					);
					$rsvp = new WP_Query($query);
				}
				
				$rsvpid = false;
				if($rsvp->have_posts()){
					while($rsvp->have_posts()): $rsvp->the_post();
						$rsvpid = $rsvp->post->ID;
					endwhile;
					wp_reset_postdata();
				}
				return $rsvpid;
			}
		// SYNC rsvp status for an event
			function sync_rsvp_count($event_id){
				global $eventon_rs;

				// check if repeat interval RSVP active
				$event_pmv = get_post_custom($event_id);
				$is_ri_count_active = $this->is_ri_count_active($event_pmv);

				$ri_count = array();
				$rsvp_count = array('y'=>0,'n'=>0,'m'=>0);

				$evoRSVP = new WP_Query( array(
					'posts_per_page'=>-1,
					'post_type' => 'evo-rsvp',
					'meta_query' => array(
						array('key' => 'e_id','value' => $event_id)
					)
				));
				if($evoRSVP->found_posts>0){
					while($evoRSVP->have_posts()): $evoRSVP->the_post();
						$rsvpPMV = get_post_custom($evoRSVP->post->ID);

						$rsvp = !empty($rsvpPMV['rsvp'])? $rsvpPMV['rsvp'][0]:false;
						$count = !empty($rsvpPMV['count'])? (int)$rsvpPMV['count'][0]:0;
						$ri = !empty($rsvpPMV['repeat_interval'])? $rsvpPMV['repeat_interval'][0]:0;

						$rsvp_count[$rsvp] = !empty($rsvp_count[$rsvp])? $rsvp_count[$rsvp]+$count: $count;

						if($is_ri_count_active){
							$ri_count[$ri][$rsvp] = !empty($ri_count[$ri][$rsvp])? $ri_count[$ri][$rsvp]+$count: $count;
						}

					endwhile;

					if(!empty($ri_count))	update_post_meta($event_id,'ri_count_rs', $ri_count );

				}

				// update the RSVP counts
				update_post_meta($event_id,'_rsvp_yes', $rsvp_count['y'] );
				update_post_meta($event_id,'_rsvp_no', $rsvp_count['n'] );
				update_post_meta($event_id,'_rsvp_maybe', $rsvp_count['m'] );

				wp_reset_postdata();

				// sync user data to rsvp info
				//$this->sync_users_to_rsvps($event_id);				

				return $rsvp_count;

				/*
					// run through each rsvp status value
					foreach( $eventon_rs->frontend->rsvp_array as $rsvp=>$rsvpf){
						$ids = array();
						
						$_status = new WP_Query( array(
							'posts_per_page'=>-1,
							'post_type' => 'evo-rsvp',
							'meta_query' => array(
								'relation' => 'AND',
								array('key' => 'rsvp','value' => $rsvp,),
								array('key' => 'e_id','value' => $event_id,)
							)
						));

						if($_status->found_posts>0):
							while($_status->have_posts()): $_status->the_post();
								$rsvpPMV = get_post_custom($_status->post->ID);

								$ids[]= get_the_ID();
							endwhile;
							$idList = implode(",", $ids);		
							$count = $wpdb->get_var($wpdb->prepare("
								SELECT sum(meta_value)
								FROM $wpdb->postmeta
								WHERE meta_key = %s
								AND post_id in (".$idList.")", 'count'
								));
							$count = (!empty($count))?$count :0;
						else:
							$count =  0;
						endif;					
						update_post_meta($event_id,'_rsvp_'.$rsvpf, $count );
						wp_reset_postdata();
					}
				*/				
			}

	// EMAIL related
		// ** deprecating
		function get_proper_times($eventpmv, $ri=0){
			$datetime = new evo_datetime();
			$correct_unix = $datetime->get_correct_event_repeat_time($eventpmv, $ri);
			$strings = $datetime->get_formatted_smart_time($correct_unix['start'], $correct_unix['end'],$eventpmv);
			
			return $strings;
		}
		function get_correct_event_time_data($epmv, $ri=0){
			$datetime = new evo_datetime();
			$correct_unix = $datetime->get_correct_event_repeat_time($epmv, $ri);
			$strings = $datetime->get_formatted_smart_time($correct_unix['start'], $correct_unix['end'],$epmv);
			
			return array(
				'readable'=>$strings,
				'start_unix'=>$correct_unix['start'],
				'end_unix'=>$correct_unix['end']
			);
		}
		public function _event_date($pmv, $start_unix, $end_unix){
			global $eventon;
			$evcal_lang_allday = eventon_get_custom_language( '','evcal_lang_allday', 'All Day');
			$date_array = $eventon->evo_generator->generate_time_('','', $pmv, $evcal_lang_allday,'','',$start_unix,$end_unix);	
			return $date_array;
		}
		public function edit_post_link($id){
			return get_admin_url().'post.php?post='.$id.'&action=edit';	
		}


	// AJAX Functions
	// CSV of attendees list
		function generate_csv_attendees_list($event_id){
			$e_id = $event_id;

			header('Content-Encoding: UTF-8');
			header('Content-type: text/csv; charset=UTF-8');
			header("Content-Disposition: attachment; filename=RSVP_attendees_".date("d-m-y").".csv");
			header("Pragma: no-cache");
			header("Expires: 0");
			echo "\xEF\xBB\xBF"; // UTF-8 BOM
			//$fp = fopen('file.csv', 'w');
			
			// additional field names
			$optRS = EVORS()->evors_opt;

			$csv_headers = apply_filters('evors_attendees_csv', array(
				'last_name'=>'Last Name',
				'first_name'=>'First Name',
				'email'=>'Email Address',
				'phone'=>'Phone',
				'updated'=>'Email Updates',
				'rsvp'=>'RSVP',
				'status'=>'Status',
				'count'=>'Count',
				'event_time'=>'Event Time',
				'names'=>'Other Attendees'
			));

			for($x=1; $x<= EVORS()->frontend->addFields; $x++){
				if(!evo_settings_val('evors_addf'.$x, $optRS) || empty($optRS['evors_addf'.$x.'_1']) ) continue;
				$csv_headers['evors_addf'.$x.'_1'] = '"'.$optRS['evors_addf'.$x.'_1'].'"';
			}

			echo implode(',', $csv_headers)."\n";

			$entries = new WP_Query(array(
				'posts_per_page'=>-1,
				'post_type' => 'evo-rsvp',
				'meta_query' => array(
					array('key' => 'e_id','value' => $e_id,'compare' => '=',	)
				)
			));

			$datetime = new evo_datetime();

			if($entries->have_posts()):
				$array = EVORS()->rsvp_array;
				while($entries->have_posts()): $entries->the_post();
					//initials
						$__id = get_the_ID();
						$pmv = get_post_meta($__id);
						$RI = !empty($pmv['repeat_interval'])? (int)$pmv['repeat_interval'][0]:0;

					// event time string
						$event_times = $datetime->get_correct_event_time($event_id, $RI);
						$event_time = $datetime->get_formatted_smart_time($event_times['start'], $event_times['end'], '', $event_id);

					foreach($csv_headers as $field=>$header){
						$switch_run = false;
						switch($field){
							case 'rsvp':
								echo EVORS()->frontend->get_rsvp_status($pmv['rsvp'][0]) .",";
								$switch_run = true;
							break;
							case 'status':
								$_checkinST = (!empty($pmv['status']) && $pmv['status'][0]=='checked')?
									'checked':'check-in';
								$checkin_status = EVORS()->frontend->get_checkin_status($_checkinST);
								echo $checkin_status .",";
								$switch_run = true;
							break;
							case 'event_time':
								echo $event_time.",";
								$switch_run = true;
							break;
							case 'names':
								if(!empty($pmv['names'])){
									$names = unserialize($pmv['names'][0]);
									echo '"' . implode(", ", $names) . '"';
								}
								$switch_run = true;
								
							break;
						}

						do_action('evors_attendees_csv_field_'.$field);

						if($switch_run) continue;

						if(isset($pmv[$field])){
							$cover = in_array($field, array('last_name','first_name','email','phone')) ?'':'"';
							echo $cover . $pmv[$field][0] . $cover;
						}else{
							echo '';
						}
						echo ",";
					}

					echo "\n";

				endwhile;
			endif;
			wp_reset_postdata();
		}

	// Supporting
		// Can we show the RSVP box still
			function show_rsvp_still($endunix, $ePMV){
				global $eventon_rs;
				
				$current_time = current_time('timestamp');

				// check if RSVP is enabled in event
				$rsvpEnabled = $this->is_rsvp_active($ePMV);				

				if(!$rsvpEnabled) return false;

				return ($current_time <= $endunix || ( !empty($eventon_rs->evors_opt['evors_allow_past']) && $eventon_rs->evors_opt['evors_allow_past']=='yes')  )? true: false;
			}

		// check if users can still RSVP - check past event and close before X minutes
			function can_still_rsvp($endunix, $ePMV){
				global $eventon_rs;

				// check if event is past or allowed to rsvp to past evnets
				$current_time = current_time('timestamp');

				if($current_time <= $endunix || ( !empty($eventon_rs->evors_opt['evors_allow_past']) && $eventon_rs->evors_opt['evors_allow_past']=='yes') ){}else{return false;}

				return ($this->close_rsvp_beforex($endunix, $ePMV))? false:true;
			}

		
		// check if rsvp activated for an event
			function is_rsvp_active($ePMV){
				return (!empty($ePMV['evors_rsvp']) && $ePMV['evors_rsvp'][0]=='yes')? true: false;
			}
		// check if rsvping is closed x minuted before
			function close_rsvp_beforex($endunix, $ePMV){
				global $eventon_rs;
				
				$current_time = current_time('timestamp');
				// check if close RSVP X minuted before is set
				$closeRSVP = !empty($ePMV['evors_close_time'])? (int)$ePMV['evors_close_time'][0]*60: false;

				return ($closeRSVP &&  ( ($closeRSVP+$current_time) >= $endunix ) )? true: false;
			}
		// check if change RSVP option can be shown
			function show_change_rsvp($optRS,$currentUserRSVP, $is_user_logged_in ){
				
				// if change rsvp option is hidden
				if( !empty($optRS['evors_hide_change']) && $optRS['evors_hide_change']=='yes') return false;
				
				if(empty($optRS['evors_onlylog_chg']) || 
					(!empty($optRS['evors_onlylog_chg']) && $optRS['evors_onlylog_chg']=='no') ||
					(!empty($optRS['evors_onlylog_chg']) && $optRS['evors_onlylog_chg']=='yes' && $is_user_logged_in && 
						(	empty($optRS['evors_change_hidden']) || 
							(!empty($optRS['evors_change_hidden']) && $optRS['evors_change_hidden']=='no') ||
							(!empty($optRS['evors_change_hidden']) && $optRS['evors_change_hidden']=='yes' && $currentUserRSVP)
						)
					)
				){return true;}else{return false;}
			}
		// check if only RSVPed guests can see guest list
			function can_show_guestList($ePMV, $currentUserRSVP){
				return (
					(!empty($ePMV['evors_whoscoming_after']) && $ePMV['evors_whoscoming_after'][0]=='yes' && $currentUserRSVP) ||
					(empty($ePMV['currentUserRSVP'])) ||
					(!empty($ePMV['currentUserRSVP']) && $ePMV['currentUserRSVP'][0]=='no')
				)? true: false;

			}
			function can_show_notcomingList($ePMV, $currentUserRSVP){
				return (
					(!empty($ePMV['_evors_whosnotcoming_after']) && $ePMV['_evors_whosnotcoming_after'][0]=='yes' && $currentUserRSVP) ||
					(empty($ePMV['currentUserRSVP'])) ||
					(!empty($ePMV['currentUserRSVP']) && $ePMV['currentUserRSVP'][0]=='no')
				)? true: false;

			}
		// CHECK FUNCTIONs remaining RSVP
			function show_spots_remaining($event_pmv){
				return (!empty($event_pmv['evors_capacity_count'])
					&& !empty($event_pmv['evors_capacity_show'])
					&& $event_pmv['evors_capacity_show'][0] == 'yes'
					&& !empty($event_pmv['evors_capacity']) && $event_pmv['evors_capacity'][0]=='yes'
				)? true:false;
			}
			function show_whoscoming($event_pmv){
				return evo_check_yn($event_pmv, 'evors_show_whos_coming');
			}
			function show_whosnotcoming($event_pmv){
				return evo_check_yn($event_pmv, '_evors_show_whos_notcoming');
			}
			// check if repeat interval rsvp is activate
			function is_ri_count_active($event_pmv){
				 return (
					!empty($event_pmv['evors_capacity']) && $event_pmv['evors_capacity'][0]=='yes'
					&& !empty($event_pmv['_manage_repeat_cap_rs']) && $event_pmv['_manage_repeat_cap_rs'][0]=='yes'
					&& !empty($event_pmv['evcal_repeat']) && $event_pmv['evcal_repeat'][0] == 'yes' 
					&& !empty($event_pmv['ri_capacity_rs']) 
				)? true:false;
			}
		// how to show the RSVP form
			function inCard_form($optRS, $pmv){
				$global_incard = evo_settings_check_yn($optRS, 'evors_incard_form');
				$event_incard = evo_check_yn($pmv, '_evors_incard_form');

				if($global_incard ) return true;

				if($event_incard) return true;

				return false;
			}
	
	// get IP address of user
		function get_client_ip() {
		    $ipaddress = '';
		    if ($_SERVER['HTTP_CLIENT_IP'])
		        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		    else if($_SERVER['HTTP_X_FORWARDED_FOR'])
		        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		    else if($_SERVER['HTTP_X_FORWARDED'])
		        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		    else if($_SERVER['HTTP_FORWARDED_FOR'])
		        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		    else if($_SERVER['HTTP_FORWARDED'])
		        $ipaddress = $_SERVER['HTTP_FORWARDED'];
		    else if($_SERVER['REMOTE_ADDR'])
		        $ipaddress = $_SERVER['REMOTE_ADDR'];
		    else
		        $ipaddress = false;
		    return $ipaddress;
		}
		function get_current_userid(){
			if(is_user_logged_in()){
				global $current_user;
				wp_get_current_user();
				return $current_user->ID;
			}else{
				return false;
			}
		}
		// check if per rsvp count max set and return the max value
		function is_per_rsvp_max_set($event_pmv){
			return (!empty($event_pmv['evors_max_active']) && $event_pmv['evors_max_active'][0]=='yes' && !empty($event_pmv['evors_max_count'])) ? $event_pmv['evors_max_count'][0]: 'na';
		}
}