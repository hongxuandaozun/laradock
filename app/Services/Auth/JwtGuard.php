<?php


namespace App\Services\Auth;


use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
class JwtGuard implements Guard
{
    use GuardHelpers;

    /**
     * The request instance.
     * @var Request
     */
    protected $request;
    /**
     * The name of the query string item from the request containing the API token.
     *
     * @var string
     */
    protected $inputKey;
    /**
     * The name of the token "column" in persistent storage.
     *
     * @var string
     */
    protected $storageKey;

    /**
     * Indicates if the logout method has been called.
     *
     * @var bool
     */
    protected $loggedOut = false;
    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $inputKey
     * @param  string  $storageKey
     * @return void
     */

    public function __construct(UserProvider $provider, Request $request, $inputKey = 'jwt_token', $storageKey = 'jwt_token')
    {
        $this->request = $request;
        $this->provider = $provider;
        $this->inputKey = $inputKey;
        $this->storageKey = $storageKey;
    }
    public function user()
    {
        if (!is_null($this->user)){
            return $this->user;
        }
        $user = null;
        $token = $this->getTokenForRequest();
        $user = $this->provider->retrieveByToken(null,$token);
        return $this->user = $user;
    }
    public function validate(array $credentials = [])
    {
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        if (!is_null($this->user)) {
            return $this->user;
        }

        $user = null;
        $token = $this->getTokenForRequest();

        if (!empty($token)) {
            $user = $this->provider->retrieveByToken(null, $token);
        }

        return $this->user = $user;
    }
    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array  $credentials
     * @return Authenticatable|null
     */
    public function login(array $credentials)
    {
        $user = $this->provider->retrieveByCredentials($credentials);
        // If an implementation of UserInterface was returned, we'll ask the provider
        // to validate the user against the given credentials, and if they are in
        // fact valid we'll log the users into the application and return true.
        if ($user) {
            $token = $this->provider->validateCredentials($user, $credentials);
            $this->setUser($user);
        }

        return $token;
    }

    public function getTokenForRequest(){
        $token = $this->request->query($this->inputKey);
        if(empty($token)){
            $token = $this->request->input($this->inputKey);
        }
        if(empty($token)){
            $token = $this->request->bearerToken();
        }
        if(empty($token)){
            $token = $this->request->cookie($this->inputKey);
        }
        return $token;
    }

    /**
     * Set the current request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    public function logout(){
        $this->loggedOut = true;
        $this->user = null;
    }
}