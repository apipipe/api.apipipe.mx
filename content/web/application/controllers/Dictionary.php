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
        $input = $this->get('input');
        $data = str_replace($this->apipies, $this->replace, $data);
        $data = str_replace('{[KEY_WORD:INSTAGRAM_CONTENT]}', '$this->load_keyword(\'instagram_content\', \'{[INPUT]}\')', $data);
        $data = str_replace('{[EVENT:TELEGRAM_SEND]}', '$this->sendTelegram(\'sendMessage\', isset($params) ? $params : null, isset($reply_type) ? $reply_type : null)', $data);
        return str_replace('{[INPUT]}', $input, $data);
    }

    public function keyword_get()
    {
        $key_word = $this->get('kw');
        $this->load->model('dictionary_model');
        if ($r = $this->dictionary_model->read($key_word)) {
            $code = $this->reeplace_variables($r->process);
            $process = $this->dictionary_model->process_exect($code);

            $response_code = REST_Controller::HTTP_BAD_REQUEST;
            if ($process['status']) {
                $response_code = REST_Controller::HTTP_OK;
            }
            $this->response($process, $response_code);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No key word was found'
            ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }
}
