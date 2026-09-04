jQuery(document).ready(function($) {

    // Gestion dynamique du slider selon la langue courante (Discret / Équilibré / Profond)
    $('.wmr-slider').on('input', function() {
        var val = $(this).val();
        var $target = $(this).closest('.wmr-field-group').find('.wmr-intensity-val');
        
        var lvl1 = $target.data('lvl1');
        var lvl2 = $target.data('lvl2');
        var lvl3 = $target.data('lvl3');

        var labelText = val == 1 ? lvl1 : (val == 2 ? lvl2 : lvl3);
        $target.text(labelText);
    });

    // Compteurs de Mots / Caractères
    function updateCounters($box, text) {
        var chars = text.length;
        var words = text.trim() ? text.trim().split(/\s+/).length : 0;
        $box.text(words + ' mots | ' + chars + ' car.');
    }

    $('.wmr-input-text').on('input', function() {
        var $container = $(this).closest('.wmr-container');
        updateCounters($container.find('.wmr-src-counter'), $(this).val());
    });

    // Traitement AJAX
    $('.wmr-process-btn').on('click', function(e) {
        e.preventDefault();
        var $c = $(this).closest('.wmr-container');
        var text = $c.find('.wmr-input-text').val().trim();
        var model = $c.find('.wmr-model-select').val();
        var intensity = $c.find('.wmr-slider').val();

        if (!text) return alert('Veuillez entrer un texte.');

        $c.find('.wmr-loader').show();
        $c.find('.wmr-score-box, .wmr-diff-box').hide();

        $.ajax({
            url: wmr_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'wmr_process_text',
                nonce: wmr_vars.nonce,
                text: text,
                model_id: model,
                intensity: intensity
            },
            success: function(res) {
                $c.find('.wmr-loader').hide();
                if (res.success) {
                    var out = res.data.result;
                    $c.find('.wmr-output-text').val(out);
                    updateCounters($c.find('.wmr-out-counter'), out);

                    // Score
                    $c.find('.wmr-score-num').text(res.data.score + '%');
                    $c.find('.wmr-score-bar-fill').css('width', res.data.score + '%');
                    $c.find('.wmr-score-box').show();

                    // Diff Viewer
                    renderDiff($c, text, out);
                    $c.find('.wmr-diff-box').show();
                } else {
                    alert('Erreur: ' + res.data);
                }
            }
        });
    });

    // Render Diff (Rouge / Vert)
    function renderDiff($container, oldText, newText) {
        var oldWords = oldText.split(' ');
        var newWords = newText.split(' ');
        var html = '';

        var max = Math.max(oldWords.length, newWords.length);
        for (var i = 0; i < max; i++) {
            if (oldWords[i] === newWords[i]) {
                if (oldWords[i]) html += oldWords[i] + ' ';
            } else {
                if (oldWords[i]) html += '<span class="wmr-diff-del">' + oldWords[i] + '</span> ';
                if (newWords[i]) html += '<span class="wmr-diff-ins">' + newWords[i] + '</span> ';
            }
        }
        $container.find('.wmr-diff-content').html(html);
    }
});