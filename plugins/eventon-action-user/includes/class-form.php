<?php
/**
* Evoau front end submission form
* @version 2.1.6
* @ Intel version 1.2
*/
class evoau_form{

  private $form_lang = 'L1';
  function __construct(){
    $this->opt_1= get_option('evcal_options_evcal_1');
    $this->opt_2 = get_option('evcal_options_evcal_2');
  }

  // get form content
  function get_content($event_id ='', $atts='', $form_field_permissions=array(), $override_pluggable_check= false, $atts2=''){

    global $eventon;

    // form default arguments
    $defaults = array(
    'lang'=>'L1',
    'ligthbox'=>'no',
    'msub'=>'no',
    'rlink'=>'',
    'calltype'=>'new',
    'wordcount'=>0
    );

    //add_post_meta(527,'aa','fre+fre');
    $atts = !empty($atts)? array_merge($defaults, $atts): $defaults;
    $hidden_fields = array(
    'action'=>'evoau_event_submission'
    );
    $hidden_fields = !empty($atts['hidden_fields'])? array_merge($hidden_fields, $atts['hidden_fields']): $hidden_fields;

    $EVOAU_Props = new EVO_Calendar('evoau_1');


    $evoopt= EVOAU()->frontend->evoau_opt;
    $evoopt_1= $this->opt_1;
    $opt_2 = $this->opt_2;

    $FIELD_ORDER = !empty($evoopt['evoau_fieldorder'])? array_filter(explode(',',$evoopt['evoau_fieldorder'])): false;

    $SELECTED_FIELDS = (!empty($evoopt['evoau_fields']))?
    ( (is_array($evoopt['evoau_fields']) && count($evoopt['evoau_fields'])>0 )? $evoopt['evoau_fields']:
    array_filter(explode(',', $evoopt['evoau_fields']))):
    false;

    ob_start();


    // INIT Values
    // language for the form fields
    $lang = $this->form_lang = (!empty($atts['lang'])? $atts['lang']:'L1');

    // login required
    $_USER_LOGIN_REQ = (evo_settings_check_yn($evoopt,'evoau_access') && !is_user_logged_in())? true:false;

    // Check loggedin user have submission permissions
    $_USER_CAN = true;

    if( !$_USER_LOGIN_REQ && evo_settings_check_yn($evoopt,'evoau_access')){
      if( evo_settings_check_yn($evoopt, 'evoau_access_role') && !current_user_can('submit_new_events_from_submission_form')){
        $_USER_CAN = false;
        $_USER_LOGIN_REQ = true;
      }
    }


    // the form type
    $_EDITFORM = ($atts['calltype']=='edit' && !empty($event_id))? true:false;

    //if shortcode arguments passed
    $atts = !empty($atts)? $atts: false;
    $_LIGTHBOX = ($atts && !empty($atts['lightbox']) && $atts['lightbox']=='yes')? true:false;
    $_LIGTHBOX = (!$_LIGTHBOX && $atts && !empty($atts['ligthbox']) && $atts['ligthbox']=='yes')? true:$_LIGTHBOX;
    $_msub = ($atts && !empty($atts['msub']) && $atts['msub']=='yes')? true:false;


    // limit submissions to one
    $LIMITSUB = (!empty($evoopt['evoau_limit_submissions']) && $evoopt['evoau_limit_submissions']=='yes' && isset($_COOKIE['evoau_event_submited']) && $_COOKIE['evoau_event_submited']=='yes')? true: false;


    // before showing the form
    // Ability for pluggable functions to display other content instead of form

    $pluggable_check = apply_filters('evoau_form_display_check',true, $event_id, $_EDITFORM, $atts);

    if(!$pluggable_check && !$override_pluggable_check){
      do_action('evoau_form_before');
      return ob_get_clean();
    }

    ?>
    <div class='eventon_au_form_section <?php echo ($_LIGTHBOX)?'overLay':'';?>' style='display:<?php echo $_LIGTHBOX?'none':'block';?>'>
      <div id='eventon_form' class='evoau_submission_form <?php echo ($_USER_LOGIN_REQ?'loginneeded':'') . ' '. ($LIMITSUB?' limitSubmission':'').' '.($_LIGTHBOX?'lightbox':'');?>' data-fid='438932' data-mce='0'>
        <a class='closeForm'>X</a>
        <form method="POST" action="" enctype="multipart/form-data" id='evoau_form' class='' data-msub='<?php echo ($_msub)?'ow':'nehe';?>' data-redirect='<?php echo ($atts && !empty($atts['rlink']) && !empty($atts['rdir']) && $atts['rdir']=='yes')?$atts['rlink']:'nehe';?>' data-rdur='<?php echo $this->val_check($atts,'rdur');?>' data-limitsubmission='<?php echo (!empty($evoopt['evoau_limit_submissions']) && $evoopt['evoau_limit_submissions']=='yes')?'ow':'nehe';?>' data-enhance="false">

          <?php

          // hidden fields for the form
          if(is_user_logged_in()){
            $current_user = wp_get_current_user();
            $hidden_fields['_current_user_id'] = $current_user->ID;
          }

          // for onlu edit forms
          if($_EDITFORM){
            $hidden_fields['form_action'] = 'editform';
            $hidden_fields['eventid'] = $event_id;
          }

          foreach(apply_filters('evoau_form_hidden_fields', $hidden_fields) as $key=>$val){
            echo "<input type='hidden' name='{$key}' value='{$val}'/>";
          }
          ?>
          <?php 	wp_nonce_field( AJDE_EVCAL_BASENAME, 'evoau_noncename' );	?>

          <div class='inner' style='display:<?php echo $LIMITSUB?'none':'block';?>'>
            <h2><?php echo $_EDITFORM? eventon_get_custom_language($opt_2, 'evoAUL_ese', 'Edit Submitted Event', $lang):
              (($atts && !empty($atts['header']))? stripslashes($atts['header']):
            ((!empty($evoopt['evo_au_title']))? stripslashes($evoopt['evo_au_title']):'Submit your event'));?></h2>
            <?php
            // form subtitle text
            $SUBTITLE = ($atts && !empty($atts['sheader']))? $atts['sheader']:
            (!empty($evoopt['evo_au_stitle'])? $evoopt['evo_au_stitle']: false);
            echo ($SUBTITLE)? '<h3>'.stripslashes($SUBTITLE).'</h3>':null;?>
            <?php

            // display event post publish status for editing events
            if($_EDITFORM && !empty($event_id)){
              $pub_status = get_post_status($event_id);

              echo "<p class='event_post_status'>". evo_lang('Event Publish Status').': <b>' . evo_lang($pub_status)."</b></p>";
            }

            // event post status
            /*?><h4><?php evo_lang_e('Event Post Status');?>: <?php echo get_post_status($event_id);?></h4><?php*/

            //access control to form
            if( $_USER_LOGIN_REQ ):

            // current logged in user does not have permission to submit events
            if( !$_USER_CAN):
            $this->get_form_access_restricted('permission');
            else:
            $this->get_form_access_restricted('login');
            endif;
            else:
            ?>
            <div class='evoau_table'>
              <?php
              // initials
              // force wp date format to be used for date format creation
              $evcal_date_format = eventon_get_timeNdate_format('',true);

              $dateFormat = $evcal_date_format[1];
              $dateFormat = ($dateFormat=='d/m/Y')? 'm/d/Y':$dateFormat;

              // the fixed date format to save the selected value
              $fixed_date_format = 'Y-m-d';

              // date format in JS compatible value
              $dateFormatJS = $evcal_date_format[0];
              $dateFormatJS = ($dateFormatJS=='dd/mm/yy')? 'mm/dd/yy':$dateFormatJS;

              $timeFormat = ($evcal_date_format[2])? 'H:i':'h:i:a';
              $EPMV = '';
              if($_EDITFORM)	$EPMV = get_post_custom($event_id);

              // form messages
              echo "<div class='form_msg' style='display:none'></div>";

              // get all the fields after processing
              $FORM_FIELDS = EVOAU()->frontend->au_form_fields();
              $EACH_FIELD = $this->process_form_fields_array($FIELD_ORDER,
              apply_filters('evoau_form_field_permissions_array', $form_field_permissions, $_EDITFORM, $event_id)
              );



              // if the user is loggedin
              if(is_user_logged_in() ) $current_user = wp_get_current_user();

              // before form fields action
              do_action('evoau_before_submission_form_fields', $_EDITFORM, $EPMV);

              // skip fields for the form
              $FORM_SKIPS = apply_filters('evoau_form_skip_fields',array('event_special_edit'));

              // EACH field array from EVOAU()->au_form_fields()
              foreach(apply_filters('evoau_form_fields_array',$EACH_FIELD)  as $__index=>$ff):

              if(in_array($ff, $FORM_SKIPS)) continue;

              $INDEX = (!empty($FIELD_ORDER))? $ff:$__index;

              if( ($SELECTED_FIELDS && in_array($INDEX, $SELECTED_FIELDS) )
              || in_array($INDEX, EVOAU()->frontend->au_form_fields('defaults_ar'))
              ){

                // get form array for the field parameter
                if(empty($FORM_FIELDS[$INDEX])) continue;

                $field = $FORM_FIELDS[$INDEX];
                $__field_name = (!empty($field[4]))?
                eventon_get_custom_language($opt_2, $field[4], $field[0], $lang) :$field[0];
                $__field_type = $field[2];
                $_placeholder = (!empty($field[3]))? __($field[3],'eventon'):null;
                $__field_id =$field[1];
                $__req = (!empty($field[5]) && $field[5]=='req')? ' *':null;
                $__req_ = (!empty($field[5]) && $field[5]=='req')? ' req':null;

                // dont show name and email field is user is logged in
                if(is_user_logged_in() && ($INDEX=='yourname' || $INDEX=='youremail') && !empty($current_user) ){

                  if($INDEX=='yourname')
                  echo "<input type='hidden' name='yourname' value='{$current_user->display_name}'/>";
                  if($INDEX=='youremail')
                  echo "<input type='hidden' name='youremail' value='{$current_user->user_email}'/>";

                  continue;
                }

                // default value for fields
                $default_val = (!empty($_POST[$__field_id]))? sanitize_text_field($_POST[$__field_id]) : null;
                if($EPMV){
                  $default_val = !empty($EPMV[$__field_id])? $EPMV[$__field_id][0]:$default_val;
                }

                // disable edit capabilities for date and time fields
                $disable_date_editing = apply_filters('evoau_datetime_editing', false, $_EDITFORM, $EPMV);


                // switch statement for dif fields
                switch($__field_type){
                  // pluggable
                  case has_action("evoau_frontform_{$__field_type}"):
                  do_action('evoau_frontform_'.$__field_type, $field, $event_id, $default_val, $EPMV, $opt_2, $lang);
                  break;

                  // default fields
                  case 'title':
                  if($EPMV)
                  $default_val = get_the_title($event_id);
                  echo "<div class='row'>
                  <p class='label'>
                  <input id='_evo_date_format' type='hidden' name='_evo_date_format' jq='".$dateFormatJS."' value='". $fixed_date_format. "'/>
                  <input id='_evo_time_format' type='hidden' name='_evo_time_format' value='".(($evcal_date_format[2])?'24h':'12h')."'/>
                  <label for='event_name'>".$__field_name." <em>*</em></label></p>
                  <p><input type='text' class='fullwidth req' name='event_name' value='".$default_val."' placeholder='".$__field_name."' data-role='none'/></p>
                  </div>";
                  break;
                  case 'startdate':
                  $isAllDay = (!empty($EPMV['evcal_allday']) && $EPMV['evcal_allday'][0]=='yes')? 'display:none': '';
                  $event_start_date = sanitize_text_field($_POST['event_start_date']);
                  $event_start_date_x = sanitize_text_field($_POST['event_start_date_x']);
                  $event_start_time = sanitize_text_field($_POST['event_start_time']);
                  $SD = ($EPMV)? date($dateFormat, (int)$EPMV['evcal_srow'][0]):
                  ((!empty($_POST['event_start_date']))? $event_start_date: null);
                  $SDX = ($EPMV)? date($fixed_date_format, (int)$EPMV['evcal_srow'][0]):
                  ((!empty($_POST['event_start_date_x']))? $event_start_date_x: null);
                  $ST = ($EPMV)? date($timeFormat, (int)$EPMV['evcal_srow'][0]):
                  ((!empty($_POST['event_start_time']))? $event_start_time: null);


                  // get translated month and day name for date picker
                  $lang_options = $opt_2[$lang];

                  $eventon_day_names = array(
                  7=>'sunday',
                  1=>'monday',
                  2=>'tuesday',
                  3=>'wednesday',
                  4=>'thursday',
                  5=>'friday',
                  6=>'saturday'
                  );

                  $daynames = $fulldayname = $month_names = array();
                  foreach($eventon_day_names as $count=>$day){
                    $daynames[] = ucfirst(substr( ((!empty($lang_options['evo_lang_3Ld_'.$count]))?
                    $lang_options['evo_lang_3Ld_'.$count]: $day), 0, 2));
                  }
                  foreach($eventon_day_names as $count=>$day){
                    $fulldayname[] = ucfirst((!empty($lang_options['evcal_lang_day'.$count]))?
                    $lang_options['evcal_lang_day'.$count]: $day);
                  }

                  $data_month_names = evo_get_long_month_names($lang_options);
                  //print_r($lang_options);

                  foreach($data_month_names as $month){
                    $month_names[] = ucfirst($month);
                  }

                  $other = array(
                  'txtnext'=>evo_lang('Next'),
                  'txtprev'=>evo_lang('Prev'),
                  );

                  echo "<div class='row'>
                  <p class='label'><label for='event_start_date'>".$__field_name." *</label></p>
                  <p><input id='evoAU_start_date' type='text' readonly='true' class='". ($disable_date_editing?'':'datepickerstartdate')." req evoau_dpicker' name='event_start_date' data-mn='". json_encode($month_names)."' data-dn='". json_encode($daynames) ."' data-fdn='". json_encode($fulldayname) ."' data-ot='". json_encode($other) ."' placeholder='".eventon_get_custom_language($opt_2, 'evoAUL_phsd', 'Start Date', $lang)."' value='".$SD."' data-role='none'/>
                  <input type='hidden' name='event_start_date_x' class='evoau_alt_date_start' value='{$SDX}'/>
                  <input class='evoau_tpicker ".($disable_date_editing?'':'evoau_time_picker')." req time' type='text' name='event_start_time' placeholder='".eventon_get_custom_language($opt_2, 'evoAUL_phst', 'Start Time', $lang)."' value='".$ST."' style='{$isAllDay}' data-role='none' ".($disable_date_editing?"readonly='true'":'')."/>
                  </p>
                  </div>";
                  break;
                  case 'enddate':
                  $isAllDay = (!empty($EPMV['evcal_allday']) && $EPMV['evcal_allday'][0]=='yes')? 'display:none': '';
                  $hideEnd = (!empty($EPMV['evo_hide_endtime']) && $EPMV['evo_hide_endtime'][0]=='yes')? 'display:none': '';
                  $event_end_date = sanitize_text_field($_POST['event_end_date']);
                  $event_end_date_x = sanitize_text_field($_POST['event_end_date_x']);
                  $event_end_time = sanitize_text_field($_POST['event_end_time']);
                  $ED = ($EPMV)? date($dateFormat, $EPMV['evcal_erow'][0]):
                  ((!empty($_POST['event_end_date']))? $event_end_date: null);
                  $EDX = ($EPMV)? date($fixed_date_format, $EPMV['evcal_erow'][0]):
                  ((!empty($_POST['event_end_date_x']))? $event_end_date_x: null);
                  $ET = ($EPMV)? date($timeFormat, $EPMV['evcal_erow'][0]):
                  ((!empty($_POST['event_end_time']))? $event_end_time: null);

                  echo "<div class='row' id='evoAU_endtime_row' style='{$hideEnd}'>
                  <p class='label'><label for='event_end_date'>".$__field_name." *</label></p>
                  <p><input id='evoAU_end_date' class='". ($disable_date_editing?'':'datepickerenddate')." req end evoau_dpicker' readonly='true' type='text' name='event_end_date' placeholder='".eventon_get_custom_language($opt_2, 'evoAUL_phed', 'End Date', $lang)."' value='".$ED."' data-role='none'/>
                  <input type='hidden' name='event_end_date_x' class='evoau_alt_date_end' value='{$EDX}'/>
                  <input class='evoau_tpicker ".($disable_date_editing?'':'evoau_time_picker')." req end time' type='text' name='event_end_time' placeholder='".eventon_get_custom_language($opt_2, 'evoAUL_phet', 'End Time', $lang)."' value='".$ET."' style='{$isAllDay}' data-role='none' ".($disable_date_editing?"readonly='true'":'')."/>
                  </p>
                  </div>";
                  break;
                  case 'allday':
                  $helper = new evo_helper();
                  echo "<div class='row'>
                  <p class='label'>";
                  echo $helper->html_yesnobtn(array(
                  'id'=>'evcal_allday',
                  'input'=>true,
                  'label'=>eventon_get_custom_language($opt_2, 'evoAUL_001', 'All Day Event', $lang),
                  'var'=> (($EPMV && !empty($EPMV['evcal_allday']) && $EPMV['evcal_allday'][0]=='yes')?'yes':'no'),
                  'lang'=>$lang
                  ));
                  echo "</p>";

                  echo "<p class='label' style='padding-top:5px'>";
                  echo $helper->html_yesnobtn(array(
                  'id'=>'evo_hide_endtime',
                  'input'=>true,
                  'label'=>eventon_get_custom_language($opt_2, 'evoAUL_002', 'No end time', $lang),
                  'var'=> (($EPMV && !empty($EPMV['evo_hide_endtime']) && $EPMV['evo_hide_endtime'][0]=='yes')?'yes':'no'),
                  'lang'=>$lang
                  ));
                  echo "</p>";

                  //echo "<input id='evoAU_all_day' name='event_all_day' type='checkbox' value='yes' ".( ($EPMV && !empty($EPMV['evcal_allday']) && $EPMV['evcal_allday'][0]=='yes')? 'checked="checked"':'')."/> <label>".eventon_get_custom_language($opt_2, 'evoAUL_001', 'All Day Event', $lang)."</label></p>
                  //<p class='label'><input id='evoAU_hide_ee' name='evo_hide_endtime' type='checkbox' value='yes' ".( ($EPMV && !empty($EPMV['evo_hide_endtime']) && $EPMV['evo_hide_endtime'][0]=='yes')? 'checked="checked"':'')."/> <label>".eventon_get_custom_language($opt_2, 'evoAUL_002', 'No end time', $lang)."</label></p>
                  echo "</div>";

                  // if set to hide repeating fields from the form
                  if(!empty($evoopt['evoau_hide_repeats']) && $evoopt['evoau_hide_repeats']=='yes'){}else{

                    echo "<div class='row evoau_repeating'><p>";
                    $evcal_repeat = ($EPMV && !empty($EPMV['evcal_repeat']) && $EPMV['evcal_repeat'][0]=='yes')? true: false;
                    echo $helper->html_yesnobtn(array(
                    'id'=>'evcal_repeat',
                    'input'=>true,
                    'label'=>eventon_get_custom_language($opt_2, 'evoAUL_ere1', 'This is a repeating event', $lang),
                    'var'=> ($evcal_repeat?'yes':'no'),
                    'lang'=>$lang
                    ));
                    echo "</p></div>";

                    // saved values for edit form
                    $evcal_rep_freq = ($EPMV && !empty($EPMV['evcal_rep_freq']))? $EPMV['evcal_rep_freq'][0]:false;
                    $evcal_rep_gap = ($EPMV && !empty($EPMV['evcal_rep_gap']))? $EPMV['evcal_rep_gap'][0]:false;
                    $evcal_rep_num = ($EPMV && !empty($EPMV['evcal_rep_num']))? $EPMV['evcal_rep_num'][0]:false;

                    echo "<div class='row' id='evoau_repeat_data' style='display:".($evcal_repeat?'':'none')."'>
                    <p class='evoau_repeat_frequency'>
                    <select name='evcal_rep_freq' data-role='none'>
                    <option value='daily' ".( $evcal_rep_freq=='daily'? "selected='selected'":'').">".eventon_get_custom_language($opt_2, 'evoAUL_ere2', 'Daily', $lang)."</option>
                    <option value='weekly' ".( $evcal_rep_freq=='weekly'? "selected='selected'":'').">".eventon_get_custom_language($opt_2, 'evoAUL_ere3', 'Weekly', $lang)."</option>
                    <option value='monthly' ".( $evcal_rep_freq=='monthly'? "selected='selected'":'').">".eventon_get_custom_language($opt_2, 'evoAUL_ere4', 'Monthly', $lang)."</option>
                    <option value='yearly' ".( $evcal_rep_freq=='yearly'? "selected='selected'":'').">".eventon_get_custom_language($opt_2, 'evoAUL_ere4y', 'Yearly', $lang)."</option>
                    </select>
                    <label>".eventon_get_custom_language($opt_2, 'evoAUL_ere5', 'Event Repeat Type', $lang)."</label>
                    </p>
                    <p class='evcal_rep_gap'>
                    <input type='number' name='evcal_rep_gap' min='1' placeholder='1' value='".($evcal_rep_gap? $evcal_rep_gap:'1')."' data-role='none'/>
                    <label>".eventon_get_custom_language($opt_2, 'evoAUL_ere6', 'Gap Between Repeats', $lang)."</label>
                    </p>
                    <p class='evcal_rep_num'>
                    <input type='number' name='evcal_rep_num' min='1' placeholder='1' value='".($evcal_rep_num? $evcal_rep_num:'1')."' data-role='none'/>
                    <label>".eventon_get_custom_language($opt_2, 'evoAUL_ere7', 'Number of Repeats', $lang)."</label>
                    </p>
                    </div>";
                  }
                  break;
                  case 'text':
                  $default_val = str_replace("'", '"', $default_val);

                  echo $this->get_form_html($__field_id, array(
                  'type'=>'text',
                  'name'=>$__field_name,
                  'placeholder'=>$_placeholder,
                  'value'=>$default_val,
                  'required_html'=>$__req,
                  'required_class'=>$__req_
                  ));

                  break;
                  case 'html':
                  $HTML = !empty($evoopt['evoau_html_content'])? $evoopt['evoau_html_content']: false;
                  if($HTML){
                    echo $this->get_form_html($__field_id, array(
                    'type'=>'html',
                    'html'=>$HTML,
                    ));
                  }
                  break;
                  case 'button':
                  echo "<div class='row'>
                  <p class='label'><label for='".$__field_id."'>".$__field_name.' '.evo_lang('(Text)', $lang,$opt_2).' '.$__req."</label></p>
                  <p><input type='text' class='fullwidth{$__req_}' name='".$__field_id."' ".$_placeholder." value='{$default_val}' data-role='none'/></p>
                  <p class='label'><label for='".$__field_id."'>".$__field_name.' '.evo_lang('(Link)', $lang,$opt_2).' '.$__req."</label></p>
                  <p><input type='text' class='fullwidth{$__req_}' name='".$__field_id."L' ".$_placeholder." value='".(!empty($EPMV[$__field_id."L"])? $EPMV[$__field_id."L"][0]:null)."' data-role='none'/></p>
                  </div>";
                  break;
                  case 'textarea':
                  // for event details field
                  if($field[1]== 'event_description'){
                    $event = get_post($event_id);
                    if($event_id){
                      setup_postdata($event);
                      $content = $event->post_content;

                      //$default_val = $eventon->frontend->filter_evo_content( $content );
                      $default_val = $content;
                      //$content = apply_filters('the_content', $content);
                      //$default_val = str_replace(']]>', ']]&gt;', $content);
                      //$default_val = $content;
                      wp_reset_postdata();
                    }else{
                      $default_val = '';
                    }
                  }
                  if($field[1]== 'event_description'){

                    // USE basic text editor
                    if(!empty(EVOAU()->frontend->evoau_opt['evoau_eventdetails_textarea']) && EVOAU()->frontend->evoau_opt['evoau_eventdetails_textarea']=='yes'){
                      echo $this->get_form_html($__field_id, array(
                      'type'=>'textarea',
                      'name'=>$__field_name,
                      'value'=>$default_val,
                      'placeholder'=>$_placeholder
                      ));
                    }else{
                      echo $this->get_form_html($__field_id, array(
                      'type'=>'textarea',
                      'name'=>$__field_name,
                      'value'=>$default_val,
                      'editor'=>'wysiwyg',
                      'placeholder'=>$_placeholder
                      ));
                      // WYSIWYG editor
                      /*$editor_id = (!empty($field[4])? $field[4]:'');
                      $editor_var_name = 'event_description';
                      $editor_args = array(
                      'wpautop'=>true,
                      'media_buttons'=>false,
                      'textarea_name'=>$editor_var_name,
                      'editor_class'=>'',
                      'tinymce'=>true,
                      );
                      //echo "<div id='{$editor_id}' class='evoau_eventdetails'>".wp_editor($default_val, $editor_id, $editor_args)."</div>";*/
                    }

                  }else{
                    echo $this->get_form_html($__field_id, array(
                    'type'=>'textarea',
                    'name'=>$__field_name,
                    'value'=>$default_val,
                    'placeholder'=>$_placeholder
                    ));
                  }

                  break;
                  case 'color':

                  // get the default color from eventon settings
                  $defaultColor = !empty(EVOAU()->frontend->options['evcal_hexcode'])? EVOAU()->frontend->options['evcal_hexcode']: '8c8c8c';

                  echo "<div class='row'>
                  <p class='color_circle' data-hex='".(!empty($EPMV['evcal_event_color'])? $EPMV['evcal_event_color'][0]:$defaultColor)."' style='background-color:#".(!empty($EPMV['evcal_event_color'])? $EPMV['evcal_event_color'][0]:$defaultColor)."'></p>
                  <p class='evoau_color_picker'>
                  <input type='hidden' class='evcal_event_color' name='evcal_event_color' value='".(!empty($EPMV['evcal_event_color'])? $EPMV['evcal_event_color'][0]:$defaultColor)."'/>
                  <input type='hidden' name='evcal_event_color_n' class='evcal_event_color_n' value='".(!empty($EPMV['evcal_event_color_n'])? $EPMV['evcal_event_color_n'][0]:'0')."'/>
                  <label for='".$__field_id."'>".$__field_name."</label>
                  </p>
                  </div>";
                  break;
                  case 'tax':
                  // get all terms for categories
                  $terms = get_terms($field[1], apply_filters('evoau_form_get_terms_'.$field[1], array('hide_empty'=>false))
                  );

                  if(count($terms)>0){
                    echo "<div class='row'>
                    <p class='label'><label for='".$__field_id."'>".$__field_name."</label></p><p class='checkbox_row'>";

                    // if edit form
                    $slectedterms = array();
                    if($_EDITFORM){
                      $postterms = wp_get_post_terms($event_id, $field[1]);
                      if(!empty($postterms)){
                        foreach($postterms as $postterm)
                        $slectedterms[] = $postterm->term_id;
                      }
                    }
                    /*
                    echo "<select multiple class='evoau_selectmul'>";
                    foreach($terms as $term){
                    echo "<option ".( (count($slectedterms) && in_array($term->term_id, $slectedterms))? 'selected="selected"':null )." value='".$term->term_id."'>".$term->name."</option>";
                    }
                    echo "</select>";
                    */

                    echo "<span class='evoau_cat_select_field {$field[1]}' data-enhance='false'>";
                    foreach($terms as $term){
                      echo "<span class='{$field[1]}_{$term->term_id}'><input type='radio' name='".$__field_id."[]' value='".$term->term_id."' ".( (count($slectedterms) && in_array($term->term_id, $slectedterms))? 'checked="checked"':null )." data-role='none'/> ".$term->name."</span>";
                    }
                    echo "</span>";

                    echo "</p>";

                    if(!empty($evoopt['evoau_add_cats']) && $evoopt['evoau_add_cats']=='yes')
                    echo "<p class='label'><label>".eventon_get_custom_language($opt_2,'evoAUL_ocn','or create New (type other categories seperated by commas)',$lang)."</label></p><p><input class='fullwidth' type='text' name='".$__field_id."_new' data-role='none'/></p>";
                    echo "</div>";
                  }
                  break;
                  case 'image':
                  // check if the user has permission to upload event images
                  if( !current_user_can('upload_files')) break;

                  // if image already exists
                  if($_EDITFORM){
                    $IMFSRC = false;
                    $img_id =get_post_thumbnail_id($event_id);
                    if($img_id!=''){
                      $img_src = wp_get_attachment_image_src($img_id,'thumbnail');
                      $IMFSRC = $img_src[0];
                    }
                  }
                  echo "<div class='row'>
                  <p class='label'><label for='".$__field_id."'>".$__field_name."</label></p>";

                  if($_EDITFORM && $IMFSRC){
                    echo"<div class='evoau_img_preview'>
                    <input class='evoau_img_input' type='hidden' name='evoau_event_image_id' value='{$img_id}'/>
                    <img src='{$IMFSRC}'/>
                    <span class='evoau_event_image_remove'>".evo_lang('Remove Image',$lang,$opt_2)."</span>
                    <input type='hidden' name='event_image_exists' value='yes'/>
                    </div>";
                  }
                  echo "<div class='evoau_file_field' style='display:".($_EDITFORM && $IMFSRC?'none':'block')."'>
                  <p>
                  <span class='evoau_img_btn' >".eventon_get_custom_language($opt_2, 'evoAUL_img002', 'Select an Image', $lang)."</span>
                  <input class='evoau_img_input' style='opacity:0' type='file' id='".$__field_id."' name='".$__field_id."' data-text='".eventon_get_custom_language($opt_2, 'evoAUL_img001', 'Image Chosen', $lang)."' data-role='none'/>";
                  wp_nonce_field( 'my_image_upload', 'my_image_upload_nonce' );
                  echo "</p></div>
                  </div>";
                  break;
                  case 'uiselect':
                  // options
                  $uis = array(
                  '1'=>eventon_get_custom_language($opt_2, 'evoAUL_ux1', 'Slide Down EventCard', $lang),
                  '2'=>eventon_get_custom_language($opt_2, 'evoAUL_ux2', 'External Link', $lang),
                  '3'=>eventon_get_custom_language($opt_2, 'evoAUL_ux3', 'Lightbox Popup Window', $lang)
                  );

                  // if single event addon is enabled
                  if(defined('EVO_SIN_EV') && EVO_SIN_EV)
                  $uis['4'] = eventon_get_custom_language($opt_2, 'evoAUL_ux4a', 'Open as Single Event Page', $lang);

                  echo "<div class='row evoau_ui'>
                  <p class='label'><label for='".$__field_id."'>".$__field_name."</label></p><p class='dropdown_row'><select name='".$__field_id."'>";

                  foreach($uis as $ui=>$uiv){
                    ?><option type='checkbox' value='<?php echo $ui;?>'> <?php echo $uiv;?></option>
                    <?php
                  }
                  echo "</select></p>
                  <div class='evoau_exter' style='display:none'>
                  <p class='label'><label for='evoau_ui'>".eventon_get_custom_language($opt_2, 'evoAUL_ux4', 'Type the External Url', $lang)."</label></p>
                  <p><input name='evcal_exlink' class='fullwidth' type='text' data-role='none'/><br/>
                  <i><input name='_evcal_exlink_target' value='yes' type='checkbox' data-role='none'/> ".eventon_get_custom_language($opt_2, 'evoAUL_lm1', 'Open in new window', $lang)."</i></p>
                  </div></div>";
                  break;
                  case 'learnmore':
                  $default_val = str_replace("'", '"', $default_val);
                  echo "<div class='row learnmove'>
                  <p class='label'><label for='".$__field_id."'>".$__field_name.$__req."</label></p>
                  <p class='input_field'><input type='text' class='fullwidth{$__req_}' name='".$__field_id."' ".$_placeholder." value='{$default_val}' data-role='none'/></p>
                  <p class='checkbox_field'>
                  <input type='checkbox' ".($default_val?'checked':'')." name='".$__field_id."_target' value='yes' data-role='none'/>
                  <label>".eventon_get_custom_language($opt_2, 'evoAUL_lm1', 'Open in new window', $lang)."</label></p>
                  </div>";
                  break;
		  // BEGIN Intel: Edit location on frontend
		  case 'evolocation':
		    $shouldHide = true;
		    ?>
		<div class='row evotest'>   
		    
			<p><label for="">Event Location</label></p>	

			<p><label for="site">Select Location Type : </label>		
			<select class="form-control" id="locationtype" name="evolocationtype">				   
				<?php 
                                $taxonomy = 'event_type_4';
                                $location_type_terms = wp_get_post_terms($event_id, $taxonomy);
                                $selected = "";
                                if(empty($location_type_terms) || count($location_type_terms) > 1 ){
                                    $selected = ' selected="selected" ';
                                } else {
				  $location_type_term_id = $location_type_terms[0]->term_id;
				  $shouldHide = false;
				}
                                echo '<option value="" ' . $selected . '>Select Location Type</option>';
                                $args = array(
                                        'parent' => 0,
                                        'hide_empty' => false,
                                        'orderby' => 'id',
                                        'order' => 'ASC',                                       
                                );
                                $terms = get_terms( $taxonomy, $args );
                                
				foreach ( $terms as $term) {
				  $selected = ($location_type_term_id == $term->term_id) ? ' selected="selected" ' : '';
				  echo '<option data-slug="' . $term->slug . '"' . ' value="' .$term->term_id . '"' . $selected . '>' . $term->name . '</option>';
				}
				?>
			</select>			
			</p> 
			
			    <p id="pregion" <?php echo ($shouldHide ? ' style="display:none;" ' : '') ?>><label for="region">Select Event's Region : </label>		
			<select class="form-control" id="region" name="evoregion">				   
				<?php 
                                $taxonomy = 'event_type_3';
                                $shouldHide = true;
                                $region_terms = wp_get_post_terms($event_id, $taxonomy);
                                $selected = "";
                                if(empty($region_terms) || count($region_terms) > 1 ){
                                    $selected = ' selected="selected" ';
                                } else {
				  $region_term_id = $region_terms[0]->term_id;
				  $shouldHide = false;
				}
                                echo '<option value="" ' . $selected . '>Select Region</option>';
				$args = array(
					'parent' => 0,
					'hide_empty' => false				// to get only parent terms
				);
                                $terms = get_terms( $taxonomy, $args );
					foreach ( $terms as $term) {
						$args1 = array(
							'parent' => $term->term_id,
							'hide_empty' => false	
						);
						$terms1 = get_terms( 'event_type_3', $args1);
						echo '<optgroup label="'.$term->name .'">';   
						foreach ( $terms1 as $term1) {
                    				        $selected = ($region_term_id == $term1->term_id) ? ' selected="selected" ' : '';
							echo '<option value="'.$term1->term_id .'" ' . $selected . '>'.$term1->name .'</option>';
						}
					}				
				?>
			</select>			
			</p>
			<p id="ploc" <?php echo ($shouldHide ? ' style="display:none;" ' : '') ?>><label for="evolocation">Select Event's Location : </label>	  	
			<select class="form-control" id="evolocation" name="evolocation">				   
				<option value="" selected="selected">Select Location</option>        
			</select>			
			</p>
			<?php
                        if( !$shouldHide ) {
                                $metakey = 'evo_event_location';
				$location_value = get_post_meta($event_id, $metakey, true);
		  ?>
			  <script>
jQuery(document).ready(function(){
    var ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
    var region = $('#region').val();
    var data = {
	'action': 'get_event_location',
	'region': region
    };

    $.post( ajax_url, data, function( response ) {
	var $loc = $( "#evolocation" );
	$loc.html( response );
	var $selectedOption = $loc.find('option[value=<?php echo intval($location_value); ?>]');
	if($selectedOption && $selectedOption.length){
	    $selectedOption.attr('selected', 'selected');
	} else {
	    $loc.find('option:first').attr('selected', 'selected');
	}
	$('#addtxt').html('Room');
    } );  
});		    </script>
			<?php }
				$shouldHide = true;
                                $metakey = 'off_site_address';
				$address_value = get_post_meta($event_id, $metakey, true);
                                if(!empty($address_value)){
						   $shouldHide = false;
                                }
				?>						   
			<p id="padd" <?php echo ($shouldHide ? ' style="display:none;" ' : '') ?>><label for="address">Enter The <span id="addtxt">Address</span>  : </label>
				<input type="text" name="address" id="address" value="<?php echo sanitize_text_field($address_value); ?>">	
			</p>
			<?php 
				$shouldHide = true;
                                $metakey = 'virtual_link';
				$virtual_link_value = get_post_meta($event_id, $metakey, true);
                                if(!empty($virtual_link_value)){
						   $shouldHide = false;
                                }
				?>						   

			<p><label for="virtual_link">Enter Virtual Link  : </label>
			<a href="https://employeecontent.intel.com/content/corp/meeting-center/home.html" style="color:black;">If you have not booked a room or virtual meeting yet, use this link.</a>
				<input type="text" name="virtual_link" id="" value="<?php echo sanitize_text_field($virtual_link_value); ?>">	
			</p>
<?php 
				$custom_fields = get_post_custom($event_id);
				$is_private = get_post_meta($event_id, 'private', true) == 1;
				?>	
						       <p class="checkbox">
			  <label>Is This A Private Event Only Open To Invited Guests?</label>
			     <label><input type="checkbox" value="1" name="private"<?php echo ($is_private ? ' checked="checked" ' : '' ) ?>>Yes, Make Private</label>
			</p>
		</div>
		
		<script>
			 jQuery("#region").change(function () {
				var ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
				var region = this.value;
				var data = {
					'action': 'get_event_location',
					'region': region
				};

				jQuery.post( ajax_url, data, function( response ) {
					jQuery( "#evolocation" ).html( response );
					
				} );  
				
			});
			
			jQuery( "#locationtype" ).on( "change", function () {
				var location_type = jQuery( this ).val();
				var location_type_slug = jQuery( this ).find( "option:selected" ).data( "slug" );
				if( location_type_slug === "site" ) {
					jQuery("#pregion").show();
					jQuery("#ploc").show();
					jQuery("#padd").show();
					jQuery( "#addtxt" ).html( 'Room' ); 
				} else if( location_type_slug === "off-site" ) {
					jQuery("#pregion").hide();
					jQuery("#ploc").hide();
					jQuery("#padd").show();
					jQuery( "#addtxt" ).html('Address');
				} else if( location_type_slug === "virtual" ) {
					jQuery("#pregion").hide();
					jQuery("#ploc").hide();
					jQuery("#padd").hide();
				} else {
					jQuery("#pregion").hide();
					jQuery("#ploc").hide();
					jQuery("#padd").hide();   
				}
			} );
		</script>
		
	<?php		 

		    break;
		  // END Intel: Edit location on frontend
                  case 'locationselect':
                  $allow_add_new = $EVOAU_Props->is_yes('evoau_allow_new');


                  $locations = get_terms('event_location', array('hide_empty'=>false));
                  $terms_exists =( ! empty( $locations ) && ! is_wp_error( $locations ) )? true:false;

                  if(!$allow_add_new && !$terms_exists) break;

                  // if location tax saved before
                  $location_terms = !empty($event_id)? wp_get_post_terms($event_id, 'event_location'):'';
                  $termMeta = $evo_location_tax_id = '';
                  if ( $location_terms && ! is_wp_error( $location_terms ) ){
                    $evo_location_tax_id =  $location_terms[0]->term_id;

                    //$termMeta = get_option( "taxonomy_$evo_location_tax_id");
                    $termMeta = evo_get_term_meta('event_location',$evo_location_tax_id, '', true);
                  }

                  echo "<div class='row locationSelect'>
                  <p class='label'><label for='".$__field_id."'>".$__field_name.$__req."</label></p>";

                  if($terms_exists):
                  echo '<p data-role="none"><select class="evoau_location_select" data-role="none">';
                  echo "<option value='-'>".eventon_get_custom_language($opt_2, 'evoAUL_ssl', 'Select Saved Locations', $lang)."</option>";
                  // each select field optinos
                  foreach ( $locations as $loc ) {
                    $taxmeta = evo_get_term_meta('event_location',$loc->term_id, '', true);
                    // /$taxmeta = get_option("taxonomy_".$loc->term_id);

                    $__selected = ($evo_location_tax_id== $loc->term_id)? "selected='selected'":null;

                    // select option attributes
                    $data = array(
                    'add'=>'location_address',
                    'lon'=>'location_lon',
                    'lat'=>'location_lat',
                    'link'=>'evcal_location_link',
                    'img'=>'evo_loc_img',
                    );
                    $datastr = '';
                    foreach($data as $f=>$v){	$datastr.= ' data-'.$f.'="'.( !empty($taxmeta[$v])?$taxmeta[$v]:'').'"';	}

                    echo "<option value='{$loc->term_id}' {$datastr} {$__selected}>" . $loc->name . '</option>';
                  }

                  $fields = EVOAU()->frontend->au_form_fields();
                  echo "</select>";
                  endif;

                  echo "<input type='hidden' name='evo_location_tax_id' value='{$evo_location_tax_id}'/>
                  <input type='hidden' name='evo_loc_img_id' value=''/>";

                  // edit location button
                  if($_EDITFORM && !empty($evo_location_tax_id) && $EVOAU_Props->is_yes('evoau_allow_edit')){
                    echo "<span class='editMeta formBtnS'>". eventon_get_custom_language($opt_2,'evoAUL_edit','Edit', $lang)."</span>";
                  }

                  // Create new
                  if($allow_add_new)
                  echo "<span class='enterNew formBtnS' data-txt='".eventon_get_custom_language($opt_2, 'evoAUL_sfl', 'Close', $lang)."' data-st='".($terms_exists?'ow':'nea')."'>". eventon_get_custom_language($opt_2,'evoAUL_cn','Create New', $lang)."</span>";

                  echo "</p>";

                  $data = array(
                  'event_location_name',
                  'event_location',
                  'event_location_cord',
                  'event_location_link',
                  );
                  echo "<div class='enterownrow' style='display:block'>";
                  foreach($data as $v){
                    $dataField = $fields[$v];
                    $savedValue = (!empty($termMeta) && !empty($termMeta[$dataField[1]]) )?$termMeta[$dataField[1]]: '';

                    // lat and lon values
                    if($v=='event_location_cord'){
                      $savedValue = (!empty($termMeta) && !empty($termMeta['location_lat']) && !empty($termMeta['location_lon']) )? $termMeta['location_lat'].','.$termMeta['location_lon']:'';
                    }

                    // location name
                    if($v == 'event_location_name' && !empty($location_terms)){
                      $savedValue = $location_terms[0]->name;
                    }
                    echo "<p class='subrows {$v}'><label class='$dataField[4]'>".eventon_get_custom_language($opt_2, $dataField[4], $dataField[0], $lang)."</label><input class='fullwidth' type='text' name='{$dataField[1]}' value='{$savedValue}' data-role='none'/></p>";
                  }
                  echo "</div>";
                  echo "</div>";

                  break;
                  case 'organizerselect':
                  $allow_add_new = (!empty($evoopt['evoau_allow_new']) && $evoopt['evoau_allow_new']=='yes')?true:false;

                  $organizers = get_terms('event_organizer' , array('hide_empty'=>false));
                  $terms_exists = ( ! empty( $organizers ) && ! is_wp_error( $organizers ) )? true:false;

                  // if no terms and can not add new
                  if(!$terms_exists && !$allow_add_new) break;

                  // if organizer tax saved before
                  $organizer_terms = !empty($event_id)? wp_get_post_terms($event_id, 'event_organizer'):'';
                  $termMeta = $evo_organizer_tax_id = '';
                  if ( $organizer_terms && ! is_wp_error( $organizer_terms ) ){
                    $evo_organizer_tax_id =  $organizer_terms[0]->term_id;

                    //$termMeta = get_option( "taxonomy_$evo_organizer_tax_id");
                    $termMeta = evo_get_term_meta('event_organizer',$evo_organizer_tax_id, '', true);
                  }

                  echo "<div class='row organizerSelect'>
                  <p class='label'><label for='".$__field_id."'>".$__field_name.$__req."</label></p>";

                  if($terms_exists):      
                  //echo '<p data-role="none"><select class="evoau_organizer_select" data-role="none">';
                  echo '<p data-role="none"><select class="evoau_organizer_select" multiple data-role="none">';
                  //echo "<option value='-'>".eventon_get_custom_language($opt_2, 'evoAUL_sso', 'Select Saved Hosts', $lang)."</option>";

                  // each organizer meta data
                  foreach ( $organizers as $org ) {
                    //$taxmeta = get_option("taxonomy_".$org->term_id);
                    $taxmeta = evo_get_term_meta('event_organizer',$org->term_id, '', true);

                    $__selected = ($evo_organizer_tax_id== $org->term_id)? "selected='selected'":null;

                    // select option attributes
                    $data = array(
                    'contact'=>(!empty($taxmeta['evcal_org_contact'])?$taxmeta['evcal_org_contact']:''),
                    'img'=>(!empty($taxmeta['evo_org_img'])? $taxmeta['evo_org_img']:''),
                    'exlink'=>(!empty($taxmeta['evcal_org_exlink'])?$taxmeta['evcal_org_exlink']:''),
                    'address'=>(!empty($taxmeta['evcal_org_address'])?$taxmeta['evcal_org_address']:''),
                    );
                    $datastr = '';
                    foreach($data as $f=>$v){
                      $datastr.= ' data-'.$f.'="'.$v.'"';
                    }

                    echo "<option value='{$org->term_id}' {$datastr} {$__selected}>" . $org->name . '</option>';
                  }

                  $fields = EVOAU()->frontend->au_form_fields();
                  echo "</select>";
                  endif;

                  echo "<input type='hidden' name='evo_organizer_tax_id' value='{$evo_organizer_tax_id}'/>
                  <input type='hidden' name='evo_org_img_id' value=''/>";

                  // edit organizer button
                  if($_EDITFORM && !empty($evo_organizer_tax_id)){
                    echo "<span class='editMeta formBtnS'>". eventon_get_custom_language($opt_2,'evoAUL_edit','Edit', $lang)."</span>";
                  }

                  // Add new organizer
                  if($allow_add_new)
                  echo "<span class='enterNew' data-txt='".eventon_get_custom_language($opt_2, 'evoAUL_sfl', 'Select from List', $lang)."' data-st='".($terms_exists?'ow':'nea')."'>". eventon_get_custom_language($opt_2,'evoAUL_cn','Create New', $lang)."</span>";
                  echo "</p>";

                  $data = array(
                  'event_organizer',
                  'event_org_contact',
                  'event_org_address',
                  'event_org_link',
                  );
                  echo "<div class='enterownrow' style='display:none'>";
                  foreach($data as $v){
                    $dataField = $fields[$v];
					//print_r($dataField);
                    $savedValue = (!empty($termMeta) && !empty($termMeta[$dataField[1]]) )?$termMeta[$dataField[1]]: '';

                    // Organizer name
                    if($v == 'event_organizer' && !empty($organizer_terms)){
                      $savedValue = $organizer_terms[0]->name ;
                    }

                    echo "<p class='subrows {$v}'><label>".eventon_get_custom_language($opt_2, $dataField[4], $dataField[0], $lang)."</label><input class='fullwidth' type='text' name='{$dataField[1]}' value='{$savedValue}' data-role='none'/></p>";
                  }
                  echo "</div>";
                  echo "</div>";

                  break;
                  case 'captcha':
                  $cals = array(	0=>'3+8', '5-2', '4+2', '6-3', '7+1'	);
                  $rr = rand(0, 4);
                  $calc = $cals[$rr];

                  echo "<div class='row au_captcha'>
                  <p><span style='margin-bottom:6px; margin-top:3px' class='verification'>{$calc} = ?</span>
                  <input type='text' data-cal='{$rr}' class='fullwidth' id='".$__field_id."' name='".$__field_id."' data-role='none'/>
                  </p>
                  <p class='label'><label for='".$__field_id."'>".$__field_name."</label></p>
                  </div>";
                  break;
                }

              }
              endforeach;

              // only edit form options
              if($_EDITFORM && in_array('event_special_edit', $SELECTED_FIELDS)){
                echo apply_filters('evoau_edit_form_options_html', $this->get_edit_form_section($EPMV), $event_id);
              }

              // footer
              $this->get_form_footer($atts, $_EDITFORM, $LIMITSUB);
              ?>

              <?php endif; // close if $_USER_LOGIN_REQ?>
            </form>
		<?php
		// BEGIN Intel update notification
		?>
		<script>
		 $('#evoau_submit').click(function(){
		    var ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';		     
		     if(confirm('Would you like to notify your registered attendees?')){
			 $.post(
			     ajax_url, {
				 'action': 'fln_update_notifications',
				 'eventId': <?php echo intval($event_id) ?>
			     },
			     function( response ) {
			     }
			 );			 
		     };
			return true;
		    });
		</script>
		<?php
		// END Intel update notification
		?>
          </div>
        </div><!--.eventon_au_form_section-->
        <?php
        return ob_get_clean();
      }

