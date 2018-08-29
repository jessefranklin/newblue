<?php
/*
   Plugin Name: EventON - Invite
   Plugin URI: http://www.myeventon.com/
   description:Invite group
   Intel Version: 1.92
   Author: Hero Digital
   Author URI: http://herodigital.com  
   License: GPL2
*/
	// Get the Timzezone in UFC Offset  format
	function get_UTC_offset(){

		$offset = (get_option('gmt_offset', 0) * 3600);
		$opt = get_option('evcal_options_evcal_1');
		$customoffset = !empty($opt['evo_time_offset'])? 
			(intval($opt['evo_time_offset'])) * 60:
			0;
		return $offset + $customoffset;

	}
	// return start and end time in array after adjusting time to UTC offset based on site timezone

		function get_utc_adjusted_times($start = '', $end='', $timezone, $separate = true ){
			
			if(empty($start) && empty($end)){

				//$times = $this->get_start_end_times();
			}else{
				$times = array('start'=>$start, 'end'=>$end);
			}
			if(empty($times)) return false;

		//	$datetime = new evo_datetime();
			$utc_offset = get_UTC_offset();
			$new_times = array('start'=> $times['start'], 'end'=> $times['end']);

			foreach($times as $key=>$unix){
				if( !$separate){
					$new_times[$key] = $unix - $utc_offset;
					continue;
				}

				$new_unix = $unix - $utc_offset;

				$new_timeT = date("Ymd", $new_unix);

				$new_timeZ = date("Hi", $new_unix);

				// $new_times[$key] = $new_timeT.'T'.$new_timeZ.'00Z';
				$new_times[$key] = $new_timeT.'T'.$new_timeZ;

			}

			return $new_times;

		}
		
        
  function invite_shortcode($atts = [], $content = null, $tag = ''){
	ob_start();
	global $wpdb;

	$user_id = get_current_user_id();

	//echo "SELECT * from $wpdb->posts WHERE post_author =".$user_id;

	$post_id = $wpdb->get_results("SELECT * from $wpdb->posts WHERE post_type='ajde_events' and post_status='publish' and post_author =".$user_id." ORDER BY post_date DESC LIMIT 1");
	if( ! isset( $post_id[ 0 ] ) ) {
		return 'You have not created any events.';
	}
	$event_link = 'https://newblueconnect.intel.com/events/' . $post_id[0]->post_name;
	//$event_link = get_permalink( $post_id[0] );
	//print_r($_POST);   
	
	// $post_id[0]->ID;
	
	$evcal_subtitle= get_post_meta($post_id[0]->ID , 'evcal_subtitle',true);
	
	$estart= get_post_meta($post_id[0]->ID , 'evcal_srow',true);
	
	$new_estart = date('jS F(l) - h:i A', $estart);
	
	$eend = get_post_meta($post_id[0]->ID , 'evcal_erow',true);
	
	$new_eend = date('jS F(l) - h:i A', $eend);
	
	$timezone = get_post_meta( $post_id[0]->ID , 'evotimezone', true );
	
	$evcal_location_name = get_post_meta( $post_id[0]->ID , 'evcal_location_name', true );
	if( ! $evcal_location_name ) {
		$evo_event_location_term_id = ( int )get_post_meta( $post_id[0]->ID , 'evo_event_location', true );
		$event_location_term = get_term( $evo_event_location_term_id, 'event_location' );
		if( $event_location_term ) {
			$evcal_location_name = $event_location_term->name;
		}
	}
	
	$location_address = get_post_meta( $post_id[0]->ID , 'location_address', true );
	if( ! $location_address ) {
		$location_address = get_post_meta( $post_id[0]->ID , 'off_site_address', true );
	}

	$evcal_organizer = get_post_meta( $post_id[0]->ID , 'evcal_organizer', true );
	
	$adjusted_times = get_utc_adjusted_times( $estart, $eend, $timezone );

	$adjusted_unix_start = $adjusted_times['start'];

	$adjusted_unix_end = $adjusted_times['end'];
	 
	$terms = wp_get_post_terms( $post_id[0]->ID, 'event_type' );
	
	//print_r();
	
	$ics_url =admin_url('admin-ajax.php').'?action=eventon_ics_download&amp;event_id='.esc_html__($post_id[0]->ID).'&amp;sunix='.$adjusted_unix_start.'&amp;eunix='.$adjusted_unix_end . 

							(isset($location_address) ? '&amp;loca='. $location_address : '' ).

							(isset($evcal_location_name) ? '&amp;locn='.$evcal_location_name : '' );

    // normalize attribute keys, lowercase
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
 
    // override default attributes with user attributes
    $wporg_atts = shortcode_atts([
                                     'file' => '',
									 'button-text' => 'Button',
									 'post-url' => ''
                                 ], $atts, $tag);
	?>
	<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.3.3/semantic.min.css">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.3.3/semantic.min.js"></script>
    <div class="wporg-box">
	 
		<div id="eventon_form" class="evoau_submission_form successForm" >   
			<div class="evoau_success_msg" style="">
				<p>
					Is this event open to all? Then you are all set!<br> 
					Otherwise, click below to select your group of attendees!
				</p>
				<h3>
					<strong>Event : </strong> <?php echo esc_html__($post_id[0]->post_title); ?>
				</h3>
				<button type="button" class="" id="myBtn"><?php echo esc_html__($wporg_atts['button-text'], 'wporg'); ?> </button>   
			</div>
		</div>
    </div>
	<div id="myModal" class="modal">
	  <!-- Modal content -->
		<div class="modal-content">
			<div class="modal-header">
				  <span aria-hidden="true" class="close">×</span>
				  <h4 class="modal-title" id="myModalLabel">Invite</h4>
			</div>
			<div class="modal-body">
				<form action="<?php echo esc_html__($wporg_atts['post-url'], 'wporg'); ?> " method="post">
				
					<input type="hidden" name="event_id" id="event_id" value="<?php echo esc_html__($post_id[0]->ID); ?>">							
					<input type="hidden" name="event_title" id="event_title" value="<?php echo esc_html__($post_id[0]->post_title); ?>">
					<input type="hidden" name="event_subtitle" id="event_subtitle" value="<?php echo esc_html__($evcal_subtitle); ?>">
					<input type="hidden" name="event_details" id="event_details" value="<?php echo esc_html__($post_id[0]->post_content); ?>">
					<input type="hidden" name="event_location" id="event_location" value="<?php echo esc_html__($evcal_location_name); ?>">
					<input type="hidden" name="event_location_address" id="event_location_address" value="<?php echo esc_html__($location_address); ?>">
					<input type="hidden" name="evcal_organizer" id="evcal_organizer" value="<?php echo esc_html__($evcal_organizer); ?>">
					<input type="hidden" name="event_time" id="event_time" value="<?php echo esc_html__($new_estart).' - '.esc_html__($new_eend); ?>">
					<input type="hidden" name="ics_url" id="ics_url" value="<?php echo esc_html__($ics_url); ?>">
					<input type="hidden" name="event_type" id="event_type" value="<?php echo esc_html__($terms[0]->name); ?>">
					<input type="hidden" name="event_link" id="event_link" value="<?php echo esc_html__($event_link); ?>">
					<div>
						<span style="font-weight: bold; margin-top: 10px; color: black; display: block;" >Select Invite List Selection Method</span>
						<select id="get_invite_type" class="ui fluid dropdown">
							<option value='custom_list' selected>Upload Custom Invite List</option>
							<option value='super_group'>Invite Super Group(s)</option>
						</select>

						<div id="group_container" style="display: none;">
							<select name="group[]" multiple="" class="ui fluid dropdown" id="group">
							</select>
						</div>
						<div id="custom_list">
							<span style="font-weight: bold; margin-top: 10px; color: black; display: block;" >Enter/Paste Emails (Email addresses can be separated by commas, semi-colons, or new-lines.):</span>
							<textarea id="txt_custom_list" style="height: 150px"></textarea>
						</div>

					</div>
					<div style="margin-top:20px;">
						<input type="submit" name="submit" value="Submit" id="submit">
					</div>
				</form>
			</div>
		</div>
	</div>
<style>
body {
	font-size: 16px;
    line-height: 1.5;
    background-color: #005395;
	font-family: "intel_clear_wlatlight", "Libre Franklin", "Helvetica Neue", helvetica, arial, sans-serif;
	font-weight: 400;
}
.header-links a {
	color: white;
}
label span {
    color: #404040;
}
select {
    width: 30%;
    height: 4em !important;
}

/* The Modal (background) */
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 10; /* Sit on top */
    padding-top: 100px; /* Location of the box */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgb(0,0,0); /* Fallback color */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}
