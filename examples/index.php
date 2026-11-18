<?php
/**
 * Demonstração da biblioteca.
 * Rode na raiz do projeto:  composer install && php -S localhost:8000 -t examples
 */
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PortalCurso\Alert\Alert;

session_start();

// Padrão Post/Redirect/Get com alerta flash
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    if ($nome === '') {
        Alert::flash('Ops!', 'Informe o nome.', Alert::ERROR);
    } else {
        Alert::flashToast('Olá, ' . $nome . '! Registro salvo.');
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PcAlert - Demo</title>
    <?= Alert::assets() ?>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 760px; margin: 2rem auto; padding: 0 1rem; color: #333; }
        button { margin: .25rem; padding: .5rem .9rem; border: 1px solid #ccc; border-radius: 6px; background: #f7f7f7; cursor: pointer; }
        form { margin: 1.5rem 0; padding: 1rem; border: 1px solid #eee; border-radius: 8px; }
        input { padding: .45rem; }
    </style>
</head>
<body>
<h1>PcAlert</h1>

<h3>Disparados pelo JavaScript</h3>
<button onclick="PcAlert.fire('Bom trabalho!', 'Você clicou no botão!', 'success')">Sucesso</button>
<button onclick="PcAlert.fire({icon:'error', title:'Oops...', text:'Algo deu errado!', footer:'<a href=&quot;#&quot;>Por que isso aconteceu?</a>'})">Erro</button>
<button onclick="confirmar()">Confirmação</button>
<button onclick="tresBotoes()">Salvar / Não salvar / Cancelar</button>
<button onclick="pedirEmail()">Input e-mail</button>
<button onclick="selecionar()">Select</button>
<button onclick="ajax()">preConfirm (loader)</button>
<button onclick="PcAlert.fire({title:'Fecha em 3s', timer:3000, timerProgressBar:true, showConfirmButton:false})">Timer</button>
<button onclick="Toast.fire({icon:'success', title:'Login realizado'})">Toast</button>
<button onclick="PcAlert.fire({title:'Tema escuro', icon:'info', theme:'dark'})">Dark</button>

<h3>Disparado pelo PHP (flash após POST)</h3>
<form method="post">
    <input name="nome" placeholder="Seu nome">
    <button type="submit">Enviar</button>
</form>

<script>
    var Toast = PcAlert.mixin({ toast: true, position: 'top-end', timer: 3000, timerProgressBar: true });

    function confirmar() {
        PcAlert.fire({
            title: 'Tem certeza?', text: 'Esta ação não pode ser desfeita!', icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir!'
        }).then(function (r) {
            if (r.isConfirmed) PcAlert.fire('Excluído!', 'O arquivo foi excluído.', 'success');
        });
    }

    function tresBotoes() {
        PcAlert.fire({
            title: 'Deseja salvar as alterações?', showDenyButton: true, showCancelButton: true,
            confirmButtonText: 'Salvar', denyButtonText: 'Não salvar'
        }).then(function (r) {
            if (r.isConfirmed) PcAlert.fire('Salvo!', '', 'success');
            else if (r.isDenied) PcAlert.fire('As alterações não foram salvas', '', 'info');
        });
    }

    async function pedirEmail() {
        const { value: email } = await PcAlert.fire({
            title: 'Seu e-mail', input: 'email', inputPlaceholder: 'nome@exemplo.com',
            validationMessage: 'E-mail inválido'
        });
        if (email) PcAlert.fire('E-mail: ' + email);
    }

    function selecionar() {
        PcAlert.fire({
            title: 'Escolha uma fruta', input: 'select', inputPlaceholder: 'Selecione...',
            inputOptions: { maca: 'Maçã', banana: 'Banana', uva: 'Uva' }, showCancelButton: true,
            inputValidator: function (v) { return v ? null : 'Escolha uma opção'; }
        }).then(function (r) { if (r.isConfirmed) PcAlert.fire('Você escolheu: ' + r.value); });
    }

    function ajax() {
        PcAlert.fire({
            title: 'Usuário do GitHub', input: 'text', showCancelButton: true,
            confirmButtonText: 'Buscar', showLoaderOnConfirm: true,
            preConfirm: function (login) {
                return new Promise(function (ok) { setTimeout(function () { ok({ login: login }); }, 1500); });
            }
        }).then(function (r) { if (r.isConfirmed) PcAlert.fire('Encontrado: ' + r.value.login, '', 'success'); });
    }
</script>

<?= Alert::render() ?>
</body>
</html>
