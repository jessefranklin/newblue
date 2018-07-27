<?php
/**
 * RSVP Email class
 *
 * @author 		AJDE
 * @category 	Admin
 * @package 	eventon-rsvp/classes
 * @version     2.5.3
 */
class evors_email{

	public function __construct(){
		global $eventon_rs;
		$this->optRS = get_option('evcal_options_evcal_rs');
		$this->opt2 = $eventon_rs->opt2;
	}
	
	// SEND email
		function send_email($args, $type='confirmation'){
			global $eventon_rs;

			// when email sending is disabled 
			if(!empty($this->optRS['evors_disable_emails']) && $this->optRS['evors_disable_emails']=='yes') return false;
  
			if($type=='confirmation'){
				$args['html']= 'yes';

				$data_args = $this->get_email_data($args);

				return $eventon_rs->helper->send_email($data_args);
			}elseif($type=='digest'){
				$args['html']= 'yes';
				return $eventon_rs->helper->send_email(
					$this->get_email_data($args, 'digest')
				);
			}else{// notification email
				if(!empty($this->optRS['evors_notif']) && $this->optRS['evors_notif']=='yes'){
					global $eventon_rs;
					$args['html']= 'yes';
					return $eventon_rs->helper->send_email(
						$this->get_email_data($args, 'notification')
					);
				}
			}
		}

	// send email confirmation of RSVP  to submitter
		function get_email_data($args, $type='confirmation'){
			$this->evors_args = $args;

			$email_data = array();

			$from_email = $this->get_from_email($type);
			
			$email_data['args'] = $args;
			$email_data['type'] = $type;

			switch ($type) {
				case 'confirmation':
					$email_data['to'] = $args['email'];

					$email_data['subject'] = '[#'.$args['rsvp_id'].'] '.((!empty($this->optRS['evors_notfiesubjest_e']))? 
					htmlspecialchars_decode($this->optRS['evors_notfiesubjest_e']): __('RSVP Confirmation','eventon'));
					$filename = 'confirmation_email';
					$headers = 'From: '.$from_email;

				break;

				case 'digest':
					
					$__to_email = (!empty($this->optRS['evors_digestemail_to']) )?
						htmlspecialchars_decode ($this->optRS['evors_digestemail_to'])
						:get_bloginfo('admin_email');
					$email_data['to'] = $__to_email;

					$text = (!empty($this->optRS['evors_digestemail_subjest']))? $this->optRS['evors_digestemail_subjest']: 'Digest Email for {event-name}';
					
					if(!empty($args['e_id']))
						$text = str_replace('{event-name}', get_the_title($args['e_id']), $text);

					$email_data['subject'] = $text;
					$filename = 'digest_email';
					$headers = 'From: '.$from_email. "\r\n";

				break;
				
				default: // notification email
					$__to_email = (!empty($this->optRS['evors_notfiemailto']) )?
						htmlspecialchars_decode ($this->optRS['evors_notfiemailto'])
						:get_bloginfo('admin_email');

					// additional emails to receive email notification
						$event_pmv = get_post_custom($args['e_id']);

					// post author to be included
						$notify_event_author = evo_check_yn($event_pmv, 'evors_notify_event_author');
						if($notify_event_author){
							$post_author_id = get_post_field( 'post_author', $args['e_id'] );

							if(!empty($post_author_id)) 
								$author_email = get_the_author_meta( 'user_email' , $post_author_id);

							if($author_email)
								$__to_email .','. $author_email;
						}	

					// other email addresses mentioned in event edit page
						$_other_to = evo_var_val($event_pmv, 'evors_add_emails');


					$email_data['to'] = $__to_email.','.$_other_to;

					if(!empty($args['emailtype']) && $args['emailtype']=='update'){							
						$text = (!empty($this->optRS['evors_notfiesubjest_update']))? $this->optRS['evors_notfiesubjest_update']: 'Update RSVP Notification';
					}else{
						$text = (!empty($this->optRS['evors_notfiesubjest']))? $this->optRS['evors_notfiesubjest']: 'New RSVP Notification';
					}

					$email_data['subject'] ='[#'.$args['rsvp_id'].'] '.$text;
					$filename = 'notification_email';
					$headers = 'From: '.$from_email. "\r\n";
					$headers .= 'Reply-To: '.$args['email']. "\r\n";

				break;
			}
		
			if(isset($email_data['to'])){
				$email_data['message'] = $this->_get_email_body($args, $filename);
				$email_data['header'] = $headers;	
				$email_data['from'] = $from_email;
			}	
			
			return $email_data;
		}

