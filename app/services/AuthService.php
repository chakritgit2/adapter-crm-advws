<?php
declare(strict_types=1);

use Phalcon\Di\DiInterface;
use Phalcon\Encryption\Security\Random;
use Phalcon\Session\Manager as SessionManager;
use Phalcon\Session\Adapter\Stream as SessionAdapter;
use Phalcon\Http\Message\ServerRequest;
use Phalcon\Http\Response\Cookies;

class AuthService
{
    /**
     * @var SessionManager
     */
    protected $session;

    /**
     * @var Random
     */
    protected $random;

    /**
     * @var string Session key for auth user
     */
    const SESSION_KEY = 'auth_user_id';

    /**
     * @var string Remember me cookie name
     */
    const REMEMBER_ME_COOKIE = 'remember_me';

    /**
     * @var DiInterface
     */
    protected $di;

    /**
     * @var Cookies
     */
    protected $cookies;

    /**
     * @var User
     */
    private $user;

    public function __construct(DiInterface $di)
    {
        $this->di = $di;
        $this->random = new Random();
        $this->cookies = $di->get('cookies');
        $this->session = $di->get('session');
        $this->user = $this->session->get('user') ? $this->session->get('user') : null;
    }


    /**
     * Attempt to authenticate a user by email and password
     */
    public function attemptLogin(string $email, string $password, bool $remember = false): bool
    {
        $user = User::findFirst([
            'conditions' => 'email = :email: AND is_active = 1',
            'bind' => ['email' => $email]
        ]);

        if (!$user) {
            return false;
        }

        if (!$user->verifyPassword($password)) {
            return false;
        }

        $this->loginUser($user, $remember);
        return true;
    }

    /**
     * Log in a user by LINE ID
     */
    public function loginWithLine(string $lineId, array $profile, bool $remember = false): ?User
    {
        $user = User::findFirst([
            'conditions' => 'line_id = :line_id:',
            'bind' => ['line_id' => $lineId]
        ]);

        // If user doesn't exist, create a new one
        if (!$user) {
            $user = new User();
            $user->line_id = $lineId;
            $user->email = $profile['email'] ?? null;
            $user->full_name = $profile['name'] ?? 'LINE User';

            if (!$user->save()) {
                return null;
            }
        }

        $this->loginUser($user, $remember);
        return $user;
    }

    /**
     * Log in a user and set up session
     */
    public function loginUser(User $user, bool $remember = false): void
    {
        if ($remember) {
            $this->setRememberMe($user);
        }

        // Regenerate session ID to prevent session fixation
        // $this->session->regenerateId();

        $this->session->set(self::SESSION_KEY, $user->id);
        $this->session->set('user', $user);
    }

    /**
     * Set remember me cookie
     */
    protected function setRememberMe(User $user): void
    {
        $token = $this->random->base64Safe(32);
        $expires = time() + (86400 * 30); // 30 days

        // Store token in database
        $rememberToken = new RememberToken();
        $rememberToken->user_id = $user->id;
        $rememberToken->token = $token;
        $rememberToken->expires_at = date('Y-m-d H:i:s', $expires);
        $rememberToken->save();

        // Set cookie
        $this->cookies->set(
            self::REMEMBER_ME_COOKIE,
            json_encode([
                'user_id' => $user->id,
                'token' => $token
            ]),
            $expires,
            '/',
            true,
            null,
            true
        );
    }

    /**
     * Log out the current user
     */
    public function logout(): void
    {
        $this->removeRememberMe();
        $this->session->destroy();
    }

    /**
     * Remove remember me cookie and token
     */
    protected function removeRememberMe(): void
    {
        $cookie = $this->cookies ? $this->cookies->get(self::REMEMBER_ME_COOKIE) : null;

        if ($cookie) {
            $data = json_decode($cookie->getValue() ?? '[]', true);

            if (isset($data['user_id'], $data['token'])) {
                $token = RememberToken::findFirst([
                    'conditions' => 'user_id = :user_id: AND token = :token:',
                    'bind' => [
                        'user_id' => $data['user_id'],
                        'token' => $data['token']
                    ]
                ]);

                if ($token) {
                    $token->delete();
                }
            }

            $cookie->delete();
        }
    }

    /**
     * Get the current authenticated user
     */
    public function user(): ?User
    {
        // static $user = null;

        // if ($user !== null) {
        //     return $user;
        // }

        // // Try to get user from session
        // $userId = $this->session->get(self::SESSION_KEY);

        // if ($userId) {
        //     $user = User::findFirst($userId);
        //     return $user;
        // }
        // vd($this->session->get(self::SESSION_KEY));
        // var_dump($this->user);

        if ($this->user) {
            return $this->user;
        }

        // Try to get user from remember me cookie
        $user = $this->getUserFromRememberToken();
        if ($user) {
            $this->loginUser($user);
            return $user;
        }

        return null;
    }

    /**
     * Get user from remember token cookie
     */
    protected function getUserFromRememberToken(): ?User
    {
        $cookie = $this->cookies != null ? $this->cookies->get(self::REMEMBER_ME_COOKIE) : null;

        if (!$cookie) {
            return null;
        }

        $data = json_decode($cookie->getValue() ?? '[]', true);

        if (!isset($data['user_id'], $data['token'])) {
            return null;
        }

        $token = RememberToken::findFirst([
            'conditions' => 'user_id = :user_id: AND token = :token: AND expires_at > NOW()',
            'bind' => [
                'user_id' => $data['user_id'],
                'token' => $data['token']
            ]
        ]);

        if (!$token) {
            return null;
        }

        return User::findFirst($token->user_id);
    }

    /**
     * Check if a user is logged in
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the current user ID
     */
    public function id(): ?int
    {
        return $this->user() ? $this->user()->id : null;
    }

    /**
     * Register a new user
     */
    public function register(array $data): ?User
    {
        $user = new User();
        $user->full_name = $data['full_name'];
        $user->email = $data['email'];
        $user->setPassword($data['password']);

        if (isset($data['phone_number'])) {
            $user->phone_number = $data['phone_number'];
        }

        if ($user->save()) {
            return $user;
        }

        return null;
    }
}
