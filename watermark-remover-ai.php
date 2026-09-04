<?php
/**
 * Plugin Name: AI Watermark Remover Pro
 * Version: 3.2.0
 */

if (!defined('ABSPATH')) exit;

define('WMR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WMR_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WMR_PLUGIN_DIR . 'includes/class-wmr-settings.php';
require_once WMR_PLUGIN_DIR . 'includes/class-wmr-api.php';

function wmr_init_plugin() {
    $settings = new WMR_Settings();
    $api      = new WMR_API();
    $settings->init();
    $api->init();
}
add_action('plugins_loaded', 'wmr_init_plugin');

function wmr_enqueue_assets() {
    wp_enqueue_style('wmr-styles', WMR_PLUGIN_URL . 'assets/css/style.css', array(), '3.2.0');
    wp_enqueue_script('wmr-script', WMR_PLUGIN_URL . 'assets/js/script.js', array('jquery'), '3.2.0', true);

    wp_localize_script('wmr-script', 'wmr_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('wmr_process_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'wmr_enqueue_assets');
add_action('admin_enqueue_scripts', 'wmr_enqueue_assets');

function wmr_render_ui_shortcode($atts, $content = null, $tag = '') {
    $lang = 'fr';
    if ($tag === 'watermark_remover_en') $lang = 'en';
    if ($tag === 'watermark_remover_es') $lang = 'es';

    $labels = array(
        'fr' => array(
            'model' => 'Sélectionner un modèle IA',
            'intensity' => 'Niveau d\'ajustement :',
            'lvl1' => 'Discret',
            'lvl2' => 'Équilibré',
            'lvl3' => 'Profond',
            'tt_lvl1' => 'Effectue de légères variations de vocabulaire sans modifier la structure des phrases.',
            'tt_lvl2' => 'Reforme la syntaxe et supprime les tournures typiques d\'IA pour un texte fluide.',
            'tt_lvl3' => 'Restructure en profondeur le style pour éliminer complètement la signature statistique d\'IA.',
            'tt_diff' => 'Affiche en rouge les éléments supprimés et en vert les ajouts effectués par l\'IA.',
            'input' => 'Texte Source :',
            'output' => 'Texte Nettoyé :',
            'btn' => 'Supprimer le Watermark',
            'score' => 'Réduction de Signature Statistique :',
            'src_cnt' => 'Mots Source :',
            'out_cnt' => 'Mots Reformulés :',
            'diff' => 'Vue Comparative (Diff) :'
        ),
        'en' => array(
            'model' => 'Select an AI model',
            'intensity' => 'Adjustment Level:',
            'lvl1' => 'Discrete',
            'lvl2' => 'Balanced',
            'lvl3' => 'Deep',
            'tt_lvl1' => 'Makes minor word choice changes without altering original sentence structures.',
            'tt_lvl2' => 'Rephrases syntax and removes common AI patterns for natural readability.',
            'tt_lvl3' => 'Completely restructures style to remove any statistical AI footprint.',
            'tt_diff' => 'Highlights removed elements in red and new edits in green.',
            'input' => 'Source Text:',
            'output' => 'Cleaned Text:',
            'btn' => 'Remove Watermark',
            'score' => 'Statistical Watermark Reduction:',
            'src_cnt' => 'Source Words:',
            'out_cnt' => 'Cleaned Words:',
            'diff' => 'Diff Viewer:'
        ),
        'es' => array(
            'model' => 'Seleccionar un modelo de IA',
            'intensity' => 'Nivel de ajuste:',
            'lvl1' => 'Discreto',
            'lvl2' => 'Equilibrado',
            'lvl3' => 'Profundo',
            'tt_lvl1' => 'Realiza ligeros cambios de vocabulario sin cambiar la estructura original.',
            'tt_lvl2' => 'Reformula la sintaxis y elimina muletillas típicas de la IA.',
            'tt_lvl3' => 'Reestructura el estilo en profundidad para eliminar la huella estadística.',
            'tt_diff' => 'Muestra en rojo el texto eliminado y en verde los cambios realizados.',
            'input' => 'Texto de origen:',
            'output' => 'Texto limpio:',
            'btn' => 'Eliminar Marca de Agua',
            'score' => 'Reducción de Firma Estadística:',
            'src_cnt' => 'Palabras Origen:',
            'out_cnt' => 'Palabras Limpias:',
            'diff' => 'Vista Comparativa (Diff):'
        )
    );

    $t = $labels[$lang];
    $models = get_option('wmr_ai_models', array());
    $groq_models = array_filter($models, function($m) { return $m['provider'] === 'groq'; });
    $openrouter_models = array_filter($models, function($m) { return $m['provider'] === 'openrouter'; });

    ob_start();
    ?>
    <div class="wmr-container" data-lang="<?php echo $lang; ?>">

        <!-- Sélecteur de modèle -->
        <div class="wmr-field-group">
            <label class="wmr-label"><?php echo esc_html($t['model']); ?></label>
            <select class="wmr-select wmr-model-select">
                <optgroup label="Groq API (Ultra-rapide)">
                    <?php foreach ($groq_models as $m) : ?>
                        <option value="<?php echo esc_attr($m['id']); ?>"><?php echo esc_html($m['name']); ?></option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="OpenRouter API (Modèles Gratuits)">
                    <?php foreach ($openrouter_models as $m) : ?>
                        <option value="<?php echo esc_attr($m['id']); ?>"><?php echo esc_html($m['name']); ?></option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
        </div>

        <!-- Slider de niveau d'ajustement -->
        <div class="wmr-field-group" style="margin-top: 25px;">
            <label class="wmr-label">
                <?php echo esc_html($t['intensity']); ?> 
                <span class="wmr-intensity-val" 
                      data-lvl1="<?php echo esc_attr($t['lvl1']); ?>" 
                      data-lvl2="<?php echo esc_attr($t['lvl2']); ?>" 
                      data-lvl3="<?php echo esc_attr($t['lvl3']); ?>">
                    <?php echo esc_html($t['lvl2']); ?>
                </span>
            </label>
            <input type="range" class="wmr-slider" min="1" max="3" value="2" step="1">
            <div class="wmr-slider-labels">
                <span>
                    <?php echo esc_html($t['lvl1']); ?> 
                    <span class="wmr-tooltip-icon" data-tooltip="<?php echo esc_attr($t['tt_lvl1']); ?>">?</span>
                </span>
                <span>
                    <?php echo esc_html($t['lvl2']); ?> 
                    <span class="wmr-tooltip-icon" data-tooltip="<?php echo esc_attr($t['tt_lvl2']); ?>">?</span>
                </span>
                <span>
                    <?php echo esc_html($t['lvl3']); ?> 
                    <span class="wmr-tooltip-icon" data-tooltip="<?php echo esc_attr($t['tt_lvl3']); ?>">?</span>
                </span>
            </div>
        </div>

        <!-- Zone texte source -->
        <div class="wmr-field-group">
            <label class="wmr-label"><?php echo esc_html($t['input']); ?></label>
            <textarea class="wmr-textarea wmr-input-text" rows="5" placeholder="..."></textarea>
            <div class="wmr-counter wmr-src-counter"><?php echo esc_html($t['src_cnt']); ?> 0 mots | 0 car.</div>
        </div>

        <button type="button" class="wmr-button wmr-process-btn"><?php echo esc_html($t['btn']); ?></button>

        <div class="wmr-loader" style="display:none; margin-top: 15px; color: #64748b;">Traitement en cours...</div>

        <!-- Barre de Score -->
        <div class="wmr-score-box" style="display:none; margin-top: 20px;">
            <label class="wmr-label"><?php echo esc_html($t['score']); ?> <strong class="wmr-score-num">92%</strong></label>
            <div class="wmr-score-bar-bg">
                <div class="wmr-score-bar-fill" style="width: 92%;"></div>
            </div>
        </div>

        <!-- Zone texte nettoyé -->
        <div class="wmr-field-group" style="margin-top: 20px;">
            <label class="wmr-label"><?php echo esc_html($t['output']); ?></label>
            <textarea class="wmr-textarea wmr-output-text" rows="5" readonly></textarea>
            <div class="wmr-counter wmr-out-counter"><?php echo esc_html($t['out_cnt']); ?> 0 mots | 0 car.</div>
        </div>

        <!-- Diff Viewer -->
        <div class="wmr-field-group wmr-diff-box" style="display:none;">
            <label class="wmr-label">
                <?php echo esc_html($t['diff']); ?>
                <span class="wmr-tooltip-icon" data-tooltip="<?php echo esc_attr($t['tt_diff']); ?>">?</span>
            </label>
            <div class="wmr-diff-content"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('watermark_remover_fr', 'wmr_render_ui_shortcode');
add_shortcode('watermark_remover_en', 'wmr_render_ui_shortcode');
add_shortcode('watermark_remover_es', 'wmr_render_ui_shortcode');