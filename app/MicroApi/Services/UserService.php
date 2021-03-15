<?php


namespace App\MicroApi\Services;


use App\MicroApi\Exceptions\RpcException;
use App\MicroApi\Facades\HttpClient;
use App\MicroApi\Items\TokenItem;
use App\MicroApi\Items\UserItem;
use Illuminate\Support\Facades\Log;

class UserService
{
    use DataHandler;
    protected $servicePrefix = '/user/userService';

    /**
     * 创建用户
     * @param $data
     * @return mixed
     * @throws RpcException
     */
    public function create($data){
        $path = $this->servicePrefix.'/create';
        $user = $this->getUser($data);
        $options = ['json' => $user];
        try {
            $response = HttpClient::post($path,$options);
        }catch (\Exception $e){
            Log::error('MicroApi.UserService.create Call Failed '.$e->getMessage());
            throw new RpcException("调用远程服务失败");
        }
        $result = $this->decode($response->getBody()->getContents());
        return $result->user;
    }

    /**
     * @param $data
     * @return UserItem
     */
    private function getUser($data){
        $user = new UserItem();
        $fields = ['name','email','password'];
        foreach ($fields as $value){
            if (!empty($data[$value])) {
                $user->$value = $data[$value];
            }
        }
        return $user;
    }

    public function auth($credentials){
        $path = $this->servicePrefix.'/auth';
        $user = $this->getUser($credentials);
        $options = ['json' => $user];
        try {
            $response = HttpClient::post($path,$options);
        }catch (\Exception $e){
            Log::error('MicroApi.UserService.auth Call Failed '.$e->getMessage());
            throw new RpcException("调用远程服务失败");
        }
        return $this->decode($response->getBody()->getContents())->token;
    }


    public function getById($id)
    {
        $path = $this->servicePrefix . '/get';
        $user = new UserItem();
        $user->id = $id;
        $options = ['json' => $user];
        try {
            $response = HttpClient::post($path, $options);
        } catch (\Exception $exception) {
            Log::error("MicroApi.UserService.getByCondition Call Failed: " . $exception->getMessage());
            throw new RpcException("调用远程服务失败");
        }
        $result = $this->decode($response->getBody()->getContents());
        return $result->user;
    }

    public function getAll()
    {
        $path = $this->servicePrefix . '/getAll';
        try {
            $response = HttpClient::get($path);
        } catch (\Exception $exception) {
            Log::error("MicroApi.UserService.getAll Call Failed: " . $exception->getMessage());
            throw new RpcException("调用远程服务失败");
        }
        $result = $this->decode($response->getBody()->getContents());
        return $result->users;
    }
    public function isAuth($token)
    {
        $path = $this->servicePrefix . '/validateToken';
        $item = new TokenItem();
        $item->token = $token;
        $options = ['json' => $item];
        try {
            $response = HttpClient::post($path, $options);
        } catch (\Exception $exception) {
            Log::error("MicroApi.UserService.auth Call Failed: " . $exception->getMessage());
            throw new RpcException("调用远程服务失败");
        }
        $result = $this->decode($response->getBody()->getContents());
        return  $result->valid;
    }
}