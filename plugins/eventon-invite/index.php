<?php
   /*
   Plugin Name: EventON - Invite
   Plugin URI: http://www.myeventon.com/
   description:Invite group
   Version: 1.2
   Author: Hero Digital
   Author URI: http://herodigital.com  
   License: GPL2
   */
       
	// Get the Timzezone in UFC Offset  format

	function get_UTC_offset(){

		$offset = (get_option('gmt_offset', 0) * 3600);
		$opt = get_option('evcal_options_evcal_1');;
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

				$new_times[$key] = $new_timeT.'T'.$new_timeZ.'00Z';

			}



			return $new_times;

		}
		
        
  function invite_shortcode($atts = [], $content = null, $tag = '')   
{
	ob_start();
	global $wpdb;

	$user_id = get_current_user_id();

	//echo "SELECT * from $wpdb->posts WHERE post_author =".$user_id;

	$post_id = $wpdb->get_results("SELECT * from $wpdb->posts WHERE post_type='ajde_events' and post_status='publish' and post_author =".$user_id." ORDER BY post_date DESC LIMIT 1");
	
	//print_r($_POST);   
	
	// $post_id[0]->ID;
	
	$evcal_subtitle= get_post_meta($post_id[0]->ID , 'evcal_subtitle',true);
	
	$estart= get_post_meta($post_id[0]->ID , 'evcal_srow',true);
	
	$new_estart = date('jS F(l) - h:i A', $estart);
	
	$eend = get_post_meta($post_id[0]->ID , 'evcal_erow',true);
	
	$new_eend = date('jS F(l) - h:i A', $eend);
	
	$timezone = get_post_meta( $post_id[0]->ID , 'evotimezone', true );
	
	$evcal_location_name = get_post_meta( $post_id[0]->ID , 'evcal_location_name', true );
	
	$location_address = get_post_meta( $post_id[0]->ID , 'location_address', true );
	
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
    <div class="wporg-box">
	
		<div id="eventon_form" class="evoau_submission_form successForm" >   
		<div class="evoau_success_msg" style=""><p><b></b>Is this event open to all? Then you are all set!<br> Otherwise, click below to select your group of attendees!</p>
		<h3><strong>Event : </strong> <?php echo esc_html__($post_id[0]->post_title); ?></h3>
			<button type="button" class="" id="myBtn"><?php echo esc_html__($wporg_atts['button-text'], 'wporg'); ?> </button>   
		
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
							
							<div>
								<select name="group[]" multiple="" class="ui fluid dropdown" id="group">
								</select>
							</div>
							<div style="margin-top:20px;">
								<input type="submit" name="submit" value="submit" id="submit">
							</div>
						</form>
					</div>
					
				</div>
			</div>
		</div>
    </div>
	
<style>
select {
    width: 30%;
    height: 4em !important;
}
body {font-family: Arial, Helvetica, sans-serif;}
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
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.3.3/semantic.min.css">
<script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.3.3/semantic.min.js"></script>
<script>
var ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
$('.ui.dropdown').dropdown({placeholder:'Select Group'});
$(document).ready(function(){    
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
					'event_time': $('#event_time').val(),
					'evcal_organizer': $('#evcal_organizer').val(),
					'evcal_type': $('#event_type').val()
				}
			};
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
				$("#myModal").hide();
				$("#myBtn").after("<p>Invitations Sent</p>");
				$("#myBtn").hide();
			} );
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

