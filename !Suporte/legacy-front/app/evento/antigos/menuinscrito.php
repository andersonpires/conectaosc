<nav id="sidebar" class="sidebar js-sidebar">
	<div class="sidebar-content js-simplebar">
		<a class="sidebar-brand" href="#">
			<center>
				<image src="imagens/logo.png" class="align-middle" style="width: 90%;">
			</center>
		</a>
		<ul class="sidebar-nav">

			<li class="sidebar-header">
				Eventos
			</li>
			
			<li class="sidebar-item" id="formInscrito.php">
				<a class="sidebar-link" href="formInscrito.php">
					<i class="align-middle" data-feather="user-plus"></i> <span class="align-middle">Inscrição</span>
				</a>
			</li>

			<li class="sidebar-item" id="#">
				<a class="sidebar-link" href="#">
					<i class="align-middle" data-feather="align-left"></i> <span class="align-middle">Resultados</span>
				</a>
			</li>
			

		</ul>
	</div>
</nav>
<?php
$paginaAtual = basename($_SERVER['PHP_SELF']);

// Atribuir a classe "active" ao item da página atual
$idSeguro = preg_replace('/[^a-zA-Z0-9_-]/', '_', $paginaAtual);
echo '<script>
    var elemento = document.getElementById("' . $paginaAtual . '");
    if (elemento) {
        elemento.classList.add("active");
    }
</script>';
?>


