<?php
/**
 * Confirmation email sent to the attendee
 * @version 	2.5.13
 *
 * To Customize this template: copy and paste this file to .../wp-content/themes/--your-theme-name--/eventon/templates/email/rsvp/ folder and edit that file.
 */

	global $eventon, $eventon_rs;
	echo $eventon->get_email_part('header');

	$args = $args;

	$event_name = get_the_title($args['e_id']);
	$event = get_post($args['e_id']);
	$e_pmv = get_post_meta($args['e_id'] );
	$rsvp_pmv = get_post_custom($args['rsvp_id']);
	
	$evo_options = get_option('evcal_options_evcal_1');
	$evo_options_2 = $eventon_rs->opt2;	
	$optRS = $eventon_rs->evors_opt;

	$lang = (!empty($args['lang']))? $args['lang']: 'L1';	 // language version

	//event time
		$repeat_interval = !empty($rsvp_pmv['repeat_interval'])? $rsvp_pmv['repeat_interval'][0]:0;		
		$time = $eventon_rs->frontend->functions->get_correct_event_time_data($e_pmv, $repeat_interval);


	// location data
		$location = false;
		if(class_exists('EVO_Event')){
			$this_event = new EVO_Event($args['e_id']);
			$location_data = $this_event->get_location_data();

			if($location_data){
				$location = (!empty($location_data['name'])? $location_data['name'].' - ': null).(!empty($location_data['location_address'])? $location_data['location_address']:null);
			}
		}else{
			$location = (!empty($e_pmv['evcal_location_name'])? $e_pmv['evcal_location_name'][0].': ': null).(!empty($e_pmv['evcal_location'])? $e_pmv['evcal_location'][0]:null);
		}
		
	//	styles
		$__styles_date = "font-size:48px; color:#ABABAB; font-weight:bold; margin-top:5px";
		$__styles_em = "font-size:14px; font-weight:bold; text-transform:uppercase; display:block;font-style:normal";
		$__styles_button = "font-size:14px; background-color:#".( !empty($evo_options['evcal_gen_btn_bgc'])? $evo_options['evcal_gen_btn_bgc']: "237ebd")."; color:#".( !empty($evo_options['evcal_gen_btn_fc'])? $evo_options['evcal_gen_btn_fc']: "ffffff")."; padding: 5px 10px; text-decoration:none; border-radius:4px; ";
		$__styles_01 = "font-size:30px; color:#303030; font-weight:bold; text-transform:uppercase; margin-bottom:0px;  margin-top:0;";
		$__styles_02 = "font-size:18px; color:#303030; font-weight:normal; text-transform:uppercase; display:block; font-style:italic; margin: 4px 0; line-height:110%;";
		$__styles_02b = "text-transform:none; font-size:14px; line-height:130%;padding:10px 0; display:inline-block";
		$__sty_lh = "line-height:110%;";
		$__styles_02a = "color:#afafaf; text-transform:none";
		$__styles_03 = "color:#afafaf; font-style:italic;font-size:14px; margin:0 0 10px 0;";
		$__styles_04 = "color:#303030; text-transform:uppercase; font-size:18px; font-style:italic; padding-bottom:0px; margin-bottom:0px; line-height:110%;";
		$__styles_05 = "padding-bottom:40px; ";
		$__styles_06 = "border-bottom:1px dashed #d1d1d1; padding:5px 20px";
		$__styles_07 = "display: inline-block;padding: 5px 10px;border: 1px solid #B7B7B7;";
		$__sty_td ="padding:0px;border:none; text-align:center;";
		$__sty_m0 ="margin:0px;";

	// reused elements
		$__item_p_beg = "<p style='{$__styles_02}'><span style='{$__styles_02a}'>";
		$event_details = $event->post_content;
?>

