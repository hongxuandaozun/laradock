<?php


namespace App\Services\Auth;


use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use App\MicroApi\Services\UserService;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Support\Str;

class ServiceTokenRepository implements TokenRepositoryInterface
{
    /**
     * @var UserService
     */
    protected $userService;

    public function __construct( ){
        $this->userService = resolve('microUserService');
    }

    public function delete(CanResetPasswordContract $user)
    {
        return $this->userService->deletePasswordReset($user);
    }

    public function exists(CanResetPasswordContract $user, $token)
    {
        return $this->userService->validatePasswordResetToken($token);
    }
    public function create(CanResetPasswordContract $user)
    {
        $email = $user->getEmailForPasswordReset();
        $key = config('app.key');
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }
        $token = hash_hmac('sha256', Str::random(40), $key);
        $payload = ['email' => $email, 'token' => $token];
        $this->userService->createPasswordReset($payload);
        return $token;
    }
    public function deleteExpired()
    {
        // TODO: Implement deleteExpired() method.
    }
}