/* Modal Content */
.modal-content {
    background-color: #fefefe;
    margin: auto;
    border: 1px solid #888;
    width: 80%;
}
.modal-header {
    padding: 15px;
    border-bottom: 1px solid #e5e5e5;
}
.modal-title {
    margin: 0;
    line-height: 1.42857143;
	clear: none;
    padding: 0px;
}
/* The Close Button */
.close {
    float: right;
    font-size: 21px;
    font-weight: 700;
    line-height: 1;
    color: #000;
    text-shadow: 0 1px 0 #fff;
    filter: alpha(opacity=20);
    opacity: .2;
}
.modal-body {
    position: relative;
    padding: 15px;
}
.close:hover,
.close:focus {
    color: #000;
    text-decoration: none;
    cursor: pointer;
}

/* success message */
		.evoau_success_msg{text-align: center;}
		.limitSubmission .evoau_success_msg p{color: #ffffff;}
		.limitSubmission .evoau_success_msg p b:before{
			content:"!";
			-webkit-transform: rotate(0deg);
  			-moz-transform: rotate(0deg);
  			-ms-transform: rotate(0deg);
  			-o-transform: rotate(0deg);
  			transform: rotate(0deg);
  			border:none;
  			margin:0;
  			top:auto; left: auto;
  			height: auto;
  			width: 40px;
  			line-height: 120%;
  			font-size: 32px;
		}
		body .evoau_success_msg h3 {color:#ffffff;} 
		body .evoau_success_msg p{color: #ffffff;
			line-height: 1.5;
	  		margin: 0;
	  		font-size: 18px;
	  		text-align: center;
	  		padding-top: 20px;
		}
		.evoau_success_msg p b{
			position: relative;
	  		display: block;
	  		width: 45px;
	  		height: 45px;
	  		border: 3px solid #ffffff;
	  		border-radius: 50%;
	  		margin: 0 auto;
				margin-bottom: 10px;
	  		box-sizing: border-box;
		}
		.evoau_success_msg p b:before{
			content: '';
	  		display: block;
	  		position: absolute;
	  		top: 50%;
	  		left: 50%;
	  		margin: -9px 0 0 -9px;
	  		height: 10px;
	 		width: 16px;
	  		border: solid #ffffff;
	  		border-width: 0 0 4px 4px;
	  		-webkit-transform: rotate(-45deg);
	  		-moz-transform: rotate(-45deg);
	  		-ms-transform: rotate(-45deg);
	  		-o-transform: rotate(-45deg);
	  		transform: rotate(-45deg);
		}
.evoau_submission_form.successForm {
    background-color: #9BD28C;
	padding: 13px 20px;
    border: 1px solid #d9d7d7;
    border-bottom-width: 3px;
    position: relative;
    border-radius: 5px;
    overflow: hidden;
}
   
.evoau_submission_form.successForm h2{
	color: #ffffff !important;
}
</style>
<script>
var ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
$('.ui.dropdown').dropdown({placeholder:'Select Group'});
$(document).ready(function(){
	$( "#get_invite_type" ).on( "change", function () {
		var invite_type = $( this ).val();
		if( invite_type === "custom_list" ) {
			$("#custom_list").show();
			$("#group_container").hide();
		} else if( invite_type === "super_group" ) {
			$("#custom_list").hide();
			$("#group_container").show();
		}
	} );
  
    $("#myBtn").click(function(){
        $("#myModal").show();		
			var dropdown = $('#group');
			dropdown.empty();
			dropdown.append('<option selected="true" disabled>Select Group</option>');
			dropdown.prop('selectedIndex', 0);
			/*const url = '<?php echo esc_html__($wporg_atts['file'], 'wporg'); ?>';
			// Populate dropdown with list of provinces
			$.getJSON(url, function (data) {
			  $.each(data, function (key, entry) {
				dropdown.append($('<option></option>').attr('value', entry.bu).text(entry.bu));
			  })
			});*/

			//Load the Super groups
			var data = {
				'action': 'fln_get_super_groups'
			};

			$.post( ajax_url, data, function( response ) {
				for( var i = 0; i < response.length; i++ ) {
					dropdown.append($('<option></option>').attr('value', response[ i ].bu).text(response[ i ].bu));
				}
			} );
    });	
	// When the user clicks on <span> (x), close the modal
	$(".close").click(function(){
			$("#myModal").hide();
	});		
	/* close on click outside of modal */
	$("#myModal").on('click', function(e) {
	  if (e.target !== this) return;
	  $("#myModal").hide();
	});
});
</script>

<script>
      $(function () {

        $('form').on('submit', function (e) {

          e.preventDefault();

          /*$.ajax({
            type: 'post',
            url: '<?php echo esc_html__($wporg_atts['post-url'], 'wporg'); ?>',
            data: $('form').serialize(),
            success: function () {
			  console.log('event_id : ' + $('#event_id').val() );
			  console.log('event_title : ' + $('#event_title').val() );
			  console.log('evcal_subtitle : ' + $('#event_subtitle').val() );
			  console.log('event_details : ' + $('#event_details').val() );	
			  console.log('ics_url : ' + $('#ics_url').val() );
			  console.log('group : ' + $('#group').val() );
			  console.log('event_location : ' + $('#event_location').val() );  		  
			  console.log('event_time : ' + $('#event_time').val() );
			  console.log('evcal_organizer : ' + $('#evcal_organizer').val() );
			  console.log('evcal_type : ' + $('#event_type').val() );
			  $("#myModal").hide();
             // window.location.href = 'http://newblueconnect.com.s224062.gridserver.com/';
            }
          });*/

			//Sub
			var data = {
				'action': 'fln_invite_guests',
				'event_data': {
					'event_id': $('#event_id').val(),
					'event_title': $('#event_title').val(),
					'evcal_subtitle': $('#event_subtitle').val(),
					'event_details': $('#event_details').val(),
					'ics_url': $('#ics_url').val(),
					'group': $('#group').val(),
					'event_location': $('#event_location').val(),
					'event_location_address': $( '#event_location_address' ).val(),
					'event_time': $('#event_time').val(),
					'evcal_organizer': $('#evcal_organizer').val(),
					'evcal_type': $('#event_type').val(),
					'event_link': $('#event_link').val()
				}
			};
			if( $( "#get_invite_type" ).val() === "custom_list" ) {
				var custom_list = $( '#txt_custom_list' ).val();
				if (custom_list.search(/<|>/g) != -1) {
				    custom_list = custom_list.match(/\S+@\S+\.\S+/g);
				    custom_list = custom_list.join('\n');
				    custom_list = custom_list.replace( /<|>|;/g, '');
				    data.event_data.custom_list = custom_list;
				    $( '#txt_custom_list' ).val( custom_list );
				} else {
				    custom_list = custom_list.replace( /; /g, ';' );
				    custom_list = custom_list.replace( /, /g, ',' );
				    custom_list = custom_list.replace( / /g, '\n');
				    custom_list = custom_list.replace( /;/g, '\n' );
				    custom_list = custom_list.replace( /,/g, '\n' );
				    data.event_data.custom_list = custom_list;
				    $( '#txt_custom_list' ).val( custom_list );
				}
			} else if( $( "#get_invite_type" ).val() === "super_group" ) {
				data.event_data.group = $( '#group' ).val();
			}

			jQuery.post( ajax_url, data, function( response ) {
				console.log('event_id : ' + $('#event_id').val() );
				console.log('event_title : ' + $('#event_title').val() );
				console.log('evcal_subtitle : ' + $('#event_subtitle').val() );
				console.log('event_details : ' + $('#event_details').val() );
				console.log('ics_url : ' + $('#ics_url').val() );
				console.log('group : ' + $('#group').val() );
				console.log('event_location : ' + $('#event_location').val() );
				console.log('event_time : ' + $('#event_time').val() );
				console.log('evcal_organizer : ' + $('#evcal_organizer').val() );
				console.log('evcal_type : ' + $('#event_type').val() );
				$("#myModal .modal-body").removeClass( "evoloadbar" );
				$("#myModal .modal-body").removeClass( "bottom" );
				$("#myModal").hide();
				$("#myBtn").after("<p>Invitations Sent</p>");
				$("#myBtn").hide();
			} );
			$("#myModal .modal-body").addClass( "evoloadbar" );
			$("#myModal .modal-body").addClass( "bottom" );
        });

      });
    </script>

<?php
 $output = ob_get_clean();
 return $output;
   // return $o;  
}
 add_shortcode('invite', 'invite_shortcode');

 /** Additional Timezone Field **/

add_filter('evoau_form_fields', 'evoautimezone_fields_to_form', 10, 1);
function evoautimezone_fields_to_form($array){
	$array['evotimezone']=array('Timezone', 'evotimezone', 'evotimezone','custom','');
	return $array;
}

add_filter('evoau_form_fields', 'evoaulocation_fields_to_form', 10, 1);
function evoaulocation_fields_to_form($array){
	$array['evolocation']=array('Location', 'evolocation', 'evolocation','custom','');
	return $array;
}

// actionUser intergration
add_action('evoau_frontform_evotimezone',  'evoautimezone_fields', 10, 6);
if(!is_admin()){
    add_action('evoau_frontform_evolocation',  'evoaulocation_fields', 10, 6);  		   
}

// Frontend showing fields and saving values  
function evoautimezone_fields($field, $event_id, $default_val, $EPMV, $opt2, $lang){
    $timezone = get_post_meta($event_id, "evo_event_timezone", true);
    $tz_opts = [
	    ["key" => "Pacific/Midway", "text" => "GMT-11:00) Midway Island, Samoa"],
	    ["key" => "America/Adak", "text" => "GMT-10:00) Hawaii-Aleutian"],
	    ["key" => "HST", "text" => "GMT-10:00) Hawaii"],
	    ["key" => "Pacific/Marquesas", "text" => "GMT-09:30) Marquesas Islands"],
	    ["key" => "Pacific/Gambier", "text" => "GMT-09:00) Gambier Islands"],
	    ["key" => "America/Anchorage", "text" => "GMT-09:00) Alaska"],
	    ["key" => "America/Ensenada", "text" => "GMT-08:00) Tijuana, Baja California"],
	    ["key" => "Etc/GMT+8", "text" => "GMT-08:00) Pitcairn Islands"],
	    ["key" => "America/Los_Angeles", "text" => "GMT-08:00) Pacific Time (US & Canada)"],
	    ["key" => "America/Denver", "text" => "GMT-07:00) Mountain Time (US & Canada)"],
	    ["key" => "America/Chihuahua", "text" => "GMT-07:00) Chihuahua, La Paz, Mazatlan"],
	    ["key" => "America/Phoenix", "text" => "GMT-07:00) Arizona"],
	    ["key" => "America/Belize", "text" => "GMT-06:00) Saskatchewan, Central America"],
	    ["key" => "America/Cancun", "text" => "GMT-06:00) Guadalajara, Mexico City, Monterrey"],
	    ["key" => "Chile/EasterIsland", "text" => "GMT-06:00) Easter Island"],
	    ["key" => "America/Chicago", "text" => "GMT-06:00) Central Time (US & Canada)"],
	    ["key" => "America/New_York", "text" => "GMT-05:00) Eastern Time (US & Canada)"],
	    ["key" => "America/Havana", "text" => "GMT-05:00) Cuba"],
	    ["key" => "America/Bogota", "text" => "GMT-05:00) Bogota, Lima, Quito, Rio Branco"],
	    ["key" => "America/Caracas", "text" => "GMT-04:30) Caracas"],
	    ["key" => "America/Santiago", "text" => "GMT-04:00) Santiago"],
	    ["key" => "America/La_Paz", "text" => "GMT-04:00) La Paz"],
	    ["key" => "Atlantic/Stanley", "text" => "GMT-04:00) Faukland Islands"],
	    ["key" => "America/Campo_Grande", "text" => "GMT-04:00) Brazil"],
	    ["key" => "America/Goose_Bay", "text" => "GMT-04:00) Atlantic Time (Goose Bay)"],
	    ["key" => "America/Glace_Bay", "text" => "GMT-04:00) Atlantic Time (Canada)"],
	    ["key" => "America/St_Johns", "text" => "GMT-03:30) Newfoundland"],
	    ["key" => "America/Araguaina", "text" => "GMT-03:00) UTC-3"],
	    ["key" => "America/Montevideo", "text" => "GMT-03:00) Montevideo"],
	    ["key" => "America/Miquelon", "text" => "GMT-03:00) Miquelon, St. Pierre"],
	    ["key" => "America/Godthab", "text" => "GMT-03:00) Greenland"],
	    ["key" => "America/Argentina/Buenos_Aires", "text" => "GMT-03:00) Buenos Aires"],
	    ["key" => "America/Sao_Paulo", "text" => "GMT-03:00) Brasilia"],
	    ["key" => "America/Noronha", "text" => "GMT-02:00) Mid-Atlantic"],
	    ["key" => "Atlantic/Cape_Verde", "text" => "GMT-01:00) Cape Verde Is."],
	    ["key" => "Atlantic/Azores", "text" => "GMT-01:00) Azores"],
	    ["key" => "Europe/Belfast", "text" => "GMT) Greenwich Mean Time : Belfast"],
	    ["key" => "Europe/Dublin", "text" => "GMT) Greenwich Mean Time : Dublin"],
	    ["key" => "Europe/Lisbon", "text" => "GMT) Greenwich Mean Time : Lisbon"],
	    ["key" => "Europe/London", "text" => "GMT) Greenwich Mean Time : London"],
	    ["key" => "Africa/Abidjan", "text" => "GMT) Monrovia, Reykjavik"],
	    ["key" => "Europe/Amsterdam", "text" => "GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna"],
	    ["key" => "Europe/Belgrade", "text" => "GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague"],
	    ["key" => "Europe/Brussels", "text" => "GMT+01:00) Brussels, Copenhagen, Madrid, Paris"],
	    ["key" => "Africa/Algiers", "text" => "GMT+01:00) West Central Africa"],
	    ["key" => "Africa/Windhoek", "text" => "GMT+01:00) Windhoek"],
	    ["key" => "Asia/Beirut", "text" => "GMT+02:00) Beirut"],
	    ["key" => "Africa/Cairo", "text" => "GMT+02:00) Cairo"],
	    ["key" => "Asia/Gaza", "text" => "GMT+02:00) Gaza"],
	    ["key" => "Africa/Blantyre", "text" => "GMT+02:00) Harare, Pretoria"],
	    ["key" => "Asia/Jerusalem", "text" => "GMT+02:00) Jerusalem"],
	    ["key" => "Europe/Minsk", "text" => "GMT+02:00) Minsk"],
	    ["key" => "Asia/Damascus", "text" => "GMT+02:00) Syria"],
	    ["key" => "Europe/Moscow", "text" => "GMT+03:00) Moscow, St. Petersburg, Volgograd"],
	    ["key" => "Africa/Addis_Ababa", "text" => "GMT+03:00) Nairobi"],
	    ["key" => "Asia/Tehran", "text" => "GMT+03:30) Tehran"],
	    ["key" => "Asia/Dubai", "text" => "GMT+04:00) Abu Dhabi, Muscat"],
	    ["key" => "Asia/Yerevan", "text" => "GMT+04:00) Yerevan"],
	    ["key" => "Asia/Kabul", "text" => "GMT+04:30) Kabul"],
	    ["key" => "Asia/Yekaterinburg", "text" => "GMT+05:00) Ekaterinburg"],
	    ["key" => "Asia/Tashkent", "text" => "GMT+05:00) Tashkent"],
	    ["key" => "Asia/Kolkata", "text" => "GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi"],
	    ["key" => "Asia/Katmandu", "text" => "GMT+05:45) Kathmandu"],
	    ["key" => "Asia/Dhaka", "text" => "GMT+06:00) Astana, Dhaka"],
	    ["key" => "Asia/Novosibirsk", "text" => "GMT+06:00) Novosibirsk"],
	    ["key" => "Asia/Rangoon", "text" => "GMT+06:30) Yangon (Rangoon)"],
	    ["key" => "Asia/Bangkok", "text" => "GMT+07:00) Bangkok, Hanoi, Jakarta"],
	    ["key" => "Asia/Krasnoyarsk", "text" => "GMT+07:00) Krasnoyarsk"],
	    ["key" => "Asia/Hong_Kong", "text" => "GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi"],
	    ["key" => "Asia/Irkutsk", "text" => "GMT+08:00) Irkutsk, Ulaan Bataar"],
	    ["key" => "Australia/Perth", "text" => "GMT+08:00) Perth"],
	    ["key" => "Asia/Kuala_Lumpur", "text" => "GMT+08:00) Malaysia"],
	    ["key" => "Australia/Eucla", "text" => "GMT+08:45) Eucla"],
	    ["key" => "Asia/Tokyo", "text" => "GMT+09:00) Osaka, Sapporo, Tokyo"],
	    ["key" => "Asia/Seoul", "text" => "GMT+09:00) Seoul"],
	    ["key" => "Asia/Yakutsk", "text" => "GMT+09:00) Yakutsk"],
	    ["key" => "Australia/Adelaide", "text" => "GMT+09:30) Adelaide"],
	    ["key" => "Australia/Darwin", "text" => "GMT+09:30) Darwin"],
	    ["key" => "Australia/Brisbane", "text" => "GMT+10:00) Brisbane"],
	    ["key" => "Australia/Hobart", "text" => "GMT+10:00) Hobart"],
	    ["key" => "Asia/Vladivostok", "text" => "GMT+10:00) Vladivostok"],
	    ["key" => "Australia/Lord_Howe", "text" => "GMT+10:30) Lord Howe Island"],
	    ["key" => "Etc/GMT-11", "text" => "GMT+11:00) Solomon Is., New Caledonia"],
	    ["key" => "Asia/Magadan", "text" => "GMT+11:00) Magadan"],
	    ["key" => "Pacific/Norfolk", "text" => "GMT+11:30) Norfolk Island"],
	    ["key" => "Asia/Anadyr", "text" => "GMT+12:00) Anadyr, Kamchatka"],
	    ["key" => "Pacific/Auckland", "text" => "GMT+12:00) Auckland, Wellington"],
	    ["key" => "Etc/GMT-12", "text" => "GMT+12:00) Fiji, Kamchatka, Marshall Is."],
	    ["key" => "Pacific/Chatham", "text" => "GMT+12:45) Chatham Islands"],
	    ["key" => "Pacific/Tongatapu", "text" => "GMT+13:00) Nuku&apos;alofa"],
	    ["key" => "Pacific/Kiritimati", "text" => "GMT+14:00) Kiritimati"]
       ];    
?>
		<div class='row evotest'><p>
		   <label for="timezone">Select the event&apos;s timezone: <em>*</em></label>
        
        <select class="form-control req" id="timezone" name="evotimezone">   
        	
        	<option value="" <?php if(empty($timezone)){ echo 'selected="selected"'; } ?> >select timezone</option>
<?php
                foreach($tz_opts as $tz){
		  $selected = "";
		  if($tz['key'] == $timezone){
		    $selected = ' selected="selected" ';
		  }
		  echo '<option value="' . $tz['key'] . '" ' . $selected . '>' . $tz['text'] . '</option>';
		}
?>
        </select>
		</p></div>
	<?php		 
}

add_action('evoau_save_formfields',  'evoautest_save_values', 10, 3);
function evoautest_save_values($field, $fn, $created_event_id){
// print_r($_POST);
// die();

	if ( isset( $_POST['evotimezone'] )){
		update_post_meta($created_event_id, 'evo_event_timezone', $_POST['evotimezone']); 
	}
	
	if ( isset( $_POST['evolocationsite'] )){
		update_post_meta($created_event_id, 'evo_event_locationsite', $_POST['evolocationsite']); 
	}
	
	if ( isset( $_POST['evoregion'] )){
		update_post_meta($created_event_id, 'evo_event_region', $_POST['evoregion']); 
		wp_set_post_terms( $created_event_id, array(  intval($_POST['evoregion']) ), 'event_type_3' );
	}
	
	if ( isset( $_POST['address'] )){
		update_post_meta($created_event_id, 'off_site_address', $_POST['address']); 
	}
	
	if ( isset( $_POST['evolocationtype'] )){
		update_post_meta($created_event_id, 'evo_event_locationtype', $_POST['evolocationtype']); 
		wp_set_post_terms( $created_event_id, array(  intval($_POST['evolocationtype']) ), 'event_type_4' );
	}
			
	
	if ( isset( $_POST['virtual_link'] )){
		update_post_meta($created_event_id, 'virtual_link', $_POST['virtual_link']); 
	} 

	if ( isset( $_POST['private'] )){
		update_post_meta($created_event_id, 'private', $_POST['private']); 
	}	 
	
	$tag = intval($_POST['evolocation']);
	if ( isset( $_POST['evolocation'] )){
		update_post_meta($created_event_id, 'evo_event_location', $_POST['evolocation']); 
		wp_set_post_terms( $created_event_id, array(  intval($_POST['evolocation']) ), 'event_location' );
	}

	if( $field =='evotimezone'){
		// for each above fields
		foreach(array(
			'evoau_test_value',
		) as $field){
			if(!empty($_POST[$field]))
				add_post_meta($created_event_id, $field, $_POST[$field]);
		}
	}
}

// Frontend showing fields and saving values  
function evoaulocation_fields($field, $event_id, $default_val, $EPMV, $opt2, $lang){
	//$fields = get_option('wpcf-termmeta');
	//$options = $fields['region']['data']['options'];
	//print_r($options);	
?>
		<div class='row evotest'>   
		    
			<p><label for="">Event Location</label></p>	

			<p><label for="site">Select Location Type : </label>		
			<select class="form-control" id="locationtype" name="evolocationtype">				   
				<option value="" selected="selected">Select Location Type</option>
				<?php 
				$taxonomy = 'event_type_4';
				$args = array(
					'parent' => 0,
					'hide_empty' => false,
					'orderby' => 'id',
					'order' => 'ASC',	   				
				);
				$terms = get_terms( $taxonomy, $args );
				
					foreach ( $terms as $term) {
						echo '<option data-slug="' . $term->slug . '"' . ' value="' .$term->term_id . '" >' . $term->name . '</option>';
						//echo '<!-- ' . print_r( $term, true ) . ' -->';
					}
					?>
				<!--<option value="site">Site</option>
				<option value="off-site">Off-Site</option>
				<option value="virtual">Virtual</option>  -->
			</select>			
			</p> 
			
			<p id="pregion" style="display:none;"><label for="region">Select Event's Region : </label>		
			<select class="form-control" id="region" name="evoregion">				   
				<option value="" selected="selected">Select Region</option>
				<?php 
				$taxonomy = 'event_type_3';
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
							echo '<option value="'.$term1->term_id .'">'.$term1->name .'</option>';
						}
					}
				?>
				<!--	<optgroup label="AMR">
					<option value="Argentina, Cordoba">Argentina, Cordoba</option>
					<option value="Arizona, Chandler">Arizona, Chandler</option>
					<option value="Arizona, Ocotillo">Arizona, Ocotillo</option>
					<option value="California, Bowers">California, Bowers</option>
					<option value="California, Folsom">California, Folsom</option>
					<option value="California, San Diego">California, San Diego</option>
					<option value="California, San Francisco">California, San Francisco</option>
					<option value="California, San Jose">California, San Jose</option>
					<option value="Colorado, Ft. Collins">Colorado, Ft. Collins</option>
					<option value="Costa Rica">Costa Rica</option>
					<option value="Massachuessets, Hudson">Massachuessets, Hudson</option>
					<option value="Mexico, Guadalajara">Mexico, Guadalajara</option>
					<option value="New Mexico, Rio Rancho">New Mexico, Rio Rancho</option>
					<option value="Oregon, Aloha">Oregon, Aloha</option>
					<option value="Oregon, Hawthorn Farm">Oregon, Hawthorn Farm</option>
					<option value="Oregon, Jones Farm">Oregon, Jones Farm</option>
					<option value="Oregon, Ronler Acres">Oregon, Ronler Acres</option>
					<option value="South Carolina, Columbia">South Carolina, Columbia</option>
					<option value="Texas, Austin">Texas, Austin</option>
				</optgroup>
				<optgroup label="GAR">
					<option value="China, Beijing GTC">China, Beijing GTC</option>
					<option value="China, Beijing RYC2">China, Beijing RYC2</option>
					<option value="China, Chengdu">China, Chengdu</option>
					<option value="China, Dallan">China, Dallan</option>
					<option value="China, Hong Kong">China, Hong Kong</option>
					<option value="China, Shanghai Mart">China, Shanghai Mart</option>
					<option value="China, Shanghai Zizhu">China, Shanghai Zizhu</option>
					<option value="China, Shenzhen VBP">China, Shenzhen VBP</option>
					<option value="India, Bangalore BGA">India, Bangalore BGA</option>
					<option value="India, Bangalore EMB">India, Bangalore EMB</option>
					<option value="India, Bangalore SRR">India, Bangalore SRR</option>
					<option value="India, Mumbai & New Delhi">India, Mumbai & New Delhi</option>
					<option value="Japan, Tokyo">Japan, Tokyo</option>
					<option value="Malaysia, Kulim & Penang">Malaysia, Kulim & Penang</option>
					<option value="Singapore">Singapore</option>
					<option value="South Korea">South Korea</option>
					<option value="Australia - Sydney">Australia - Sydney</option>
					<option value="Taiwan, Taipei">Taiwan, Taipei</option>
					<option value="Vietnam">Vietnam</option>
				</optgroup>
				<optgroup label="GER">
					<option value="Belgium, Kontich">Belgium, Kontich</option>
					<option value="Denmark, Aalborg">Denmark, Aalborg</option>
					<option value="Finland, Espoo & Tampere">Finland, Espoo & Tampere</option>
					<option value="Germany, Campeon">Germany, Campeon</option>
					<option value="Germany, Duisburg">Germany, Duisburg</option>
					<option value="Germany, Karlsruhe TPK">Germany, Karlsruhe TPK</option>
					<option value="Germany, MU-Feldkirchhen IMU">Germany, MU-Feldkirchhen IMU</option>
					<option value="Ireland, Leixlip">Ireland, Leixlip</option>
					<option value="Ireland, Shannon">Ireland, Shannon</option>
					<option value="Israel, FAB28">Israel, FAB28</option>
					<option value="Israel, IDC">Israel, IDC</option>
					<option value="Israel, IDCJ and IDPJ">Israel, IDCJ and IDPJ</option>
					<option value="Israel, PTK">Israel, PTK</option>
					<option value="Israel, Yakum">Israel, Yakum</option>
					<option value="Italy, Milan">Italy, Milan</option>
					<option value="Poland, Gdansk">Poland, Gdansk</option>
					<option value="Russia, Moscow">Russia, Moscow</option>
					<option value="Russia, Nizhiniy">Russia, Nizhiniy</option>
					<option value="Spain, Barcelona">Spain, Barcelona</option>
					<option value="Sweden, DRT">Sweden, DRT</option>
					<option value="Sweden, Kista">Sweden, Kista</option>
					<option value="UK, Swindon">UK, Swindon</option>
				</optgroup>
				<optgroup label="Other">	
					<option value="Virtual">Virtual</option>
					<option value="Off-Site">Off-Site</option>
				</optgroup>		 -->		
			</select>			
			</p>
			<p id="ploc" style="display:none;"><label for="evolocation">Select Event's Location : </label>	  	
			<select class="form-control" id="evolocation" name="evolocation">				   
				<option value="" selected="selected">Select Location</option>        
			</select>			
			</p>
			
		<!--	<p><label for="site">Select Event's Site : </label>		
			<select class="form-control" id="site" name="evolocationsite">				   
				<option value="" selected="selected">Select Site</option>
				<option value="CH7">CH7</option>
				<option value="CH8">CH8</option>
				<option value="CH9">CH9</option>
			</select>			
			</p>  -->

			<p id="padd" style="display:none;"><label for="address">Enter The <span id="addtxt">Address</span>  : </label>
				<input type="text" name="address" id="address" value="">	
			</p>
			<p><label for="virtual_link">Enter Virtual Link  : </label>
			<a href="https://employeecontent.intel.com/content/corp/meeting-center/home.html" style="color:black;">If you have not booked a room or virtual meeting yet, use this link.</a>
				<input type="text" name="virtual_link" id="" value="">	
			</p>
			<p class="checkbox">
			  <label>Is This A Private Event Only Open To Invited Guests?</label>
			  <label><input type="checkbox" value="1" name="private">Yes, Make Private</label>
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
}

