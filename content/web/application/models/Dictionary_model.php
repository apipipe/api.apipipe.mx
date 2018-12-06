<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Dictionary_model extends CI_Model
{
    /**
     * The constructor class
     */
    function __construct(){
        parent::__construct();
        $this->config->load('apipipe');
    }

    private function sendTelegram($command = 'sendMessage', $params, $replay_markup_type = null)
    {
        if (!empty($command)) {
            if (!empty($replay_markup_type)) {
                switch ($replay_markup_type) {
                    case 'y/n':
                        $params['reply_markup'] = json_encode([
                            'inline_keyboard' => [[
                                [
                                    'text' => 'Si',
                                    'callback_data' => 'A1'
                                ], 
                                [
                                    'text' => 'No',
                                    'callback_data' => 'C1'
                                ]]
                            ]
                        ]);
                        break;
                    
                    default:
                        // Default message
                        break;
                }
            }
            $ch = curl_init();
            $options = [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $params,
                CURLOPT_URL => $this->config->item("TELEGRAM_URI").$command
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

    private function load_keyword($key_word, $input = '') 
    {
        if (!empty($key_word)) {
            $ch = curl_init();
            $options = [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL            => base_url("dictionary/keyword/$key_word?input=$input")
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

    public function read($key_word)
    {
        $query = $this->db
            ->select('process')
            ->where('key_word', $key_word)
            ->get('dictionary');
        if ($r = $query->result()) {
            return $r[0];
        }
        return false;
    }

    public function process_exect($code)
    {
        try {
            return ['status' => 'success', 'data' => eval($code)];
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'ERROR:' . $e->getMessage()];
        }
    }
}