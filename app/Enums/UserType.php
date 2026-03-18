<?php

namespace App\Enums;

enum UserType: string
{
    case ADMIN = 'admin';
    case WAREHOUSE_MAN = 'warehouse_man';
    case AREA_MANAGER = 'area_manger';
    case CHECKER = 'checker';
    case APPROVING_MANAGER = "approving_manager";
    case BU_MANAGER = "bu_manager";
    case EMPLOYEE = "employee";
}
