<style>
    .mostra-dettagli-btn {
        margin-top: 10px;
        padding: 6px 12px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
    }
    .mostra-dettagli-btn:hover {
        background-color: #0056b3;
    }
    .dettagli {
        border-top: 1px solid #ccc;
        margin-top: 10px;
        padding-top: 10px;
        display: none;
    }
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".mostra-dettagli-btn").forEach(btn => {
        btn.addEventListener("click", function(e) {
            e.preventDefault();
            const dettagli = btn.nextElementSibling;
            const visibile = dettagli.style.display === "block";
            dettagli.style.display = visibile ? "none" : "block";
            btn.textContent = visibile ? "Mostra dettagli" : "Nascondi dettagli";
        });
    });
});
</script>

<style>
    .evento {
        cursor: pointer;
        position: relative;
    }
    .evento .dettagli {
        display: none;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #ccc;
        animation: fadeIn 0.3s ease-in-out;
    }
    .evento.attiva .dettagli {
        display: block;
    }
    @keyframes fadeIn {
        from {opacity: 0;}
        to {opacity: 1;}
    }
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".evento").forEach(evento => {
        evento.addEventListener("click", function(e) {
            // evitare conflitto con click su checkbox o pulsanti
            if (e.target.tagName === "INPUT" || e.target.tagName === "BUTTON" || e.target.closest("button")) return;
            evento.classList.toggle("attiva");
        });
    });
});
</script>

<?php defined('_JEXEC') or die; ?>

<style>
    .hermes-eventi {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
    }
    .evento {
        background-color: #f9f9f9;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        padding: 15px;
        margin-bottom: 20px;
        overflow: hidden;
        transition: all 0.5s ease;
        width: 300px;
        flex: 0 0 auto;
    }
    .evento.aperta {
        width: 100%;
        max-width: 100%;
    }
    .mostra-dettagli-btn {
        margin-top: 10px;
        padding: 6px 12px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
    }
    .mostra-dettagli-btn:hover {
        background-color: #0056b3;
    }
    .dettagli {
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transition: max-height 0.5s ease, opacity 0.5s ease;
        margin-top: 10px;
        border-top: 1px solid #ccc;
        padding-top: 10px;
    }
    .evento.aperta .dettagli {
        opacity: 1;
    }
</style>

<style>
    .filtro-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 20px auto;
        justify-content: center;
        align-items: center;
    }
    .filtro-container select,
    .filtro-container input {
        padding: 6px 10px;
        font-size: 14px;
        border-radius: 6px;
        border: 1px solid #ccc;
    }

    .hermes-eventi {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
        padding: 10px;
    }
    .evento {
        width: 300px;
        background-color: #f9f9f9;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        padding: 15px;
        box-sizing: border-box;
        transition: transform 0.2s;
    }
    .evento:hover {
        transform: scale(1.02);
    }
    .evento h2 {
        font-size: 1.2em;
        margin-top: 0;
        color: #333;
    }
    .evento img {
        width: 100%;
        height: auto;
        border-radius: 8px;
        cursor: zoom-in;
        margin-top: 10px;
    }
    .evento p {
        margin: 5px 0;
        font-size: 0.95em;
        color: #555;
    }
    .evento label {
        display: block;
        margin-top: 10px;
        font-weight: bold;
        font-size: 0.9em;
    }
</style>

<div class="filtro-container">
    <select id="filtro-tipo" onchange="filtraEventi()">
        <option value="">Tutti i tipi</option>
        <?php
            $tipi_unici = array_unique(array_map(function($e){ return $e->tipo; }, $eventi));
            sort($tipi_unici);
            foreach ($tipi_unici as $tipo) {
                echo "<option value='" . htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($tipo) . "</option>";
            }
        ?>
    </select>
    <input type="date" id="filtro-data" onchange="filtraEventi()" />
    <input type="time" id="filtro-ora" onchange="filtraEventi()" />
    <select id="ordinamento" onchange="filtraEventi()">
        <option value="default">Ordine originale</option>
        <option value="data_asc">Data crescente</option>
        <option value="data_desc">Data decrescente</option>
        <option value="tipo_asc">Tipo A-Z</option>
        <option value="tipo_desc">Tipo Z-A</option>
    </select>
</div>

