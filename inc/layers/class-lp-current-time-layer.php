<?php

class LP_Certificate_Current_Time_Layer extends LP_Certificate_Datetime_Layer
{
	public function __construct($options)
	{
		parent::__construct($options);
	}

	public function apply($data)
	{
		// $this->options['text'] = date($this->get_format(), current_time('timestamp'));
		//get user items
		$user_items = AQskill_Certificate::get_user_items($data['user_id'], $data['course_id']);
		//get course completion date
		$course_completion_date = array_shift($user_items);
		$this->options['text'] = date('F j, Y', strtotime($course_completion_date->end_time));
	}
}