	// return proper FROM email with name
		function get_from_email($type='confirmation'){

			if($type=='digest'){
				$__from_email = $this->get_from_email_address($type);
				$__from_email_name = $this->get_from_email_name($type);
					$from_email = (!empty($__from_email_name))? 
						$__from_email_name.' <'.$__from_email.'>' : $__from_email;
			}else{
				$var = ($type=='confirmation')?'_e':'';

				$__from_email = $this->get_from_email_address($type);
				$__from_email_name = $this->get_from_email_name($type);
					$from_email = (!empty($__from_email_name))? 
						$__from_email_name.' <'.$__from_email.'>' : $__from_email;
			}					
			return $from_email;
		}

		function get_from_email_address($type='confirmation'){
			if($type=='digest'){
				$__from_email = (!empty($this->optRS['evors_digestemail_from']) )?
					htmlspecialchars_decode ($this->optRS['evors_digestemail_from'])
					:get_bloginfo('admin_email');
			}else{
				$var = ($type=='confirmation')?'_e':'';
				$__from_email = (!empty($this->optRS['evors_notfiemailfrom'.$var]) )?
					htmlspecialchars_decode ($this->optRS['evors_notfiemailfrom'.$var])
					:get_bloginfo('admin_email');				
			}
			return $__from_email;
		}
		function get_from_email_name($type = 'confirmation'){
			if($type=='digest'){
				$__from_email_name = (!empty($this->optRS['evors_digestemail_fromN']) )?
					($this->optRS['evors_digestemail_fromN'])
					:get_bloginfo('name');					
			}else{
				$var = ($type=='confirmation')?'_e':'';
				$__from_email_name = (!empty($this->optRS['evors_notfiemailfromN'.$var]) )?
					($this->optRS['evors_notfiemailfromN'.$var])
					:get_bloginfo('name');
			}	
			return $__from_email_name;
		}

	// email body for confirmation
		function _get_email_body($evors_args, $file){
			global $eventon_rs;
			ob_start();

			$args = $evors_args;

			$file_location = EVO()->template_locator(
				$file.'.php', 
				$eventon_rs->addon_data['plugin_path']."/templates/", 
				'templates/email/rsvp/'
			);
			include($file_location);
			
			return ob_get_clean();
		}
	// this will return eventon email template driven email body
	// need to update this after evo 2.3.8 release
		function get_evo_email_body($message){
			global $eventon;
			// /echo $eventon->get_email_part('footer');
			ob_start();
			echo $eventon->get_email_part('header');
			echo $message;
			echo $eventon->get_email_part('footer');
			return ob_get_clean();
		}

	// Digest emails
		public function schedule_digest_email(){
			if(!empty($this->optRS['evors_digest']) && $this->optRS['evors_digest']=='yes'){
				$events = new WP_Query(array(
					'post_type'=>'ajde_events',
					'posts_per_page'=>-1,
					'meta_key'     => 'evors_daily_digest',
					'meta_value'   => 'yes',
				));

				// if there are events with RSVP digest enabled
				if($events->have_posts()){
					global $eventon_rs;

					while($events->have_posts()): $events->the_post();
						$eventid = $events->post->ID;
						$eventStartTime = get_post_meta($eventid, 'evcal_srow',true);
						$currentTime = current_time('timestamp');

						if($eventStartTime<= $currentTime) break;

						$what = $eventon_rs->frontend->send_email(array(
							'e_id'=>$eventid,
						), 'digest');
					endwhile;
				}
				wp_reset_postdata();
			}
		}
}