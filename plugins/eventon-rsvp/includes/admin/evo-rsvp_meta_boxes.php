<?php
/**
 * Meta boxes for evo-rsvp
 *
 * @author 		AJDE
 * @category 	Admin
 * @package 	EventON/Admin/evo-rsvp
 * @version     0.2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class evors_meta_boxes{
	public function __construct(){
		add_action( 'add_meta_boxes', array($this, 'evoRS_meta_boxes') );
		add_action( 'eventon_save_meta', array($this, 'evoRS_save_meta_data'), 10 , 2 );
		add_action( 'save_post', array($this, 'evoRS_save_rsvp_meta_data'), 1 , 2 );

		// Debug reminders
		//$fnc = new evorm_fnc();
		//$result = $fnc->send_email(13, 'evorm_pre_1');

	}
	// Initiate
		function evoRS_meta_boxes(){
			add_meta_box('evors_mb1',__('RSVP Event','eventon'), array($this, 'evors_metabox_content'),'ajde_events', 'normal', 'high');
			add_meta_box('evors_mb1',__('RSVP Event','eventon'), array($this, 'evoRS_metabox_rsvp'),'evo-rsvp', 'normal', 'high');
			add_meta_box('evors_mb2',__('RSVP Notifications','eventon'), array($this, 'evoRS_notifications_box'),'evo-rsvp', 'side', 'default');
			add_meta_box('evors_mb3',__('RSVP Email','eventon'), array($this, 'evoRS_notifications_box2'),'evo-rsvp', 'side', 'default');
			
			do_action('evoRS_add_meta_boxes'); // pluggable function
		}	
	
// notification email box
	function evoRS_notifications_box(){
		global $post;
		?>
		<div class='evoRS_resend_conf'>
			<div class='evoRS_rc_in'>
				<p><i><?php _e('You can re-send the RSVP confirmation email to submitter if they have not received it. Make sure to check spam folder.','eventon');?></i></p>
				<a id='evoRS_resend_email' class='button' data-rsvpid='<?php echo $post->ID;?>'><?php _e('Re-send Confirmation Email','eventon');?></a>
				<p class='message' style='display:none'><?php _e('Email resend action performed!','eventon');?></p>
			</div>
		</div>
		<?php
	}
	function evoRS_notifications_box2(){
		global $post;
		?>
		<div class='evoRS_resend_conf'>
			<div class='evoRS_rc_in'>
				<p><i><?php _e('Send RSVP confirmation email to other email addresses using below fields. <br/>NOTE: you can send to multiple email address separated by commas.','eventon');?></i></p>
				<p class='field'><input type='text' placeholder='Comma separated email addresses' style="width:100%" /></p>
				<a id='evoRS_custom_email' class='button' data-rsvpid='<?php echo $post->ID;?>' data-empty='<?php _e('Email field can not be empty!','eventon');?>' ><?php _e('Send Email','eventon');?></a>
				<p class='message' style='display:none'><?php _e('Email send action performed!','eventon');?></p>
			</div>
		</div>
		<?php
	}

// META box for evo-rsvp post page
	function evoRS_metabox_rsvp(){
		global $post, $eventon_rs, $ajde, $pagenow;
		$pmv = get_post_meta($post->ID);
		$optRS = $eventon_rs->evors_opt;

		//print_r($pmv);

		//$what = $eventon_rs->frontend->send_email(array(
		//	'e_id'=>1335,
		//), 'digest');

		// Debug email templates
			$show_debug_email = false;
			if($show_debug_email):
				$tt = $eventon_rs->email->_get_email_body(array(
					'e_id'=>$pmv['e_id'][0],
					'rsvp'=>'y',
					'count'=>'1',
					'first_name'=>'Jason','last_name'=>'Miller',
					'rsvp_id'=>$post->ID,
					'email'=>'test@msn.com'), 'confirmation_email');
				print_r($tt);
			endif;
		
		// get translated check-in status
			$_checkinST = (!empty($pmv['status']) && $pmv['status'][0]=='checked')?
				'checked':'check-in';
			$checkin_status = $eventon_rs->frontend->get_checkin_status($_checkinST);			
			wp_nonce_field( plugin_basename( __FILE__ ), 'evorsvp_nonce' );
		?>	
		<div class='eventon_mb' style='margin:-6px -12px -12px'>
		<div style='background-color:#ECECEC; padding:15px;'>
			<div style='background-color:#fff; border-radius:8px;'>

			<table id='evors_rsvp_tb' width='100%' class='evo_metatable'>	

			<?php
				$table_rows = array(
					'rsvp_id'=>array(
						'type'=>'normal',
						'name'=> __('RSVP #','eventon'),
						'value'=>$post->ID
					),
					'rsvp'=>array(
						'type'=> 'select',
						'name'=> __('RSVP Status','eventon'),
						'options'=> $eventon_rs->rsvp_array_,
					),
					'checkin_status'=>array(
						'type'=>'checkin_status',
					),
					'first_name'=>array(
						'type'=>'normal',
						'name'=> __('First Name','eventon'),
						'required'=>true,
						'editable'=>true,
						'value'=> (!empty($pmv['first_name']) ? $pmv['first_name'][0]:'')
					),'last_name'=>array(
						'type'=>'normal',
						'name'=> __('Last Name','eventon'),
						'required'=>true,
						'editable'=>true,
						'value'=> (!empty($pmv['last_name']) ? $pmv['last_name'][0]:'')
					),'email'=>array(
						'type'=>'normal',
						'name'=> __('Email Address','eventon'),
						'required'=>true,
						'editable'=>true,
						'value'=> (!empty($pmv['email']) ? $pmv['email'][0]:'')
					),'count'=>array(
						'type'=>'normal',
						'name'=> __('Count','eventon'),
						'editable'=>true,
						'value'=> (!empty($pmv['count']) ? $pmv['count'][0]:'')
					),'phone'=>array(
						'type'=>'normal',
						'name'=> __('Phone','eventon'),
						'editable'=>true,
						'value'=> (!empty($pmv['phone']) ? $pmv['phone'][0]:'')
					),
				);

				foreach($table_rows as $key=>$data){
					switch($data['type']){
						case 'normal':
							echo "<tr><td>". $data['name'] .":". (!empty($data['required']) && $data['required']? '*':'') ." </td><td>";
							if(!empty($data['editable']) && $data['editable']){
								echo "<input type='text' name='{$key}' value='".$data['value']."'/>";
							}else{
								echo $data['value'];
							}
							echo "</td></tr>";
						break;
						case 'select':
							?><tr><td><?php echo $data['name'];?>: </td>
							<td><select name='<?php echo $key;?>'>
							<?php 
								foreach($data['options'] as $rsvpOptions=>$rsvpV){
									echo "<option ".( !empty($pmv[$key]) && $rsvpOptions==$pmv[$key][0]? 'selected="selected"':'')." value='{$rsvpOptions}'>{$rsvpV}</option>";
								}
							?>
							</select>
							</td></tr>
							<?php
						break;
						case 'checkin_status':
							?>
							<tr><td><?php _e('Checkin to Event Status','eventon');?>: </td><td><span class='rsvp_ch_st <?php echo $_checkinST;?>' data-status='<?php echo $_checkinST;?>' data-nonce="<?php echo wp_create_nonce(AJDE_EVCAL_BASENAME);?>" data-rsvpid='<?php echo $post->ID;?>'><?php echo $checkin_status;?></span>
							</td></tr>
							<?php 
						break;
					}
				}
			?>
				
				<tr><td><?php _e('Receive Email Updates','eventon');?>: </td>
					<td><?php echo $ajde->wp_admin->html_yesnobtn(array(
						'id'=>'updates','input'=>true,
						'default'=>((!empty($pmv['updates']) && $pmv['updates'][0]=='yes')? 'yes':'no' )
					));?></td></tr>
				<tr><td><?php _e('Event','eventon');?>: </td>
					<td><?php 
						// event for rsvp
						if(empty($pmv['e_id'])){
							$events = get_posts(array('posts_per_page'=>-1, 'post_type'=>'ajde_events'));
							if($events && count($events)>0 ){
								echo "<select name='e_id'>";
								foreach($events as $event){
									echo "<option value='".$event->ID."'>".get_the_title($event->ID)."</option>";
								}
								echo "</select>";
							}
							wp_reset_postdata();
						}else{
							echo '<a href="'.get_edit_post_link($pmv['e_id'][0]).'">'.get_the_title($pmv['e_id'][0]).'</a></td></tr>';
						}

				// REPEATING interval
				if($pagenow!='post-new.php' && !empty($pmv['e_id'])){

					$saved_ri = (!empty($pmv['repeat_interval']) && $pmv['repeat_interval'][0]!='0')?
						$pmv['repeat_interval'][0]:'0';
					$event_pmv = get_post_custom($pmv['e_id'][0]);
					$datetime = new evo_datetime();
					?>
					<tr><td><?php _e('Event Date','eventon');?>: </td>
					<td>
					<?php 
					$repeatIntervals = (!empty($event_pmv['repeat_intervals'])? unserialize($event_pmv['repeat_intervals'][0]): false);

					// If the event has repeating instances
					if($repeatIntervals && count($repeatIntervals)>0){
								
						echo "<select name='repeat_interval'>";
						$x=0;
						$wp_date_format = get_option('date_format');
						foreach($repeatIntervals as $interval){
							$time = $datetime->get_int_correct_event_time($event_pmv,$x);
							echo "<option value='".$x."' ".( $saved_ri == $x?'selected="selected"':'').">".date($wp_date_format.' h:i:a',$time)."</option>"; $x++;
						}
						echo "</select>";
					}else{
					// not a repeating event
						$time = $datetime->get_correct_event_repeat_time( $event_pmv,$saved_ri);
						echo $datetime->get_formatted_smart_time($time['start'], $time['end'], $event_pmv);
					}
					?></td></tr>
					<?php
				}

				// additional fields
				for($x=1; $x<=$eventon_rs->frontend->addFields; $x++){
					// if fields is activated and name of the field is not empty
					if(evo_settings_val('evors_addf'.$x, $optRS) && !empty($optRS['evors_addf'.$x.'_1'])){
						$FIELDTYPE = !empty($optRS['evors_addf'.$x.'_2'])? $optRS['evors_addf'.$x.'_2']:'text';
						$FIELDNAME = !empty($optRS['evors_addf'.$x.'_1'])? $optRS['evors_addf'.$x.'_1']:'Field';
						$FIELDVAL = (!empty($pmv['evors_addf'.$x.'_1']))? $pmv['evors_addf'.$x.'_1'][0]: '-';

						echo "<tr>";

						switch ($FIELDTYPE) {
							case 'text':
								echo "<td>".$FIELDNAME."</td>
								<td><input type='text' name='evors_addf".$x."_1' value='".$FIELDVAL."'/></td>";
								break;	
							case 'html':
								echo "<td>".$FIELDNAME."</td>
								<td>".$FIELDVAL."</td>";
								break;	
							case 'textarea':
								echo "<td>".$FIELDNAME."</td>
								<td><textarea style='width:100%' name='evors_addf".$x."_1'>".$FIELDVAL."</textarea></td>";
								break;
							case 'dropdown':
								echo "<td>".$FIELDNAME."</td>
								<td><select name='evors_addf{$x}_1'>";
									$OPTIONS = $eventon_rs->frontend->get_additional_field_options($optRS['evors_addf'.$x.'_4']);
									foreach($OPTIONS as $slug=>$options ){
										echo "<option ".(!empty($pmv['evors_addf'.$x.'_1']) && $slug==$pmv['evors_addf'.$x.'_1'][0]?'selected="selected"':'')." value='{$slug}'>{$options}</option>";
									}
								echo "</select></td>";
								break;
							case has_action("evors_additional_field_evorsvp_cpt_{$FIELDTYPE}"):		
								do_action("evors_additional_field_evorsvp_cpt_{$FIELDTYPE}", $FIELDNAME, $FIELDVAL);
							break;
						}
						echo "</tr>";
					}
				}?>

				<?php
				// addional guest names
				$names = !empty($pmv['names'])? unserialize($pmv['names'][0]): false;
				if($names){
					echo "<tr><td>".__('Other Attendee Names','eventon').": </td><td>";
					foreach($names as $name){
						echo "<p>".$name . "</p>";
					}
					echo "</td></tr>";
				}
				?>
				
				<tr><td><?php _e('Additional Notes','eventon');?>: </td>
					<td><textarea style='width:100%' type='text' name='additional_notes'><?php echo !empty($pmv['additional_notes'])?$pmv['additional_notes'][0]:'';?></textarea></td></tr>

				<?php
				// plugabble hook
				if(!empty($pmv['e_id']))	do_action('eventonrs_rsvp_post_table',$post->ID, $pmv);
				?>
			</table>
			</div>
		</div>
		</div>
		<?php
	}

// SAVE values for evo-rsvp post 
	function evoRS_save_rsvp_meta_data($post_id, $post){
		if($post->post_type!='evo-rsvp')
			return;
			
		// Stop WP from clearing custom fields on autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)	return;

		// Prevent quick edit from clearing custom fields
		if (defined('DOING_AJAX') && DOING_AJAX)	return;
		
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if( isset($_POST['evorsvp_nonce']) && !wp_verify_nonce( $_POST['evorsvp_nonce'], plugin_basename( __FILE__ ) ) ){
			return;
		}

		// Check permissions
		if ( !current_user_can( 'edit_post', $post_id ) )	return;	

		global $pagenow;
		$_allowed = array( 'post-new.php', 'post.php' );
		if(!in_array($pagenow, $_allowed)) return;
		
		global $eventon_rs;

		$fields = array(
			'count', 'first_name','last_name','email','phone',
			'rsvp','updates','e_id','repeat_interval',
			'additional_notes'
		);

		// Append additional fields
			for($x = 1; $x <= $eventon_rs->frontend->addFields; $x++){
				$fields[]= 'evors_addf'.$x.'_1';
			}

		// Pluggable action to perform for special fields
			do_action('evors_save_other_metadata', $post_id);

		// Foreach field save data
			foreach($fields as $field){
				if(!empty($_POST[$field])){
					update_post_meta( $post_id, $field, $_POST[$field] );
				}else{
					if($field!='e_id')
						delete_post_meta($post_id, $field);
				}
			}

		// sync event rsvp count
			global $eventon_rs;
			if(!empty($_POST['e_id'])){
				$eventon_rs->frontend->functions->sync_rsvp_count($_POST['e_id']);
			}
	}

// RSVP meta box for EVENT posts
	function evors_metabox_content(){

		global $post, $eventon_rs, $eventon, $ajde;

		$optRS = $eventon_rs->evors_opt;
		
		$eventID = $post->ID;
		$pmv = get_post_meta($eventID);

		wp_nonce_field( plugin_basename( __FILE__ ), 'evors_nonce' );

		ob_start();

		$evors_rsvp = (!empty($pmv['evors_rsvp']))? $pmv['evors_rsvp'][0]:null;
		$evors_show_rsvp = (!empty($pmv['evors_show_rsvp']))? $pmv['evors_show_rsvp'][0]:null;
		$evors_show_whos_coming = (!empty($pmv['evors_show_whos_coming']))? $pmv['evors_show_whos_coming'][0]:null;
		$evors_add_emails = (!empty($pmv['evors_add_emails']))? $pmv['evors_add_emails'][0]:null;
		?>
		<div class='eventon_mb'>
		<div class="evors">
			<p class='yesno_leg_line ' style='padding:10px'>
				<?php echo eventon_html_yesnobtn(array('var'=>$evors_rsvp, 'attr'=>array('afterstatement'=>'evors_details'))); ?>
				<input type='hidden' name='evors_rsvp' value="<?php echo ($evors_rsvp=='yes')?'yes':'no';?>"/>
				<label for='evors_rsvp'><?php _e('Allow visitors to RSVP to this event')?></label>
			</p>
			<div id='evors_details' class='evors_details evomb_body ' <?php echo ( $evors_rsvp=='yes')? null:'style="display:none"'; ?>>		
				<div class="evors_stats" style='padding-top:5px'>			
				<?php
					// initial values
					$synced = $eventon_rs->frontend->functions->total_rsvp_counts($pmv);
					$evors_capacity_count = (!empty($pmv['evors_capacity_count']))? $pmv['evors_capacity_count'][0]:null; 

				?>
					<p><b><?php echo $synced['y']; ?></b><span><?php _e('YES','eventon');?></span></p>
					<p><b><?php echo $synced['m'];?></b><span><?php _e('Maybe','eventon');?></span></p>
					<p><b><?php echo $synced['n'];?></b><span><?php _e('No','eventon');?></span></p>
					<div class='clear'></div>
				</div>
				<?php if(!empty($evors_capacity_count)):?>
					<div class='evors_stats_bar'>
						<p><span class='yes' style='width:<?php echo (int)(($synced['y']/$evors_capacity_count)*100);?>%'></span><span class='maybe' style='width:<?php echo (int)(($synced['m']/$evors_capacity_count)*100);?>%'></span><span class='no' style='width:<?php echo (int)(($synced['n']/$evors_capacity_count)*100);?>%'></span></p>
					</div>
				<?php endif;?>
				<div class='evo_negative_25'>
				<table width='100%' class='eventon_settings_table'>

					<!-- Set Capacity Limit -->
						<tr><td colspan='2'>			
							<?php $evors_capacity = (!empty($pmv['evors_capacity']))? $pmv['evors_capacity'][0]:null;?>
							<?php $this->html_yesno_fields('evors_capacity',
								'Set capacity limit for RSVP',$pmv, 
								__('Activating this will allow you to add a limit to how many RSVPs you can receive. When the limit is reached RSVP will close.','eventon'), 
								'evors_capacity_row');?>
						</td></tr>
					
					<?php 					
						$evors_capacity_show = (!empty($pmv['evors_capacity_show']))? $pmv['evors_capacity_show'][0]:null; 
					?>
					<tr class='evors_capacity_row yesnosub' style='display:<?php echo ($evors_capacity=='yes')?'':'none';?>'>
						<td><?php _e('Total Event RSVP capacity','eventon'); ?><?php echo $eventon->throw_guide('This is the maximum capacity of the event including current attendees');?></td>
						<td><input type='text' id='evors_capacity_count' name='evors_capacity_count' value="<?php echo $evors_capacity_count;?>"/></td>
					</tr>

					<?php
					// manange RSVP capacity separate for repeating events
						if(!empty($pmv['evcal_repeat']) && $pmv['evcal_repeat'][0]=='yes'):
							$manage_repeat_cap = evo_meta_yesno($pmv,'_manage_repeat_cap_rs','yes','yes','no' );
					?>
					<tr class='evors_capacity_row yesnosub' style='display:<?php echo ($evors_capacity=='yes')?'':'none';?>'><td colspan='2'>
						<?php $this->html_yesno_fields('_manage_repeat_cap_rs',
							'Manage available capacity separate for each repeating interval of this event',$pmv, 
							'Once repeating event capacities are set the total capacity for event will be overridden. If you just made event repeat, this event need to be updated for repeat options to show up.',
							'evors_ri_cap',
							'evors_mcap',''
							);?>
						
					<?php 
						$repeat_intervals = !empty($pmv['repeat_intervals'])? unserialize($pmv['repeat_intervals'][0]): false;
					?>
						<div id='evors_ri_cap' class='evotx_repeat_capacity' style='padding-top:15px; padding-bottom:20px;display:<?php echo evo_meta_yesno($pmv,'_manage_repeat_cap_rs','yes','','none' );?>'>
							<p><em style='opacity:0.6'><?php _e('NOTE: The capacity above should match the total number of capacity for each repeat occurance below for this event.','eventon');?></em></p>
							<?php
								// if repeat intervals set 
								if($repeat_intervals && count($repeat_intervals)>0){
									$count =0;

									// get saved capacities for repeats
									$ri_capacity_rs = !empty($pmv['ri_capacity_rs'])?
										unserialize($pmv['ri_capacity_rs'][0]): false;

									echo "<div class='evotx_ri_cap_inputs'>";
									// for each repeat interval
									$evcal_opt1 = get_option('evcal_options_evcal_1');
									
									foreach($repeat_intervals as $interval){
										$date_format = eventon_get_timeNdate_format($evcal_opt1);
										$TIME = eventon_get_editevent_kaalaya($interval[0], $date_format[1], $date_format[2]);
										$ri_open_count = ($ri_capacity_rs && !empty($ri_capacity_rs[$count]))? $ri_capacity_rs[$count]:'0';

										$remainCount = $eventon_rs->frontend->functions->get_ri_remaining_count('y', $count,$ri_open_count,$pmv);
										echo "<p class='".($count>10?'hidden':'')."'><input type='text' name='ri_capacity_rs[]' value='". ($ri_open_count) . "'/><span>" . $TIME[0] . "<br/><em>Remaining <i class='rem_{$remainCount}'>".$remainCount."</i></em></span></p>";
										$count++;
									}
									
									echo "<div class='clear'></div>";
									echo $count>10? "<p class='evors_repeats_showrest'>".__('Show Rest','eventon')."</p>":'';
									echo "</div>";
									echo (count($repeat_intervals)>5)? 
										"<p class='evotx_ri_view_more'><a class='button_evo'>Click here</a> to view the rest of repeat occurances.</p>":null;
								}
							?>
						</div>
					</td></tr>
					<?php endif;?>
					
					<!-- available space -->
						<tr class='evors_capacity_row yesnosub' style='display:<?php echo ($evors_capacity=='yes')?'':'none';?>'><td colspan='2'>
							<?php $this->html_yesno_fields(
								'evors_capacity_show',
								__('Show remaining spaces count on front-end','eventon'),$pmv
							);?>	
						</td></tr>

						
					<tr><td colspan='2'>
						<?php $this->html_yesno_fields('evors_show_rsvp',
							__('Show RSVP count for the event on EventCard','eventon'), $pmv, 
							__('This will show how many guests are coming for each RSVP option as a number next to it on eventcard.','eventon')
						);?>
					</td></tr>
					<!-- show whos coming to the event -->
					<?php
						$evors_whoscoming_after = (!empty($pmv['evors_whoscoming_after']))? $pmv['evors_whoscoming_after'][0]:null;
					?>
					<tr><td colspan='2'>
						<?php $this->html_yesno_fields('evors_show_whos_coming', 
							__('Show guest list to event (on eventCard)','eventon'), 
							$pmv, '','evors_whoscoming_after');?>						
					</td></tr>
						<tr class='evors_whoscoming_after yesnosub' style='display:<?php echo ($evors_show_whos_coming=='yes')?'':'none';?>'>
							<td colspan='2'>
								<?php $this->html_yesno_fields('evors_whoscoming_after',
								__('Show guest list ONLY after RSVP-ing to event','eventon'),$pmv,
								__('This will allow only guests that have RSVP-ed to the event see the guest list.','eventon')
								);?>
							</td>
						</tr>

					<!-- show whos NOT coming to the event -->
					<?php
						$_evors_whosnotcoming_after = (!empty($pmv['_evors_whosnotcoming_after']))? $pmv['_evors_whosnotcoming_after'][0]:null;
					?>
					<tr><td colspan='2'>
						<?php $this->html_yesno_fields('_evors_show_whos_notcoming', 
							__('Show list of guests who are NOT coming to the event (on eventCard)','eventon'), 
							$pmv, '','_evors_whosnotcoming_after');?>						
					</td></tr>
						<tr class='_evors_whosnotcoming_after yesnosub' style='display:<?php echo evo_check_yn($pmv,'_evors_show_whos_notcoming')?'':'none';?>'>
							<td colspan='2'>
								<?php $this->html_yesno_fields('_evors_whosnotcoming_after',
								__('Show list of guests who are NOT coming ONLY after RSVP-ing to event','eventon'),$pmv,
								__('This will allow only guests that have RSVP-ed to the event see the list of guests not coming to the event.','eventon')
								);?>
							</td>
						</tr>
					
					<!-- Limit Maximum cap per rsvp -->
						<tr><td colspan='2'>			
							<?php 
								$evors_max_active = (!empty($pmv['evors_max_active']))? $pmv['evors_max_active'][0]:'no';
								$evors_max_count = (!empty($pmv['evors_max_count']))? $pmv['evors_max_count'][0]:null;
							?>

							<p class="yesno_leg_line">
							<?php 
							echo $ajde->wp_admin->html_yesnobtn(array(
								'id'=>'evors_max_active',
								'input'=>true,
								'label'=> __('Limit maximum capacity count per each RSVP','eventon'),
								'var'=> $evors_max_active,
								'guide'=> __('This will allow you to limit each RSVP reservation count to a set max number, then the guests can not book more spaces than this limit','eventon'),
								'afterstatement'=>'evors_max_count_row',
								'attr'=> array('as_type'=>'class')
							));
							?>
							</p>							
						</td></tr>
							<tr class='evors_max_count_row yesnosub' style='display:<?php echo ($evors_max_active=='yes')?'':'none';?>'>
								<td><?php _e('Maximum count number','eventon'); ?></td>
								<td><input type='text' id='evors_max_count' name='evors_max_count' value="<?php echo $evors_max_count;?>"/></td>
							</tr>

					<!-- // Only registered users can RSVP -->
						<tr><td colspan='2'>
							<?php $this->html_yesno_fields('evors_only_loggedin',
								__('Allow only logged-in users to RSVP to this event','eventon'), $pmv);?>
						</td></tr>
					
					<!-- is happening -->
						<tr><td colspan='2'>			
							<?php 
								$evors_min_cap = evo_var_val($pmv, 'evors_min_cap');
								$evors_min_count = evo_var_val($pmv, 'evors_min_count');
							?>
							<?php $this->html_yesno_fields('evors_min_cap',
								__('Activate event happening minimum capacity','eventon'),$pmv,
								__('With this you can set a minimum capacity for this event, at which point the event will take place for certain.','eventon'),
								'evors_min_count_row');?>							
						</td></tr>
							<tr class='evors_min_count_row yesnosub' style='display:<?php echo ($evors_min_cap=='yes')?'':'none';?>'>
								<td><?php _e('Minimum capacity for event to happen','eventon'); ?></td>
								<td><input type='text' id='evors_min_count' name='evors_min_count' value="<?php echo $evors_min_count;?>"/></td>
							</tr>

					<!-- in card RSVP form -->
						<tr><td colspan='2'>			
							<?php 
								$incard_form = evo_var_val($pmv, '_evors_incard_form');
							?>
							<?php $this->html_yesno_fields(
								'_evors_incard_form',
								__('Show RSVP form within EventCard instead of lightbox','eventon'),$pmv,
								__('This will show RSVP form in the eventCard instead of showing the form as a lightbox. This value will be overridden by RSVP settings global value for inCard RSVP form.','eventon'),''
							);?>							
						</td></tr>


					<!-- close rsvp before X time -->
						<?php $evors_close_time = (!empty($pmv['evors_close_time']))? $pmv['evors_close_time'][0]:null;  ?>
						<tr>
							<td><?php _e('Close RSVP before event start (in minutes)','eventon');?><?php echo $eventon->throw_guide(__('Set how many minutes before the event start time to close RSVP form. Time must be in minutes. Leave blank to not close RSVP before event time.','eventon'));?></td>
							<td><input type='text' id='evors_close_time' name='evors_close_time' value="<?php echo $evors_close_time;?>" placeholder='eg. 45'/></td>
						</tr>

					<!-- additional information for rsvped -->
						<tr>
							<td colspan='2' style='padding-top:10px;'><?php _e('Additional Information only visible to loggedin RSVPed guests & in Confirmation Email','eventon');?><?php echo $eventon->throw_guide(__('Information entered in here will only be visible on front-end once user has RSVPed to the event.','eventon'));?>
							<br/><textarea style='width:100%; margin-top:10px' name='evors_additional_data'><?php echo (!empty($pmv['evors_additional_data']))? $pmv['evors_additional_data'][0]:null;?></textarea>
							</td>
						</tr>
					
					<?php
					// if notifications enabled show additional emails field
						if(!empty($optRS['evors_notif']) && $optRS['evors_notif']=='yes' ):?>
							<tr><td colspan='2'>
								<p style='padding:5px 0'>
									<label for='evors_add_emails' style='padding-bottom:8px; display:inline-block'><i><?php _e('Additional email addresses to receive email notifications for new RSVPs','eventon')?><?php echo $eventon->throw_guide(__("Set additional email addresses seperated by commas to receive email notifications upon new RSVP reciept",'eventon'), '',false)?></i></label>
									<input type='text' name='evors_add_emails' value="<?php echo $evors_add_emails;?>" style='width:100%' placeholder='eg. you@domain.com'/>			
								</p>
							</td></tr>
							<tr><td colspan='2'>
								<?php $this->html_yesno_fields(
									'evors_notify_event_author',
									__('Send email notifications to event author','eventon'),$pmv,
									__('Enabling this will send email notification upon new RSVPs to event author in addition to above email addresses and emails set up in RSVP settings','eventon')
									);
								?>	
							</td></tr>
						<?php endif;?>
					<!-- daily digest -->
						<tr><td colspan='2'>
							<?php $this->html_yesno_fields('evors_daily_digest',
								__('Receive daily digest for this event','eventon'),$pmv,
								__('This will send you daily email digest of RSVP information for this event. Email settings can be customized from RSVP settings. This is in BETA version','eventon'));?>							
						</td></tr>
					
					<?php
						// pluggable function for addons
						do_action('evors_event_metafields', $pmv, $eventID, $optRS);
					?>
					<tr><td colspan='2' style=''><p style='opacity:0.7'><i><?php _e('NOTE: All text strings that appear for RSVP section on eventcard can be editted via myEventon > languages','eventon');?></i></p></td></tr>
				</table>
				</div>
				<?php

				// INITIAL information for lightbox data section
				// @version 0.2
				// DOWNLOAD CSV link 
					$exportURL = add_query_arg(array(
					    'action' => 'the_ajax_evors_f3',
					    'e_id' => $eventID,     // cache buster
					), admin_url('admin-ajax.php'));

					// repeat event interval data
					$ri_count_active = $eventon_rs->frontend->functions->is_ri_count_active($pmv);
					$repeatIntervals = !empty($pmv['repeat_intervals'])? unserialize($pmv['repeat_intervals'][0]): false;
					$datetime = new evo_datetime();	$wp_date_format = get_option('date_format');	
				?>
				<div class='evcal_rep evors_info_actions'>				
					<div class='evcalr_1'>
					<p class='actions'>
						<a id='evors_VA' data-e_id='<?php echo $eventID;?>' data-riactive='<?php echo ($ri_count_active && $repeatIntervals)?'yes':'no';?>' data-popc='evors_lightbox' class='button_evo attendees ajde_popup_trig' ><?php _e('View Attendees','eventon');?></a> 
						<a class='button_evo download' href="<?php echo $exportURL;?>"><?php _e('Download (CSV)','eventon');?></a> 
						<a id='evors_SY' data-e_id='<?php echo $eventID;?>' class='button_evo sync' ><?php _e('Sync Count','eventon');?></a> 
						<a id='evors_EMAIL' data-e_id='<?php echo $eventID;?>' data-popc='evors_email_attendee' class='button_evo email ajde_popup_trig' ><?php _e('Emailing','eventon');?></a> 
						<a href='<?php echo get_admin_url('','/admin.php?page=eventon&tab=evcal_5');?>'class='button_evo troubleshoot ajde_popup_trig' title='<?php _e('Troubleshoot RSVP Addon','eventon');?>'><?php _e('Troubleshoot','eventon');?></a> 
					</p>
					
					<?php 
					// lightbox content for emailing section
						ob_start();?>
						<div id='evors_emailing' style=''>
							<p><label><?php _e('Select Emailing Option','eventon');?></label>
								<select name="" id="evors_emailing_options">
									<option value="someone"><?php _e('Email Attendees List to Someone','eventon');?></option>
									<option value="someonenot"><?php _e('Email Not-coming guest List to Someone','eventon');?></option>
									<option value="coming"><?php _e('Email to Only Attending Guests','eventon');?></option>
									<option value="notcoming"><?php _e('Email to Guests not Coming to Event','eventon');?></option>
									<option value="all"><?php _e('Email to All Rsvped Guests','eventon');?></option>
								</select>
							</p>
							<?php
								// if repeat interval count separatly						
								if($ri_count_active && $repeatIntervals ){
									if(count($repeatIntervals)>0){
										echo "<p><label>". __('Select Event Repeat Instance','eventon')."</label> ";
										echo "<select name='repeat_interval' id='evors_emailing_repeat_interval'>
											<option value='all'>".__('All','eventon')."</option>";																
										$x=0;								
										foreach($repeatIntervals as $interval){
											$time = $datetime->get_correct_formatted_event_repeat_time($pmv,$x, $wp_date_format);
											echo "<option value='".$x."'>".$time['start']."</option>"; $x++;
										}
										echo "</select>";
										echo $eventon->throw_guide("Select which instance of repeating events of this event you want to use for this emailing action.", '',false);
										echo "</p>";
									}
								}
							?>
							<p style='' class='text'><label for=""><?php _e('Email Addresses (separated by commas)','eventon');?></label><br/><input style='width:100%' type="text"></p>
							<p style='' class='subject'><label for=""><?php _e('Subject for email','eventon');?> *</label><br/><input style='width:100%' type="text"></p>
							<p style='' class='textarea'><label for=""><?php _e('Message for the email','eventon');?></label><br/>
								<textarea cols="30" rows="5" style='width:100%'></textarea></p>
							<p><a data-eid='<?php echo $eventID;?>' id="evors_email_submit" class='evo_admin_btn btn_prime'><?php _e('Send Email','eventon');?></a></p>
						</div>
					<?php $emailing_content = ob_get_clean();?>

					<?php
						// lightbox content for view attendees					
						if($repeatIntervals && $ri_count_active && count($repeatIntervals)>0):
						ob_start();?>
						<div id='evors_view_attendees'>
							<p style='text-align:center'><label><?php _e('Select Repeating Instance of Event','eventon');?></label> 
								<select name="" id="evors_event_repeatInstance">
									<option value="all"><?php _e('All Repeating Instances','eventon');?></option>
									<?php
									$x=0;								
									foreach($repeatIntervals as $interval){
										$time = $datetime->get_correct_formatted_event_repeat_time($pmv,$x, $wp_date_format);
										echo "<option value='".$x."'>".$time['start']."</option>"; $x++;
									}
									?>
								</select>
							</p>
							<p style='text-align:center'><a id='evors_VA_submit' data-e_id='<?php echo $eventID;?>' class='evo_admin_btn btn_prime' ><?php _e('Submit','eventon');?></a> </p>
						</div>
						<div id='evors_view_attendees_list'></div>
						<?php 
						$viewattendee_content = ob_get_clean();
						else:	
							$viewattendee_content = "<p class='evo_lightbox_loading'></p>";	
						endif;
					?>

					<p id='evors_message' style='display:none'></p>
					<?php 
						global $ajde;
						
						// Lightbox
						echo $ajde->wp_admin->lightbox_content(array(
							'class'=>'evors_lightbox', 
							'content'=>$viewattendee_content, 
							'title'=>__('View Attendee List','eventon')
							)
						);
						echo $ajde->wp_admin->lightbox_content(array(
							'class'=>'evors_email_attendee', 
							'content'=>$emailing_content, 
							'title'=>__('Email Attendee List','eventon'), 
							'type'=>'padded' )
						);
						echo $ajde->wp_admin->lightbox_content(array(
							'class'=>'evors_attendee_info_lightbox', 
							'content'=> __('Loading','eventon'), 
							'title'=>__('Attendee Information','eventon'),
							'width'=>'500'
							)
						);
					?>
					</div>
				</div>
			</div>
		</div>
		</div>
		<?php
		echo ob_get_clean();
	}

	// print out the yes no value HTML for meta box for RSVP
		function html_yesno_fields($var, $label, $pmv , $guide='', $afterstatement='', $id='', $as_type='class'){
			global $eventon;

			$val = (!empty($pmv[$var]))? $pmv[$var][0]:null;
			?>
			<p class='yesno_leg_line '>
				<?php echo eventon_html_yesnobtn(array('id'=>$id, 'var'=>$val, 'attr'=>array('afterstatement'=>$afterstatement,'as_type'=>$as_type)) ); ?>					
				<input type='hidden' name='<?php echo $var;?>' value="<?php echo ($val=='yes')?'yes':'no';?>"/>
				<label for='<?php echo $var;?>'><?php _e($label,'eventon')?><?php echo !empty($guide)? $eventon->throw_guide($guide, '',false):'';?></label>
			</p>
			<?php
		}

/** Save the menu data meta box. **/
	function evoRS_save_meta_data($arr, $post_id){
		$fields = apply_filters('evors_event_metafield_names', array(
			'evors_rsvp', 'evors_show_rsvp','evors_show_whos_coming','evors_whoscoming_after',
			'_evors_whosnotcoming_after','_evors_show_whos_notcoming',
			'evors_add_emails','evors_close_time',
			'evors_capacity','evors_capacity_count','evors_capacity_show',
			'_manage_repeat_cap_rs','evors_additional_data','evors_min_cap','evors_min_count',
			'_evors_incard_form',
			'evors_max_active','evors_max_count', 'evors_daily_digest','evors_notify_event_author',
			'evors_only_loggedin'
		), $post_id);

		foreach($fields as $field){
			if(!empty($_POST[$field])){
				update_post_meta( $post_id, $field, $_POST[$field] );
			}else{
				delete_post_meta($post_id, $field);
			}
		}

		// repeat interval capacities
		if(!empty($_POST['ri_capacity_rs']) && evo_settings_check_yn($_POST,'_manage_repeat_cap_rs') ){

			// get total
			$count = 0; 
			foreach($_POST['ri_capacity_rs'] as $cap){
				$count = $count + ( (int)$cap);
			}
			update_post_meta( $post_id, 'ri_capacity_rs',$_POST['ri_capacity_rs']);

			if($count >0 )
				update_post_meta($post_id, 'evors_capacity_count', $count);
		}			
	}
}
new evors_meta_boxes();