<table width='100%' style='width:100%; margin:0; font-family:"open sans"' cellspacing="0" cellpadding="0">
	<tr>
		<td style='<?php echo $__sty_td;?>'>
			<div style="padding:45px 20px; font-family:'open sans'">
				<p style='<?php echo $__sty_lh;?>font-size:18px; font-style:italic; margin:0'><?php echo $eventon_rs->lang('evoRSLX_009', 'You have RSVP-ed', $lang)?></p>
				<p style='<?php echo $__styles_07;?>'><?php echo $eventon_rs->frontend->get_rsvp_status($args['rsvp'], $lang);?></p>
				<p style='<?php echo $__styles_01.$__sty_lh;?> padding-bottom:15px;padding-top:30px'><?php echo $event_name;?></p>

				<?php echo $__item_p_beg;?><?php echo $eventon_rs->lang('evoRSLX_008', 'Event Time', $lang)?>:</span> <?php echo $time['readable'];?></p>

				<?php if(!empty($event_details)):?>
					<p style='<?php echo $__styles_02;?> padding-top:10px;'><span style='<?php echo $__styles_02a;?>'><?php echo $eventon_rs->lang('evoRSLX_008b', 'Event Details', $lang)?>:</span><br/><em style='<?php echo $__styles_02b;?>'><?php echo $event_details;?></em></p>
				<?php endif;?>

				<p style='<?php echo $__styles_02;?> padding-top:10px;'><span style='<?php echo $__styles_02a;?>'><?php echo $eventon_rs->lang('evoRSL_007a', 'RSVP ID', $lang)?>:</span> # <?php echo $args['rsvp_id'];?></p>

				<?php echo $__item_p_beg;?><?php echo $eventon_rs->lang('evoRSLX_002', 'Primary Contact on RSVP', $lang)?>:</span> <?php echo (!empty($args['last_name'])? $args['last_name']:'').' '.(!empty($args['first_name'])? $args['first_name']:'');?></p>

				<?php if(!empty($rsvp_pmv['names'])):?>
					<?php echo $__item_p_beg;?><?php evo_lang_e('Additional guest names', $lang)?>:</span> <?php echo implode(', ', unserialize($rsvp_pmv['names'][0]));?></p>

				<?php endif;?>

				<p style='<?php echo $__styles_02;?> padding-bottom:40px;'><span style='<?php echo $__styles_02a;?>'><?php echo $eventon_rs->lang('evoRSLX_003', 'Spaces', $lang)?>:</span> <?php echo evo_meta($rsvp_pmv,'count');?></p>
	

				<?php 
				//additional fields
				for($x=1; $x<=$eventon_rs->frontend->addFields; $x++){
					if(evo_settings_val('evors_addf'.$x, $optRS) && !empty($optRS['evors_addf'.$x.'_1'])  ){
						echo $__item_p_beg. $optRS['evors_addf'.$x.'_1'].": </span>".( (!empty($rsvp_pmv['evors_addf'.$x.'_1']))? $rsvp_pmv['evors_addf'.$x.'_1'][0]: '-')."</p>";
					}
				}
				
				//-- additional information -->
					if(!empty($e_pmv['evors_additional_data'])){?>
						<p style='<?php echo $__styles_04;?>'><?php echo evo_lang('Additional Information', $lang);?></p>
						<p style='<?php echo $__styles_03;?> padding-bottom:10px;'><?php echo $e_pmv['evors_additional_data'][0];?></p><?php
					}?>	

				<!-- location -->
				<?php if(!empty($location)):?>
					<p style='<?php echo $__styles_04;?>'><?php echo $eventon_rs->lang('evoRSLX_003x', 'Location', $lang)?></p>
					<p style='<?php echo $__styles_03;?> padding-bottom:10px;'><?php echo $location;?></p>
				<?php endif;?>
				
				<?php do_action('eventonrs_confirmation_email', $args['rsvp_id'], $rsvp_pmv, $args['rsvp']);?>
				
				<?php //add to calendar 
					$adjusted_unix_start = evo_get_adjusted_utc($time['start_unix']);
					$adjusted_unix_end = evo_get_adjusted_utc($time['end_unix']);
					$location = !empty($location) ? '&amp;loca='. stripcslashes($location): '' ;
				?>
				<p><a style='<?php echo $__styles_button;?>' href='<?php echo admin_url();?>admin-ajax.php?action=eventon_ics_download&event_id=<?php echo $args['e_id'];?>&sunix=<?php echo $adjusted_unix_start;?>&eunix=<?php echo $adjusted_unix_end . $location;?>' target='_blank'><?php echo $eventon_rs->lang('evcal_evcard_addics', 'Add to calendar', $lang);?></a></p>
			</div>
		</td>
	</tr>
	<tr>
		<td  style='padding:20px; border-top:1px dashed #d1d1d1; font-style:italic; color:#ADADAD; text-align:center;background-color:#f7f7f7;border-radius:0 0 5px 5px;'>
			<?php 
				$contactLink = (!empty($optRS['evors_contact_link']))? $optRS['evors_contact_link']:site_url();
			?>
			<p style='<?php echo $__sty_lh.$__sty_m0;?> padding-bottom:5px;'><?php echo $eventon_rs->lang('evoRSLX_005', 'We look forward to seeing you!', $lang)?></p>
		</td>
	</tr>
</table>
<?php
	echo $eventon->get_email_part('footer');
?>