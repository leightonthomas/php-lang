<?php

declare(strict_types=1);

namespace App\Model\Compiler\CustomBytecode;

enum Opcode : int
{
    case RET = 0; // RET
    case CALL = 1; // CALL "foo"
    case PUSH = 2; // PUSH 4
    case POP = 3; // POP
    case LET = 4; // LET "foo" (assigns current stack item to name)
    case ECHO = 5; // ECHO
    case LOAD = 6; // LOAD "foo"
    case END = 7; // END
    case SUB = 8; // SUB
    case ADD = 9; // ADD
    case NEG = 10; // NEGATE
}
