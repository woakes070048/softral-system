<?php namespace App\Http\Middleware;

use Closure,Session;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use LaravelAcl\Authentication\Interfaces\AuthenticateInterface;

class RedirectIfAuthenticated {

	/**
	 * The Guard implementation.
	 *
	 * @var Guard
	 */
	protected $auth;
	protected $auth1;

	/**
	 * Create a new filter instance.
	 *
	 * @param  Guard  $auth
	 * @return void
	 */
	public function __construct(Guard $auth,AuthenticateInterface $auth1)
	{
		$this->auth = $auth;
		$this->auth1 = $auth1;
	
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$logged_user = $this->auth1->getLoggedUser();
		
		if ($this->auth->check())
		{
			return new RedirectResponse(url('/home'));
		}

		return $next($request);
	}

}
