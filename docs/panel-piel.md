# La piel del panel, y el menú de Ajustes

Lote 48. Primer paso de llevar el panel al estilo de Stripe: los primitivos
compartidos, y Ajustes como la pantalla piloto.

---

## Qué se copió de Stripe, y qué no

**La forma.** Superficie blanca sobre fondo claro, borde de un píxel en vez de
sombra marcada, radio pequeño, mucho aire dentro de la tarjeta y poco entre
secciones, rótulos pequeños y apagados, el peso tipográfico puesto en el dato y
no en la etiqueta, y una barra de acciones al pie que separa lo que se edita de
lo que se pulsa.

**El color NO.** La paleta sigue siendo la de Goliath —navy, naranja de
seguridad, acero— porque es la misma que llevan el sitio público, los correos al
cliente y la página de rastreo que ve alguien que no es usuario nuestro. Un
panel morado y un correo naranja son dos empresas distintas para quien los
recibe.

`BrandColorsTest` ya lo sujetaba para todo el proyecto —rechaza cualquier clase
de color fuera de las familias de la marca— y hay además una guarda en este lote
que lo dice en los cuatro ficheros de primitivos, donde se lee.

---

## Los primitivos

`resources/js/components/Ui/`

| Fichero | Qué trae |
|---|---|
| `Surface.tsx` | `Card`, `CardHeader`, `FieldGrid`, `Wide`, `Note` |
| `Field.tsx` | `Field`, `TextInput`, `SelectInput`, `Checkbox`, `ReadOnly` |
| `Button.tsx` | `Button` (cuatro pesos), `FormActions` |
| `Tabs.tsx` | `Tabs`, `TabPanel` |

Las mismas nueve clases de Tailwind repetidas en treinta pantallas se
desincronizan en cuanto alguien cambia una. Aquí hay un sitio donde cambiar el
borde de las tarjetas.

Tres decisiones que no son de estilo:

- **El error desplaza a la pista, no se suma.** Las dos juntas obligan a leer
  dos líneas pequeñas para averiguar cuál dice qué hacer ahora.
- **`aria-invalid` y `role="alert"` de verdad.** Un mensaje rojo debajo del
  campo es el error para quien lo ve; para quien usa un lector de pantalla no
  existe si el campo no lo apunta.
- **`ReadOnly` no es un campo desactivado.** Un campo gris invita a pulsarlo y a
  preguntarse por qué no responde. Un dato se lee como un dato.

---

## El menú horizontal de Ajustes

Seis pestañas: Dinero, Numeración, Operación, Contacto público, Marca, Webhook.

### La pestaña vive en la URL

Es la decisión que sostiene todo lo demás. Con la pestaña en `useState`:

- guardar devuelve `back()`, el componente se vuelve a montar y la pestaña salta
  a la primera: quien guarda «Contacto» aterriza en «Dinero» y lo que ve es una
  pantalla que no hizo nada;
- recargar pierde el sitio;
- no se puede mandar un enlace a una sección concreta.

Con `?tab=contact` las tres cosas se arreglan solas, y el botón de atrás del
navegador hace lo que la gente espera.

La pestaña pedida **se valida en el servidor**. Comprobarla en el cliente
dejaría que `?tab=loquesea` pintara una pantalla sin ninguna sección abierta: ni
la pedida, que no existe, ni la primera, que nadie eligió. Una pestaña que no se
reconoce cae a la primera.

### Un botón de guardar por pestaña

Es lo que hace que «lo que ve es lo que guarda» sea verdad.

**En el cliente**, un `useForm` por sección con SOLO los campos de esa sección.
Con un formulario único, pulsar «Guardar» en Dinero mandaría también lo que
alguien hubiera escrito sin guardar en Contacto, sin tenerlo delante.

**En el servidor**, `update()` exige un campo `section` y valida solo las reglas
de esa sección:

```php
$seccion = $request->validate(['section' => ['required', Rule::in(array_keys($secciones))]])['section'];
$data    = $request->validate($secciones[$seccion]);
```

Sin valor por omisión a propósito: una petición sin sección no dice qué columnas
quiere escribir, y adivinarlo convertiría un error del cliente en una escritura
silenciosa de campos que nadie tenía delante.

Y como `validate()` devuelve solo las claves que validó, **una sección no puede
escribir columnas de otra** aunque la petición las mande. Hay una prueba que lo
comprueba mandando campos de tres secciones ajenas y afirmando que ninguno se
guardó.

### Por qué las reglas tenían que partirse