      // function process form fields with permissions
      function process_form_fields_array($FIELD_ORDER='', $form_field_permissions){

        if(!empty($FIELD_ORDER)){
          $EACH_FIELD = array_merge( EVOAU()->frontend->au_form_fields('defaults_ar') , $FIELD_ORDER);
        }else{
          $FORM_FIELDS = EVOAU()->frontend->au_form_fields();
          $EACH_FIELD = array_merge(EVOAU()->frontend->au_form_fields('default'), $FORM_FIELDS);
        }

        if(empty($form_field_permissions) || sizeof($form_field_permissions)<1)
        return $EACH_FIELD;

        $new_fields_array = array();

        foreach($form_field_permissions as $field){
          if(in_array($field, $EACH_FIELD))
          $new_fields_array[] = $field;
        }

        $new_fields_array = array_merge( EVOAU()->frontend->au_form_fields('defaults_ar') , $new_fields_array);
        return $new_fields_array;
      }

      // edit form section
      function get_edit_form_section($EPMV){
        ob_start();
        ?><div class='edit_special'><?php
        foreach(apply_filters('evoau_editform_options_array',EVOAU()->frontend->au_form_fields('editonly'))
        as $key=>$value
        ){
          if(in_array($key, array('event_special_edit'))) continue;
          echo $this->get_form_html(
          $key,
          array(
          'type'=>$value[2],
          'yesno_args'=>array(
          'label'=>evo_lang($value[0]),
          'input'=>true,
          'id'=>$key,
          'default'=> (evo_check_yn($EPMV,$key)?'yes':'no')
          )
          )
          );
        }
        ?>
      </div>
      <?php
      return ob_get_clean();
    }

