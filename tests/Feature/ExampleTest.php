<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Root URL harus me-redirect ke admin panel.
     */
    public function test_root_redirects_to_admin_panel(): void
    {
        $response = $this->get('/');
        $response->assertStatus(302);
        $response->assertRedirect('/admin');
    }

    /**
     * Halaman login Filament harus dapat diakses dan merespons 200 OK.
     */
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    /**
     * Route fallback 'login' harus me-redirect ke /admin/login.
     */
    public function test_fallback_login_route_redirects(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');
    }

    /**
     * Endpoint health check harus mengembalikan respon JSON valid.
     */
    public function test_health_check_endpoint(): void
    {
        $response = $this->get('/health');
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'timestamp']);
    }
}