Con un único bloque, guardar «Contacto» —donde todo es opcional— fallaría por
los `required` de Dinero: campos que quien guarda no tiene delante, ni forma de
ver.

Partirlas trajo además una comprobación que antes no existía: **ninguna columna
puede estar en dos secciones**. Si lo estuviera, el último que guardara ganaría
sin que ninguna de las dos pantallas lo dijera.

### Un error no se esconde en una pestaña cerrada

La pestaña con errores lleva un punto rojo. Sin eso, un error en un campo de
otra sección es un error invisible: la pantalla dice que algo falló y no dice
dónde.

Los errores se leen de la **página**, no del `useForm` de turno: la respuesta
con errores llega a la página, y para entonces el formulario que la provocó
puede no estar montado —quien guardó y cambió de pestaña mientras tanto—.

Hay una guarda que compara el mapa de errores de la pantalla contra las reglas
del servidor, campo por campo. Si un campo se cae del mapa, su error vuelve a
ser invisible.

### Las dos pestañas que no pasan por `update()`

Marca y Webhook tienen endpoint propio, y por buenas razones: la marca lleva un
fichero —mezclarla obligaría a mandar el formulario entero como multipart cada
vez que alguien cambia un plazo de pago— y el webhook lleva un secreto de
escritura que no viaja a la pantalla.

Las seis pestañas están declaradas en el servidor (`PESTANAS`) y hay una guarda
que exige que **cada una** tenga reglas o endpoint. Una pestaña con botón y sin
destino es la promesa más barata de hacer y la más cara de descubrir.

---

## Un arreglo que encontró el recorrido

Los mensajes de validación de Ajustes decían el nombre de la columna en inglés
dentro de una frase en español: «El campo load number prefix no tiene un formato
válido». Estaba así desde siempre; al pegar cada mensaje a su campo en una
pestaña, se leyó. Ya están los nombres en `lang/es/validation.php`.

---

## Lo que queda

Esto es el piloto. El resto del panel —cargas, transportistas, conductores,
finanzas— sigue con la piel anterior y se pasa por grupos en los lotes
siguientes, ahora que los primitivos existen y la forma está aprobada sobre una
pantalla de verdad.

---

## Elegir entre pocas opciones: tarjetas, no desplegable (lote 49)

`resources/js/components/Form/RadioCards.tsx`

Un desplegable esconde las opciones hasta que alguien lo abre, y con ello
esconde la diferencia entre ellas. Cuando son dos o tres y cada una necesita una
línea de explicación —USDOT y MC son dos números distintos que mucha gente
confunde— enseñarlas enteras es más corto de leer que abrir una lista, leerla y
cerrarla.

Primer sitio donde se usa: el paso 1 del alta pública de transportista.

### Radios de verdad, no `div`s que se pintan

Cada tarjeta ES un `<input type="radio">` dentro de su `<label>`. De ahí salen
gratis, y bien hechas, las cuatro cosas que un puñado de `div`s con `onClick`
obliga a reimplementar mal:

- las flechas del teclado recorren el grupo;
- el lector de pantalla anuncia «opción 1 de 2, seleccionada»;
- pulsar en cualquier sitio de la tarjeta la elige, porque el `label` entero es
  el objetivo;
- el formulario sabe qué hay elegido sin que nadie se lo cuente.

El radio se esconde con `sr-only` y **no** con `hidden`: escondido del todo
dejaría de recibir el foco y el grupo volvería a ser inalcanzable con el
teclado, que es justo lo que se estaba evitando. Y como el radio no se ve, el
anillo de foco lo pinta la tarjeta con `focus-within`.

El `fieldset`/`legend` no es decoración: sin él, un lector de pantalla lee dos
opciones sueltas y nunca dice a qué pregunta contestan.

El `name` del grupo se genera con `useId()` si no se pasa. Dos grupos de radios
con el mismo `name` son UN grupo: elegir en uno deselecciona el otro, y nadie
entendería por qué.

### Lo que encontró el cambio

Buscar por USDOT, encontrar, y cambiar a MC dejaba **la ficha encontrada en
pantalla debajo de la nueva elección**, leyéndose como su respuesta. El defecto
estaba igual de puesto con el desplegable y se veía menos; con las dos opciones
delante, cambiar de tipo es un gesto normal.

Ahora cambiar de tipo devuelve el panel a reposo. El número escrito **se
conserva**: lo que estaba mal era la afirmación de la pantalla, no lo que
escribió la persona. Y volver a pulsar la tarjeta que ya estaba elegida no tira
nada, que sería un castigo sin motivo por un clic repetido.
