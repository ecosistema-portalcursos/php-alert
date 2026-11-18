<?php

declare(strict_types=1);

namespace PortalCurso\Alert;

use PortalCurso\Alert\Storage\FlashStorageInterface;
use PortalCurso\Alert\Storage\SessionStorage;

/**
 * Fachada principal da biblioteca PcAlert.
 *
 * Exemplo:
 *   echo Alert::assets();                                    // no <head> ou antes de </body>
 *   echo Alert::success('Salvo!', 'Registro gravado.');      // exibe o alerta
 *   Alert::make()->title('Excluir?')->icon('warning')->showCancelButton()->flash();
 *   echo Alert::render();                                    // exibe alertas flash pendentes
 */
final class Alert
{
    public const VERSION = '1.0.0';

    public const SUCCESS = 'success';
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const INFO = 'info';
    public const QUESTION = 'question';

    /** @var FlashStorageInterface|null */
    private static $storage;

    /** @var string|null */
    private static $nonce;

    /** @var bool */
    private static $assetsPrinted = false;

    /** @var array<string,mixed> */
    private static $defaults = [];

    private function __construct()
    {
    }

    // ------------------------------------------------------------ fábrica ---

    /**
     * Inicia um alerta com a API fluente.
     *
     * @param array<string,mixed> $options
     */
    public static function make(array $options = []): AlertBuilder
    {
        return new AlertBuilder(array_merge(self::$defaults, $options));
    }

    /**
     * Equivalente ao Swal.fire(): aceita um array de opções ou (título, texto, ícone).
     * Retorna a tag <script> pronta para ser impressa.
     *
     * @param array<string,mixed>|string $titleOrOptions
     */
    public static function fire($titleOrOptions, string $text = '', ?string $icon = null): string
    {
        return self::build($titleOrOptions, $text, $icon)->render();
    }

    public static function success(string $title, string $text = ''): string
    {
        return self::fire($title, $text, self::SUCCESS);
    }

    public static function error(string $title, string $text = ''): string
    {
        return self::fire($title, $text, self::ERROR);
    }

    public static function warning(string $title, string $text = ''): string
    {
        return self::fire($title, $text, self::WARNING);
    }

    public static function info(string $title, string $text = ''): string
    {
        return self::fire($title, $text, self::INFO);
    }

    public static function question(string $title, string $text = ''): string
    {
        return self::fire($title, $text, self::QUESTION);
    }

    /**
     * Notificação pequena no canto da tela, que some sozinha.
     */
    public static function toast(string $title, string $icon = self::SUCCESS, int $timer = 3000, string $position = 'top-end'): string
    {
        return self::toastBuilder($title, $icon, $timer, $position)->render();
    }

    /**
     * Alerta de confirmação. Use ->then(), ->redirectOnConfirm() ou ->submitOnConfirm() no retorno.
     */
    public static function confirm(
        string $title,
        string $text = '',
        string $confirmText = 'Sim',
        string $cancelText = 'Cancelar'
    ): AlertBuilder {
        $options = [
            'title' => $title,
            'icon' => self::QUESTION,
            'showCancelButton' => true,
            'confirmButtonText' => $confirmText,
            'cancelButtonText' => $cancelText,
        ];
        if ($text !== '') {
            $options['text'] = $text;
        }

        return self::make($options);
    }

    // -------------------------------------------------------------- flash ---

    /**
     * Guarda um alerta para a próxima requisição (padrão Post/Redirect/Get).
     *
     * @param array<string,mixed>|string $titleOrOptions
     */
    public static function flash($titleOrOptions, string $text = '', ?string $icon = null): void
    {
        self::build($titleOrOptions, $text, $icon)->flash();
    }

    public static function flashToast(string $title, string $icon = self::SUCCESS, int $timer = 3000, string $position = 'top-end'): void
    {
        self::toastBuilder($title, $icon, $timer, $position)->flash();
    }

    public static function hasFlash(): bool
    {
        return self::storage()->has();
    }

    /**
     * Imprime os alertas flash pendentes (em fila, um após o outro) e os remove.
     */
    public static function render(): string
    {
        $items = self::storage()->pull();
        if ($items === []) {
            return '';
        }

        $chain = 'Promise.resolve()';
        foreach ($items as $js) {
            $chain .= '.then(function () { return ' . $js . '; })';
        }

        return self::script($chain . ';');
    }

    // ------------------------------------------------------------ assets ---

    /**
     * Retorna CSS + JS da biblioteca.
     *
     * - Sem $baseUrl: os arquivos são embutidos (inline) — funciona sem publicar nada.
     * - Com $baseUrl: gera <link>/<script src> apontando para os arquivos publicados
     *   com Alert::publishAssets().
     *
     * Por padrão só é impresso uma vez por requisição; use $force = true para repetir.
     */
    public static function assets(?string $baseUrl = null, bool $force = false): string
    {
        if (self::$assetsPrinted && !$force) {
            return '';
        }
        self::$assetsPrinted = true;

        return self::styles($baseUrl) . "\n" . self::scripts($baseUrl);
    }

