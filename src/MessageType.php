<?php

declare(strict_types=1);

namespace Denshoch\MsgStack;

enum MessageType: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case SUCCESS = 'success';
}
