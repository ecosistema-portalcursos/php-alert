<?php

declare(strict_types=1);

namespace PortalCurso\Alert\Storage;

/**
 * Armazena os alertas flash na $_SESSION nativa do PHP.
 */
final class SessionStorage implements FlashStorageInterface
{
    /** @var string */
    private $key;

    public function __construct(string $key = '_pc_alert_flash')
    {
        $this->key = $key;
    }

    public function push(string $alertJs): void
    {
        $this->ensureSession();
        if (!isset($_SESSION[$this->key]) || !is_array($_SESSION[$this->key])) {
            $_SESSION[$this->key] = [];
        }
        $_SESSION[$this->key][] = $alertJs;
    }

    public function pull(): array
    {
        $this->ensureSession();
        $items = isset($_SESSION[$this->key]) && is_array($_SESSION[$this->key]) ? $_SESSION[$this->key] : [];
        unset($_SESSION[$this->key]);

        return array_values(array_filter($items, 'is_string'));
    }

    public function has(): bool
    {
        $this->ensureSession();

        return !empty($_SESSION[$this->key]);
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (PHP_SAPI !== 'cli' && headers_sent($file, $line)) {
            throw new \RuntimeException(sprintf(
                'PcAlert: a sessão não está ativa e os headers já foram enviados (%s:%d). Chame session_start() antes.',
                $file,
                $line
            ));
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
