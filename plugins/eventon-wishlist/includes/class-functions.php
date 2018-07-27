<?php
/**
 * Functions
 */

class evowi_fnc{	

	public	$evo_wishlist;

	public function __construct(){
		$this->evo_wishlist = get_option('_evo_wishlist');
	}

	// get current user wishlisted events
	function user_wishlist(){

		$userid = get_current_user_id();

		if(!$userid && $userid!= 0) return false;

		$wishlist_events = get_option('_evo_wishlist');

		if(empty($wishlist_events[$userid])) return false;

		return $wishlist_events[$userid];
	}

	function get_wishlist_count($event_id, $repeat_interval=0, $options=''){
		
		$wishlist_events = !empty($options)? $options: $this->evo_wishlist;
		$count = 0;

		if(!$wishlist_events || sizeof($wishlist_events) == 0) return $count;

		$event_str = $event_id .'-'. $repeat_interval;

		
		foreach($wishlist_events as $userid=>$data){
			if(in_array($event_str, $data)){
				$count++;
			}
		}
		return $count;
	}


	function get_user_wishlist_events_array($user_wishlist){
		$events = array();
		foreach($user_wishlist as $key=>$data){
			$data_ = explode('-', $data);
			$events[] = $data_[0];
		}
		return $events;
	}

	function is_event_wishlisted($event_id, $repeat_interval=0, $user_wishlist='' ){

		$user_wishlist = (empty($user_wishlist)? $this->user_wishlist(): $user_wishlist);

		$userid = get_current_user_id();

		if($user_wishlist && $userid && is_array($user_wishlist)){
			if(in_array( $event_id .'-'. $repeat_interval , $user_wishlist )) 
				return true;

			return false;
		}else{
			return false;
		}
	}	

	function change_user_wishlist($type='add', $event_id, $repeat_interval=0, $userid=''){
		$wishlist_events = array();
		$wishlist_events = get_option('_evo_wishlist');

		$userid = !empty($userid)? $userid: get_current_user_id();

		if(!$userid && $userid!= 0) return false;

		if($type=='add'){
			$wishlist_events[$userid][] = $event_id .'-'. $repeat_interval;
		}else{ // remove

			if(!empty($wishlist_events[$userid])){
				$key = array_search( $event_id .'-'. $repeat_interval, $wishlist_events[$userid] ); 

				if($key !== false){
					unset($wishlist_events[$userid][$key]);
				}
			}
		}


		update_option('_evo_wishlist', $wishlist_events);

		return get_option('_evo_wishlist');
	}

}