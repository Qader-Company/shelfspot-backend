<?php

namespace App\Modules\V1\AccessControl\Domain\ValueObjects;

enum CompanyPermissionEnum: string
{
    case VIEW_COMPANY = 'view_company';
    case EDIT_COMPANY = 'edit_company';
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
    case EDIT_TASK = 'edit_task';
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

    public function group(): PermissionGroupEnum
    {
        return match ($this) {
            self::VIEW_COMPANY, self::EDIT_COMPANY => PermissionGroupEnum::COMPANY_PROFILE,
            self::VIEW_BRAND, self::CREATE_BRAND, self::EDIT_BRAND, self::DELETE_BRAND => PermissionGroupEnum::BRANDS,
            self::VIEW_SUB_BRAND, self::CREATE_SUB_BRAND, self::EDIT_SUB_BRAND, self::DELETE_SUB_BRAND => PermissionGroupEnum::SUB_BRANDS,
            self::VIEW_CATEGORY, self::CREATE_CATEGORY, self::EDIT_CATEGORY, self::DELETE_CATEGORY => PermissionGroupEnum::CATEGORIES,
            self::VIEW_SUB_CATEGORY, self::CREATE_SUB_CATEGORY, self::EDIT_SUB_CATEGORY, self::DELETE_SUB_CATEGORY => PermissionGroupEnum::SUB_CATEGORIES,
            self::VIEW_PRODUCT, self::CREATE_PRODUCT, self::EDIT_PRODUCT, self::DELETE_PRODUCT => PermissionGroupEnum::PRODUCTS,
            self::VIEW_SERVICE => PermissionGroupEnum::SERVICES,
            self::VIEW_WALLET, self::RECHARGE_WALLET => PermissionGroupEnum::WALLET,
            self::VIEW_TASK, self::CREATE_TASK, self::EDIT_TASK, self::DELETE_TASK => PermissionGroupEnum::TASKS,
            self::VIEW_ROLE, self::CREATE_ROLE, self::EDIT_ROLE, self::DELETE_ROLE => PermissionGroupEnum::ROLES,
            self::VIEW_ADMIN, self::CREATE_ADMIN, self::EDIT_ADMIN, self::DELETE_ADMIN => PermissionGroupEnum::ADMINS,
        };
    }
}
