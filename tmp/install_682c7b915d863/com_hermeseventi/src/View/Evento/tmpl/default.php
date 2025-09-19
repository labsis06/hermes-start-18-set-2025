<?php
\defined('_JEXEC') or die;
$evento = $this->item;
?>

<div style="max-width:800px;margin:auto;padding:20px;">
    <h1>Evento #<?php echo $evento->id; ?></h1>
    <p><strong>Data:</strong> <?php echo $evento->data; ?> | <strong>Ora:</strong> <?php echo $evento->ora; ?></p>
    <p><strong>Tipo:</strong> <?php echo $evento->tipo; ?> | <strong>Area:</strong> <?php echo $evento->area; ?></p>
    <p><strong>Stazione:</strong> <?php echo $evento->stazione_first; ?></p>
    <p><strong>Note:</strong> <?php echo $evento->note; ?></p>

    <?php if (!empty($evento->immagini)) : ?>
        <div style="margin-top:20px;">
            <h3>Galleria immagini</h3>
            <?php foreach ($evento->immagini as $img) : ?>
                <div style="margin-bottom:10px;">
                    <img src="<?php echo JUri::root() . $img->percorso; ?>" style="width:100%; border:1px solid #ccc;">
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p><em>Nessuna immagine disponibile.</em></p>
    <?php endif; ?>
</div>
