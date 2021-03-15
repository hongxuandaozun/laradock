<?php


namespace App\MicroApi\Services;


trait DataHandler
{
    public function encode($data){
        return json_encode($data);
    }
    public function decode($data){
        return json_decode($data);
    }
}