<?php
defined('BASEPATH') OR exit('No direct script access allowed');
	class Language extends CI_controller
	{
		function change($lang){
			$this->session->set_userdata(array('Language'=>$lang));
			redirect($_SERVER['HTTP_REFERER'],'refresh');
		}
	}

?>