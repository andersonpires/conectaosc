<?php
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
$config = $pdo->query("SELECT * FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<div class="container-fluid">
    <div class="row text-muted">
        <div class="col-6 text-start">
            <p class="mb-0">
                
                <a class="text-muted" href="#" target="_blank"><?php echo ($config['NomeSistema'] ?? ''); ?></a> &copy;
            </p>
        </div>
        <div class="col-6 text-end">
            <ul class="list-inline">
                <!-- <li class="list-inline-item">
                    <a class="text-muted" href="#" target="_blank">Suporte</a>
                </li>
                <li class="list-inline-item">
                    <a class="text-muted" href="#" target="_blank">Celtral de ajuda</a>
                </li>
                <li class="list-inline-item">
                    <a class="text-muted" href="#" target="_blank">Privacidade</a>
                </li> -->
                <li class="list-inline-item">
                    <a class="text-muted" href="<?php echo $_SESSION['BASE_URL']; ?>/template/paginasFooter.php?page=termos-de-uso" target="_self">Termos de uso</a>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- Adicioanar Máscara nos inputs e função viaCEP -->

