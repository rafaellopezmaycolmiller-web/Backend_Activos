<?php

namespace App;

enum RoleScope: string
{
    case OwnArea = 'own_area';
    case Global = 'global';
}
