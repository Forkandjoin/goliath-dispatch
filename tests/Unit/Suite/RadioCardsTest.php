<?php

declare(strict_types=1);

use Tests\Support\Source;

/**
 * Elegir entre USDOT y MC con las dos opciones a la vista.
 *
 * ## Lo que este lote vino a sujetar
 *
 * **Un control que lo parece y no lo es.** La forma fácil de pintar dos
 * tarjetas seleccionables son dos `div` con `onClick`. Se ve igual y deja
 * fuera a quien navega con teclado, a quien usa un lector de pantalla y a
 * quien pulsa en el borde de la tarjeta en vez de en el texto.
 *
 * **Una pregunta que solo existe si la ves.** Dos opciones sin `legend` se leen
 * como dos casillas sueltas que no contestan a nada.
 *
 * **Un resultado que se queda del número anterior.** Buscar por USDOT,
 * encontrar, y cambiar a MC dejaba la ficha encontrada debajo de la nueva
 * elección, leyéndose como su respuesta. Con el desplegable el defecto estaba
 * igual de puesto y se veía menos; con las dos opciones delante, cambiar de
 * tipo es un gesto normal.
 */
function fuenteTarjetas(string $ruta): string
{
    return Source::codigo(Source::root().'/'.$ruta);
}

/* ── Es un grupo de radios de verdad ────────────────────────────────────── */

it('cada tarjeta es un radio nativo', function (): void {
    /*
    | De ahí salen gratis las flechas del teclado, el anuncio «opción 1 de 2,
    | seleccionada», y que pulsar en cualquier parte de la tarjeta la elija.
    */
    $fuente = fuenteTarjetas('resources/js/components/Form/RadioCards.tsx');
    $cuerpo = Source::cuerpo($fuente, 'export function RadioCards<T extends string>(');

    test()->assertStringContainsString('type="radio"', $cuerpo);
    test()->assertStringContainsString('checked={elegida}', $cuerpo);

    // Y no un div que se pinta: no hay onClick en ningún sitio.
    test()->assertStringNotContainsString('onClick', $cuerpo);
});

it('el radio se esconde sin perder el foco', function (): void {
    /*
    | `sr-only` lo saca de la vista y lo deja en el recorrido del tabulador.
    | `hidden` o `display:none` lo sacarían de los dos, y el grupo volvería a
    | ser inalcanzable con el teclado — que es lo que se estaba evitando.
    */
    $fuente = fuenteTarjetas('resources/js/components/Form/RadioCards.tsx');

    test()->assertStringContainsString('className="sr-only"', $fuente);
    test()->assertStringNotContainsString('display: none', $fuente);
    test()->assertStringNotContainsString('hidden', $fuente);
});

it('el grupo dice a qué pregunta contesta', function (): void {
    // La pregunta se ve en la pantalla; para quien no la ve, el `legend` ES la
    // pregunta.
    $fuente = fuenteTarjetas('resources/js/components/Form/RadioCards.tsx');

    test()->assertStringContainsString('<fieldset', $fuente);
    test()->assertStringContainsString('<legend', $fuente);
    test()->assertStringContainsString('{legend}', $fuente);
});

it('dos grupos en la misma página no se pisan', function (): void {
    // Dos grupos de radios con el mismo `name` son UN grupo: elegir en uno
    // deselecciona el otro, y nadie entendería por qué.
    $fuente = fuenteTarjetas('resources/js/components/Form/RadioCards.tsx');

    test()->assertStringContainsString('const generado = useId()', $fuente);
    test()->assertStringContainsString('const grupo = name ?? generado', $fuente);
    test()->assertStringContainsString('name={grupo}', $fuente);
});

it('la tarjeta elegida se distingue, y con el color de la marca', function (): void {
    $fuente = fuenteTarjetas('resources/js/components/Form/RadioCards.tsx');
    $cuerpo = Source::cuerpo($fuente, 'export function RadioCards<T extends string>(');

    test()->assertStringContainsString('border-safety-500 bg-safety-50', $cuerpo);

    foreach (['indigo', 'violet', 'purple', 'amber-', 'slate-', 'gray-'] as $ajena) {
        test()->assertStringNotContainsString($ajena, $fuente, "«{$ajena}» no es de la marca.");
    }
});

