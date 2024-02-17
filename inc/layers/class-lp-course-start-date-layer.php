<?php

class LP_Certificate_Course_Start_Date_Layer extends LP_Certificate_Datetime_Layer
{
	public function apply($data)
	{
		if (!isset($data['user_id']) || !isset($data['course_id'])) {
			return;
		}

		$user        = learn_press_get_user($data['user_id']);
		$course_data = $user->get_course_data($data['course_id']);
		$course_data->get_start_time();
		$start_time_formated = learn_press_date_i18n($course_data->get_start_time()->getTimestamp(), $this->get_format());

		if (!$start_time_formated) {
			$start_time_formated = learn_press_date_i18n($course_data->get_start_time_gmt()->getTimestamp(), $this->get_format());
		}
		$this->options['text'] = ($course_data) ? $start_time_formated : $this->options['text'];
	}
}
