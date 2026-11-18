# PcAlert — alertas no estilo SweetAlert para PHP

Biblioteca de alertas, modais e toasts para PHP 7.4+ com HTML, CSS e JavaScript puros (sem jQuery, sem dependências).
A API JavaScript é praticamente igual à do `Swal.fire()`, e o lado PHP permite disparar alertas direto do backend, inclusive como **flash** após um redirect.

## Instalação

```bash
composer require portalcurso/php-alert
```

## Uso rápido

```php
<?php
require 'vendor/autoload.php';

use PortalCurso\Alert\Alert;

session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?= Alert::assets() ?>   <!-- CSS + JS embutidos (inline) -->
</head>
<body>
    ...
    <?= Alert::success('Salvo!', 'Registro gravado com sucesso.') ?>
    <?= Alert::render() ?>   <!-- exibe alertas flash pendentes -->
</body>
</html>
```

### Assets publicados (opcional)

Por padrão `Alert::assets()` embute o CSS/JS na página (funciona sem configurar nada, pois a pasta `vendor` não é pública).
Para servir como arquivos estáticos (com cache do navegador):

```php
Alert::publishAssets(__DIR__ . '/public/vendor/pc-alert'); // uma vez (ex.: em um script de deploy)

echo Alert::assets('/vendor/pc-alert');                    // gera <link> e <script src>
```

## PHP

```php
// Equivalente ao Swal.fire
echo Alert::fire('Título', 'Texto', 'info');
echo Alert::fire(['title' => 'Oops...', 'text' => 'Algo deu errado!', 'icon' => 'error', 'footer' => '<a href="/ajuda">Ajuda</a>']);

// Atalhos
echo Alert::success('Pronto!');
echo Alert::error('Erro', 'Tente novamente.');
echo Alert::warning('Atenção');
echo Alert::info('Info');
echo Alert::question('Dúvida?');
echo Alert::toast('Salvo com sucesso');                // canto superior direito, some em 3s
echo Alert::toast('Falhou', Alert::ERROR, 5000, 'bottom-end');

// Confirmação
echo Alert::confirm('Excluir registro?', 'Esta ação não pode ser desfeita.', 'Sim, excluir')
    ->submitOnConfirm('#form-excluir');

echo Alert::confirm('Sair do sistema?')->redirectOnConfirm('/logout');

// API fluente (qualquer opção do JS pode ser chamada como método)
echo Alert::make()
    ->title('Qual seu nome?')
    ->input('text')
    ->inputPlaceholder('Digite aqui')
    ->showCancelButton()
    ->confirmButtonText('Enviar')
    ->inputValidator(Alert::js('function (v) { return v ? null : "Campo obrigatório"; }'))
    ->then('if (result.isConfirmed) { console.log(result.value); }');
```

### Alertas flash (Post/Redirect/Get)

```php
// no controller, antes do redirect
Alert::flash('Cadastro realizado!', 'Bem-vindo.', Alert::SUCCESS);
Alert::flashToast('Perfil atualizado');
Alert::make()->title('Pedido #123 criado')->icon('success')->timer(4000)->flash();
header('Location: /painel');
exit;

// na view (layout), imprime todos em fila e limpa
echo Alert::render();
```

Para usar o session de um framework, implemente `PortalCurso\Alert\Storage\FlashStorageInterface` e registre com `Alert::setStorage($storage)`.

### Configuração

```php
Alert::setDefaults([            // vale para PHP e JS
    'confirmButtonColor' => '#0d6efd',
    'cancelButtonText'   => 'Voltar',
    'theme'              => 'dark',
]);

Alert::setNonce($cspNonce);     // adiciona nonce="" em todas as tags <script>/<style>
```

> **Segurança:** `title`, `text`, etc. são codificados com segurança para dentro do `<script>`. As opções `html` e `footer`
> são renderizadas como HTML — não coloque dados do usuário nelas sem escapar. O mesmo vale para `Alert::js()`.

## JavaScript

O objeto global `PcAlert` funciona como o `Swal`:

```js
PcAlert.fire('Bom trabalho!', 'Você clicou no botão!', 'success');

PcAlert.fire({
  title: 'Tem certeza?',
  text: 'Esta ação não pode ser desfeita!',
  icon: 'warning',
  showCancelButton: true,
  confirmButtonText: 'Sim, excluir!'
}).then((result) => {
  if (result.isConfirmed) {
    PcAlert.fire('Excluído!', 'O arquivo foi excluído.', 'success');
  }
});

const { value: email } = await PcAlert.fire({ title: 'Seu e-mail', input: 'email' });

const Toast = PcAlert.mixin({ toast: true, position: 'top-end', timer: 3000, timerProgressBar: true });
Toast.fire({ icon: 'success', title: 'Login realizado' });

PcAlert.fire({
  title: 'Buscar usuário', input: 'text', showLoaderOnConfirm: true,
  preConfirm: (login) => fetch('/api/users/' + login).then(r => r.json())
});
```

