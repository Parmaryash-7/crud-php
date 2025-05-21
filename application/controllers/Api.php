<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->helper(['url', 'form']);
        $this->load->library('upload');

        // CORS
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
        header('Content-Type: application/json');

        // Handle CORS preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
    }

    public function users($id = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => false, 'message' => 'Invalid request method. Use GET.']);
            return;
        }

        $data = $this->User_model->get_users($id);
        echo json_encode($data ?: ['status' => false, 'message' => 'User not found']);
    }

    public function create_user()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => false, 'message' => 'Invalid request method. Use POST.']);
            return;
        }

        $name = $this->input->post('name');
        $email = $this->input->post('email');

        if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => false, 'message' => 'Valid name and email are required.']);
            return;
        }

        $image_path = base_url('uploads/Default.jpg');

        // Handle Image Upload
        if (!empty($_FILES['image']['name'])) {
            $config = [
                'upload_path' => FCPATH . 'uploads/',
                'allowed_types' => 'jpg|jpeg|png|gif',
                'max_size' => 2048,
                'encrypt_name' => true
            ];

            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0777, true);
            }

            $this->upload->initialize($config);

            if ($this->upload->do_upload('image')) {
                $uploaded_data = $this->upload->data();
                $image_path = base_url('uploads/' . $uploaded_data['file_name']);
            } else {
                echo json_encode(['status' => false, 'message' => $this->upload->display_errors()]);
                return;
            }
        }

        $user_data = [
            'name' => $name,
            'email' => $email,
            'image' => $image_path
        ];

        $insert_id = $this->User_model->create_user($user_data);
        echo json_encode(['status' => true, 'message' => 'User created successfully', 'user_id' => $insert_id]);
    }

    public function update_user($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => false, 'message' => 'Invalid request method. Use PUT or POST.']);
            return;
        }

        $existing_user = $this->User_model->get_users($id);
        if (!$existing_user) {
            echo json_encode(['status' => false, 'message' => 'User not found']);
            return;
        }

        // Manually parse raw PUT data
        parse_str(file_get_contents("php://input"), $_PUT);
        $name = $_PUT['name'] ?? $existing_user->name;
        $email = $_PUT['email'] ?? $existing_user->email;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => false, 'message' => 'Invalid email format']);
            return;
        }

        $image_path = $existing_user->image;

        if (!empty($_FILES['image']['name'])) {
            $config = [
                'upload_path' => FCPATH . 'uploads/',
                'allowed_types' => 'jpg|jpeg|png|gif',
                'max_size' => 2048,
                'encrypt_name' => true
            ];

            $this->upload->initialize($config);

            if ($this->upload->do_upload('image')) {
                $uploaded_data = $this->upload->data();
                $image_path = base_url('uploads/' . $uploaded_data['file_name']);
            } else {
                echo json_encode(['status' => false, 'message' => $this->upload->display_errors()]);
                return;
            }
        }

        $update_data = [
            'name' => $name,
            'email' => $email,
            'image' => $image_path
        ];

        $updated = $this->User_model->update_user($id, $update_data);
        echo json_encode($updated ? ['status' => true, 'message' => 'User updated successfully'] : ['status' => false, 'message' => 'Failed to update user']);
    }

    public function delete_user($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            echo json_encode(['status' => false, 'message' => 'Invalid request method. Use DELETE.']);
            return;
        }

        $user = $this->User_model->get_users($id);
        if (!$user) {
            echo json_encode(['status' => false, 'message' => 'User not found']);
            return;
        }

        $deleted = $this->User_model->delete_user($id);
        echo json_encode($deleted ? ['status' => true, 'message' => 'User deleted successfully'] : ['status' => false, 'message' => 'Failed to delete user']);
    }
}
