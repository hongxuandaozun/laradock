<?php
namespace App\MicroApi\Items;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\RoutesNotifications;

class UserItem implements Authenticatable,\Illuminate\Contracts\Auth\CanResetPassword
{
    use RoutesNotifications,CanResetPassword;
    public $id;
    public $name;
    public $password;
    public $email;
    public $status;

    protected $hidden = ['password'];

    public $remember_token;

    /**
     * 将 JSON 序列化对象数据填充到 UserItem
     */
    public function fillAttributes($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
//        if (is_null($data)){return  null;}
        foreach ($data as $key => $value) {
            if (in_array($key, $this->hidden)) {
                continue;
            }
            switch ($key) {
                case 'id':
                    $this->id = $value;
                    break;
                case 'name':
                    $this->name = $value;
                    break;
                case 'email':
                    $this->email = $value;
                    break;
                case 'status':
                    $this->status = $value;
                    break;
                default:
                    break;
            }
        }
        return $this;
    }
    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }
    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->id;
    }
    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }
    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        return $this->remember_token;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}