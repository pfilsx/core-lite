<?php if (is_array($lines) && !empty($lines)) { ?>
    <div class="call-stack-item" data-line="<?= (int)($line - (array_keys($lines)[0])) ?>" <?= $visible ? ' style="display: block;" ': ''?>>
        <div class="code-wrap">
            <div class="error-line"></div>
            <?php foreach ($lines as $line) : ?>
                <div class="hover-line"></div>
            <?php endforeach; ?>
            <div class="code">
                <?php foreach ($lines as $rownum => $line) : ?>
                    <span class="lines-item"><?= (int) ($rownum + 1) ?></span>
                <?php endforeach; ?>
                <pre><?php
                    // fill empty lines with a whitespace to avoid rendering problems in opera
                    foreach ($lines as $line) {
                        echo (trim($line) === '') ? " \n" : htmlspecialchars($line, ENT_QUOTES, 'UTF-8');;
                    }
                    ?></pre>
            </div>
        </div>
    </div>
<?php } ?>