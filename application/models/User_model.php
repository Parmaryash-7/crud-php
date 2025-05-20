<?php
class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_users($id = null) {
        if ($id === null) {
            return $this->db->get('users')->result();
        } else {
            return $this->db->get_where('users', ['id' => $id])->row();
        }
    }

    public function insert_user($data) {
        return $this->db->insert('users', $data);
    }

    public function update_user($id, $data) {
        if (!empty($data)) {
            $this->db->where('id', $id);
            return $this->db->update('users', $data); 
        } else {
            return false;
        }
    }

    public function delete_user($id) {
        return $this->db->delete('users', ['id' => $id]);
    }
}
