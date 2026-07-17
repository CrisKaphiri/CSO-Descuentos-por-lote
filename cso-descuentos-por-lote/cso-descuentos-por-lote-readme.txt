=== CSO - Descuentos por Lote ===
Contributors: cristobalsanchezorellana
Tags: woocommerce, descuentos, promociones, carrito, packs
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 2.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Descuentos automáticos por volumen (packs) en el carrito de WooCommerce, con niveles, colores y etiquetas excluidas totalmente configurables desde el panel de administración.

== Description ==

CSO - Descuentos por Lote agrega packs de descuento configurables al carrito de WooCommerce. Por ejemplo: "lleva 4 productos por un precio fijo", "lleva 10 por otro precio fijo", etc. Todo se configura desde un panel propio, sin tocar código.

**Características principales**

* Niveles de pack ilimitados: cantidad de productos + precio fijo, agregables/quitables desde el admin.
* Etiquetas de producto excluidas del conteo de la promoción (por ejemplo, para dejar fuera bundles o preventas), configurables como lista separada por comas.
* Aviso visual de progreso ("Añade X más para conseguir la promoción") con emoji y colores personalizables por nivel.
* El aviso se puede mostrar automáticamente en el mini carrito, en la parte superior del carrito, y/o en el checkout (cada uno con su propio interruptor), o insertarse manualmente en cualquier página con un shortcode — útil para maquetadores como Elementor.
* Aviso adicional configurable para productos con etiquetas excluidas.

**Shortcodes disponibles**

* `[cso_anuncio_promocion_carrito]` — muestra el aviso de progreso de la promoción.
* `[cso_aviso_productos_excluidos]` — muestra un aviso cuando el carrito contiene productos con alguna etiqueta excluida.

**Requisitos**

* WooCommerce activo (obligatorio).
* Elementor u otro constructor de páginas es opcional — solo se necesita si quieres insertar los shortcodes manualmente con un widget visual. El plugin funciona igual sin él.

**Nota sobre el menú de administración**

Este plugin registra su panel bajo un menú "CSO" en el admin de WordPress. Si también tienes instalado el plugin "CSO - Botón WhatsApp Pedido", ambos comparten el mismo menú padre — no es necesario tener uno instalado para que el otro funcione.

== Installation ==

1. Descarga el `.zip` del plugin.
2. En tu WordPress, ve a Plugins → Añadir nuevo → Subir plugin.
3. Selecciona el `.zip` y haz clic en "Instalar ahora".
4. Activa el plugin.
5. Ve a **CSO → Descuentos por Lote** en el menú de administración para configurar tus niveles de descuento, etiquetas excluidas y dónde mostrar el aviso.

== Frequently Asked Questions ==

= ¿Necesito Elementor para que funcione? =

No. WooCommerce es obligatorio, Elementor no. Los shortcodes funcionan en cualquier editor de WordPress.

= ¿Qué pasa si desinstalo el plugin? =

Al desinstalarlo (no solo desactivarlo), se eliminan automáticamente las opciones guardadas en la base de datos.

= ¿Puedo tener más de un nivel de descuento? =

Sí, puedes agregar tantos niveles como quieras desde el panel de administración.

== Changelog ==

= 2.4.1 =
* Corrección: el submenú "fantasma" que WordPress agrega automáticamente (duplicando el nombre del menú padre "CSO") ahora se elimina en el momento correcto, evitando que apareciera un ítem "CSO" repetido dentro del propio menú CSO.

= 2.4.0 =
* El menú de administración ahora es compartido: si tienes instalado también el plugin "CSO - Botón WhatsApp Pedido", ambos aparecen bajo un mismo menú padre "CSO" en vez de tener cada uno su propio menú de nivel superior.

= 2.3.1 =
* Se eliminó el texto explicativo bajo "Dónde mostrar el aviso de progreso".
* "Productos excluidos por etiqueta" ahora viene vacío por defecto (antes traía "activacion-2" precargado).

= 2.3.0 =
* Se quitaron las opciones de "parte superior del carrito" y "checkout"; el aviso de progreso ahora solo se activa/desactiva para el mini carrito (el shortcode sigue disponible para insertarlo manualmente donde quieras).
* Se corrigieron textos del panel de administración (etiqueta de sección "Productos excluidos por etiqueta" y descripción de shortcodes).

= 2.2.1 =
* Corrección: el chequeo de dependencia de WooCommerce se movió al hook `plugins_loaded`, para evitar que el plugin se autodesactivara por orden alfabético de carga de plugins (podía cargar antes que WooCommerce y no detectarlo).

= 2.2.0 =
* Descripción del plugin más breve, sin nombrar shortcodes en el encabezado.
* Se renombró el shortcode de exclusión a `[cso_aviso_productos_excluidos]`.

= 2.1.0 =
* Se agregó verificación de dependencia de WooCommerce con aviso en el admin.
* Se agregaron interruptores para mostrar el aviso en mini carrito, carrito y checkout de forma independiente.
* Los shortcodes ahora se muestran directamente en el panel de administración.
* Se renombró el shortcode de aviso de progreso a `[cso_anuncio_promocion_carrito]`.
* Mejor descripción y ejemplos genéricos para el campo de etiquetas excluidas.

= 2.0.0 =
* Renombrado a "CSO - Descuentos por Lote", con menú propio en el admin.
* Soporte para múltiples etiquetas excluidas.
* Colores y emoji configurables por nivel de promoción.

= 1.1.0 =
* Se integraron los avisos visuales (mini carrito y exclusión) al plugin.

= 1.0.0 =
* Versión inicial: packs de descuento configurables por cantidad y precio fijo.