    // Form Access restricted content
    function get_form_access_restricted($permission_type){

      if($permission_type == 'login'){
        $evoopt_1= $this->opt_1;
        $opt_2 = $this->opt_2;
        $lang = $this->form_lang;

        $__001 = eventon_get_custom_language($opt_2, 'evoAUL_ymlse', 'You must login to submit events.', $lang);
        $text_login = eventon_get_custom_language($opt_2, 'evoAUL_00l1', 'Login', $lang);
        $text_register = eventon_get_custom_language($opt_2, 'evoAUL_00l2', 'Register', $lang);

        // Login link
        $login_link = wp_login_url(get_permalink());

        // check if custom link passed
        if(!empty( $evoopt_1['evo_login_link'])) $login_link = $evoopt_1['evo_login_link'];

        $log_msg = $__001. (sprintf(__(' <br/><a class="evcal_btn" title="%1$s" href="%2$s">%1$s</a>','eventon'), $text_login, $login_link ) );

        // register new user
        if (get_option('users_can_register')){
          $log_msg.= (sprintf(__(' <a class="evcal_btn" title="%1$s" href="%2$s/wp-login.php?action=register">%1$s</a>','eventon'), $text_register, get_bloginfo('wpurl') ) );
        }
        echo "<p class='eventon_form_message'><span>".$log_msg."</span></p>";
      }

      if($permission_type=='permission'){
        $log_msg = evo_lang('You do not have permission to submit events!', $this->form_lang);
        echo "<p class='eventon_form_message'><span>".$log_msg."</span></p>";
      }

    }

