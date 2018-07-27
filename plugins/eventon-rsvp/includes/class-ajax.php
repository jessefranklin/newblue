<?php
/**
* RSVP Events Ajax Handlers
*
* Handles AJAX requests via wp_ajax hook (both admin and front-end events)
*
* @author 		AJDE
* @category 	Core
* @package 	EventON-RS/Functions/AJAX
* @version     2.3.3
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class evorsvp_ajax{
  public function __construct(){
    $ajax_events = array(
    'the_ajax_evors'=>'evoRS_save_rsvp',
    //'the_ajax_evors_fnd'=>'evoRS_find_rsvp',
    'the_ajax_evors_a7'=>'rsvp_from_eventtop',
    //'the_ajax_evors_a8'=>'find_rsvp_byuser',
    'the_ajax_evors_a10'=>'update_rsvp_manager',
    'evors_get_rsvp_form'=>'evors_get_rsvp_form',
    'evors_find_rsvp_form'=>'evors_find_rsvp_form',
    'the_ajax_evors_f3'=>'generate_attendee_csv',
    'the_ajax_evors_f4'=>'checkin_guests',
    );
    foreach ( $ajax_events as $ajax_event => $class ) {
      add_action( 'wp_ajax_'.  $ajax_event, array( $this, $class ) );
      add_action( 'wp_ajax_nopriv_'.  $ajax_event, array( $this, $class ) );
    }
  }

  // checkin guests
  function checkin_guests(){
    global $eventon_rs;

    $nonceDisabled = evo_settings_check_yn($eventon_rs->frontend->optRS, 'evors_nonce_disable');

    // verify nonce check
    if(isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], AJDE_EVCAL_BASENAME ) && !$nonceDisabled){
      echo json_encode(array('message','Invalid Nonce'));
      exit;
    }

    $rsvp_id = $_POST['rsvp_id'];
    $status = $_POST['status'];

    update_post_meta($rsvp_id, 'status',$status);
    $return_content = array(
    'status'=>'0',
    'new_status_lang'=>$eventon_rs->frontend->get_checkin_status($status),
    );

    echo json_encode($return_content);
    exit;
  }

  // Download CSV of attendance
  function generate_attendee_csv(){
    global $eventon_rs;

    $nonceDisabled = evo_settings_check_yn($eventon_rs->frontend->optRS, 'evors_nonce_disable');

    // verify nonce check
    if(( !$nonceDisabled && isset($_REQUEST['nonce']) && !wp_verify_nonce( $_REQUEST['nonce'], AJDE_EVCAL_BASENAME ) )
    ){
      echo json_encode(array('message','Invalid Nonce!'));exit;
    }
    if( !empty($_REQUEST['e_id'])){
      $eid = sanitize_text_field($_REQUEST['e_id']);
      $eventon_rs->functions->generate_csv_attendees_list($e_id);
    }else{
      echo json_encode(array('message','Event ID not provided!'));exit;
    }
  }

  // NEW RSVP from EVENTTOP
  function rsvp_from_eventtop(){
    $status = 0;
    $message = $content = '';

    global $eventon_rs;
    $front = $eventon_rs->frontend;

    // sanitize each posted values
    foreach($_POST as $key=>$val){
      $post[$key]= sanitize_text_field(urldecode($val));
    }

    // pull email and name from user data
    if(!empty($post['uid'])){
      $user_info = get_userdata($post['uid']);
      if(!empty($user_info->user_email))
      $post['email']= $user_info->user_email;
      if(!empty($user_info->first_name))
      $post['first_name']= $user_info->first_name;
      if(!empty($user_info->last_name))
      $post['last_name']= $user_info->last_name;

      // other default values
      $post['count']='1';
    }

    // check if already rsvped
    $already_rsvped = $front->functions->has_user_rsvped($post);
    if(!$already_rsvped){ // if user have not already RSVPed save the RSVP

      $save= $front->_form_save_rsvp($post);
      $message = ($save==7)?
      $front->get_form_message('err7', $post['lang']):
      $front->get_form_message('succ', $post['lang']);

      $content = $front->get_eventtop_data('',$post['repeat_interval'], $post['e_id']);

    }else{// already rsvped
      $message = $front->get_form_message('err8', $post['lang']);
      $status = 0;
    }

    $return_content = array(
    'message'=> $message,
    'status'=>(($status==7)?7:0),
    'content'=>$content
    );

    echo json_encode($return_content);
    exit;
  }

  // GET RSVP form
  function evors_get_rsvp_form(){
    global $eventon_rs;

    $args = array();
    foreach(array(
    'e_id',
    'repeat_interval',
    'uid',
    'cap',
    'precap',
    'rsvp',
    'formtype',
    'rsvpid',
    'lang',
    'incard'
    ) as $key){
      $args[$key] = (!empty($_POST[$key])? sanitize_text_field($_POST[$key]): '');
    }

    $content = $eventon_rs->rsvpform->get_form($args);
    echo json_encode(array(
    'status'=>'good',
    'content'=>$content
    )); exit;
  }

  // SAVE a RSVP from the rsvp form
  function evoRS_save_rsvp(){
    global $eventon_rs;

    $nonce = $_POST['evors_nonce'];
    $status = 0;
    $message = $save = $rsvpID = $e_id = $option_selection = '';

    // if nonce is disabled
    $nonceDisabled = (!empty($eventon_rs->frontend->optRS['evors_nonce_disable']) &&
    $eventon_rs->frontend->optRS['evors_nonce_disable'] =='yes')? true: false;

    // set lang
    if(!empty($_POST['lang']))	EVORS()->l = $_POST['lang'];
    if(isset($_POST['lang'])) EVORS()->frontend->currentlang = $_POST['lang'];

    // verify nonce check
    if(!wp_verify_nonce( $nonce, AJDE_EVCAL_BASENAME ) && !$nonceDisabled){
      $status = 1;	$message ='Invalid Nonce';
    }else{

      $front = $eventon_rs->frontend;

      // sanitize each posted values
      foreach($_POST as $key=>$val){
        if(empty($val)) continue;
        $post[$key]= (!is_array($val))? sanitize_text_field($val): $val;
      }

      // after process
      $e_id = !empty($post['e_id'])? $post['e_id']:false;
      $repeat_interval = !empty($post['repeat_interval'])? $post['repeat_interval']:0;
      $epmv = get_post_custom($e_id);

      // if UPDATING
      if(!empty($post['rsvpid'])){
        $rsvpID = $post['rsvpid'];
        $save= $front->functions->_form_update_rsvp($post);
        $status = 0;
      }else{
        // check if already rsvped
        $already_rsvped = $front->functions->has_user_rsvped($post);
        if(!$already_rsvped){
          $save= $front->_form_save_rsvp($post); // pass the rsvp id for change rsvp status after submit
          $rsvpID = $save;
          $status = ($save==7)? 7: 0;
        }else{
          $status = 8;
          $rsvpID = $already_rsvped;
        }
      }
      $message = $save;
    }

    // get success message HTML
    $otherdata = array('guestlist'=>'','newcount'=>'0', 'remaining'=>'0','minhap'=>'0');
    if($status==0){

      $message = $eventon_rs->rsvpform->form_message(
      $rsvpID, (!empty($_POST['formtype'])? $_POST['formtype']:'submit')
      );

      // guest list information
      $otherParts = $eventon_rs->rsvpform->get_form_guestlist($e_id, $post);
      if($otherParts){
        $otherdata['guestlist'] = $otherParts['guestlist'];
        $otherdata['newcount'] = $otherParts['newcount'];
      }

      // remaining
      $otherdata['remaining'] = $eventon_rs->functions->remaining_rsvp($epmv, $repeat_interval, $e_id);

      // rsvp status options selection new HTML
      $currentUserID = 	$eventon_rs->frontend->functions->current_user_id();
      $currentUserID = $currentUserID!=0? $currentUserID:'';

      $option_selection = "<p class='nobrbr loggedinuser' data-uid='{$currentUserID}' data-eid='{$e_id}' data-ri='{$repeat_interval}'>";
      $option_selection .= evo_lang('You have already RSVP-ed', EVORS()->frontend->currentlang).": <em class='evors_rsvped_status_user'>".$eventon_rs->frontend->get_rsvp_status($post['rsvp'])."</em> ";
      $option_selection .= "</p>";

    }

    // data content
    $content = $eventon_rs->frontend->get_eventcard_rsvp_html($e_id, $repeat_interval);
    $eventtop_content = $eventon_rs->frontend->get_eventtop_data('', $repeat_interval, $e_id);
    $eventtop_content_your = $eventon_rs->frontend->get_eventtop_your_rsvp('', $repeat_interval, $e_id);
    $new_rsvp_text = (!empty($post['rsvp'])? 	$eventon_rs->frontend->get_rsvp_status($post['rsvp']):'');

    $return_content = array(
    // 'post'=>$_POST,
    'message'=> $message,
    'status'=>$status,
    'rsvpid'=> $rsvpID,
    'guestlist'=>$otherdata['guestlist'],
    'newcount'=>$otherdata['newcount'],
    'e_id'=> $e_id,
    'ri'=>$repeat_interval,
    'lang'=> EVORS()->frontend->currentlang,
    'option_selection'=>$option_selection,
    'data_content'=>$content,
    'data_content_eventtop'=>$eventtop_content,
    'data_content_eventtop_your'=>$eventtop_content_your,
    'new_rsvp_text'=>$new_rsvp_text
    );

    echo json_encode($return_content);
    exit;
  }

  // FIND RSVP in order to change
  function evors_find_rsvp_form(){
    global $eventon_rs;

    $rsvpid = $eventon_rs->frontend->functions->get_rsvp_post(
    $_POST['e_id'],
    (!empty($_POST['repeat_interval'])?$_POST['repeat_interval']:''),
    array(
    'email'=>$_POST['email']
    )
    );

    //echo $rsvpid.'tt';

    if($rsvpid){
      $args = array();
      foreach(array(
      'e_id',
      'repeat_interval',
      'cap',
      'precap',
      'email',
      'formtype',
      'incard'
      ) as $key){
        $args[$key] = (!empty($_POST[$key])? $_POST[$key]: '');
      }

      $args['rsvpid'] = $rsvpid;

      $content = $eventon_rs->rsvpform->get_form($args);
      echo json_encode(array(
      'status'=>'good',
      'content'=>$content
      )); exit;
    }else{
      echo json_encode(array(
      'status'=>'bad',
      )); exit;
    }
  }
  /*function evoRS_find_rsvp(){
  global $eventon_rs;
  $front = $eventon_rs->frontend;

  $rsvp = get_post($_POST['rsvpid']);
  $post_type = get_post_type($_POST['rsvpid']);

  if($rsvp!='' && $post_type =='evo-rsvp'){
  $rsvp_meta = get_post_meta($_POST['rsvpid']);
  }else{
  $rsvp_meta = false;
  }
  // send out results
  echo json_encode(array(
  'status'=>(($rsvp!='')? '0':'1'),
  'content'=> $rsvp_meta,
  ));
  exit;
  }
  */
  /*function find_rsvp_byuser(){
  $rsvp = new WP_Query(array(
  'post_type'=>'evo-rsvp',
  'meta_query' => array(
  array(
  'key'     => 'userid',
  'value'   => $_POST['uid'],
  ),
  array(
  'key'     => 'e_id',
  'value'   => $_POST['eid'],
  ),array(
  'key'     => 'repeat_interval',
  'value'   => $_POST['ri'],
  ),
  ),
  ));
  $rsvpid = false;
  if($rsvp->have_posts()){
  while($rsvp->have_posts()): $rsvp->the_post();
  $rsvpid = $rsvp->post->ID;
  endwhile;
  wp_reset_postdata();

  if(!empty($rsvpid)){
  $rsvp_meta = get_post_meta($rsvpid);
  $status = 0;
  }else{
  $status = 1;
  }
  }else{
  $status = 1;
  }

  // send out results
  echo json_encode(array(
  'status'=>$status,
  'rsvpid'=> ($rsvpid? $rsvpid:''),
  'content'=> (!empty($rsvp_meta)? $rsvp_meta: ''),
  ));
  exit;
  }
  */

  // update RSVP Manager
  function update_rsvp_manager(){
    global $eventon_rs;
    $manager = new evors_event_manager();
    $return_content = array(
    'content'=> $manager->get_user_events($_POST['uid'])
    );

    echo json_encode($return_content);
    exit;
  }

}
new evorsvp_ajax();
?>