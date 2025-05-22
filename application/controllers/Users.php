<?php
defined('BASEPATH') OR exit('No direct script access allowed');
#[\AllowDynamicProperties]
class Users extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('Users_model');
        $this->load->helper('url');
        header('Content-Type: application/json');
        $this->load->library('session');
    }

    private function _is_loggedIn($verified, $user_data){
        if($verified){
            echo json_encode(['status'=> true, 'message'=> 'Pass Match']);
            $newdata = array(
                'id'  => $user_data->id,
                'email'     => $user_data->email,
                'logged_in' => TRUE
            );
            $this->session->set_userdata('loggedInUser',$newdata);
            return;
        };
        echo json_encode(['status'=> false, 'message'=> 'Invalid Pass.']);
        return;
    }

    public function index() {
        $base_url = base_url();
        if ($this->input->method() !== 'get') {
            echo json_encode(['status' => false, 'message' => 'Invalid HTTP method. Use GET method.']);
            return;
        }
        if(empty($this->session->loggedInUser)){
            echo json_encode(['status' => false, 'message' => 'Not Logged In!']);
            return;
        }

        $query = $this->db->get('users_2');
        $result = $query->result();

        if(!empty($result)){
            foreach($result as $user){
                $user->image = $base_url . 'uploads/' . $user->image;
            }
            echo json_encode($result);
        }else {
            echo json_encode(["status"=> false, "message"=> "No Users Found!"]);
        }
        return;
    }

    public function create() {
        if ($this->input->method() !== 'post') {
            echo json_encode(['status' => false, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }

        $this->load->library('upload');
        $base_url = base_url();

        $config['upload_path'] = FCPATH . 'uploads/';
        $config['allowed_types'] = 'jpg|jpeg|png|gif';
        $config['max_size']      = 2048;
        $config['encrypt_name']  = TRUE;
        $this->upload->initialize($config);

        $name  = $this->input->post('name');
        $email = $this->input->post('email');
        $password = $this->input->post('password');
        $gender = $this->input->post('gender');
        $phone_number = $this->input->post('phone_number');
        $address = $this->input->post('address');

        if(!empty($password)){
            $password = password_hash($password, PASSWORD_BCRYPT);
        }

        $image_name = 'Default.jpg';
        if (!empty($_FILES['image']['name'])) {
            if(count($_FILES['image']['name']) != 1){
                echo json_encode(['status' => false, 'message' => "Multiple images not allowed!"]);
                return;
            }else {
                $_FILES['single_image']['name']     = $_FILES['image']['name'][0];
                $_FILES['single_image']['type']     = $_FILES['image']['type'][0];
                $_FILES['single_image']['tmp_name'] = $_FILES['image']['tmp_name'][0];
                $_FILES['single_image']['error']    = $_FILES['image']['error'][0];
                $_FILES['single_image']['size']     = $_FILES['image']['size'][0];
            }            
            if (!$this->upload->do_upload('single_image')) {
                echo json_encode(['status' => false, 'message' => $this->upload->display_errors()]);
                return;
            } else {
                $uploaded_data = $this->upload->data();
                $image_name = $uploaded_data['file_name'];
            }
        }

        $user_data = [
            'name'         => $name,
            'email'        => $email,
            'password'     => $password,
            'image'        => $image_name,
            'gender'       => $gender,
            'phone_number' => $phone_number,
            'address'      => $address,
        ];

        $insert_id = $this->Users_model->create_user($user_data);

        if ($insert_id) {
            echo json_encode(['status' => true, 'message' => 'User created', 'user_id' => $insert_id]);
        } else {
            $db_error = $this->db->error();
            echo json_encode(['status' => false,'message' => $db_error['message']]);
        }
        return;
    }

    public function get_user($id){
        if ($this->input->method() !== 'get') {
            echo json_encode(['status' => false, 'message' => 'Invalid HTTP method. Use GET method.']);
            return;
        }
        if(empty($this->session->loggedInUser)){
            echo json_encode(['status' => false, 'message' => 'Not Logged In!']);
            return;
        }
        $base_url = base_url();
        $user = $this->Users_model->get_user_by_id($id);
        if (!empty($user)) {
            $user->image = $base_url . 'uploads/' . $user->image;
            echo json_encode(['status' => true, 'user' => $user]);
        } else {
            echo json_encode(['status' => false, 'message' => 'User Not Found with id ' . $id]);
        }
        return;
    }

    public function update($id) {
        if ($this->input->method() !== 'post') {
            echo json_encode(['status' => false, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }
        if(empty($this->session->loggedInUser)){
            echo json_encode(['status' => false, 'message' => 'Not Logged In!']);
            return;
        }

        $this->load->library('upload');
        $base_url = base_url();
        $user = $this->Users_model->get_user_by_id($id);
        if (empty($user)) {
            echo json_encode(['status' => false, 'message' => 'User not found']);
            return;
        }

        $name  = $this->input->post('name') ?? $user->name;
        $email = $this->input->post('email') ?? $user->email;
        $password = $this->input->post('password') ?? $user->password;
        $gender = $this->input->post('gender') ?? $user->gender;
        $phone_number = $this->input->post('phone_number') ?? $user->phone_number;
        $address = $this->input->post('address') ?? $user->address;
        $image_name = $user->image;

        if (!empty($_FILES['image']['name'])) {
            if(count($_FILES['image']['name']) != 1){
                echo json_encode(['status' => false, 'message' => "Multiple images not allowed!"]);
                return;
            }else {
                $_FILES['single_image']['name']     = $_FILES['image']['name'][0];
                $_FILES['single_image']['type']     = $_FILES['image']['type'][0];
                $_FILES['single_image']['tmp_name'] = $_FILES['image']['tmp_name'][0];
                $_FILES['single_image']['error']    = $_FILES['image']['error'][0];
                $_FILES['single_image']['size']     = $_FILES['image']['size'][0];
            }            
            if (!$this->upload->do_upload('single_image')) {
                echo json_encode(['status' => false, 'message' => $this->upload->display_errors()]);
                return;
            } else {
                $uploaded_data = $this->upload->data();
                $image_name = $uploaded_data['file_name'];
            }
        }

        $update_data = [
            'name'         => $name,
            'email'        => $email,
            'password'     => $password,
            'image'        => $image_name,
            'gender'       => $gender,
            'phone_number' => $phone_number,
            'address'      => $address,
        ];

        $updated = $this->Users_model->update_user($id, $update_data);

        if ($updated) {
            echo json_encode(['status' => true, 'message' => 'User updated successfully']);
        } else {
            $db_error = $this->db->error();
            echo json_encode(['status' => false,'message' => $db_error['message']]);        
        }
        return;
    }

    public function delete($id) {
        if ($this->input->method() !== 'delete') {
            echo json_encode(['status' => false, 'message' => 'Invalid HTTP method. Use DELETE method.']);
            return;
        }
        if(empty($this->session->loggedInUser)){
            echo json_encode(['status' => false, 'message' => 'Not Logged In!']);
            return;
        }
        $user = $this->Users_model->get_user_by_id($id);
        if (!$user) {
            echo json_encode(['status' => false, 'message' => 'User not found']);
            return;
        }
        $result = $this->Users_model->delete_user($id);
        echo json_encode([
            'status' => $result,
            'message' => $result ? 'Deleted user of id ' . $id : 'Failed to delete user'
        ]);
        return;
    }

    public function login() {
        if ($this->input->method() !== 'post') {
            echo json_encode(['status' => false, 'message' => 'Invalid HTTP method. Use POST method.']);
            return;
        }
        $email = $this->input->post('email');
        $password = $this->input->post('password');

        if(empty($email) || empty($password)){
            echo json_encode(['status'=> false, 'message'=> 'Email & Pass Req!']);
            return;
        }
        $user = $this->db->get_where('users_2', ['email'=> $email])->row();
        $pass_verified = false;

        if(password_verify($password, $user->password)){
            $pass_verified = true;
        }

        $this->_is_loggedIn($pass_verified, $user);
    }

    public function logout() {
        if(empty($this->session->loggedInUser)){
            echo json_encode(['status' => false, 'message' => 'Not Logged In!']);
            return;
        }
        $this->session->unset_userdata('loggedInUser');
        echo json_encode(['status' => true, 'message' => 'Logged Out!']);
        return;
    }
}
