<?php
/**
 * Auth: ax
 * Date: 2016/11/3 15:20
 */

namespace jt;

interface TemplateInterface
{
    public function __construct(array $config);

    public function render(string $tpl, array $data): string;

    public function hadCache(string $uri, string $queryString): bool;
}