    // form footer
    function get_form_footer($atts, $_EDITFORM, $LIMITSUB){
      global $eventon_au;

      $_msub = ($atts && !empty($atts['msub']) && $atts['msub']=='yes')? true:false;
      $lang = (!empty($atts['lang'])? $atts['lang']:'L1');

      // form message
      echo "<p class='formeMSG' style='display:none'></p>";

      // Submit button
      $btn_text = ($_EDITFORM)? evo_lang('Update Event',$lang, $this->opt_2): eventon_get_custom_language($this->opt_2, 'evoAUL_se', 'Submit Event', $lang);
      echo "<div class='submit_row row'><p><a id='evoau_submit' class='evcal_btn evoau_event_submission_form_btn'>".$btn_text."</a></p></div>";

      ?>
    </div><!-- .evoau_table-->
  </div><!-- inner -->
  <div class='evoau_json' style='display:none'><?php
    $nofs = array(
    'nof0'=>((!empty(EVOAU()->frontend->evoau_opt['evoaun_msg_f']))?
    (EVOAU()->frontend->evoau_opt['evoaun_msg_f'])
    :eventon_get_custom_language($this->opt_2, 'evoAUL_nof1', 'Required Field(s) Missing', $lang) ),
    'nof1'=> eventon_get_custom_language($this->opt_2, 'evoAUL_nof1', 'Required Field(s) Missing', $lang),
    'nof2'=>eventon_get_custom_language($this->opt_2, 'evoAUL_nof2', 'Invalid validation code please try again', $lang),
    'nof3'=>eventon_get_custom_language($this->opt_2, 'evoAUL_nof3', 'Is this event open to all? Then you are all set! Otherwise, click below to select your group of attendees!', $lang),
    'nof4'=>eventon_get_custom_language($this->opt_2, 'evoAUL_nof4', 'Could not create event post, try again later!', $lang),
    'nof5'=>eventon_get_custom_language($this->opt_2, 'evoAUL_nof5', 'Bad nonce form verification, try again!', $lang),
    'nof6'=>eventon_get_custom_language($this->opt_2, 'evoAUL_nof6', 'You can only submit one event!', $lang),
    'nof7'=>eventon_get_custom_language($this->opt_2, 'evoAUL_nof7', 'Image upload failed', $lang),
    'nof8'=>evo_lang('Thank you for updating your event!', $lang),
    );
    echo json_encode($nofs);
  ?></div>

  <div class='evoau_success_msg' style='display:<?php echo $LIMITSUB?'block':'none';?>'><p><b></b><?php echo $LIMITSUB? eventon_get_custom_language($this->opt_2, 'evoAUL_nof6', 'You can only submit one event!', $lang):'';?></p>
  </div>
  <?php
  // if allow submit another event after submission
  if($_msub && !$_EDITFORM):?>
  <p class='msub_row' style='display:none;text-align:center'><a id='evoau_msub' class='msub evcal_btn'><?php echo evo_lang('Submit another event',$lang, $this->opt_2);?></a></p>
  <?php endif;
}

// form HTML content
function get_form_html($field, $data){
  global $eventon;
  if(empty($data['type'])) return false;

  ob_start();
  $helper = new evo_helper();

  $tooltip = $reqdep = '';
  if(!empty($data['tooltip'])){
    $tooltip = $helper->tooltips($data['tooltip']);
  }

  // required dependancy - the field that also need value for this field to be required
  if(!empty($data['req_dep'])){
    $reqdep = "data-reqd='".json_encode($data['req_dep']) ."'";
  }


  switch($data['type']){
    case 'hidden':
    echo "<input type='hidden' name='{$field}' value='{$data['value']}'/>";
    break;
    case 'text':
    echo "<div class='row {$field}'>
    <p class='label'>
    <label for='".$field."'>".$this->val_check($data,'name').$this->val_check($data,'required_html').$tooltip."</label>
    </p>
    <p><input type='text' class='fullwidth ".$this->val_check($data,'name').$this->val_check($data,'required_class')."' name='".$field."' placeholder='".$this->val_check($data,'placeholder')."' value='".$this->val_check($data,'value')."' data-role='none' {$reqdep}/>";

    echo "</p>
    </div>";
    break;
    case 'yesno':
    echo "<div class='row {$field} row_yesno'>
    <p class='yesno_row' style='padding-top:8px;'>";
    echo $helper->html_yesnobtn($data['yesno_args']);
    echo "</p>";
    echo "</div>";
    break;
    case 'html':
    echo "<div class='row'>";
    echo html_entity_decode($eventon->frontend->filter_evo_content($data['html']));
    echo "</div>";
    break;
    case 'textarea':
    echo "<div class='row textarea {$field}'>
    <p class='label'><label for='".$field."'>".$this->val_check($data,'name')."</label></p>";

    // wysiwig editor
    if($this->val_check($data,'editor')== 'wysiwyg'){

      $editor_id = $field.'au';

      echo "<div id='evoau_form_wisywig' class='evoau_editor_wysiwig' data-textareaname='{$field}' data-editorid='{$editor_id}' >";

      echo "<textarea id='evoau_form_wisywig_content' style='display:none'>". (!empty($data['value'])? $data['value']:'') ."</textarea>";
      // WYSIWYG editor

      $editor_var_name = $field;
      $editor_args = array(
      'wpautop'=>true,
      'media_buttons'=>false,
      'textarea_name'=>$editor_var_name,
      'editor_class'=>'',
      'tinymce'=>true,
      );
      echo "<div id='{$editor_id}' class='{$field}' >".
      wp_editor(	$this->val_check($data,'value'), $editor_id, $editor_args);
      // /_WP_Editors::editor_js();
      echo "</div>";

      echo "<div class='evoau_count_limit' data-len='3' style='display:none'>Word Count (<em>0</em>/50)</div>";
      echo "</div>";

    }else{
      echo "<p><textarea id='".$field."' type='text' class='fullwidth' name='".$field."' ".$this->val_check($data,'placeholder')." data-role='none' placeholder='".$this->val_check($data,'placeholder')."'>".$this->val_check($data,'value')."</textarea></p>";
    }

    echo "</div>";
    break;
    case 'minor_notice':
    echo "<p class='non_simple_wc_product_notice minor_notice'>". $this->val_check($data,'content')."</p>";
    break;

  }

  return ob_get_clean();
}
function val_check($array, $key){
  return !empty($array[$key])? $array[$key]:'';
}
}
