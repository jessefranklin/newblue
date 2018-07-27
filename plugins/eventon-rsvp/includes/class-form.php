<?php
/**
 * RSVP frontend form function
 * @version 2.5.7
 */
class evors_form{
	function get_form($args=''){
		global $eventon_rs;

		$args = !empty($args)?$args: array();
		$args = array_merge(array(
			'e_id'=>'',
			'repeat_interval'=>'',
			'uid'=>get_current_user_id(),
			'rsvpid'=>'',
			'cap'=>'na',
			'precap'=>'na',
			'rsvp'=>'',
			'fname'=>'',
			'lname'=>'',
			'email'=>'',
			'formtype'=>'submit',
			'lang'=>'L1',
			'incard'=>'no'
		), $args);

		//print_r($args);

		// Intial values
			$user_ID = $args['uid'];
			$optRS = $eventon_rs->frontend->optRS;
			$frontend = $eventon_rs->frontend;
			$e_id = $args['e_id'];
			$lang = $eventon_rs->l = $args['lang'];

			$evors = $eventon_rs;

			// Disable pre-filled field editing
				$prefill_edittable = (!empty($optRS['evors_prefil_block']) && $optRS['evors_prefil_block']=='yes')? false: true;
				$prefill = (!empty($optRS['evors_prefil']) && $optRS['evors_prefil']=='yes')? true: false;

			// form fields
				$active_fields =(!empty($optRS['evors_ffields']))?$optRS['evors_ffields']:false;


			// if RSVP information is avialable
				$rpmv = $rsvpid = false;
				if(!empty($args['e_id'])  ){
					if(empty($args['rsvpid']) && !empty($args['uid'])){
						$args['rsvpid'] = $frontend->functions->get_rsvp_post(
							$args['e_id'],
							$args['repeat_interval'],
							array('uid'=>$args['uid'])
						);
					}
				}

				//if(!$prefill) $args['rsvpid'] = '';
				if(!empty($args['rsvpid']))	$rpmv = get_post_custom($args['rsvpid']);

			// if form type is update but can not find RSVP id
				if(empty($args['rsvpid']) && $args['formtype']=='update')
					return $this->find_rsvp_form($args);

			// if user loggedin prefil user date
				if(!empty($user_ID)){
					$user_info = get_userdata($user_ID);
					$args['fname'] = $user_info->first_name;
					$args['lname'] = $user_info->last_name;
					$args['email'] = $user_info->user_email;
				}

				// should form be prefilled or not
					if( !$prefill && $args['formtype'] !='update'  ){
						$args['fname'] = $args['lname'] = $args['email'] = '';
					}

					if($args['formtype']=='update'){
						$args['fname'] = ($rpmv && !empty($rpmv['first_name']))?$rpmv['first_name'][0]:'';
						$args['lname'] = ($rpmv && !empty($rpmv['last_name']))?$rpmv['last_name'][0]:'';
						$args['email'] = ($rpmv && !empty($rpmv['email']))?$rpmv['email'][0]:'';
					}

		// RSVP status
			$rsvpChoice = ($rpmv && !empty($rpmv['rsvp']))? $rpmv['rsvp'][0]: 	(!empty($args['rsvp'])? $args['rsvp']:'');

		ob_start();

if($args['incard']=='yes'){
	echo "<a class='evors_incard_close'></a>";
}



?>
<div id='evorsvp_form' class='evors_forms form_<?php echo $args['formtype'];?>' data-rsvpid='<?php echo $args['rsvpid'];?>' data-cap='<?php echo $args['cap'];?>' data-precap='<?php echo $args['precap'];?>'>
	<form class='evors_submission_form' method="POST" action="" enctype="multipart/form-data">

		<?php
			// hidden input fields
			$arr = array(
				'rsvpid'=>$args['rsvpid'],
				'e_id'=>$args['e_id'],
				'repeat_interval'=>$args['repeat_interval'],
				'uid'=>$user_ID,
				'formtype'=>$args['formtype'],
				'lang'=>$lang,
			);
			if($args['formtype'] == 'update') $arr['original_status'] = $rsvpChoice;

			foreach($arr as $key=>$val){
				echo "<input type='hidden' name='{$key}' value='{$val}'/>";
			}
		?>


		<?php 	wp_nonce_field( AJDE_EVCAL_BASENAME, 'evors_nonce' );	?>
		<?php do_action('evors_before_form');?>

		<div class='submission_form form_section <?php echo $rsvpChoice?'rsvp_'.$rsvpChoice:'';?>'>

			<h3 class="form_header"><?php
				echo ($args['formtype']=='submit')?
					$frontend->replace_en( $eventon_rs->lang('evoRSL_x2','RSVP to [event-name]'), get_the_title($args['e_id'])):
					$frontend->replace_en( $eventon_rs->lang('evoRSL_x2a','Change RSVP to [event-name]'), get_the_title($args['e_id']));
			?></h3>
			<?php // subtitle
				$subtitle_text = ($args['formtype']=='submit')?
					$eventon_rs->lang_e('Fill in the form below to RSVP!', $lang):
					($prefill ? $eventon_rs->lang_e('You have already RSVPed for this event!', $lang):'');
			?>
			<p class='evors_subtitle'><i><?php echo $subtitle_text; ?></i></p>

			<div class="form_row rsvp_status">
				<?php if($args['formtype']=='update' && $prefill):?>
					<p class='evors_rsvpid_tag'><?php echo $eventon_rs->lang('evoRSL_007a','RSVP ID #');?>: <?php echo $args['rsvpid'];?></p>
				<?php endif;?>

				<?php
				// RSVP choices
					$choices_content = $frontend->get_rsvp_choices($frontend->opt2, $optRS, array(), $rsvpChoice ,$args['formtype']);
				?>
				<p class='<?php echo ($frontend->rsvp_option_count==1)?'sin':'';?>'>
					<?php echo $choices_content;?>
					<input type="hidden" name='rsvp' value='<?php echo $rsvpChoice;?>'/>
				</p>
			</div>
			<?php
				$_field_fname = $evors->lang( 'evoRSL_007','First Name');
				$_field_lname = $evors->lang( 'evoRSL_008','Last Name');

			?>
			<div class="form_row name">
				<input class='name input req' name='first_name' type="text" placeholder='<?php echo $_field_fname;?>' title='<?php echo $_field_fname;?>' data-passed='' value='<?php echo $args['fname'];?>' <?php echo (!$prefill_edittable && !empty($args['fname']))? 'readonly="readonly"':'';?>/>
				<input class='name input' name='last_name' type="text" placeholder='<?php echo $_field_lname;?>' title='<?php echo $_field_lname;?>' data-passed='' value='<?php echo $args['lname'];?>' <?php echo (!$prefill_edittable && !empty($args['lname']))? 'readonly="readonly"':'';?>/>
			</div>

		<?php
			// initial key fields
			foreach(array(
				'email'=>array('Email Address','evoRSL_009'),
				'phone'=>array('Phone Number','evoRSL_009a'),
				'count'=>array('How Many People in Your Party?','evoRSL_010'),
			) as $key=>$val){
				if(
					$key == 'email'||
					($key !='email' && $active_fields && in_array($key, $active_fields) )
				):
					$name = $evors->lang( $val[1], $val[0]);
					$value = (isset($rpmv[$key]) && $key!= 'email')? $rpmv[$key][0]: (!empty($args[$key])? $args[$key]:'');

					// Read only field
					$readonly = ($key=='email' && $args['formtype']=='update' && !empty($value) )?
						'readonly="readonly"':'';

					// capacity limit
					if($key=='count'){
						$cap = 'na';
						if(!empty($args['cap']) || !empty($args['precap'])){
							$cap = min( (!empty($args['cap'])? $args['cap']:''),(!empty($args['precap'])? $args['precap']:'') );
							if(!empty($value) && $value>0){
								$cap = max($value, $cap);
							}
						}
						$value = empty($value)? 1:$value;
					}
				?>
					<div class="form_row <?php echo $key.' '.( in_array($key, array('count','phone'))?'hide_no':'');?>">
						<?php echo ( in_array($key, array('count')))? '<label>'.$name.'</label>':'';?>
						<input <?php echo $readonly;?> class='regular input req evors_rsvp_<?php echo $key;?>' name='<?php echo $key;?>' type="text" placeholder='<?php echo ($key!='count')?$name:'';?>' title='<?php echo $name;?>' data-passed='' value='<?php echo $value;?>' <?php echo $key=='count'? 'data-cap="'.$cap.'"':'';?> <?php echo ((!$prefill_edittable && !empty($value) && $key=='email')?'readonly="readonly"':'');?>/>
					</div>
				<?php
				endif;
			}

			// Additional Guest Names
			if($active_fields && in_array('names', $active_fields)):
				$_field_names = $evors->lang('evoRSL_010b','List Full Name of Other Guests');
				$count = !empty($rpmv['count'])? (int)$rpmv['count'][0]:1;
				$names = !empty($rpmv['names'])? unserialize($rpmv['names'][0]):false;
				// /print_r($names);

		?>
			<div class="form_row names form_guest_names hide_no" style='display:<?php echo ($count>1)?'':'none';?>'>
				<label><?php echo $_field_names;?></label>
				<div class='form_row_inner form_guest_names_list'>
					<?php for($x=0; $x< ($count-1); $x++):
						$name = ($names && isset($names[$x] ))? $names[$x]:'';
					?>
					<input class='regular input <?php echo $x;?>' name='names[]' type="text" value='<?php echo $name;?>'/>
					<?php endfor;?>
				</div>
			</div>
		<?php  endif;?>

			<?php
			// ADDITIONAL FIELDS
				for($x=1; $x <= $frontend->addFields; $x++){
					// if fields is activated and name of the field is not empty
					if(evo_settings_val('evors_addf'.$x, $optRS) && !empty($optRS['evors_addf'.$x.'_1'])){

						$required = evo_settings_check_yn($optRS , 'evors_addf'.$x.'_3')? 'req':null;

						$FIELDTYPE = (!empty($optRS['evors_addf'.$x.'_2']) || (!empty($optRS['evors_addf'.$x.'_2']) && $optRS['evors_addf'.$x.'_2']=='dropdown' && !empty($optRS['evors_addf'.$x.'_4']))
							)? 	$optRS['evors_addf'.$x.'_2']:'text';

						$value = !empty($rpmv['evors_addf'.$x.'_1'])? $rpmv['evors_addf'.$x.'_1'][0]:'';
						$placeholder = !empty($optRS['evors_addf'.$x.'_1'])? $optRS['evors_addf'.$x.'_1']: '';
						$FIELDNAME = !empty($optRS['evors_addf'.$x.'_1'])? $optRS['evors_addf'.$x.'_1']: 'field';

						// Label
						$asterix = $required? '<abbr class="required" title="required">*</abbr>':'';
						$label_content = '<label for="'.'evors_addf'.$x.'_1'.'">'.$FIELDNAME . $asterix .'</label>';

					?>
						<div class="form_row additional_field hide_no">

					<?php
						switch($FIELDTYPE){
							case 'text':
								?><p class='inputfield'>
								<?php echo $label_content;?>
								<input title='<?php echo $FIELDNAME;?>' placeholder='<?php echo $placeholder;?>' class='regular input <?php echo $required;?>' name='<?php echo 'evors_addf'.$x.'_1';?>'type="text" value='<?php echo $value;?>'/><?php
							break;
							case 'html':
								?><p><?php echo $FIELDNAME;?></p><?php
							break;
							case 'textarea':
								?><p><?php echo $label_content;?>
								<textarea title='<?php echo $FIELDNAME;?>' placeholder='<?php echo $placeholder;?>' class='regular input <?php echo $required;?>' name='<?php echo 'evors_addf'.$x.'_1';?>'><?php echo $value;?></textarea></p><?php
							break;
							case 'dropdown':
								?><p>
									<?php echo $label_content;?>
									<select name='<?php echo 'evors_addf'.$x.'_1';?>' class='input dropdown'>
									<?php
										global $eventon_rs;
										$OPTIONS = $frontend->get_additional_field_options($optRS['evors_addf'.$x.'_4']);
										foreach($OPTIONS as $slug=>$option){
											$selected = (!empty($value) && $value == $slug)? 'selected="selected"':'';
											echo "<option value='{$slug}' {$selected}>{$option}</option>";
										}
									?>
									</select>
								</p><?php
							break;
							case has_action("evors_additional_field_{$FIELDTYPE}"):
								do_action("evors_additional_field_{$FIELDTYPE}", $value, $FIELDNAME, $required);
							break;
						}
					?>

						</div>
					<?php
					}
				}
			?>
			<?php
				// additional notes field for NO option
				if($active_fields && in_array('additional', $active_fields)):
					$_text_additional = $evors->lang('evoRSL_010a','Additional Notes');
					$value = !empty($rpmv['additional_notes'])? $rpmv['additional_notes'][0]:'';
			?>
				<div class="form_row additional_note hide_no" >
					<label><?php echo $_text_additional;?></label>
					<textarea class='input' name='additional_notes' type="text" placeholder='<?php echo $_text_additional;?>'><?php echo $value;?></textarea>
				</div>
			<?php endif;?>
			<?php
				if($active_fields && in_array('captcha', $active_fields)):

				// validation calculations
				$cals = array(	0=>'3+8', '5-2', '4+2', '6-3', '7+1'	);
				$rr = rand(0, 4);
				$calc = $cals[$rr];
			?>

			<div class="form_row captcha">
				<p><?php echo $evors->lang( 'evoRSL_011a','Verify you are a human');?></p>
				<p><?php echo $calc;?> = <input type="text" data-cal='<?php echo $rr;?>' class='regular_a captcha'/></p>
			</div>
			<?php endif;?>
			<?php if($active_fields && in_array('updates', $active_fields)):
				$checked = (!empty($rpmv['updates']) && $rpmv['updates'][0]=='yes')? 'checked="checked"':'';
			?>
			<div class="form_row updates">
				<input type="checkbox" name='updates' value='yes' <?php echo $checked;?>/> <label><?php echo $evors->lang( 'evoRSL_011','Receive updates about event');?></label>
			</div>
			<?php endif;?>
			<div class="form_row">
				<a id='submit_rsvp_form' class='evors_submit_rsvpform_btn evcal_btn evors_submit'><?php echo $evors->lang( 'evoRSL_012','Submit');?></a>
				<!-- form terms & conditions -->
				<?php
					if(!empty($optRS['evors_terms']) && $optRS['evors_terms']=='yes' && !empty($optRS['evors_terms_link']) ){
						echo "<p class='terms' style='padding-top:10px'><a href='".$optRS['evors_terms_link']."' target='_blank'>".$evors->lang( 'evoRSL_tnc','Terms & Conditions')."</a></p>";
					}
				?>
			</div>
			<?php do_action('evors_after_form');?>
		</div>
	<!-- submission_form-->
	</form>
	<?php $this->form_footer($evors->l );?>
</div>
<?php
		return ob_get_clean();
	}

// Find RSVP form
	function find_rsvp_form($args=''){
		global $eventon_rs;
		$front = $eventon_rs->frontend;

		// set Lang
			if(!empty($args['lang'])) $eventon_rs->l = $args['lang'];

		ob_start();

		if($args['incard']=='yes')		echo "<a class='evors_incard_close'></a>";

		?>
	<div id='evorsvp_form' class='evors_forms'>
	<div class='find_rsvp_to_change form_section'>
	<form class='evors_findrsvp_form' method="POST" action="" enctype="multipart/form-data">
		<?php 	wp_nonce_field( AJDE_EVCAL_BASENAME, 'evors_nonce' );	?>
		<?php
			if(!empty($args) && is_array($args)){
				foreach($args as $key=>$val){
					if(empty($val)) continue;
					echo "<input type='hidden' name='{$key}' value='{$val}'/>";
				}
			}
		?>

		<h3 class="form_header"><?php echo $front->replace_en( $eventon_rs->lang('evoRSL_x3','Find my RSVP for [event-name]'), get_the_title($args['e_id']));?></h3>
		<div class="form_row">
			<?php /*<input class='name input req' name='first_name' type="text" placeholder=' <?php echo $_field_fname;?>'/>
			<input class='name input req' name='last_name' type="text" placeholder=' <?php echo $_field_lname;?>'/>*/?>
			<input class='regular input req' name='email' type="text" placeholder='<?php echo $eventon_rs->lang( 'evoRSL_009','Email Address');?>' value=''/>
		</div>
		<?php
		/*
		<div class="form_row">
			<input class='regular input req' name='rsvpid' type="text" placeholder='<?php echo $front->lang( 'evoRSL_007a','RSVP ID');?>' value=''/>
		</div>
		*/?>
		<div class="form_row evors_find_action">
			<p><i><?php echo $eventon_rs->lang( 'evoRSL_x1','We have to look up your RSVP in order to change it!');?></i></p>
			<a id='change_rsvp_form' class='evors_findrsvp_form_btn evcal_btn evors_submit'><?php echo $eventon_rs->lang( 'evoRSL_012y','Find my RSVP');?></a>
		</div>
		<?php $this->form_footer($eventon_rs->l);?>
	</form>
	</div>
	</div>
		<?php
		return ob_get_clean();
	}

// Success message content
	function form_message($rsvpid, $type='submit'){
		global $eventon_rs;

		$front = $eventon_rs->frontend;
		$rpmv = get_post_custom($rsvpid);
		$optRS = $front->optRS;
		$active_fields =(!empty($optRS['evors_ffields']))?$optRS['evors_ffields']:false;
		$eventName = get_the_title($rpmv['e_id'][0]);

		ob_start();
		?>
	<div id='evorsvp_form' class='evors_forms'>
	<div class='rsvp_confirmation form_section' data-rsvpid='<?php echo $rsvpid;?>'>
		<b></b>
		<?php if($type=='submit'):?>
			<h3 class="form_header submit"><?php echo $front->replace_en( $eventon_rs->lang( 'evoRSL_x5','Successfully RSVP-ed for [event-name]'), $eventName );?></h3>
		<?php else:?>
			<h3 class="form_header update"><?php echo $front->replace_en($eventon_rs->lang( 'evoRSL_x4','Successfully updated RSVP for [event-name]'), get_the_title($rpmv['e_id'][0]) );?></h3>
		<?php endif;?>
		<p><?php echo $eventon_rs->lang( 'evoRSL_x7','Thank You');?> <span class='name'></span></p>
		<?php

		// Sucess message body content based on RSVP status
		// YES
		if($rpmv['rsvp'][0]=='y'){
			if($active_fields && in_array('count', $active_fields) && !empty($rpmv['count']) ){

				$_txt_reseverd = str_replace('[spaces]',
					"<span class='spots'>".($rpmv['count'][0])."</span>",
					$eventon_rs->lang( 'evoRSL_x6','You have reserved [spaces] space(s) for [event-name]')
				);
				$_txt_reseverd = $front->replace_en($_txt_reseverd, $eventName);
				echo "<p class='coming'>{$_txt_reseverd}</p>";
			}

			// check whether confirmation emails are disabled
			if( !evo_settings_check_yn($optRS, 'evors_disable_emails')){
				$_txt_emails = str_replace('[email]',
					"<span class='email'>".(!empty($rpmv['email'])? $rpmv['email'][0]:'' )."</span>",
					$eventon_rs->lang( 'evoRSL_x8','We have emailed you a confirmation to [email]')
				);
				echo "<p class='coming'>{$_txt_emails}</p>";
				echo "<br><a class='ics-link evcal_btn' href='../'>Add to Calendar</a>";
			}

		}elseif($rpmv['rsvp'][0]=='n'){
			echo "<p class='notcoming'>".evo_lang('Sorry to hear you are not coming', EVORS()->l)."</p>";
		}else{}


		// get data string
		$datastring = $front->event_rsvp_data(
			$rpmv['e_id'][0],
			(!empty($rpmv['repeat_interval'])?$rpmv['repeat_interval'][0]:0),
			'',
			true
		);

		?>
		<div class="form_row" style='padding-top:10px' data-rsvpid='<?php echo $rsvpid;?>'
		<?php echo $datastring;?>>
			<a id='call_change_rsvp_form' class='evcal_btn evors_submit'><?php echo $eventon_rs->lang('evoRSL_012x','Change my RSVP');?></a>
		</div>
	</div>
	</div>
		<?php
		return ob_get_clean();
	}

// Return the guest list after a user has rsvped so the list on event card can be updated with new information
	function get_form_guestlist($e_id, $post){
		if(empty($e_id)) return false;

		$epmv = get_post_custom($e_id);
		global $eventon_rs;

		if(!$eventon_rs->frontend->functions->show_whoscoming($epmv)) return false;

		$repeat_interval = !empty($post['repeat_interval']) ? $post['repeat_interval']:0;
		$attendee_icons = $eventon_rs->frontend->GET_attendees_icons($e_id, $repeat_interval);
		if(!$attendee_icons) return false;

		$newCount = $eventon_rs->frontend->functions->get_rsvp_count($epmv,'y',$repeat_interval);

		return array(
			'guestlist'=>"<em class='tooltip'></em>".$attendee_icons,
			'newcount'=>$newCount
		);
	}

	function form_footer($lang){
		global $eventon_rs;
		echo '<div class="form_row notification" style="display:none"><p></p></div>';
		echo $eventon_rs->frontend->get_form_msg($lang);
	}
}
