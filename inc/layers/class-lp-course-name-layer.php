<?php
/**
 * Class LP_Certificate_Course_Name_Layer
 */

class LP_Certificate_Course_Name_Layer extends LP_Certificate_Layer {
	public function apply( $data ) {
		$this->options['text'] = ! empty( $data['course_id'] ) ? get_post( $data['course_id'] )->post_title : $this->options['text'];
	}
}
