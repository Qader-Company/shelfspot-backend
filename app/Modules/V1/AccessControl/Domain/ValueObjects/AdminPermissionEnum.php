<?php

namespace App\Modules\V1\AccessControl\Domain\ValueObjects;

enum AdminPermissionEnum: string
{
    case VIEW_DASHBOARD = 'view_dashboard';
    case VIEW_PLATFORM_SETTINGS = 'view_platform_settings';
    case EDIT_PLATFORM_SETTINGS = 'edit_platform_settings';
    case VIEW_COMPANY = 'view_company';
    case CREATE_COMPANY = 'create_company';
    case EDIT_COMPANY = 'edit_company';
    case DELETE_COMPANY = 'delete_company';
    case MANAGE_COMPANY_CATALOG = 'manage_company_catalog';
    case VIEW_SERVICE = 'view_service';
    case CREATE_SERVICE = 'create_service';
    case EDIT_SERVICE = 'edit_service';
    case DELETE_SERVICE = 'delete_service';
    case VIEW_WORKER = 'view_worker';
    case CREATE_WORKER = 'create_worker';
    case EDIT_WORKER = 'edit_worker';
    case DELETE_WORKER = 'delete_worker';
    case VIEW_TASK = 'view_task';
    case DELETE_TASK = 'delete_task';
    case REASSIGN_TASK = 'reassign_task';
    case VIEW_PAYMENT = 'view_payment';
    case VIEW_WALLET_COUPON = 'view_wallet_coupon';
    case CREATE_WALLET_COUPON = 'create_wallet_coupon';
    case EDIT_WALLET_COUPON = 'edit_wallet_coupon';
    case DELETE_WALLET_COUPON = 'delete_wallet_coupon';
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
        return __('access_control.permissions.admin.'.$this->value);
    }

    public function group(): PermissionGroupEnum
    {
        return match ($this) {
            self::VIEW_DASHBOARD => PermissionGroupEnum::DASHBOARD,
            self::VIEW_PLATFORM_SETTINGS, self::EDIT_PLATFORM_SETTINGS => PermissionGroupEnum::PLATFORM_SETTINGS,
            self::VIEW_COMPANY, self::CREATE_COMPANY, self::EDIT_COMPANY, self::DELETE_COMPANY => PermissionGroupEnum::COMPANIES,
            self::MANAGE_COMPANY_CATALOG => PermissionGroupEnum::COMPANY_CATALOG,
            self::VIEW_SERVICE, self::CREATE_SERVICE, self::EDIT_SERVICE, self::DELETE_SERVICE => PermissionGroupEnum::SERVICES,
            self::VIEW_WORKER, self::CREATE_WORKER, self::EDIT_WORKER, self::DELETE_WORKER => PermissionGroupEnum::WORKERS,
            self::VIEW_TASK, self::DELETE_TASK, self::REASSIGN_TASK => PermissionGroupEnum::TASKS,
            self::VIEW_PAYMENT => PermissionGroupEnum::PAYMENTS,
            self::VIEW_WALLET_COUPON, self::CREATE_WALLET_COUPON, self::EDIT_WALLET_COUPON, self::DELETE_WALLET_COUPON => PermissionGroupEnum::WALLET_COUPONS,
            self::VIEW_ROLE, self::CREATE_ROLE, self::EDIT_ROLE, self::DELETE_ROLE => PermissionGroupEnum::ROLES,
            self::VIEW_ADMIN, self::CREATE_ADMIN, self::EDIT_ADMIN, self::DELETE_ADMIN => PermissionGroupEnum::ADMINS,
        };
    }
}
