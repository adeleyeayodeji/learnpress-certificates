<?php

class LP_Certificate_Course_End_Date_Layer extends LP_Certificate_Datetime_Layer {
	public function apply( $data ) {
		$user        = isset( $data['user_id'] ) ? learn_press_get_user( $data['user_id'] ) : '';
		$course_data = isset( $data['course_id'] ) ? $user->get_course_data( $data['course_id'] ) : false;

		if ( $course_data ) {
			$finish_time = $course_data->get_end_time();
			if ( $finish_time ) {
				$end_time_formated     = learn_press_date_i18n( $course_data->get_end_time()->getTimestamp(), $this->get_format() );
				$this->options['text'] = $end_time_formated;
			}
		} else {
			$this->options['text'] = $this->options['text'];
		}
	}
}
