<?php

namespace App\Enums;

enum ROLE: string
{
    case USER = 'user';
    case ADMIN = 'admin';
    case PARTICIPANT = 'participant';
    case ORGANIZER = 'organizer';
    case GROUP_ADMIN = 'group_admin';
    case GROUP_MEMBER = 'group_member';
}
