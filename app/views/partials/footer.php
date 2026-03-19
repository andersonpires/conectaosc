<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
$config = $pdo->query("SELECT * FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$toastMsg = isset($_GET['msg']) ? trim((string) $_GET['msg']) : '';
$toastErro = isset($_GET['erro']) ? trim((string) $_GET['erro']) : '';
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
                    <a class="text-muted" href="<?php echo $BASE_para_URL; ?>/paginas-footer?page=termos-de-uso" target="_self">Termos de uso</a>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- Adicionar mascara nos inputs e funcoes via CEP -->

<script>
    (function() {
        const toastMessage = <?php echo json_encode($toastMsg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const toastError = <?php echo json_encode($toastErro, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        if (!toastMessage && !toastError) {
            return;
        }

        function showToast() {
            if (typeof window.toastr === 'undefined') {
                return false;
            }

            toastr.options = {
                closeButton: true,
                debug: false,
                newestOnTop: true,
                progressBar: true,
                positionClass: 'toast-top-center',
                preventDuplicates: true,
                onclick: null,
                showDuration: '300',
                hideDuration: '1000',
                timeOut: '4000',
                extendedTimeOut: '1000',
                showEasing: 'swing',
                hideEasing: 'linear',
                showMethod: 'fadeIn',
                hideMethod: 'fadeOut'
            };

            if (toastMessage) {
                toastr.success(toastMessage);
            }

            if (toastError) {
                toastr.error(toastError);
            }

            return true;
        }

        function injectScript(src, marker, onload) {
            if (document.querySelector(`script[${marker}]`)) {
                return false;
            }

            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.setAttribute(marker, '1');

            if (typeof onload === 'function') {
                script.onload = onload;
            }

            document.body.appendChild(script);
            return true;
        }

        function ensureToastr() {
            if (showToast()) {
                return;
            }

            if (typeof window.jQuery === 'undefined') {
                if (!injectScript('https://code.jquery.com/jquery-3.6.0.min.js', 'data-toast-jquery', ensureToastr)) {
                    window.setTimeout(ensureToastr, 150);
                }
                return;
            }

            if (typeof window.toastr === 'undefined') {
                if (!injectScript('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js', 'data-toast-lib', showToast)) {
                    window.setTimeout(showToast, 150);
                }
                return;
            }

            if (!showToast()) {
                window.setTimeout(ensureToastr, 150);
            }
        }

        ensureToastr();
    })();
</script>




