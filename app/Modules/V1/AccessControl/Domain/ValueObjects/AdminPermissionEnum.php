<?php

namespace App\Modules\V1\AccessControl\Domain\ValueObjects;

enum AdminPermissionEnum: string
{
    case VIEW_DASHBOARD = 'view_dashboard';
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
}
