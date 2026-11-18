<?php

declare(strict_types=1);

namespace PortalCurso\Alert\Tests;

use PHPUnit\Framework\TestCase;
use PortalCurso\Alert\Alert;
use PortalCurso\Alert\JsEncoder;
use PortalCurso\Alert\Storage\ArrayStorage;

final class AlertTest extends TestCase
{
    protected function setUp(): void
    {
        Alert::reset();
        Alert::setStorage(new ArrayStorage());
    }

    public function testFireWithArgumentsGeneratesScript(): void
    {
        $html = Alert::fire('Olá', 'Mundo', 'success');

        self::assertStringStartsWith('<script>', $html);
        self::assertStringContainsString('PcAlert.fire({"title":"Olá","text":"Mundo","icon":"success"})', $html);
    }

    public function testShortcuts(): void
    {
        self::assertStringContainsString('"icon":"error"', Alert::error('x'));
        self::assertStringContainsString('"icon":"warning"', Alert::warning('x'));
        self::assertStringContainsString('"icon":"info"', Alert::info('x'));
        self::assertStringContainsString('"icon":"question"', Alert::question('x'));
    }

    public function testScriptInjectionIsEscaped(): void
    {
        $html = Alert::fire('</script><script>alert(1)</script>');

        self::assertSame(1, substr_count($html, '</script>'));
        self::assertStringContainsString('\\u003C/script\\u003E', $html);
    }

    public function testBuilderAndRawJs(): void
    {
        $js = Alert::make()
            ->title('Nome')
            ->input('text')
            ->showCancelButton()
            ->inputValidator(Alert::js('function (v) { return v ? null : "Obrigatório"; }'))
            ->then('console.log(result.value);')
            ->toJs();

        self::assertStringContainsString('"showCancelButton":true', $js);
        self::assertStringContainsString('"inputValidator":function (v) { return v ? null : "Obrigatório"; }', $js);
        self::assertStringContainsString('.then(function (result) {console.log(result.value);})', $js);
    }

    public function testRedirectOnConfirm(): void
    {
        $js = Alert::confirm('Sair?')->redirectOnConfirm('/logout?a=1&b="2"')->toJs();

        self::assertStringContainsString('window.location.href = "/logout?a=1\\u0026b=\\u00222\\u0022"', $js);
    }

    public function testFlashQueue(): void
    {
        self::assertSame('', Alert::render());

        Alert::flash('Primeiro', '', 'success');
        Alert::flashToast('Segundo');
        self::assertTrue(Alert::hasFlash());

        $html = Alert::render();
        self::assertStringContainsString('"title":"Primeiro"', $html);
        self::assertStringContainsString('"title":"Segundo"', $html);
        self::assertStringContainsString('"toast":true', $html);
        self::assertFalse(Alert::hasFlash());
        self::assertSame('', Alert::render());
    }

    public function testAssetsInlineOnlyOnce(): void
    {
        $assets = Alert::assets();
        self::assertStringContainsString('<style>', $assets);
        self::assertStringContainsString('PcAlert', $assets);
        self::assertSame('', Alert::assets());
        self::assertNotSame('', Alert::assets(null, true));
    }

    public function testAssetsWithBaseUrl(): void
    {
        $assets = Alert::assets('/vendor/pc-alert/');
        self::assertStringContainsString('<link rel="stylesheet" href="/vendor/pc-alert/pc-alert.css">', $assets);
        self::assertStringContainsString('<script src="/vendor/pc-alert/pc-alert.js"></script>', $assets);
    }

    public function testNonce(): void
    {
        Alert::setNonce('abc123');
        self::assertStringStartsWith('<script nonce="abc123">', Alert::success('ok'));
    }

    public function testDefaults(): void
    {
        Alert::setDefaults(['confirmButtonColor' => '#000']);
        self::assertStringContainsString('"confirmButtonColor":"#000"', Alert::success('ok'));
        self::assertStringContainsString('PcAlert.setDefaults({"confirmButtonColor":"#000"})', Alert::scripts());
    }

    public function testPublishAssets(): void
    {
        $dir = sys_get_temp_dir() . '/pc-alert-test-' . uniqid('', true);
        $files = Alert::publishAssets($dir);

        self::assertCount(2, $files);
        foreach ($files as $file) {
            self::assertFileExists($file);
            unlink($file);
        }
        rmdir($dir);
    }

    public function testEncoderListsAndObjects(): void
    {
        self::assertSame('[1,2]', JsEncoder::encode([1, 2]));
        self::assertSame('{"a":[]}', JsEncoder::encode(['a' => []]));
        self::assertSame('{"1":"Um","2":"Dois"}', JsEncoder::encode([1 => 'Um', 2 => 'Dois']));
    }
}
