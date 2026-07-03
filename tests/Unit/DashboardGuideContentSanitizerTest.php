<?php

namespace Tests\Unit;

use App\Http\Controllers\ConfigController;
use ReflectionMethod;
use Tests\TestCase;

class DashboardGuideContentSanitizerTest extends TestCase
{
    public function test_it_keeps_safe_guide_html_and_removes_unsafe_content(): void
    {
        $controller = app(ConfigController::class);
        $method = new ReflectionMethod($controller, 'sanitizeDashboardGuideContent');
        $method->setAccessible(true);

        $result = $method->invoke($controller, '<div onclick="alert(1)" style="color:red">Hello <strong>user</strong></div><script>alert(1)</script><a href="javascript:alert(1)">bad</a><img src="https://example.com/a.png" onerror="alert(1)" alt="A"><iframe src="https://www.youtube.com/embed/abc123XYZ" title="Video"></iframe><iframe src="https://evil.test/embed/abc123XYZ"></iframe>');

        $this->assertStringContainsString('<p>Hello <strong>user</strong></p>', $result);
        $this->assertStringContainsString('<img src="https://example.com/a.png" alt="A">', $result);
        $this->assertStringContainsString('<iframe src="https://www.youtube.com/embed/abc123XYZ"', $result);
        $this->assertStringNotContainsString('script', strtolower($result));
        $this->assertStringNotContainsString('javascript:', strtolower($result));
        $this->assertStringNotContainsString('onclick', strtolower($result));
        $this->assertStringNotContainsString('onerror', strtolower($result));
        $this->assertStringNotContainsString('evil.test', strtolower($result));
    }
}
