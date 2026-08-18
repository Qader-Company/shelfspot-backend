<?php

namespace App\Modules\V1\AccessControl\Domain\ValueObjects;

enum PermissionGroupEnum: string
{
    case DASHBOARD = 'dashboard';
    case PLATFORM_SETTINGS = 'platform_settings';
    case COMPANIES = 'companies';
    case COMPANY_CATALOG = 'company_catalog';
    case COMPANY_PROFILE = 'company_profile';
    case SERVICES = 'services';
    case WORKERS = 'workers';
    case TASKS = 'tasks';
    case PAYMENTS = 'payments';
    case WALLET_COUPONS = 'wallet_coupons';
    case BRANDS = 'brands';
    case SUB_BRANDS = 'sub_brands';
    case CATEGORIES = 'categories';
    case SUB_CATEGORIES = 'sub_categories';
    case PRODUCTS = 'products';
    case WALLET = 'wallet';
    case ROLES = 'roles';
    case ADMINS = 'admins';

    public function label(): string
    {
        return __('access_control.groups.'.$this->value);
    }
}
