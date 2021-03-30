<?php


namespace App\Services\Auth;

use App\MicroApi\Exceptions\RpcException;
use App\MicroApi\Services\UserService;
use Firebase\JWT\JWT;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class MicroUserProvider implements UserProvider
{
    /**
     * @var UserService
     */
    protected $userService;
    /**
     * The auth user model
     * @var string
     */
    protected $model;
    /**
     * Create a new auth user provider.
     *
     * @param  string  $model
     * @return void
     */
    public function __construct($model){
        $this->model = $model;
        $this->userService = resolve('microUserService');
    }
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     * @throws RpcException
     */
    public function retrieveById($identifier)
    {
        $user = $this->userService->getById($identifier);
        if ($user){
            $model = $this->createModel();
            $model->fillAttributes($user);
        }else{
            $model = null;
        }
        return $model;
    }
    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     * @return UserItem|Authenticatable|null
     * @throws AuthenticationException
     */
    public function retrieveByCredentials(array $credentials)
    {

        if (empty($credentials) ||
            (count($credentials) === 1 &&
                array_key_exists('password', $credentials))) {
            return;
        }

        try {
            $user = $this->userService->getByEmail($credentials['email']);
        } catch (RpcException $exception) {
            throw new AuthenticationException("认证失败：邮箱和密码不匹配");
        }
        if (is_null($user)){
           throw new AuthenticationException("认证失败：邮箱不存在");
        }
        $model = $this->createModel($user);
        $model->fillAttributes($user);
        return $model;
    }
    public function updateRememberToken(Authenticatable $user, $token)
    {
        // TODO: Implement updateRememberToken() method.
    }
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        try {
            if (empty($credentials['jwt_token'])) {
                $token = $this->userService->auth($credentials);
            } else {
                $token = $this->userService->isAuth($credentials['jwt_token']);
            }
        } catch (RpcException $exception) {
            $message = empty($credentials['jwt_token']) ? '注册邮箱与密码不匹配' : '令牌失效';
            throw new AuthenticationException("认证失败：" . $message);
        }
        return $token;
    }

    public function retrieveByToken($identifier, $token)
    {
        $model = $this->createModel();

        if(is_null($token)){
            return null;
        }
//        dd($token);
        $data = JWT::decode($token,config('services.micro.jwt_key'),[config('services.micro.jwt_algorithms')]);
        if ($data->exp <= time()){
            return null;
        }
        $model->fillAttributes($data);
        return $model;
    }

    /**
     * Create a new instance of the model.
     *
     * @return UserItem
     */
    public function createModel()
    {
        $class = '\\'.ltrim($this->model, '\\');

        return new $class;
    }

    /**
     * @return string
     */
    public function getModel(){
        return $this->model;
    }

    /**
     * @param $model
     * @return $this
     */
    public function setModel($model){
        $this->model = $model;
        return $this;
    }
}