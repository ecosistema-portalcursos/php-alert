<?php

declare(strict_types=1);

namespace PortalCurso\Alert;

/**
 * Construtor fluente de alertas.
 *
 * Qualquer opção suportada pelo PcAlert (JS) pode ser definida chamando um método
 * com o mesmo nome, ex.: ->showCancelButton(true), ->inputPlaceholder('Seu nome').
 *
 * @method self title(string $title)
 * @method self text(string $text)
 * @method self html(string $html)
 * @method self icon(string $icon)
 * @method self iconColor(string $color)
 * @method self footer(string $html)
 * @method self imageUrl(string $url)
 * @method self imageWidth(int|string $width)
 * @method self imageHeight(int|string $height)
 * @method self imageAlt(string $alt)
 * @method self width(int|string $width)
 * @method self padding(int|string $padding)
 * @method self background(string $color)
 * @method self color(string $color)
 * @method self theme(string $theme)
 * @method self position(string $position)
 * @method self toast(bool $toast = true)
 * @method self backdrop(bool|string $backdrop)
 * @method self timer(int $milliseconds)
 * @method self timerProgressBar(bool $show = true)
 * @method self showConfirmButton(bool $show = true)
 * @method self showDenyButton(bool $show = true)
 * @method self showCancelButton(bool $show = true)
 * @method self showCloseButton(bool $show = true)
 * @method self confirmButtonText(string $text)
 * @method self denyButtonText(string $text)
 * @method self cancelButtonText(string $text)
 * @method self confirmButtonColor(string $color)
 * @method self denyButtonColor(string $color)
 * @method self cancelButtonColor(string $color)
 * @method self reverseButtons(bool $reverse = true)
 * @method self focusConfirm(bool $focus = true)
 * @method self focusCancel(bool $focus = true)
 * @method self allowOutsideClick(bool $allow)
 * @method self allowEscapeKey(bool $allow)
 * @method self allowEnterKey(bool $allow)
 * @method self input(string $type)
 * @method self inputLabel(string $label)
 * @method self inputPlaceholder(string $placeholder)
 * @method self inputValue(mixed $value)
 * @method self inputOptions(array $options)
 * @method self inputAttributes(array $attributes)
 * @method self inputValidator(RawJs $callback)
 * @method self validationMessage(string $message)
 * @method self preConfirm(RawJs $callback)
 * @method self preDeny(RawJs $callback)
 * @method self showLoaderOnConfirm(bool $show = true)
 * @method self customClass(array $classes)
 * @method self didOpen(RawJs $callback)
 * @method self willClose(RawJs $callback)
 * @method self didClose(RawJs $callback)
 */
final class AlertBuilder implements \JsonSerializable
{
    /** @var array<string,mixed> */
    private $options = [];

    /** @var RawJs|null */
    private $then;

    /**
     * @param array<string,mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Define uma opção qualquer.
     *
     * @param mixed $value
     */
    public function set(string $option, $value): self
    {
        $this->options[$option] = $value;

        return $this;
    }

    /**
     * @param array<string,mixed> $options
     */
    public function merge(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * @param mixed[] $arguments
     */
    public function __call(string $name, array $arguments): self
    {
        $this->options[$name] = array_key_exists(0, $arguments) ? $arguments[0] : true;

        return $this;
    }

    /**
     * Código JS executado após o alerta fechar. Recebe a variável "result"
     * ({isConfirmed, isDenied, isDismissed, value, dismiss}).
     *
     * Ex.: ->then('if (result.isConfirmed) { document.getElementById("f").submit(); }')
     *
     * @param string|RawJs $jsBody corpo da função ou função completa (RawJs)
     */
    public function then($jsBody): self
    {
        $this->then = $jsBody instanceof RawJs ? $jsBody : new RawJs('function (result) {' . $jsBody . '}');

        return $this;
    }

    /**
     * Redireciona quando o usuário confirmar.
     */
    public function redirectOnConfirm(string $url): self
    {
        return $this->then('if (result.isConfirmed) { window.location.href = ' . JsEncoder::encode($url) . '; }');
    }

    /**
     * Submete um formulário (seletor CSS) quando o usuário confirmar.
     */
    public function submitOnConfirm(string $formSelector): self
    {
        return $this->then(
            'if (result.isConfirmed) { var f = document.querySelector(' . JsEncoder::encode($formSelector) . '); if (f) { f.submit(); } }'
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->options;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->options;
    }

    /**
     * Expressão JS que dispara o alerta, ex.: PcAlert.fire({...}).then(...)
     */
    public function toJs(): string
    {
        $js = 'PcAlert.fire(' . JsEncoder::encode($this->options) . ')';
        if ($this->then !== null) {
            $js .= '.then(' . $this->then->getCode() . ')';
        }

        return $js;
    }

    /**
     * Retorna a tag <script> que exibe o alerta quando a página carregar.
     */
    public function render(): string
    {
        return Alert::script($this->toJs());
    }

    /**
     * Guarda o alerta para ser exibido na próxima página (após redirect).
     */
    public function flash(): void
    {
        Alert::storage()->push($this->toJs());
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
