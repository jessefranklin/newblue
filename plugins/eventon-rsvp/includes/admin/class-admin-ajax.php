<?php
/**
* Admin ajax functions
* @version 2.5.5
*/

class evors_admin_ajax{
  public function __construct(){
    $ajax_events = array(
    'the_ajax_evors_a1'=>'get_attendees_list',
    'the_ajax_evors_a2'=>'sync_rsvp_count',
    'the_ajax_evors_a5'=>'evoRS_admin_resend_confirmation',
    'the_ajax_evors_a6'=>'evoRS_admin_custom_confirmation',
    'the_ajax_evors_a9'=>'emailing_rsvp_admin',
    'evorsadmin_attendee_info'=>'get_attendee_info',

    );
    foreach ( $ajax_events as $ajax_event => $class ) {
      add_action( 'wp_ajax_'.  $ajax_event, array( $this, $class ) );
      add_action( 'wp_ajax_nopriv_'.  $ajax_event, array( $this, $class ) );
    }
  }


  // GET list of attendees for event
  function get_attendees_list(){
    global $eventon_rs;
    $status = 0;
    ob_start();

    $event_pmv = get_post_custom($_POST['e_id']);
    $ri_count_active = $eventon_rs->frontend->functions->is_ri_count_active($event_pmv);

    $ri = !empty($_POST['ri'])? $_POST['ri']: '0';
    //echo $ri=='0'?'t':'y';
    //$ri = ($ri == '0' && $ri_count_active)? 	'0':'all'; // repeat interval

    $__checking_status_text = $eventon_rs->frontend->get_trans_checkin_status();

    $RSVP_LIST = $eventon_rs->frontend->functions->GET_rsvp_list($_POST['e_id'], $ri);


    echo "<div class='evors_list'>";
    echo "<p class='header'>RSVP Status: YES</p>"; /***/
    if(!empty($RSVP_LIST['y']) && count($RSVP_LIST['y'])>0){
      foreach($RSVP_LIST['y'] as $_id=>$rsvp){
        echo $this->each_attendee_data_row($_id, $rsvp, $__checking_status_text);
      }
    }else{
      echo "<p>".__('No Attendees found.','eventon')."</p>";
    }

    echo "<p class='header'>RSVP Status: MAYBE</p>"; /***/
    if(!empty($RSVP_LIST['m']) && count($RSVP_LIST['m'])>0){
      foreach($RSVP_LIST['m'] as $_id=>$rsvp){
        echo $this->each_attendee_data_row($_id ,$rsvp, $__checking_status_text);
      }
    }else{
      echo "<p>".__('No Attendees found.','eventon')."</p>";
    }


    echo "<p class='header'>RSVP Status: NO</p>"; /***/
    if(!empty($RSVP_LIST['n']) && count($RSVP_LIST['n'])>0){
      foreach($RSVP_LIST['n'] as $_id=>$rsvp){
        echo "<div class='evors_rsvp_no_attendees'>";
        echo $this->each_attendee_data_row($_id ,$rsvp, $__checking_status_text);
        echo "</div>";
      }
    }else{
      echo "<p>".__('No Attendees found.','eventon')."</p>";
    }

    echo "</div>";

    $output = ob_get_clean();
    echo json_encode(array(
    'content'=> $output,
    'status'=>$status
    ));
    exit;
  }

  function each_attendee_data_row($_id, $rsvp, $text){
    ob_start();

    $phone = !empty($rsvp['phone'])? $rsvp['phone']:false;
    $status_var = (!empty($rsvp['status']))? $rsvp['status']:'check-in';
    $_status = $text[$status_var];
    ?>
    <p data-rsvpid='<?php echo $_id;?>'>
      <em class='evorsadmin_rsvp' title='<?php _e('Click for more information','eventon');?>'><?php echo '#'.$_id;?></em>
      <?php echo ' '. $rsvp['name'];?>
      <span data-id='<?php echo $_id;?>' data-status='<?php echo $status_var;?>' class='checkin <?php echo ($status_var=='checked')? 'checked':null;?>'>
        <?php echo $_status;?>
      </span>
      <span><?php echo $rsvp['count'];?></span>

      <i><?php echo $rsvp['email'].( $phone? ' PHONE:'.$phone:'');?></i>

      <?php
      // if RSVP have other names show those as well
      if($rsvp['names']!= 'na'):
      ?>
      <span class='other_names'>
        <?php  echo implode(', ', $rsvp['names']); ?>
      </span>
      <?php endif;?>
    </p>
    <?php
    return ob_get_clean();
  }

