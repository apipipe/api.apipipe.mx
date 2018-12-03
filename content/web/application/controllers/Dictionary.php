<?php
use Restserver\Libraries\REST_Controller;
defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
//To Solve File REST_Controller not found
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 */
class Dictionary extends REST_Controller {

    private $apipies  = ['{[USER]}'];
    private $replace = [1];

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    private function reeplace_variables($data)
    {
        $data = str_replace($this->apipies, $this->replace, $data);
        $data = str_replace('{[KEY_WORD:INSTAGRAM_CONTENT]}', '$this->load_keyword(\'instagram_content\')', $data);
        return str_replace('{[INPUT]}', $this->get('input'), $data);
    }

    private function load_keyword($key_word) 
    {
        if (!empty($key_word)) {
            $ch = curl_init();
            $options = [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL            => base_url($this->reeplace_variables("dictionary/keyword/$key_word?input={[INPUT]}"))
            ];

            curl_setopt_array($ch, $options);

            if ($json_file = curl_exec($ch)) {
                $json_data = json_decode($json_file, true);
            } else {
                return false;
            }
            curl_close($ch);
            return $json_data;
        }
        return false;
    }

    public function keyword_get()
    {
        $key_word = $this->get('kw');
        $this->load->model('dictionary_model');
        if ($r = $this->dictionary_model->read($key_word)) {
            $code = $this->reeplace_variables($r->process);
            try {
                $data = eval($code);
                $this->response(['status' => 'success', 'data' => $data], REST_Controller::HTTP_OK);
            } catch (Exception $e) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'ERROR:' . $e->getMessage()
                ], REST_Controller::HTTP_BAD_REQUEST); // NOT_FOUND (404) being the HTTP response code
            }
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No key word was found'
            ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }
}
