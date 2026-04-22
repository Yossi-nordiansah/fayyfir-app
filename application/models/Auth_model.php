<?php
	
	class Auth_model extends CI_model
	{
	
	function register($username,$password,$nama,$email,$notelp,$code,$aktif)
	{
		$data_user = array(
			'nama'=>$nama,
			'email'=>$email,
			'notelp'=>$notelp,
			'username'=>$username,
			'password'=>$password,
			'resetpassword'=>$code,
			'aktif'=>$aktif
		);
		$this->db->insert('user',$data_user);
		return $this->db->insert_id();
	}

	function getUser($id)
	{
	  $query = $this->db->get_where('user',array('iduser'=>$id));
	  return $query->row_array();
	}

	public function activate($data, $id)
	{
	  $this->db->where('user.iduser', $id);
	  return $this->db->update('user', $data);
	}

	function testloginadmin($table,$where){
		return $this->db->get_where($table,$where);

	}

	function ploginadmin($table,$where){
		$query = $this->db->get_where($table,$where);
		return $query->result();
	}

	function testlogin($email,$password){		

		$this->db->select('*');
		$this->db->from('user');
		$this->db->where("(user.email = '$email' OR user.username = '$email')");
		$this->db->where('user.password', $password);
		// $this->db->where('user.aktif', 'y');
		return $this->db->get('');
		// return $this->db->get_where($table,$where);
	}	

	function plogin($email,$password){		
		$this->db->select('*');
		$this->db->from('user');
		$this->db->where("(user.email = '$email' OR user.username = '$email')");
		$this->db->where('user.password', $password);
		// $this->db->where('user.aktif', 'y');
		return $this->db->get('')->result();

		// $query = $this->db->get_where($table,$where);
		// return $query->result();
	}

	function updateresetkey($email,$resetkey)
	{
		$this->db->where('email', $email);
		$data = array('resetpassword' => $resetkey );
		$this->db->update('user', $data);
		if ($this->db->affected_rows()>0) {
			return true;
		}else{
			return false;
		}
	}	

	function resetpassword($resetkey,$password)
	{
		$this->db->where('resetpassword', $resetkey);
		$data = array('password' => $password );
		$this->db->update('user', $data);
		if ($this->db->affected_rows()>0) {
			return true;
		}else{
			return false;
		}
	}	

	function cekresetkey($resetkey)
	{
		$this->db->where('resetpassword', $resetkey);
		$this->db->from('user');
		return $this->db->count_all_results();
	}	

	public function dataadmin()
	{
		$this->db->select('*');
		$this->db->from('user');
		$this->db->where("(user.level = 'admin')");
		return $this->db->get('')->result();
	}

	public function datauser()
	{
		$this->db->select('*');
		$this->db->from('user');
		$this->db->where("(user.level = 'user')");
		return $this->db->get('')->result();
	}

	}
?>