add_action('evoau_manager_row',  'event_manager_row_invite', 10, 3);

function event_manager_row_invite($event_id, $EPMV){
			if( evo_check_yn($EPMV,'evors_rsvp') ){
				echo "<a class='invite_event_single evoauem_additional_buttons ' data-eid='{$event_id}'>".evo_lang('Invite')."</a>";
			}
		}

		
add_action('evoau_manager_after_events', 'after_events_invite',10,1);
function after_events_invite($atts){
?>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.3.3/semantic.min.css">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.3.3/semantic.min.js"></script>
<div id="myModal" class="modal">
	  <!-- Modal content -->
		<div class="modal-content">
			<div class="modal-header">
				  <span aria-hidden="true" class="close">×</span>
				  <h4 class="modal-title" id="myModalLabel">Invite</h4>
			</div>
			<div class="modal-body">
				<form action="<?php echo esc_html__($wporg_atts['post-url'], 'wporg'); ?> " method="post">
				
					<!--<input type="hidden" name="event_id" id="event_id" value="<?php echo esc_html__($post_id[0]->ID); ?>">							
					<input type="hidden" name="event_title" id="event_title" value="<?php echo esc_html__($post_id[0]->post_title); ?>">
					<input type="hidden" name="event_subtitle" id="event_subtitle" value="<?php echo esc_html__($evcal_subtitle); ?>">
					<input type="hidden" name="event_details" id="event_details" value="<?php echo esc_html__($post_id[0]->post_content); ?>">
					<input type="hidden" name="event_location" id="event_location" value="<?php echo esc_html__($evcal_location_name); ?>">
					<input type="hidden" name="event_location_address" id="event_location_address" value="<?php echo esc_html__($location_address); ?>">
					<input type="hidden" name="evcal_organizer" id="evcal_organizer" value="<?php echo esc_html__($evcal_organizer); ?>">
					<input type="hidden" name="event_time" id="event_time" value="<?php echo esc_html__($new_estart).' - '.esc_html__($new_eend); ?>">
					<input type="hidden" name="ics_url" id="ics_url" value="<?php echo esc_html__($ics_url); ?>">
					<input type="hidden" name="event_type" id="event_type" value="<?php echo esc_html__($terms[0]->name); ?>">
					<input type="hidden" name="event_link" id="event_link" value="<?php echo esc_html__($event_link); ?>"> -->
					<div>
						<span style="font-weight: bold; margin-top: 10px; color: black; display: block;" >Select Invite List Selection Method</span>
						<select id="get_invite_type" class="ui fluid dropdown">
							<option value='custom_list' selected>Upload Custom Invite List</option>
							<option value='super_group'>Invite Super Group(s)</option>
						</select>

						<div id="group_container" style="display: none;">
							<select name="group[]" multiple="" class="ui fluid dropdown" id="group">
							</select>
						</div>
						<div id="custom_list">
							<span style="font-weight: bold; margin-top: 10px; color: black; display: block;" >Enter/Paste Emails (Email addresses can be separated by commas, semi-colons, or new-lines.):</span>
							<textarea id="txt_custom_list" style="height: 150px"></textarea>
						</div>

					</div>
					<div style="margin-top:20px;">
						<input type="submit" name="submit" value="Submit" id="submit">
					</div>
				</form>
			</div>
		</div>
	</div>
<style>
body {
	font-size: 16px;
    line-height: 1.5;
    background-color: #005395;
	font-family: "intel_clear_wlatlight", "Libre Franklin", "Helvetica Neue", helvetica, arial, sans-serif;
	font-weight: 400;
}
.header-links a {
	color: white;
}
label span {
    color: #404040;
}
select {
    width: 30%;
    height: 4em !important;
}

/* The Modal (background) */
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 10; /* Sit on top */
    padding-top: 100px; /* Location of the box */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgb(0,0,0); /* Fallback color */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}
