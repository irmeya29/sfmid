<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasPermission('products.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermission('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermission('products.delete');
    }

    public function disable(User $user, Product $product): bool
    {
        return $user->hasPermission('products.disable');
    }

    public function updatePurchasePrice(User $user, Product $product): bool
    {
        return $user->hasPermission('products.update_purchase_price');
    }

    public function updateSalePrice(User $user, Product $product): bool
    {
        return $user->hasPermission('products.update_sale_price');
    }

    public function viewMargin(User $user, Product $product): bool
    {
        return $user->hasPermission('products.view_margin');
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('products.export');
    }
}
