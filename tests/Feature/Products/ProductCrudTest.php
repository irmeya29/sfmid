<?php

namespace Tests\Feature\Products;

use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    public function test_guest_is_redirected_to_login_from_home(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