// only for frontend
if(!is_admin()){
	// actionUser intergration
	add_action('evoau_frontform_evotimezone',  'evoautimezone_fields', 10, 6);	
}
// Frontend showing fields and saving values  
function evoautimezone_fields($field, $event_id, $default_val, $EPMV, $opt2, $lang){
	?>
		<div class='row evotest'><p>
		<label for="timezone">Select the event's timezone:</label>
        
        <select class="form-control" id="timezone" name="evotimezone">   
        	
        	<option value="" selected="selected">select timezone</option>
			<option value="Pacific/Midway">(GMT-11:00) Midway Island, Samoa</option>
			<option value="America/Adak">(GMT-10:00) Hawaii-Aleutian</option>
			<option value="Etc/GMT+10">(GMT-10:00) Hawaii</option>
			<option value="Pacific/Marquesas">(GMT-09:30) Marquesas Islands</option>
			<option value="Pacific/Gambier">(GMT-09:00) Gambier Islands</option>
			<option value="America/Anchorage">(GMT-09:00) Alaska</option>
			<option value="America/Ensenada">(GMT-08:00) Tijuana, Baja California</option>
			<option value="Etc/GMT+8">(GMT-08:00) Pitcairn Islands</option>
			<option value="America/Los_Angeles">(GMT-08:00) Pacific Time (US & Canada)</option>
			<option value="America/Denver">(GMT-07:00) Mountain Time (US & Canada)</option>
			<option value="America/Chihuahua">(GMT-07:00) Chihuahua, La Paz, Mazatlan</option>
			<option value="America/Dawson_Creek">(GMT-07:00) Arizona</option>
			<option value="America/Belize">(GMT-06:00) Saskatchewan, Central America</option>
			<option value="America/Cancun">(GMT-06:00) Guadalajara, Mexico City, Monterrey</option>
			<option value="Chile/EasterIsland">(GMT-06:00) Easter Island</option>
			<option value="America/Chicago">(GMT-06:00) Central Time (US & Canada)</option>
			<option value="America/New_York">(GMT-05:00) Eastern Time (US & Canada)</option>
			<option value="America/Havana">(GMT-05:00) Cuba</option>
			<option value="America/Bogota">(GMT-05:00) Bogota, Lima, Quito, Rio Branco</option>
			<option value="America/Caracas">(GMT-04:30) Caracas</option>
			<option value="America/Santiago">(GMT-04:00) Santiago</option>
			<option value="America/La_Paz">(GMT-04:00) La Paz</option>
			<option value="Atlantic/Stanley">(GMT-04:00) Faukland Islands</option>
			<option value="America/Campo_Grande">(GMT-04:00) Brazil</option>
			<option value="America/Goose_Bay">(GMT-04:00) Atlantic Time (Goose Bay)</option>
			<option value="America/Glace_Bay">(GMT-04:00) Atlantic Time (Canada)</option>
			<option value="America/St_Johns">(GMT-03:30) Newfoundland</option>
			<option value="America/Araguaina">(GMT-03:00) UTC-3</option>
			<option value="America/Montevideo">(GMT-03:00) Montevideo</option>
			<option value="America/Miquelon">(GMT-03:00) Miquelon, St. Pierre</option>
			<option value="America/Godthab">(GMT-03:00) Greenland</option>
			<option value="America/Argentina/Buenos_Aires">(GMT-03:00) Buenos Aires</option>
			<option value="America/Sao_Paulo">(GMT-03:00) Brasilia</option>
			<option value="America/Noronha">(GMT-02:00) Mid-Atlantic</option>
			<option value="Atlantic/Cape_Verde">(GMT-01:00) Cape Verde Is.</option>
			<option value="Atlantic/Azores">(GMT-01:00) Azores</option>
			<option value="Europe/Belfast">(GMT) Greenwich Mean Time : Belfast</option>
			<option value="Europe/Dublin">(GMT) Greenwich Mean Time : Dublin</option>
			<option value="Europe/Lisbon">(GMT) Greenwich Mean Time : Lisbon</option>
			<option value="Europe/London">(GMT) Greenwich Mean Time : London</option>
			<option value="Africa/Abidjan">(GMT) Monrovia, Reykjavik</option>
			<option value="Europe/Amsterdam">(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna</option>
			<option value="Europe/Belgrade">(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague</option>
			<option value="Europe/Brussels">(GMT+01:00) Brussels, Copenhagen, Madrid, Paris</option>
			<option value="Africa/Algiers">(GMT+01:00) West Central Africa</option>
			<option value="Africa/Windhoek">(GMT+01:00) Windhoek</option>
			<option value="Asia/Beirut">(GMT+02:00) Beirut</option>
			<option value="Africa/Cairo">(GMT+02:00) Cairo</option>
			<option value="Asia/Gaza">(GMT+02:00) Gaza</option>
			<option value="Africa/Blantyre">(GMT+02:00) Harare, Pretoria</option>
			<option value="Asia/Jerusalem">(GMT+02:00) Jerusalem</option>
			<option value="Europe/Minsk">(GMT+02:00) Minsk</option>
			<option value="Asia/Damascus">(GMT+02:00) Syria</option>
			<option value="Europe/Moscow">(GMT+03:00) Moscow, St. Petersburg, Volgograd</option>
			<option value="Africa/Addis_Ababa">(GMT+03:00) Nairobi</option>
			<option value="Asia/Tehran">(GMT+03:30) Tehran</option>
			<option value="Asia/Dubai">(GMT+04:00) Abu Dhabi, Muscat</option>
			<option value="Asia/Yerevan">(GMT+04:00) Yerevan</option>
			<option value="Asia/Kabul">(GMT+04:30) Kabul</option>
			<option value="Asia/Yekaterinburg">(GMT+05:00) Ekaterinburg</option>
			<option value="Asia/Tashkent">(GMT+05:00) Tashkent</option>
			<option value="Asia/Kolkata">(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi</option>
			<option value="Asia/Katmandu">(GMT+05:45) Kathmandu</option>
			<option value="Asia/Dhaka">(GMT+06:00) Astana, Dhaka</option>
			<option value="Asia/Novosibirsk">(GMT+06:00) Novosibirsk</option>
			<option value="Asia/Rangoon">(GMT+06:30) Yangon (Rangoon)</option>
			<option value="Asia/Bangkok">(GMT+07:00) Bangkok, Hanoi, Jakarta</option>
			<option value="Asia/Krasnoyarsk">(GMT+07:00) Krasnoyarsk</option>
			<option value="Asia/Hong_Kong">(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi</option>
			<option value="Asia/Irkutsk">(GMT+08:00) Irkutsk, Ulaan Bataar</option>
			<option value="Australia/Perth">(GMT+08:00) Perth</option>
			<option value="Australia/Eucla">(GMT+08:45) Eucla</option>
			<option value="Asia/Tokyo">(GMT+09:00) Osaka, Sapporo, Tokyo</option>
			<option value="Asia/Seoul">(GMT+09:00) Seoul</option>
			<option value="Asia/Yakutsk">(GMT+09:00) Yakutsk</option>
			<option value="Australia/Adelaide">(GMT+09:30) Adelaide</option>
			<option value="Australia/Darwin">(GMT+09:30) Darwin</option>
			<option value="Australia/Brisbane">(GMT+10:00) Brisbane</option>
			<option value="Australia/Hobart">(GMT+10:00) Hobart</option>
			<option value="Asia/Vladivostok">(GMT+10:00) Vladivostok</option>
			<option value="Australia/Lord_Howe">(GMT+10:30) Lord Howe Island</option>
			<option value="Etc/GMT-11">(GMT+11:00) Solomon Is., New Caledonia</option>
			<option value="Asia/Magadan">(GMT+11:00) Magadan</option>
			<option value="Pacific/Norfolk">(GMT+11:30) Norfolk Island</option>
			<option value="Asia/Anadyr">(GMT+12:00) Anadyr, Kamchatka</option>
			<option value="Pacific/Auckland">(GMT+12:00) Auckland, Wellington</option>
			<option value="Etc/GMT-12">(GMT+12:00) Fiji, Kamchatka, Marshall Is.</option>
			<option value="Pacific/Chatham">(GMT+12:45) Chatham Islands</option>
			<option value="Pacific/Tongatapu">(GMT+13:00) Nuku'alofa</option>
			<option value="Pacific/Kiritimati">(GMT+14:00) Kiritimati</option>

        </select>
		</p></div>
	<?php		 
}


add_action('evoau_save_formfields',  'evoautest_save_values', 10, 3);
function evoautest_save_values($field, $fn, $created_event_id){

	if ( isset( $_POST['evotimezone'] )){
    update_post_meta($created_event_id, 'evo_event_timezone', $_POST['evotimezone']); 
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