    public static function styles(?string $baseUrl = null): string
    {
        if ($baseUrl !== null) {
            return '<link rel="stylesheet" href="' . self::e(rtrim($baseUrl, '/') . '/pc-alert.css') . '">';
        }

        return '<style' . self::nonceAttr() . '>' . self::readAsset('pc-alert.css') . '</style>';
    }

    public static function scripts(?string $baseUrl = null): string
    {
        $setup = self::$defaults !== []
            ? self::script('PcAlert.setDefaults(' . JsEncoder::encode(self::$defaults) . ');', false)
            : '';

        if ($baseUrl !== null) {
            return '<script' . self::nonceAttr() . ' src="' . self::e(rtrim($baseUrl, '/') . '/pc-alert.js') . '"></script>' . $setup;
        }

        return '<script' . self::nonceAttr() . '>' . self::readAsset('pc-alert.js') . '</script>' . $setup;
    }

    /**
     * Copia pc-alert.css e pc-alert.js para uma pasta pública (ex.: public/vendor/pc-alert).
     *
     * @return string[] caminhos dos arquivos copiados
     */
    public static function publishAssets(string $targetDir): array
    {
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Não foi possível criar o diretório: ' . $targetDir);
        }

        $copied = [];
        foreach (['pc-alert.css', 'pc-alert.js'] as $file) {
            $dest = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $file;
            if (!copy(self::assetPath($file), $dest)) {
                throw new \RuntimeException('Não foi possível copiar ' . $file . ' para ' . $dest);
            }
            $copied[] = $dest;
        }

        return $copied;
    }

    public static function assetPath(string $file): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $file;
    }

    // ------------------------------------------------------ configuração ---

    /**
     * Opções padrão aplicadas a todos os alertas (no PHP e no JS).
     * Ex.: Alert::setDefaults(['confirmButtonColor' => '#0d6efd', 'theme' => 'dark']);
     *
     * @param array<string,mixed> $defaults
     */
    public static function setDefaults(array $defaults): void
    {
        self::$defaults = $defaults;
    }

    /**
     * @return array<string,mixed>
     */
    public static function getDefaults(): array
    {
        return self::$defaults;
    }

    /**
     * Nonce para Content-Security-Policy (adicionado a todas as tags <script>/<style>).
     */
    public static function setNonce(?string $nonce): void
    {
        self::$nonce = $nonce;
    }

    public static function setStorage(FlashStorageInterface $storage): void
    {
        self::$storage = $storage;
    }

    public static function storage(): FlashStorageInterface
    {
        if (self::$storage === null) {
            self::$storage = new SessionStorage();
        }

        return self::$storage;
    }

    /**
     * Cria um trecho de JavaScript bruto (para callbacks como preConfirm/inputValidator).
     */
    public static function js(string $code): RawJs
    {
        return new RawJs($code);
    }

    /**
     * Restaura o estado estático (útil em testes e workers de longa duração).
     */
    public static function reset(): void
    {
        self::$storage = null;
        self::$nonce = null;
        self::$assetsPrinted = false;
        self::$defaults = [];
    }

    // ----------------------------------------------------------- internos ---

    /**
     * Envolve um código JS em <script> que aguarda o DOM e o PcAlert estarem prontos.
     *
     * @internal
     */
    public static function script(string $js, bool $waitDom = true): string
    {
        $js = rtrim($js, "; \n") . ';';
        if ($waitDom) {
            $js = '(function () { var run = function () { if (typeof PcAlert === "undefined") { '
                . 'console.error("PcAlert não carregado. Inclua Alert::assets() na página."); return; } ' . $js . ' }; '
                . 'if (document.readyState === "loading") { document.addEventListener("DOMContentLoaded", run); } else { run(); } })();';
        }

        return '<script' . self::nonceAttr() . '>' . $js . '</script>';
    }

    /**
     * @param array<string,mixed>|string $titleOrOptions
     */
    private static function build($titleOrOptions, string $text, ?string $icon): AlertBuilder
    {
        if (is_array($titleOrOptions)) {
            return self::make($titleOrOptions);
        }

        $options = ['title' => $titleOrOptions];
        if ($text !== '') {
            $options['text'] = $text;
        }
        if ($icon !== null) {
            $options['icon'] = $icon;
        }

        return self::make($options);
    }

    private static function toastBuilder(string $title, string $icon, int $timer, string $position): AlertBuilder
    {
        return self::make([
            'toast' => true,
            'title' => $title,
            'icon' => $icon,
            'position' => $position,
            'timer' => $timer,
            'timerProgressBar' => true,
            'showConfirmButton' => false,
        ]);
    }

    private static function readAsset(string $file): string
    {
        $content = file_get_contents(self::assetPath($file));
        if ($content === false) {
            throw new \RuntimeException('Asset não encontrado: ' . $file);
        }

        return $content;
    }

    private static function nonceAttr(): string
    {
        return self::$nonce !== null ? ' nonce="' . self::e(self::$nonce) . '"' : '';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