<script>
function filtraEventi() {
    const tipoFiltro = document.getElementById("filtro-tipo").value.toLowerCase();
    const dataFiltro = document.getElementById("filtro-data").value;
    const oraFiltro = document.getElementById("filtro-ora").value;
    const ordinamento = document.getElementById("ordinamento").value;

    const eventi = Array.from(document.querySelectorAll(".evento"));
    eventi.forEach(e => e.style.display = "block");

    eventi.forEach(evento => {
        const tipo = evento.querySelector("h2").innerText.toLowerCase();
        const data = evento.querySelector("p:nth-of-type(1)").innerText.replace("Data: ", "");
        const ora = evento.querySelector("p:nth-of-type(2)").innerText.replace("Ora: ", "");

        let show = true;
        if (tipoFiltro && !tipo.includes(tipoFiltro)) show = false;
        if (dataFiltro && !data.includes(dataFiltro)) show = false;
        if (oraFiltro && !ora.startsWith(oraFiltro)) show = false;

        evento.style.display = show ? "block" : "none";
    });

    // Ordinamento
    const container = document.querySelector(".hermes-eventi");
    if (ordinamento !== "default") {
        eventi.sort((a, b) => {
            const tipoA = a.querySelector("h2").innerText.toLowerCase();
            const tipoB = b.querySelector("h2").innerText.toLowerCase();
            const dataA = a.querySelector("p:nth-of-type(1)").innerText.replace("Data: ", "");
            const dataB = b.querySelector("p:nth-of-type(1)").innerText.replace("Data: ", "");

            if (ordinamento === "tipo_asc") return tipoA.localeCompare(tipoB);
            if (ordinamento === "tipo_desc") return tipoB.localeCompare(tipoA);
            if (ordinamento === "data_asc") return dataA.localeCompare(dataB);
            if (ordinamento === "data_desc") return dataB.localeCompare(dataA);
        });
        eventi.forEach(e => container.appendChild(e));
    }
}
</script>

<div class="hermes-eventi">
    <?php foreach ($eventi as $evento): ?>
        <div class="evento" style="margin-bottom: 20px;">
            <h2><?php echo htmlspecialchars($evento->tipo); ?></h2>
            <?php if (!empty($evento->percorso_file)) : ?>
                <label>
                    <input type="checkbox" class="compare-check" data-src="<?php echo JUri::base() . htmlspecialchars($evento->percorso_file, ENT_QUOTES, 'UTF-8'); ?>" onchange="updateComparison(this)">
                    Confronta
                </label><br>
                
<?php
$immagini = explode("||", $evento->percorso_file);
foreach ($immagini as $img) {
    if (!empty($img)) {
        $src = JUri::base() . htmlspecialchars($img, ENT_QUOTES, 'UTF-8');
        echo '<div style="display:inline-block; text-align:center; margin:5px;">';
        echo '<img src="' . $src . '" alt="Immagine evento" class="evento-img" style="max-width:100px;"><br>';
        echo '<input type="checkbox" class="compare-check" data-src="' . $src . '" onchange="updateComparison(this)"> Confronta';
        echo '</div>';
    }
}
?>

            <?php else : ?>
                <p><em>Immagine non disponibile</em></p>
            <?php endif; ?>
            <p><strong>Data:</strong> <?php echo nl2br(htmlspecialchars($evento->data)); ?></p>
    <p><strong>Ora:</strong> <?php echo nl2br(htmlspecialchars($evento->ora)); ?></p>

    <button class="mostra-dettagli-btn">Mostra dettagli</button>

    <div class="dettagli" style="display: none;">
        <p><strong>ID Evento:</strong> <?php echo nl2br(htmlspecialchars($evento->id)); ?></p>
        <p><strong>Stazione:</strong> <?php echo nl2br(htmlspecialchars($evento->stazione_first)); ?></p>
        <p><strong>Percorso file:</strong> <?php echo htmlspecialchars($evento->percorso_file); ?></p>
    </div>
          </div>
    <?php endforeach; ?>


<div class="popup-buttons">
    <button onclick="openComparison()">Apri confronto</button>
</div>

<div class="lightbox-overlay" id="lightbox" onclick="closeLightbox()">
    <img id="lightbox-img" src="" alt="Immagine ingrandita">
</div>

<div id="confronto-popup">
    <h2>Confronto immagini</h2>
    <div class="popup-buttons">
        <button onclick="clearComparison()">Svuota</button>
        <button onclick="window.print()">Stampa</button>
        <button onclick="closeComparison()">Chiudi</button>
    </div>
    <div id="confronto-immagini-popup"></div>
