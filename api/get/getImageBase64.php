<script>
    // Função para carregar imagem e convertê-la em Base64
    function getImageBase64(url, callback) {
    const img = new Image();
    img.crossOrigin = 'Anonymous'; // Ajuda a evitar problemas de CORS
    img.onload = function() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);
        const dataURL = canvas.toDataURL('image/png');
        callback(dataURL, img.width, img.height);
    };
    img.src = url;
}
</script>

