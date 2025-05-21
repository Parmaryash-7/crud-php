<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        header('Content-Type: application/json');

        // Optional CORS headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
    }

    // GET: /api/users or /api/users/{id}
    public function users($id = null)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $data = $id ? $this->User_model->get_users($id) : $this->User_model->get_users();
            echo json_encode($data ?: ['message' => 'User not found']);
        }
    }

    // POST: /api/create_user
    // public function create_user()
    // {
    //     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //         // Get raw JSON input
    //         $data = json_decode(file_get_contents('php://input'), true);

    //         // print_r($data);
    //         // die;

    //         // Basic validation
    //         if (empty($data['name']) || empty($data['email'])) {
    //             echo json_encode(['message' => 'Name and Email are required']);
    //             return;
    //         }

    //         $result = $this->User_model->insert_user($data);
    //         echo json_encode($result ? ['message' => 'User created'] : ['message' => 'Failed to create user']);
    //     } else {
    //         echo json_encode(['message' => 'Invalid request method']);
    //     }
    // }

    public function create_user()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $name = $this->input->post('name');
            $email = $this->input->post('email');

            if (empty($name) || empty($email)) {
                echo json_encode(['status' => false, 'message' => 'Name and Email are required']);
                return;
            }

            $this->load->library('upload');
            $base_url = base_url();

            $config['upload_path'] = FCPATH . 'uploads/';
            $config['allowed_types'] = 'jpg|jpeg|png|gif';
            $config['max_size'] = 2048;
            $config['encrypt_name'] = TRUE;

            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0777, true);
            }

            $this->upload->initialize($config);

            $image_name = 'Default.jpg';

            if (!empty($_FILES['image']['name'])) {
                if (count($_FILES['image']['name']) != 1) {
                    echo json_encode(['status' => false, 'message' => 'Multiple images not allowed']);
                    return;
                }

                $_FILES['single_image']['name'] = $_FILES['image']['name'][0];
                $_FILES['single_image']['type'] = $_FILES['image']['type'][0];
                $_FILES['single_image']['tmp_name'] = $_FILES['image']['tmp_name'][0];
                $_FILES['single_image']['error'] = $_FILES['image']['error'][0];
                $_FILES['single_image']['size'] = $_FILES['image']['size'][0];

                if (!$this->upload->do_upload('single_image')) {
                    echo json_encode(['status' => false, 'message' => $this->upload->display_errors()]);
                    return;
                }

                $uploaded_data = $this->upload->data();
                $image_name = $uploaded_data['file_name'];
            }

            $user_data = [
                'name' => $name,
                'email' => $email,
                'image' => $base_url . 'uploads/' . $image_name,
            ];

            $result = $this->User_model->insert_user($user_data);

            echo json_encode($result ? ['status' => true, 'message' => 'User created'] : ['status' => false, 'message' => 'Failed to create user']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Invalid request method']);
        }
    }

    // PUT: /api/update_user/{id}
    // public function update_user($id)
    // {
    //     if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    //         $data = json_decode(file_get_contents('php://input'), true);

    //         // print_r($data);
    //         // die;
    //         // echo $data;
    //         // exit;

    //         if (empty($data)) {
    //             echo json_encode(['message' => 'No data provided']);
    //             return;
    //         }

    //         $result = $this->User_model->update_user($id, $data);
    //         echo json_encode($result ? ['message' => 'User updated'] : ['message' => 'Failed to update user']);
    //     }
    // }

    public function update_user($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$id || !is_numeric($id)) {
                echo json_encode(['status' => false, 'message' => 'Invalid user ID']);
                return;
            }

            $existing_user = $this->User_model->get_users($id);
            if (!$existing_user) {
                echo json_encode(['status' => false, 'message' => 'User not found']);
                return;
            }

            $name = $this->input->post('name');
            $email = $this->input->post('email');

            if (empty($name) || empty($email)) {
                echo json_encode(['status' => false, 'message' => 'Name and Email are required']);
                return;
            }

            $this->load->library('upload');
            $base_url = base_url();

            $config['upload_path'] = FCPATH . 'uploads/';
            $config['allowed_types'] = 'jpg|jpeg|png|gif';
            $config['max_size'] = 2048;
            $config['encrypt_name'] = TRUE;

            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0777, true);
            }

            $this->upload->initialize($config);

            $image_name = null;

            if (!empty($_FILES['image']['name'])) {
                if (count($_FILES['image']['name']) != 1) {
                    echo json_encode(['status' => false, 'message' => 'Multiple images not allowed']);
                    return;
                }

                $_FILES['single_image']['name'] = $_FILES['image']['name'][0];
                $_FILES['single_image']['type'] = $_FILES['image']['type'][0];
                $_FILES['single_image']['tmp_name'] = $_FILES['image']['tmp_name'][0];
                $_FILES['single_image']['error'] = $_FILES['image']['error'][0];
                $_FILES['single_image']['size'] = $_FILES['image']['size'][0];

                if (!$this->upload->do_upload('single_image')) {
                    echo json_encode(['status' => false, 'message' => $this->upload->display_errors()]);
                    return;
                }

                $uploaded_data = $this->upload->data();
                $image_name = $uploaded_data['file_name'];

                // Delete old image file (optional)
                $old_image_path = FCPATH . str_replace(base_url(), '', $existing_user->image);
                if (file_exists($old_image_path) && $existing_user->image != base_url() . 'uploads/Default.jpg') {
                    unlink($old_image_path);
                }
            }

            $update_data = [
                'name' => $name,
                'email' => $email,
            ];

            if ($image_name) {
                $update_data['image'] = $base_url . 'uploads/' . $image_name;
            }

            $result = $this->User_model->update_user($id, $update_data);
            echo json_encode($result ? ['status' => true, 'message' => 'User updated'] : ['status' => false, 'message' => 'Failed to update user']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Invalid request method']);
        }
    }


    // DELETE: /api/delete_user/{id}
    public function delete_user($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $result = $this->User_model->delete_user($id);
            echo json_encode($result ? ['message' => 'User deleted'] : ['message' => 'Failed to delete user']);
        } else {
            echo json_encode(['message' => 'Invalid request method']);
        }
    }

}