/* Modal Content */
.modal-content {
    background-color: #fefefe;
    margin: auto;
    border: 1px solid #888;
    width: 80%;
}
.modal-header {
    padding: 15px;
    border-bottom: 1px solid #e5e5e5;
}
.modal-title {
    margin: 0;
    line-height: 1.42857143;
	clear: none;
    padding: 0px;
}
/* The Close Button */
.close {
    float: right;
    font-size: 21px;
    font-weight: 700;
    line-height: 1;
    color: #000;
    text-shadow: 0 1px 0 #fff;
    filter: alpha(opacity=20);
    opacity: .2;
}
.modal-body {
    position: relative;
    padding: 15px;
}
.close:hover,
.close:focus {
    color: #000;
    text-decoration: none;
    cursor: pointer;
}

/* success message */
		.evoau_success_msg{text-align: center;}
		.limitSubmission .evoau_success_msg p{color: #ffffff;}
		.limitSubmission .evoau_success_msg p b:before{
			content:"!";
			-webkit-transform: rotate(0deg);
  			-moz-transform: rotate(0deg);
  			-ms-transform: rotate(0deg);
  			-o-transform: rotate(0deg);
  			transform: rotate(0deg);
  			border:none;
  			margin:0;
  			top:auto; left: auto;
  			height: auto;
  			width: 40px;
  			line-height: 120%;
  			font-size: 32px;
		}
		body .evoau_success_msg h3 {color:#ffffff;} 
		body .evoau_success_msg p{color: #ffffff;
			line-height: 1.5;
	  		margin: 0;
	  		font-size: 18px;
	  		text-align: center;
	  		padding-top: 20px;
		}
		.evoau_success_msg p b{
			position: relative;
	  		display: block;
	  		width: 45px;
	  		height: 45px;
	  		border: 3px solid #ffffff;
	  		border-radius: 50%;
	  		margin: 0 auto;
				margin-bottom: 10px;
	  		box-sizing: border-box;
		}
		.evoau_success_msg p b:before{
			content: '';
	  		display: block;
	  		position: absolute;
	  		top: 50%;
	  		left: 50%;
	  		margin: -9px 0 0 -9px;
	  		height: 10px;
	 		width: 16px;
	  		border: solid #ffffff;
	  		border-width: 0 0 4px 4px;
	  		-webkit-transform: rotate(-45deg);
	  		-moz-transform: rotate(-45deg);
	  		-ms-transform: rotate(-45deg);
	  		-o-transform: rotate(-45deg);
	  		transform: rotate(-45deg);
		}
.evoau_submission_form.successForm {
    background-color: #9BD28C;
	padding: 13px 20px;
    border: 1px solid #d9d7d7;
    border-bottom-width: 3px;
    position: relative;
    border-radius: 5px;
    overflow: hidden;
}
   
.evoau_submission_form.successForm h2{
	color: #ffffff !important;
}
</style>
<script>
var g_event_id;

var ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
$('.ui.dropdown').dropdown({placeholder:'Select Group'});
$(document).ready(function(){
	$( "#get_invite_type" ).on( "change", function () {
		var invite_type = $( this ).val();
		if( invite_type === "custom_list" ) {
			$("#custom_list").show();
			$("#group_container").hide();
		} else if( invite_type === "super_group" ) {
			$("#custom_list").hide();
			$("#group_container").show();
		}
	} );
  
    $(".invite_event_single").click(function() {
		//Store the event id in a global variable
		g_event_id = $( this ).data( "eid" );

        $("#myModal").show();		
			var dropdown = $('#group');
			dropdown.empty();
			dropdown.append('<option selected="true" disabled>Select Group</option>');
			dropdown.prop('selectedIndex', 0);
			/*const url = '<?php echo esc_html__($wporg_atts['file'], 'wporg'); ?>';
			// Populate dropdown with list of provinces
			$.getJSON(url, function (data) {
			  $.each(data, function (key, entry) {
				dropdown.append($('<option></option>').attr('value', entry.bu).text(entry.bu));
			  })
			});*/

			//Load the Super groups
			var data = {
				'action': 'fln_get_super_groups'
			};

			$.post( ajax_url, data, function( response ) {
				for( var i = 0; i < response.length; i++ ) {
					dropdown.append($('<option></option>').attr('value', response[ i ].bu).text(response[ i ].bu));
				}
			} );
    });	
	// When the user clicks on <span> (x), close the modal
	$(".close").click(function(){
			$("#myModal").hide();
	});		
	/* close on click outside of modal */
	$("#myModal").on('click', function(e) {
	  if (e.target !== this) return;
	  $("#myModal").hide();
	});
});
</script>

<script>
      $(function () {

        $('form').on('submit', function (e) {

          e.preventDefault();

          /*$.ajax({
            type: 'post',
            url: '<?php echo esc_html__($wporg_atts['post-url'], 'wporg'); ?>',
            data: $('form').serialize(),
            success: function () {
			  console.log('event_id : ' + $('#event_id').val() );
			  console.log('event_title : ' + $('#event_title').val() );
			  console.log('evcal_subtitle : ' + $('#event_subtitle').val() );
			  console.log('event_details : ' + $('#event_details').val() );	
			  console.log('ics_url : ' + $('#ics_url').val() );
			  console.log('group : ' + $('#group').val() );
			  console.log('event_location : ' + $('#event_location').val() );  		  
			  console.log('event_time : ' + $('#event_time').val() );
			  console.log('evcal_organizer : ' + $('#evcal_organizer').val() );
			  console.log('evcal_type : ' + $('#event_type').val() );
			  $("#myModal").hide();
             // window.location.href = 'http://newblueconnect.com.s224062.gridserver.com/';
            }
          });*/

			//Sub
			var data = {
				'action': 'fln_invite_guests',
				'event_data': {
					'event_id': g_event_id,
					'additional_invites': true
				}
			};
			if( $( "#get_invite_type" ).val() === "custom_list" ) {
				var custom_list = $( '#txt_custom_list' ).val();
				if (custom_list.search(/<|>/g) != -1) {
				    custom_list = custom_list.match(/\S+@\S+\.\S+/g);
				    custom_list = custom_list.join('\n');
				    custom_list = custom_list.replace( /<|>|;/g, '');
				    data.event_data.custom_list = custom_list;
				    $( '#txt_custom_list' ).val( custom_list );
				} else {
				    custom_list = custom_list.replace( /; /g, ';' );
				    custom_list = custom_list.replace( /, /g, ',' );
				    custom_list = custom_list.replace( / /g, '\n');
				    custom_list = custom_list.replace( /;/g, '\n' );
				    custom_list = custom_list.replace( /,/g, '\n' );
				    data.event_data.custom_list = custom_list;
				    $( '#txt_custom_list' ).val( custom_list );
				}
			} else if( $( "#get_invite_type" ).val() === "super_group" ) {
				data.event_data.group = $( '#group' ).val();
			}

			jQuery.post( ajax_url, data, function( response ) {
				console.log('event_id : ' + g_event_id );
				$("#myModal .modal-body").removeClass( "evoloadbar" );
				$("#myModal .modal-body").removeClass( "bottom" );
				$("#myModal").hide();
				$("#myBtn").after("<p>Invitations Sent</p>");
				$("#myBtn").hide();
			} );
			$("#myModal .modal-body").addClass( "evoloadbar" );
			$("#myModal .modal-body").addClass( "bottom" );
        });

      });
    </script>

<?php 
}

add_action( 'wp_ajax_fln_update_notifications', 'fln_update_notifications', 10, 3);
function fln_update_notifications() {
	global $flnNewBlueConnect;
	//$flnNewBlueConnect->log( 'Update Notifications' );
	if( ! current_user_can( 'edit_eventon' ) && ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( 'You do not have access to this feature.' );
		$flnNewBlueConnect->log( 'You do not have access to this feature.' );
		return;
	}

	$event_id = intval( $_POST[ 'eventId' ] );
	if( isset( $event_id ) && $event_id > 0 ) {
		$flnNewBlueConnect->send_event_update_notifications( $event_id );
		return true;
	}
	wp_send_json_error( 'Invalid Event!' );
	return;
}