O resultado da Promise é `{ isConfirmed, isDenied, isDismissed, value, dismiss }`, onde `dismiss` pode ser
`PcAlert.DismissReason.backdrop | cancel | close | esc | timer`.

### Opções

| Opção | Padrão | Descrição |
|---|---|---|
| `title`, `text`, `html`, `footer` | `''` | Conteúdo. `text` é texto puro; `html`/`footer` aceitam HTML |
| `icon` | `null` | `success`, `error`, `warning`, `info`, `question` |
| `iconColor` | `null` | Cor do ícone |
| `imageUrl`, `imageWidth`, `imageHeight`, `imageAlt` | | Imagem no topo |
| `toast` | `false` | Modo notificação (sem backdrop, canto da tela) |
| `position` | `center` | `top`, `top-start`, `top-end`, `center`, `center-start`, `center-end`, `bottom`, `bottom-start`, `bottom-end` |
| `theme` | `light` | `light` ou `dark` |
| `width`, `padding`, `background`, `color` | | Aparência do popup |
| `backdrop` | `true` | `false` remove o fundo; string define a cor (ex.: `rgba(0,0,123,.4)`) |
| `timer`, `timerProgressBar` | `null`, `false` | Fecha automaticamente (ms) com barra de progresso |
| `showConfirmButton`, `showDenyButton`, `showCancelButton`, `showCloseButton` | `true`, `false`, `false`, `false` | Botões |
| `confirmButtonText`, `denyButtonText`, `cancelButtonText` | `OK`, `Não`, `Cancelar` | Textos dos botões |
| `confirmButtonColor`, `denyButtonColor`, `cancelButtonColor` | | Cores dos botões |
| `reverseButtons`, `focusConfirm`, `focusCancel` | | Ordem e foco |
| `allowOutsideClick`, `allowEscapeKey`, `allowEnterKey` | `true` | Formas de fechar/confirmar |
| `input` | `null` | `text`, `email`, `password`, `number`, `tel`, `url`, `textarea`, `select`, `checkbox` |
| `inputLabel`, `inputPlaceholder`, `inputValue`, `inputOptions`, `inputAttributes` | | Configuração do input |
| `inputValidator` | `null` | `fn(value)` → mensagem de erro (ou Promise) para bloquear |
| `validationMessage` | `null` | Mensagem para validação nativa (ex.: e-mail inválido) |
| `preConfirm`, `preDeny` | `null` | `fn(value)` → novo valor, `false` para bloquear, ou Promise |
| `showLoaderOnConfirm` | `false` | Mostra loader enquanto `preConfirm` executa |
| `customClass` | `{}` | Classes extras: `container`, `popup`, `title`, `htmlContainer`, `icon`, `image`, `input`, `actions`, `confirmButton`, `denyButton`, `cancelButton`, `closeButton`, `footer`, `timerProgressBar`, `validationMessage` |
| `didOpen`, `willClose`, `didClose` | `null` | Callbacks do ciclo de vida |

### Métodos

`fire`, `mixin`, `close`, `isVisible`, `isLoading`, `showLoading`, `hideLoading`, `showValidationMessage`, `resetValidationMessage`,
`clickConfirm`, `clickDeny`, `clickCancel`, `getPopup`, `getContainer`, `getTitle`, `getHtmlContainer`, `getInput`,
`getConfirmButton`, `getDenyButton`, `getCancelButton`, `getTimerLeft`, `stopTimer`, `resumeTimer`, `isTimerRunning`,
`increaseTimer`, `setDefaults`, `queue`.

## Demonstração

```bash
composer install
php -S localhost:8000 -t examples
```

## Testes

```bash
composer test
```

## Versionamento e publicação

O projeto segue [SemVer](https://semver.org/lang/pt-BR/). Cada alteração publicada gera uma nova tag (`vX.Y.Z`) no GitHub;
o Packagist detecta a tag automaticamente (via webhook do GitHub) e disponibiliza a nova versão.

- **PATCH** (`1.0.1`): correções sem mudar a API
- **MINOR** (`1.1.0`): novas funcionalidades compatíveis
- **MAJOR** (`2.0.0`): mudanças incompatíveis

## Licença

MIT — veja [LICENSE](LICENSE).
