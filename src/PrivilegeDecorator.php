<?php
/**
 * Copyright © Upscale Software. All rights reserved.
 * See COPYRIGHT.txt for license details.
 */
namespace Upscale\Swoole\Warmup;

class PrivilegeDecorator
{
    /**
     * @var callable
     */
    protected $subject;
    
    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $group;

    /**
     * Inject dependencies
     * 
     * @param callable $subject Middleware
     * @param string $user User name
     * @param string|null $group Group name
     * @throws \UnexpectedValueException
     */
    public function __construct(callable $subject, $user, $group = null)
    {
        if (!extension_loaded('posix')) {
            throw new \UnexpectedValueException('Process control extension POSIX is missing or disabled.');
        }
        $this->subject = $subject;
        $this->user = $user;
        $this->group = $group;
    }

   /**
     * Execute the underlying middleware impersonated as user/group
     *
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     * @throws \UnexpectedValueException
     */
    public function __invoke(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        $originalUid = posix_geteuid();
        $originalGid = posix_getegid();
        $uid = $this->identifyUser($this->user);
        $gid = $this->group ? $this->identifyGroup($this->group) : $this->identifyUser($this->user, 'gid');
        $middleware = $this->subject;
        $this->impersonate($uid, $gid);
        try {
            $middleware($request, $response);
        } finally {
            $this->impersonate($originalUid, $originalGid);
        }
    }

    /**
     * Impersonate current process as given user/group
     * 
     * @param int $uid User ID
     * @param int $gid Group ID
     * @throws \UnexpectedValueException
     */
    protected function impersonate($uid, $gid)
    {
        if (!posix_setegid($gid)) {
            throw new \UnexpectedValueException("Cannot impersonate as user with gid $gid.");
        }
        if (!posix_seteuid($uid)) {
            throw new \UnexpectedValueException("Cannot impersonate as user with uid $uid.");
        }
    }

    /**
     * Retrieve identity field of a user by its name
     * 
     * @param string $name User name
     * @param string $field Field name: uid, pid
     * @return int
     * @throws \UnexpectedValueException
     */
    protected function identifyUser($name, $field = 'uid')
    {
        $user = posix_getpwnam($name);
        if (!isset($user[$field])) {
            throw new \UnexpectedValueException("Cannot identify $field of user '$name'.");
        }
        return $user[$field];
    }

    /**
     * Retrieve identity field of a group by its name
     * 
     * @param string $name Group name
     * @param string $field Field name: gid
     * @return int
     * @throws \UnexpectedValueException
     */
    protected function identifyGroup($name, $field = 'gid')
    {
        $group = posix_getgrnam($name);
        if (!isset($group[$field])) {
            throw new \UnexpectedValueException("Cannot identify $field of group '$name'.");
        }
        return $group[$field];
    }
}
