<?php

namespace App\Http\Controllers\Auth;

use App\MicroApi\Exceptions\RpcException;
use App\MicroApi\Services\UserService;
use App\Shop\Customers\Customer;
use App\Http\Controllers\Controller;
use App\Shop\Customers\Requests\RegisterCustomerRequest;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/accounts';

    private $userService;

    /**
     * Create a new controller instance.
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->middleware('guest');
        $this->userService = $userService;
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return Customer
     * @throw RpcException
     */
    protected function create(array $data)
    {
        return $this->userService->create($data);
    }

    /**
     * @param RegisterCustomerRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throw RpcException
     */
    public function register(RegisterCustomerRequest $request)
    {
        $data = $request->except('_method', '_token');

        $user = $this->create($data);
        if($user){
            $token = Auth::login($data);
            return redirect()->route('accounts')->cookie('jwt_token', $token);
        } else {
            throw new AuthenticationException('注册失败，请重试');
        }

//        session([md5($token) => $user]);
//        return redirect()->route('user.profile')->cookie('jwt-token',$token);
    }
}