  function get_attendee_info(){
    global $eventon_rs;

    $optRS = $eventon_rs->evors_opt;
    $rsvpid = sanitize_text_field($_POST['rsvpid']);
    $rpmv = get_post_custom($rsvpid);
    $rsvpArray = array('y'=>'Yes','m'=>'Maybe','n'=>'No');

    ob_start();
    ?>
    <p class='name'><?php echo (!empty($rpmv['first_name'])? $rpmv['first_name'][0]:'').' '.(!empty($rpmv['last_name'])? $rpmv['last_name'][0]:'');?> (#<?php echo $rsvpid;?>)</p>
    <?php

    $array = array(
    'rsvp'=>__('RSVP Status','eventon'),
    'email'=>__('Email Address','eventon'),
    'phone'=>__('Phone Number','eventon'),
    'e_id'=>__('Event','eventon'),
    'repeat_interval'=>__('Event Date','eventon'),
    'count'=>__('Spaces Reserved','eventon'),
    'names'=>__('Additional Attendees','eventon'),
    );

    // additional fields
    for($x=1; $x<=$eventon_rs->frontend->addFields; $x++){
      if(evo_settings_val('evors_addf'.$x, $optRS) && !empty($optRS['evors_addf'.$x.'_1'])){
        if($optRS['evors_addf'.$x.'_2']=='html') continue;
        $array['evors_addf'.$x.'_1'] = $optRS['evors_addf'.$x.'_1'];
      }
    }

    foreach($array as $key=>$val){
      if(!empty($rpmv[$key])){
        $value = $rpmv[$key][0];

        switch($key){
          case 'rsvp':
          $value = $rsvpArray[$value];
          break;
          case 'e_id':
          $value = get_the_title($value);
          break;
          case 'repeat_interval':
          $event_pmv = get_post_custom($rpmv['e_id'][0]);
          $saved_ri = !empty($rpmv['repeat_interval'])? $rpmv['repeat_interval'][0]:0;
          $datetime = new evo_datetime();
          $repeatIntervals = (!empty($event_pmv['repeat_intervals'])? unserialize($event_pmv['repeat_intervals'][0]): false);
          $time = $datetime->get_correct_event_repeat_time( $event_pmv,$saved_ri);
          $value = $datetime->get_formatted_smart_time($time['start'], $time['end'], $event_pmv);
          break;
          case 'names':
          $value = implode(', ', unserialize($value) );
          break;
        }
        echo "<p><em>{$val}</em>".$value."</p>";
      }
    }

    // checking status
    $checkinSTATUS = $_checkinST = (!empty($rpmv['status']) && $rpmv['status'][0]=='checked')?'checked':'check-in';
    $status = $eventon_rs->frontend->get_checkin_status($checkinSTATUS);
    echo "<p class='status' data-rsvpid='{$rsvpid}' data-status='{$checkinSTATUS}'><em>".__('Checkin Status','eventon').'</em>'.$status.'</p>';

    // edit this attendee information
    echo "<p class='action'><a href='".admin_url('post.php?post='.$rsvpid.'&action=edit')."' class='evo_admin_btn'>".__('Edit Attendee Info','eventon')."</p>";

    $return_content = array(
    'status'=>'0',
    'content'=>ob_get_clean()
    );
    echo json_encode($return_content);
    exit;
  }

  // SYNC count
  function sync_rsvp_count(){
    $status = 0;
    $e_id = $_POST['e_id'];

    global $eventon_rs;
    $synced = $eventon_rs->frontend->functions->sync_rsvp_count($e_id);
    ob_start();
    ?>
    <p><b><?php echo $synced['y']; ?></b><span>YES</span></p>
    <p><b><?php echo $synced['m'];?></b><span>Maybe</span></p>
    <p><b><?php echo $synced['n'];?></b><span>No</span></p>
    <div class='clear'></div>
    <?php

    $return_content = array(
    'content'=> ob_get_clean(),
    'status'=>$status
    );

    echo json_encode($return_content);
    exit;
  }

  // resend confirmation
  function evoRS_admin_resend_confirmation(){
    global $eventon_rs;

    $rsvp_id = $_POST['rsvp_id'];
    $rsvp_pmv = get_post_custom($rsvp_id);

    $args['rsvp_id'] = $rsvp_id;
    $args['first_name'] = (!empty($rsvp_pmv['first_name']))?$rsvp_pmv['first_name'][0]:null;
    $args['last_name'] = (!empty($rsvp_pmv['last_name']))?$rsvp_pmv['last_name'][0]:null;
    $args['email'] = (!empty($rsvp_pmv['email']))?$rsvp_pmv['email'][0]:null;
    $args['e_id'] = (!empty($rsvp_pmv['e_id']))?$rsvp_pmv['e_id'][0]:null;
    $args['rsvp'] = (!empty($rsvp_pmv['rsvp']))?$rsvp_pmv['rsvp'][0]:null;
    $args['repeat_interval'] = (!empty($rsvp_pmv['repeat_interval']))?$rsvp_pmv['repeat_interval'][0]:0;

    $send_mail = $eventon_rs->email->send_email($args);

    $return_content = array(
    'status'=>'0',
    'send'=> ($send_mail?'sent':'no')
    );

    echo json_encode($return_content);
    exit;
  }

  // send custom emails
  function evoRS_admin_custom_confirmation(){
    global $eventon_rs;

    $rsvp_id = $_POST['rsvp_id'];
    $rsvp_pmv = get_post_custom($rsvp_id);

    $args['rsvp_id'] = $rsvp_id;
    $args['first_name'] = (!empty($rsvp_pmv['first_name']))?$rsvp_pmv['first_name'][0]:null;
    $args['last_name'] = (!empty($rsvp_pmv['last_name']))?$rsvp_pmv['last_name'][0]:null;
    $args['email'] = $_POST['email'];
    $args['e_id'] = (!empty($rsvp_pmv['e_id']))?$rsvp_pmv['e_id'][0]:null;
    $args['rsvp'] = (!empty($rsvp_pmv['rsvp']))?$rsvp_pmv['rsvp'][0]:null;
    $args['repeat_interval'] = (!empty($rsvp_pmv['repeat_interval']))?$rsvp_pmv['repeat_interval'][0]:0;

    $send_mail = $eventon_rs->email->send_email($args);

    $return_content = array(
    'status'=>'0',
    'result'=>$send_mail
    );

    echo json_encode($return_content);
    exit;
  }

  // emaling attendees
  function emailing_rsvp_admin(){
    global $eventon_rs, $eventon;

    $eid = sanitize_text_field($_POST['eid']);
    $type = sanitize_text_field($_POST['type']);
    $RI = !empty($_POST['repeat_interval'])? sanitize_text_field($_POST['repeat_interval']) :'all'; // repeat interval
    $EMAILED = $_message_addition = false;
    $emails = array();

    // email attendees list to someone
    if($type=='someone' || $type == 'someonenot' ){

      $attending = $type =='someone'? true: false;

      $emails = explode(',', str_replace(' ', '', htmlspecialchars_decode($_POST['emails'])));

      $guests = $eventon_rs->frontend->functions->GET_rsvp_list($eid, $RI);
      if(is_array($guests) && isset($guests['y']) && count($guests['y'])>0){
        ob_start();

        $datetime = new evo_datetime();
        $epmv = get_post_custom($eid);
        $eventdate = $datetime->get_correct_formatted_event_repeat_time($epmv, ($RI=='all'?'0':$RI));

        // All the supported fields
        $emailfields = apply_filters('evors_email_someone_fields', array(
        'lname'=>'Last Name',
        'fname'=>'First Name',
        'email'=>'Email',
        'count'=>'Count'
        ));


        echo "<p>". ($attending? 'Guests Attending to':'Guests Not-attending to' )."  ".get_the_title($eid)." on ".$eventdate['start']."</p>";

        echo "<table style='padding-top:15px; width:100%;text-align:left'><thead><tr>";
        foreach($emailfields as $fieldnames){
          echo "<th>".$fieldnames."</th>";
        }
        echo "</tr></thead><tbody>";

        $rsvp_type = $attending? 'y':'n';

        // Foreach guest name
        foreach($guests[$rsvp_type] as $guest){
          echo "<tr>";

          foreach($emailfields as $field=>$v){
            echo "<td>". (!empty($guest[$field])? $guest[$field]:'') ."</td>";
          }

          echo "</tr>";
        }
        echo "</tbody></table>";
        $_message_addition = ob_get_clean();
      }
    }elseif($type=='coming'){
      $guests = EVORS()->functions->GET_rsvp_list($eid, $RI);
      foreach(array('y','m') as $rsvp_status){
        if(is_array($guests) && isset($guests[$rsvp_status]) && count($guests[$rsvp_status])>0){
          foreach($guests[$rsvp_status] as $guest){
            if(!isset($guest['email'])) continue;
            $emails[] = $guest['email'];
          }
        }
      }
    }elseif($type=='notcoming'){
      $guests = EVORS()->functions->GET_rsvp_list($eid, $RI);
      if(is_array($guests) && isset($guests['n']) && count($guests['n'])>0){
        foreach($guests['n'] as $guest){
          $emails[] = $guest['email'];
        }
      }
    }elseif($type=='all'){
      $guests = EVORS()->functions->GET_rsvp_list($eid, $RI);
      foreach(array('y','m','n') as $rsvp_status){
        if(is_array($guests) && isset($guests[$rsvp_status]) && count($guests[$rsvp_status])>0){
          foreach($guests[$rsvp_status] as $guest){
            $emails[] = $guest['email'];
          }
        }
      }
    }

    // emaling
    if($emails){
      $messageBODY = "<div style='padding:15px'>".(!empty($_POST['message'])? strip_tags(sanitize_textarea_field($_POST['message']) ).'<br/><br/>':'' ).($_message_addition?$_message_addition:'') . "</div>";
      $messageBODY = $eventon_rs->email->get_evo_email_body($messageBODY);
      $from_email = $eventon_rs->email->get_from_email_address();

      $args = array(
      'html'=>'yes',
      'type'=> ($type == 'someone'? 'regular':'bcc'),
      'to'=> $emails,
      'subject'=>sanitize_text_field($_POST['subject']),
      'from'=>$from_email,
      'from_email'=>$from_email,
      'from_name'=>$eventon_rs->email->get_from_email_name(),
      'message'=>$messageBODY,
      );

      $helper = new evo_helper();
      $EMAILED = $helper->send_email($args);
    }

    $return_content = array(
    'status'=> ($EMAILED?'0':'did not go'),
    'other'=>$args
    );

    echo json_encode($return_content);
    exit;
  }

}
new evors_admin_ajax();