it('el foco del teclado se ve en la tarjeta, no solo en el radio escondido', function (): void {
    // El radio está fuera de la vista: sin esto, tabular hasta el grupo no
    // enseñaría nada y quien navega con teclado se perdería.
    $fuente = fuenteTarjetas('resources/js/components/Form/RadioCards.tsx');

    test()->assertStringContainsString('focus-within:ring-2', $fuente);
});

/* ── El alta lo usa, y ya no hay desplegable ────────────────────────────── */

it('el alta elige con tarjetas y no con un desplegable', function (): void {
    $fuente = fuenteTarjetas('resources/js/components/Marketing/FmcsaLookup.tsx');

    test()->assertStringContainsString('<RadioCards', $fuente);
    test()->assertStringNotContainsString('<select', $fuente);
    test()->assertStringNotContainsString('<option', $fuente);
});

it('cada opción lleva su explicación', function (): void {
    // «USDOT» y «MC» son dos números distintos que mucha gente confunde, y la
    // diferencia cabe en una línea.
    $fuente = fuenteTarjetas('resources/js/components/Marketing/FmcsaLookup.tsx');

    test()->assertStringContainsString("hint: texto('dotHint')", $fuente);
    test()->assertStringContainsString("hint: texto('mcHint')", $fuente);
});

/* ── El resultado no sobrevive al cambio de tipo ────────────────────────── */

it('cambiar de tipo tira el resultado anterior', function (): void {
    $fuente = fuenteTarjetas('resources/js/components/Marketing/FmcsaLookup.tsx');
    $cuerpo = Source::cuerpo($fuente, "const cambiarTipo = (nuevo: 'dot' | 'mc')");

    test()->assertStringContainsString("setEstado('idle')", $cuerpo);
    test()->assertStringContainsString('setRespuesta(null)', $cuerpo);
});

it('cambiar de tipo NO borra el número escrito', function (): void {
    /*
    | Lo que estaba mal era la afirmación de la pantalla, no lo que escribió la
    | persona. Quien se equivocó de tipo y teclea el mismo número no tiene por
    | qué volver a teclearlo.
    */
    $fuente = fuenteTarjetas('resources/js/components/Marketing/FmcsaLookup.tsx');
    $cuerpo = Source::cuerpo($fuente, "const cambiarTipo = (nuevo: 'dot' | 'mc')");

    test()->assertStringNotContainsString('setNumero', $cuerpo);
});

it('elegir el tipo que ya estaba no tira nada', function (): void {
    // Volver a pulsar la tarjeta elegida no es cambiar de tipo, y tirar un
    // resultado bueno por un clic repetido sería un castigo sin motivo.
    $fuente = fuenteTarjetas('resources/js/components/Marketing/FmcsaLookup.tsx');
    $cuerpo = Source::cuerpo($fuente, "const cambiarTipo = (nuevo: 'dot' | 'mc')");

    test()->assertStringContainsString('if (nuevo === kind) {', $cuerpo);
});

/* ── Los rótulos ────────────────────────────────────────────────────────── */

it('cada rótulo nuevo está en los dos idiomas', function (): void {
    foreach (['en', 'es'] as $idioma) {
        /** @var array<string, mixed> $dic */
        $dic = json_decode(
            (string) file_get_contents(Source::root()."/lang/{$idioma}/marketing.json"),
            true,
        );

        foreach (['kind', 'dot', 'dotHint', 'mc', 'mcHint'] as $clave) {
            $valor = data_get($dic, "carrierSignup.lookup.{$clave}");

            test()->assertNotNull($valor, "Falta carrierSignup.lookup.{$clave} en {$idioma}.");
            test()->assertNotSame('', trim((string) $valor));
        }
    }
});

it('el rótulo del campo sigue siendo distinto del de la tarjeta', function (): void {
    /*
    | La tarjeta dice QUÉ número es («USDOT») y el campo dice qué escribir
    | («Número USDOT»). Si fueran el mismo rótulo, la pantalla tendría la misma
    | palabra dos veces seguidas y ninguna de las dos diría nada.
    */
    foreach (['en', 'es'] as $idioma) {
        /** @var array<string, mixed> $dic */
        $dic = json_decode(
            (string) file_get_contents(Source::root()."/lang/{$idioma}/marketing.json"),
            true,
        );

        test()->assertNotSame(
            data_get($dic, 'carrierSignup.lookup.dot'),
            data_get($dic, 'carrierSignup.lookup.dotNumber'),
            "En {$idioma}, la tarjeta y el campo dicen lo mismo.",
        );
    }
});
