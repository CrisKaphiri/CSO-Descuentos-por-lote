<?php
/**
 * Plugin Name: CSO - Descuentos por Lote
 * Description: Descuentos automáticos por volumen (packs) en el carrito de WooCommerce, con niveles, colores y etiquetas excluidas configurables desde un panel propio.
 * Version: 2.3.1
 * Author: Cristóbal Sánchez Orellana
 * Text Domain: cso-descuentos-lote
 * Requires Plugins: woocommerce
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // No acceso directo.
}

class CSO_Descuentos_Por_Lote {

    const OPTION_TIERS          = 'csodl_tiers';
    const OPTION_EXCLUDE_TAGS   = 'csodl_exclude_tags';
    const OPTION_ENABLE_MINICART = 'csodl_enable_minicart';
    const SETTINGS_SLUG         = 'cso-descuentos-lote';
    const MENU_SLUG             = 'cso-panel';

    public function __construct() {
        // Todo se registra recién en 'plugins_loaded', momento en el que
        // WordPress ya terminó de cargar TODOS los plugins (incluido WooCommerce).
        // Si registráramos esto directo en el constructor, el chequeo de
        // WooCommerce podría fallar por orden alfabético de carga de plugins.
        add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
    }

    public function init_plugin() {
        add_action( 'admin_notices', array( $this, 'maybe_show_woocommerce_notice' ) );

        // Si WooCommerce no está activo, no registramos nada más para evitar errores fatales.
        if ( ! $this->woocommerce_is_active() ) {
            return;
        }

        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_dynamic_packs' ), 20 );

        // Aviso escalonado de progreso: mini carrito (según opción).
        add_action( 'woocommerce_mini_cart_contents', array( $this, 'render_progress_notice_minicart' ) );

        // Shortcode disponible siempre, para colocar manualmente donde quieras (ej. Elementor).
        add_shortcode( 'cso_anuncio_promocion_carrito', array( $this, 'render_progress_notice_shortcode' ) );
        add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'refresh_progress_notice_fragment' ) );

        // Aviso de exclusión para productos con etiquetas excluidas.
        add_shortcode( 'cso_aviso_productos_excluidos', array( $this, 'render_exclusion_notice' ) );
    }

    private function woocommerce_is_active() {
        return class_exists( 'WooCommerce' );
    }

    public function maybe_show_woocommerce_notice() {
        if ( $this->woocommerce_is_active() ) {
            return;
        }
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }
        echo '<div class="notice notice-error"><p><strong>CSO - Descuentos por Lote</strong> necesita que <strong>WooCommerce</strong> esté instalado y activo para funcionar.</p></div>';
    }

    /**
     * Valores por defecto: los mismos 3 niveles originales, con emoji y colores por defecto.
     */
    private function get_default_tiers() {
        return array(
            array(
                'quantity'    => 4,
                'price'       => 15000,
                'emoji'       => '🎮',
                'color_start' => '#00c6ff',
                'color_end'   => '#7928ca',
            ),
            array(
                'quantity'    => 10,
                'price'       => 30000,
                'emoji'       => '🚀',
                'color_start' => '#ff416c',
                'color_end'   => '#ff4b2b',
            ),
            array(
                'quantity'    => 15,
                'price'       => 40000,
                'emoji'       => '👑',
                'color_start' => '#ffb300',
                'color_end'   => '#f100a5',
            ),
        );
    }

    private function get_tiers() {
        $tiers = get_option( self::OPTION_TIERS, null );
        if ( ! is_array( $tiers ) || empty( $tiers ) ) {
            return $this->get_default_tiers();
        }
        return $tiers;
    }

    /**
     * Devuelve el arreglo de etiquetas (slugs) excluidas del conteo de packs.
     */
    private function get_exclude_tags() {
        $tags = get_option( self::OPTION_EXCLUDE_TAGS, null );
        if ( ! is_array( $tags ) ) {
            return array();
        }
        return $tags;
    }

    private function product_is_excluded( $product_id ) {
        $tags = $this->get_exclude_tags();
        if ( empty( $tags ) ) {
            return false;
        }
        return has_term( $tags, 'product_tag', $product_id );
    }

    /* =========================================================
     *  LÓGICA DE DESCUENTO
     * ========================================================= */
    public function apply_dynamic_packs( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }
        if ( $cart->is_empty() ) {
            return;
        }

        $prices = array();

        foreach ( $cart->get_cart() as $cart_item ) {
            $product_id = $cart_item['product_id'];

            if ( $this->product_is_excluded( $product_id ) ) {
                continue;
            }

            $quantity   = $cart_item['quantity'];
            $unit_price = $cart_item['line_subtotal'] / $quantity;

            for ( $i = 0; $i < $quantity; $i++ ) {
                $prices[] = (float) $unit_price;
            }
        }

        if ( empty( $prices ) ) {
            return;
        }

        rsort( $prices );
        $total_items = count( $prices );

        // Niveles ordenados de mayor a menor cantidad, para que se evalúe
        // primero el pack más grande al que la persona califique.
        $tiers = $this->get_tiers();
        usort( $tiers, function ( $a, $b ) {
            return $b['quantity'] <=> $a['quantity'];
        } );

        foreach ( $tiers as $tier ) {
            $qty         = (int) $tier['quantity'];
            $fixed_price = (float) $tier['price'];

            if ( $total_items >= $qty ) {
                $top_slice   = array_slice( $prices, 0, $qty );
                $slice_total = array_sum( $top_slice );

                if ( $slice_total > $fixed_price ) {
                    $discount = $slice_total - $fixed_price;
                    $label    = sprintf( 'Promoción %d Productos', $qty );
                    $cart->add_fee( $label, -$discount );
                    return; // Solo se aplica un pack, el primero que califique.
                }
            }
        }
    }

    /* =========================================================
     *  AVISO ESCALONADO DE PROGRESO (mini carrito + carrito Elementor)
     * ========================================================= */
    private function count_eligible_items() {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return 0;
        }
        $count = 0;
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product_id = $cart_item['product_id'];
            if ( $this->product_is_excluded( $product_id ) ) {
                continue;
            }
            $count += $cart_item['quantity'];
        }
        return $count;
    }

    private function format_price( $price ) {
        return number_format_i18n( $price, 0 );
    }

    public function get_progress_notice_html() {
        $count = $this->count_eligible_items();
        if ( $count === 0 ) {
            return '';
        }

        $tiers = $this->get_tiers();
        usort( $tiers, function ( $a, $b ) {
            return $a['quantity'] <=> $b['quantity'];
        } );

        if ( empty( $tiers ) ) {
            return '';
        }

        // Buscar el índice del nivel ya alcanzado (el más alto con quantity <= count).
        $achieved_index = -1;
        foreach ( $tiers as $i => $tier ) {
            if ( $count >= $tier['quantity'] ) {
                $achieved_index = $i;
            }
        }

        $texto = '';

        if ( $achieved_index === -1 ) {
            // Aún no alcanza el primer nivel.
            $first        = $tiers[0];
            $faltantes    = $first['quantity'] - $count;
            $emoji        = $first['emoji'];
            $degradado_bg = 'linear-gradient(135deg, ' . $first['color_start'] . ', ' . $first['color_end'] . ')';
            $texto = '<strong>' . $emoji . ' Promoción ' . $first['quantity'] . ' x $' . $this->format_price( $first['price'] ) . '</strong><br>Añade <span style="color:#ffffff; background:rgba(255,255,255,0.2); padding:2px 6px; border-radius:4px; font-weight:bold;">' . $faltantes . ' más</span> para adquirir la promoción';
        } elseif ( $achieved_index === count( $tiers ) - 1 ) {
            // Ya alcanzó el nivel máximo.
            $last         = $tiers[ $achieved_index ];
            $emoji        = $last['emoji'];
            $degradado_bg = 'linear-gradient(135deg, ' . $last['color_start'] . ', ' . $last['color_end'] . ')';
            $texto = '<strong>' . $emoji . ' ¡Promoción Máxima Conseguida!</strong><br>Has desbloqueado la oferta máxima de ' . $last['quantity'] . ' productos por $' . $this->format_price( $last['price'] );
        } else {
            // Alcanzó un nivel intermedio, mostrar progreso hacia el siguiente.
            $achieved     = $tiers[ $achieved_index ];
            $next         = $tiers[ $achieved_index + 1 ];
            $faltantes    = $next['quantity'] - $count;
            $emoji        = $next['emoji'];
            $degradado_bg = 'linear-gradient(135deg, ' . $next['color_start'] . ', ' . $next['color_end'] . ')';
            $texto = '<strong>' . $emoji . ' Promoción ' . $achieved['quantity'] . ' x $' . $this->format_price( $achieved['price'] ) . ' Conseguida</strong><br>Añade <span style="color:#ffffff; background:rgba(255,255,255,0.2); padding:2px 6px; border-radius:4px; font-weight:bold;">' . $faltantes . ' más</span> para adquirir la siguiente <strong>promoción ' . $next['quantity'] . ' x $' . $this->format_price( $next['price'] ) . '</strong>';
        }

        return '<div class="bloque-anuncio-ajax-render">
        	<div class="aviso-promo-gaming" style="background:' . $degradado_bg . '; color:#ffffff;">
            	' . $texto . '
        	</div>
    	</div>';
    }

    public function render_progress_notice_minicart() {
        if ( ! get_option( self::OPTION_ENABLE_MINICART, '1' ) ) {
            return;
        }
        echo $this->get_progress_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput
    }

    public function render_progress_notice_shortcode() {
        return $this->get_progress_notice_html();
    }

    public function refresh_progress_notice_fragment( $fragments ) {
        $fragments['.bloque-anuncio-ajax-render'] = $this->get_progress_notice_html();
        return $fragments;
    }

    /* =========================================================
     *  AVISO DE EXCLUSIÓN (productos con etiquetas excluidas)
     * ========================================================= */
    public function render_exclusion_notice() {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return '';
        }
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product_id = $cart_item['product_id'];
            if ( $this->product_is_excluded( $product_id ) ) {
                return '
                <div class="kaphi-aviso-activacion-plus">
                    ⚠️ Algunos de tus productos no están disponibles en las promociones configuradas.
                </div>';
            }
        }
        return '';
    }

    /* =========================================================
     *  PANEL DE ADMINISTRACIÓN
     * ========================================================= */
    public function add_settings_page() {
        add_menu_page(
            'CSO',
            'CSO',
            'manage_woocommerce',
            self::MENU_SLUG,
            array( $this, 'render_settings_page' ),
            'dashicons-tag',
            56
        );

        add_submenu_page(
            self::MENU_SLUG,
            'Descuentos por Lote',
            'Descuentos por Lote',
            'manage_woocommerce',
            self::SETTINGS_SLUG,
            array( $this, 'render_settings_page' )
        );

        // Evita el submenú duplicado que WordPress genera automáticamente
        // con el mismo slug que el menú principal.
        remove_submenu_page( self::MENU_SLUG, self::MENU_SLUG );
    }

    public function register_settings() {
        register_setting( self::SETTINGS_SLUG, self::OPTION_TIERS, array(
            'sanitize_callback' => array( $this, 'sanitize_tiers' ),
        ) );
        register_setting( self::SETTINGS_SLUG, self::OPTION_EXCLUDE_TAGS, array(
            'sanitize_callback' => array( $this, 'sanitize_exclude_tags' ),
        ) );
        register_setting( self::SETTINGS_SLUG, self::OPTION_ENABLE_MINICART, array(
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
        ) );
    }

    public function sanitize_checkbox( $input ) {
        return ( $input === '1' ) ? '1' : '';
    }

    public function sanitize_tiers( $input ) {
        $clean = array();

        if ( ! is_array( $input ) ) {
            return $this->get_default_tiers();
        }

        foreach ( $input as $row ) {
            $qty   = isset( $row['quantity'] ) ? absint( $row['quantity'] ) : 0;
            $price = isset( $row['price'] ) ? floatval( str_replace( ',', '.', $row['price'] ) ) : 0;

            if ( $qty > 0 && $price > 0 ) {
                $emoji       = isset( $row['emoji'] ) ? sanitize_text_field( $row['emoji'] ) : '🎮';
                $color_start = isset( $row['color_start'] ) ? sanitize_hex_color( $row['color_start'] ) : '#00c6ff';
                $color_end   = isset( $row['color_end'] ) ? sanitize_hex_color( $row['color_end'] ) : '#7928ca';

                $clean[] = array(
                    'quantity'    => $qty,
                    'price'       => $price,
                    'emoji'       => $emoji ? $emoji : '🎮',
                    'color_start' => $color_start ? $color_start : '#00c6ff',
                    'color_end'   => $color_end ? $color_end : '#7928ca',
                );
            }
        }

        return ! empty( $clean ) ? $clean : $this->get_default_tiers();
    }

    /**
     * Recibe un texto separado por comas (ej: "bundle, pack, preventa")
     * y lo convierte en un arreglo de slugs de etiquetas.
     */
    public function sanitize_exclude_tags( $input ) {
        if ( ! is_string( $input ) || trim( $input ) === '' ) {
            return array();
        }

        $raw_tags = explode( ',', $input );
        $clean    = array();

        foreach ( $raw_tags as $tag ) {
            $tag = sanitize_title( trim( $tag ) );
            if ( $tag !== '' ) {
                $clean[] = $tag;
            }
        }

        return array_unique( $clean );
    }

    public function render_settings_page() {
        $tiers        = $this->get_tiers();
        $exclude_tags = $this->get_exclude_tags();
        $exclude_tags_text = implode( ', ', $exclude_tags );
        ?>
        <div class="wrap">
            <h1>CSO - Descuentos por Lote</h1>
            <p>Configura aquí los niveles de descuento por volumen. Puedes agregar o quitar niveles, y personalizar el emoji y los colores del aviso de cada uno.</p>

            <form method="post" action="options.php">
                <?php settings_fields( self::SETTINGS_SLUG ); ?>

                <table class="wp-list-table widefat fixed striped" id="csodl-tiers-table">
                    <thead>
                        <tr>
                            <th style="width:18%">Cantidad de productos</th>
                            <th style="width:18%">Precio fijo del pack ($)</th>
                            <th style="width:12%">Emoji</th>
                            <th style="width:15%">Color inicio</th>
                            <th style="width:15%">Color fin</th>
                            <th style="width:12%">Quitar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $tiers as $i => $tier ) : ?>
                        <tr>
                            <td>
                                <input type="number" min="1" step="1"
                                    name="<?php echo esc_attr( self::OPTION_TIERS ); ?>[<?php echo $i; ?>][quantity]"
                                    value="<?php echo esc_attr( $tier['quantity'] ); ?>" required>
                            </td>
                            <td>
                                <input type="number" min="1" step="1"
                                    name="<?php echo esc_attr( self::OPTION_TIERS ); ?>[<?php echo $i; ?>][price]"
                                    value="<?php echo esc_attr( $tier['price'] ); ?>" required>
                            </td>
                            <td>
                                <input type="text" maxlength="4" style="width:60px; text-align:center;"
                                    name="<?php echo esc_attr( self::OPTION_TIERS ); ?>[<?php echo $i; ?>][emoji]"
                                    value="<?php echo esc_attr( $tier['emoji'] ); ?>">
                            </td>
                            <td>
                                <input type="color"
                                    name="<?php echo esc_attr( self::OPTION_TIERS ); ?>[<?php echo $i; ?>][color_start]"
                                    value="<?php echo esc_attr( $tier['color_start'] ); ?>">
                            </td>
                            <td>
                                <input type="color"
                                    name="<?php echo esc_attr( self::OPTION_TIERS ); ?>[<?php echo $i; ?>][color_end]"
                                    value="<?php echo esc_attr( $tier['color_end'] ); ?>">
                            </td>
                            <td>
                                <button type="button" class="button csodl-remove-row">Quitar</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p>
                    <button type="button" class="button button-secondary" id="csodl-add-row">+ Añadir nivel</button>
                </p>

                <h2>Productos excluidos por etiqueta</h2>
                <p>Los productos con las etiquetas indicadas a continuación no entran en el conteo de la promoción. Usa esta opción si quieres dejar fuera productos específicos de la promoción — por ejemplo: <code>bundle, pack, preventa</code>.</p>
                <input type="text" name="<?php echo esc_attr( self::OPTION_EXCLUDE_TAGS ); ?>"
                    value="<?php echo esc_attr( $exclude_tags_text ); ?>" class="regular-text"
                    placeholder="bundle, pack, preventa">

                <h2>Dónde mostrar el aviso de progreso</h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">Mini carrito (cabecera)</th>
                        <td>
                            <input type="hidden" name="<?php echo esc_attr( self::OPTION_ENABLE_MINICART ); ?>" value="0">
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( self::OPTION_ENABLE_MINICART ); ?>" value="1"
                                    <?php checked( get_option( self::OPTION_ENABLE_MINICART, '1' ), '1' ); ?>>
                                Mostrar en el desplegable del mini carrito
                            </label>
                        </td>
                    </tr>
                </table>

                <h2>Shortcodes disponibles</h2>
                <p>Independiente de la casilla de arriba, puedes insertar estos shortcodes manualmente en cualquier página o plantilla (por ejemplo, con el widget de "Shortcode" de Elementor):</p>
                <table class="form-table">
                    <tr>
                        <th scope="row">Aviso de progreso</th>
                        <td><code>[cso_anuncio_promocion_carrito]</code></td>
                    </tr>
                    <tr>
                        <th scope="row">Aviso de productos excluidos</th>
                        <td><code>[cso_aviso_productos_excluidos]</code></td>
                    </tr>
                </table>

                <?php submit_button( 'Guardar cambios' ); ?>
            </form>
        </div>

        <script>
        (function() {
            var table = document.getElementById('csodl-tiers-table').getElementsByTagName('tbody')[0];
            var addBtn = document.getElementById('csodl-add-row');

            function newIndex() {
                return table.getElementsByTagName('tr').length;
            }

            function bindRemove(row) {
                row.querySelector('.csodl-remove-row').addEventListener('click', function() {
                    row.remove();
                });
            }

            addBtn.addEventListener('click', function() {
                var idx = newIndex();
                var row = document.createElement('tr');
                row.innerHTML =
                    '<td><input type="number" min="1" step="1" name="<?php echo esc_attr( self::OPTION_TIERS ); ?>[' + idx + '][quantity]" required></td>' +
                    '<td><input type="number" min="1" step="1" name="<?php echo esc_attr( self::OPTION_TIERS ); ?>[' + idx + '][price]" required></td>' +
                    '<td><input type="text" maxlength="4" style="width:60px; text-align:center;" name="<?php echo esc_attr( self::OPTION_TIERS ); ?>[' + idx + '][emoji]" value="🎮"></td>' +
                    '<td><input type="color" name="<?php echo esc_attr( self::OPTION_TIERS ); ?>[' + idx + '][color_start]" value="#00c6ff"></td>' +
                    '<td><input type="color" name="<?php echo esc_attr( self::OPTION_TIERS ); ?>[' + idx + '][color_end]" value="#7928ca"></td>' +
                    '<td><button type="button" class="button csodl-remove-row">Quitar</button></td>';
                table.appendChild(row);
                bindRemove(row);
            });

            Array.prototype.forEach.call(table.getElementsByTagName('tr'), bindRemove);
        })();
        </script>
        <?php
    }
}

new CSO_Descuentos_Por_Lote();
