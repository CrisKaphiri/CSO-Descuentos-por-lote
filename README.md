# CSO - Descuentos por Lote

Plugin de WordPress/WooCommerce que aplica descuentos automáticos por volumen (packs) en el carrito, con niveles, colores y etiquetas excluidas totalmente configurables desde un panel de administración propio — sin tocar código.

## Descripción

Muchas tiendas de WooCommerce necesitan promociones del tipo "lleva 4 productos por un precio fijo", "lleva 10 por otro precio fijo", etc. Este plugin resuelve eso agregando un descuento automático (`fee` negativo) al carrito según la cantidad de productos elegibles, y muestra un aviso visual de progreso animando al cliente a alcanzar el siguiente nivel.

Todo es configurable desde el panel **CSO → Descuentos por Lote**: no es necesario editar código para agregar, quitar o modificar niveles de descuento.

## Características

- **Niveles de pack ilimitados**: cantidad de productos + precio fijo, agregables/quitables desde el admin.
- **Productos excluidos por etiqueta**: cualquier producto con alguna de las etiquetas configuradas queda fuera del conteo de la promoción (por ejemplo, para excluir bundles o preventas).
- **Aviso visual de progreso** ("Añade X más para conseguir la promoción"), con emoji y colores personalizables por nivel.
- **Activable/desactivable** en el mini carrito de la cabecera con un solo checkbox.
- **Shortcodes** disponibles para insertar el aviso manualmente en cualquier página o plantilla (ideal para maquetadores visuales como Elementor).
- Verificación de dependencia de WooCommerce, con aviso en el admin si no está activo.
- Limpieza automática de datos al desinstalar (`uninstall.php`).

## Shortcodes disponibles

| Shortcode | Descripción |
|---|---|
| `[cso_anuncio_promocion_carrito]` | Muestra el aviso de progreso de la promoción. |
| `[cso_aviso_productos_excluidos]` | Muestra un aviso cuando el carrito contiene productos con alguna etiqueta excluida. |

## Requisitos

- WordPress 6.0+
- WooCommerce activo (obligatorio)
- PHP 7.4+
- Elementor u otro constructor de páginas: **opcional**, solo necesario si quieres insertar los shortcodes manualmente con un widget visual.

## Instalación

1. Descarga el `.zip` desde este repositorio (o clónalo y comprime la carpeta `cso-descuentos-por-lote/`).
2. En tu WordPress, ve a **Plugins → Añadir nuevo → Subir plugin**.
3. Selecciona el `.zip` y haz clic en **Instalar ahora**.
4. Activa el plugin.
5. Ve a **CSO → Descuentos por Lote** para configurar tus niveles de descuento, etiquetas excluidas y dónde mostrar el aviso.

## Capturas de pantalla

> _Agrega aquí capturas del panel de administración (tabla de niveles, selector de colores, y el aviso renderizado en el mini carrito)._

## Estructura del repositorio

```
cso-descuentos-por-lote/
├── cso-descuentos-por-lote.php   # Lógica principal del plugin
├── uninstall.php                  # Limpieza de datos al desinstalar
└── readme.txt                     # Formato estándar de WordPress.org
```

## Changelog

Ver [`readme.txt`](./cso-descuentos-por-lote/readme.txt) para el historial completo de versiones.

## Licencia

GPLv2 or later — ver [LICENSE](./LICENSE).

## Autor

Cristóbal Sánchez Orellana
