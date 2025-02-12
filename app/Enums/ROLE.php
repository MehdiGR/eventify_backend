<?php

namespace App\Enums;

enum ROLE: string
{
    case USER = 'user';
    case ADMIN = 'admin';
    case PARTICIPANT = 'participant';
    case ORGANIZER = 'organizer';
}
