<?php

/**
 * Class LP_Certificate_Student_Name_Layer
 */
class LP_Certificate_Student_Name_Layer extends LP_Certificate_Layer
{
	public function apply($data)
	{
		if (!empty($data['user_id'])) {
			$user                  = learn_press_get_user($data['user_id']);
			$this->options['text'] = !empty($user) ? $user->get_display_name() : $this->options['text'];
		}
	}
}