</div>
<style>
    .confronto-immagine { position: relative; display: inline-block; }
    .img-wrapper { overflow: hidden; position: relative; }
    .img-wrapper img { cursor: grab; user-select: none; }
    .img-wrapper img.dragging { cursor: grabbing; }
    .zoom-buttons {
        position: absolute;
        top: 5px;
        right: 5px;
        z-index: 10;
        display: flex;
        gap: 4px;
    }
</style>  
<style>
    .confronto-immagine { position: relative; display: inline-block; }
    .img-wrapper { overflow: hidden; position: relative; }
    .img-wrapper img { cursor: grab; user-select: none; }
    .img-wrapper img.dragging { cursor: grabbing; }
    .zoom-buttons {
        position: absolute;
        top: 5px;
        right: 5px;
        z-index: 10;
        display: flex;
        gap: 4px;
    }
</style>


  
<script>
    const confrontoPopup = document.getElementById('confronto-popup');
    const confrontoImmaginiPopup = document.getElementById('confronto-immagini-popup');
    const checkboxes = document.querySelectorAll('.compare-check');

    function updateComparison(checkbox) {
        const selected = document.querySelectorAll('.compare-check:checked');
        if (selected.length > 3) {
            checkbox.checked = false;
            alert("Puoi selezionare al massimo 3 immagini per il confronto.");
        }
    }

    function openComparison() {
        confrontoImmaginiPopup.innerHTML = '';
        const selected = document.querySelectorAll('.compare-check:checked');
        if (selected.length === 0) {
            alert("Seleziona almeno un'immagine da confrontare.");
            return;
        }
        selected.forEach((cb, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'confronto-immagine';

            const scrollBox = document.createElement('div');
            scrollBox.className = 'img-wrapper';

            const img = document.createElement('img');
            img.src = cb.dataset.src;
            img.id = 'zoom-img-' + index;
            img.setAttribute('data-zoom', 1);
            
            img.setAttribute('data-x', 0);
            img.setAttribute('data-y', 0);
            img.draggable = false;

            let isDragging = false;
            let startX = 0;
            let startY = 0;

            scrollBox.addEventListener('mousedown', e => {
                if (parseFloat(img.getAttribute('data-zoom')) <= 1) return;
                isDragging = true;
                img.classList.add('dragging');
                startX = e.clientX - parseFloat(img.getAttribute('data-x'));
                startY = e.clientY - parseFloat(img.getAttribute('data-y'));
            });

            scrollBox.addEventListener('mousemove', e => {
                if (!isDragging) return;
                const x = e.clientX - startX;
                const y = e.clientY - startY;
                const zoom = parseFloat(img.getAttribute('data-zoom'));
                img.style.transform = `translate(${x}px, ${y}px) scale(${zoom})`;
                img.setAttribute('data-x', x);
                img.setAttribute('data-y', y);
            });

            ['mouseup', 'mouseleave'].forEach(evt => {
                scrollBox.addEventListener(evt, () => {
                    isDragging = false;
                    img.classList.remove('dragging');
                });
            });
            
            scrollBox.appendChild(img);

            const zoomControls = document.createElement('div');
            zoomControls.className = 'zoom-buttons';

            const zoomIn = document.createElement('button');
            zoomIn.textContent = '+';
            zoomIn.onclick = () => zoomImage(img.id, 1.2);

            const zoomOut = document.createElement('button');
            zoomOut.textContent = '−';
            zoomOut.onclick = () => zoomImage(img.id, 0.8);

            zoomControls.appendChild(zoomIn);
            zoomControls.appendChild(zoomOut);

            wrapper.appendChild(scrollBox);
            wrapper.appendChild(zoomControls);
            confrontoImmaginiPopup.appendChild(wrapper);
        });
        confrontoPopup.style.display = 'block';
    }

    function zoomImage(id, factor) {
       
        let currentZoom = parseFloat(img.getAttribute('data-zoom')) * factor;
        let currentX = parseFloat(img.getAttribute('data-x')) || 0;
        let currentY = parseFloat(img.getAttribute('data-y')) || 0;
        if (currentZoom <= 1) {
            currentZoom = 1;
            currentX = 0;
            currentY = 0;
        }
        img.style.transform = `translate(${currentX}px, ${currentY}px) scale(${currentZoom})`;
        img.style.transformOrigin = 'center';
        img.setAttribute('data-zoom', currentZoom);
        img.setAttribute('data-x', currentX);
        img.setAttribute('data-y', currentY);
    }

    function clearComparison() {
        document.querySelectorAll('.compare-check:checked').forEach(cb => cb.checked = false);
        confrontoImmaginiPopup.innerHTML = '';
    }

    function closeComparison() {
        confrontoPopup.style.display = 'none';
    }

    function openLightbox(src) {
        document.getElementById('lightbox-img').src = src;
        document.getElementById('lightbox').style.display = 'flex';
    }

    function closeLightbox() {
        document.getElementById('lightbox').style.display = 'none';
        document.getElementById('lightbox-img').src = '';
    }
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".mostra-dettagli-btn").forEach(btn => {
        btn.addEventListener("click", function(e) {
            e.preventDefault();
            const evento = btn.closest(".evento");
            const dettagli = evento.querySelector(".dettagli");

            document.querySelectorAll(".evento").forEach(el => {
                if (el !== evento) {
                    el.classList.remove("aperta");
                    el.querySelector(".dettagli").style.maxHeight = "0px";
                    el.querySelector(".mostra-dettagli-btn").textContent = "Mostra dettagli";
                }
            });

            const isOpen = evento.classList.toggle("aperta");

            if (isOpen) {
                dettagli.style.maxHeight = dettagli.scrollHeight + "px";
                btn.textContent = "Nascondi dettagli";
            } else {
                dettagli.style.maxHeight = "0px";
                btn.textContent = "Mostra dettagli";
            }
        });
    });
});
</script>




