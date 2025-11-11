<?php

namespace App\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * 参数映射转换
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class Mapper extends AbstractAnnotation
{
    /**
     * @var array|string[] 来源 key
     */
    public array $src;

    /**
     * @param string ...$src 当前参数的来源 key
     */
    public function __construct(string ...$src)
    {
        $this->src = $src;
    }
}
