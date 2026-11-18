<?php

declare(strict_types=1);

namespace PortalCurso\Alert\Storage;

/**
 * Armazenamento em memória (útil para testes ou para exibir na mesma requisição).
 */
final class ArrayStorage implements FlashStorageInterface
{
    /** @var string[] */
    private $items = [];

    public function push(string $alertJs): void
    {
        $this->items[] = $alertJs;
    }

    public function pull(): array
    {
        $items = $this->items;
        $this->items = [];

        return $items;
    }

    public function has(): bool
    {
        return $this->items !== [];
    }
}
