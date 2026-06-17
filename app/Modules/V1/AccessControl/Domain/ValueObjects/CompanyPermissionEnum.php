<?php

namespace App\Modules\V1\AccessControl\Domain\ValueObjects;

enum CompanyPermissionEnum: string
{
    case VIEW_BRAND = 'view_brand';
    case CREATE_BRAND = 'create_brand';
    case EDIT_BRAND = 'edit_brand';
    case DELETE_BRAND = 'delete_brand';
    case VIEW_SUB_BRAND = 'view_sub_brand';
    case CREATE_SUB_BRAND = 'create_sub_brand';
    case EDIT_SUB_BRAND = 'edit_sub_brand';
    case DELETE_SUB_BRAND = 'delete_sub_brand';
    case VIEW_CATEGORY = 'view_category';
    case CREATE_CATEGORY = 'create_category';
    case EDIT_CATEGORY = 'edit_category';
    case DELETE_CATEGORY = 'delete_category';
    case VIEW_SUB_CATEGORY = 'view_sub_category';
    case CREATE_SUB_CATEGORY = 'create_sub_category';
    case EDIT_SUB_CATEGORY = 'edit_sub_category';
    case DELETE_SUB_CATEGORY = 'delete_sub_category';
    case VIEW_PRODUCT = 'view_product';
    case CREATE_PRODUCT = 'create_product';
    case EDIT_PRODUCT = 'edit_product';
    case DELETE_PRODUCT = 'delete_product';
    case VIEW_SERVICE = 'view_service';
    case VIEW_WALLET = 'view_wallet';
    case RECHARGE_WALLET = 'recharge_wallet';
    case VIEW_TASK = 'view_task';
    case CREATE_TASK = 'create_task';
    case DELETE_TASK = 'delete_task';
    case VIEW_ROLE = 'view_role';
    case CREATE_ROLE = 'create_role';
    case EDIT_ROLE = 'edit_role';
    case DELETE_ROLE = 'delete_role';
    case VIEW_ADMIN = 'view_admin';
    case CREATE_ADMIN = 'create_admin';
    case EDIT_ADMIN = 'edit_admin';
    case DELETE_ADMIN = 'delete_admin';

    public function label(): string
    {
        return __('access_control.permissions.company.'.$this->value);
    }
}
