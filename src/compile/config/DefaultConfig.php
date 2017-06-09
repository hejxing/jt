<?php
/**
 * Auth: ax
 * Created: 2017/5/3 0:21
 */

namespace jt\compile\config;


new class
{
    const CHARSET     = 'UTF-8';
    const TIME_ZONE   = 'Asia/Shanghai';
    const JSON_FORMAT = JSON_UNESCAPED_UNICODE;

    const ACCEPT_MIME = ['json', 'html'];
    const DEFAULT_AUTH_CHECKER = '\jt\auth\Lost';
    const DEFAULT_LOG_WRITER = '\jt\log\DarkHole';
};