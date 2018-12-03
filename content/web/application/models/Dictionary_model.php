<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Dictionary_model extends CI_Model
{
    /**
     * The constructor class
     */
    function __construct(){
        parent::__construct();
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
}