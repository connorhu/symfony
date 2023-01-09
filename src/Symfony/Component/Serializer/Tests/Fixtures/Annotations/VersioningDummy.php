<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Annotations;

use Symfony\Component\Serializer\Annotation\Since;
use Symfony\Component\Serializer\Annotation\Until;

class VersioningDummy
{
    /**
     * @Since("1.0.0")
     * @Until("1.1.9")
     */
    public $foo;

    public $bar;

    /**
     * @Since("0.9.0")
     */
    public $username;

    /**
     * @Since("1.1.2")
     */
    public function getBar()
    {
        return $this->foo;
    }

    /**
     * @Since("1.3.0")
     */
    public function getUsername()
    {
        return $this->username;
    }
}
