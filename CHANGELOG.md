# Changelog

Todas as mudanças relevantes deste projeto são documentadas aqui.
O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e o projeto usa [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [1.0.0] - 2026-11-18

### Adicionado
- Biblioteca JavaScript `PcAlert` (API semelhante ao `Swal.fire`): ícones animados, toasts, timer com barra de progresso, inputs (text, email, password, number, tel, url, textarea, select, checkbox), `inputValidator`, `preConfirm` com loader, botões confirmar/negar/cancelar, tema escuro, `mixin`, posições e acessibilidade (foco, ESC, ARIA).
- Fachada PHP `Alert` com `fire`, `success`, `error`, `warning`, `info`, `question`, `toast`, `confirm`.
- API fluente `AlertBuilder` com `then`, `redirectOnConfirm`, `submitOnConfirm`.
- Alertas flash (Post/Redirect/Get) com armazenamento plugável (`SessionStorage`, `ArrayStorage`).
- Assets inline ou publicados (`Alert::publishAssets`), suporte a nonce de CSP.