<style>
.lightbox-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.85);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    flex-direction: column;
}
.lightbox-overlay img {
    max-width: 90%;
    max-height: 70%;
}
.lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    font-size: 48px;
    color: white;
    cursor: pointer;
    user-select: none;
    padding: 10px;
}
#lightbox-prev { left: 20px; }
#lightbox-next { right: 20px; }
#lightbox-download {
    margin-top: 20px;
    padding: 10px 20px;
    background-color: white;
    color: black;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
}
#lightbox-download:hover {
    background-color: #ddd;
}
</style>
<div class="lightbox-overlay" id="lightbox">
    <span class="lightbox-nav" id="lightbox-prev">&#10094;</span>
    <img id="lightbox-img" src="" alt="Immagine ingrandita">
    <span class="lightbox-nav" id="lightbox-next">&#10095;</span>
    <a id="lightbox-download" href="#" download>Scarica immagine</a>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const lightbox = document.getElementById("lightbox");
    const lightboxImg = document.getElementById("lightbox-img");
    const prevBtn = document.getElementById("lightbox-prev");
    const nextBtn = document.getElementById("lightbox-next");
    const downloadBtn = document.getElementById("lightbox-download");

    let currentGroup = [];
    let currentIndex = -1;

    document.querySelectorAll(".immagini-container").forEach(container => {
        const images = Array.from(container.querySelectorAll("img"));
        images.forEach((img, idx) => {
            img.addEventListener("click", function () {
                currentGroup = images;
                currentIndex = idx;
                showImage();
                lightbox.style.display = "flex";
            });
        });
    });

    function showImage() {
        if (currentGroup.length > 0 && currentIndex >= 0) {
            const src = currentGroup[currentIndex].src;
            lightboxImg.src = src;
            downloadBtn.href = src;
        }
    }

    prevBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        if (currentIndex > 0) {
            currentIndex--;
            showImage();
        }
    });

    nextBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        if (currentIndex < currentGroup.length - 1) {
            currentIndex++;
            showImage();
        }
    });

    lightbox.addEventListener("click", function () {
        lightbox.style.display = "none";
        currentGroup = [];
        currentIndex = -1;
    });
});
</script>



<style>
.lightbox-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.85);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}
.lightbox-overlay img {
    max-width: 90%;
    max-height: 80%;
}
</style>
<div class="lightbox-overlay" id="lightbox">
    <img id="lightbox-img" src="" alt="Immagine ingrandita">
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const lightbox = document.getElementById("lightbox");
    const lightboxImg = document.getElementById("lightbox-img");

    document.querySelectorAll(".evento-img").forEach(img => {
        img.addEventListener("click", function () {
            lightboxImg.src = img.src;
            lightbox.style.display = "flex";
        });
    });

    lightbox.addEventListener("click", function () {
        lightbox.style.display = "none";
    });
});
</script>
