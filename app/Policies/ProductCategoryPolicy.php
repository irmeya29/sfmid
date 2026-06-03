<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;

class ProductCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('products.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('products.create');
    }

    public function update(User $user, ProductCategory $productCategory): bool
    {
        return $user->hasPermission('products.update');
    }

    public function delete(User $user, ProductCategory $productCategory): bool
    {
        return $user->hasPermission('products.delete')
            && ! $productCategory->products()->exists();
    }
}
