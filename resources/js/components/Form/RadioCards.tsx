import { useId, type ReactNode } from 'react'

/**
 * Elegir UNA cosa entre pocas, con las opciones a la vista.
 *
 * ## Por qué no un desplegable
 *
 * Un desplegable esconde las opciones hasta que alguien lo abre, y con ello
 * esconde la diferencia entre ellas. Cuando las opciones son dos o tres y cada
 * una necesita una línea de explicación —«USDOT» y «MC» son dos números
 * distintos que mucha gente confunde— enseñarlas enteras es más corto de leer
 * que abrir una lista, leerla y cerrarla.
 *
 * ## Radios de verdad, no `div`s que se pintan
 *
 * Cada tarjeta ES un `<input type="radio">` dentro de su `<label>`. Eso trae
 * gratis, y bien hecho, todo lo que un puñado de `div`s con `onClick` obliga a
 * reimplementar mal:
 *
 *  • las flechas del teclado recorren el grupo;
 *  • el lector de pantalla anuncia «opción 1 de 2, seleccionada»;
 *  • pulsar en cualquier sitio de la tarjeta la elige, porque el `label` entero
 *    es el objetivo;
 *  • el formulario sabe qué hay elegido sin que nadie se lo cuente.
 *
 * El radio se esconde con `sr-only` y NO con `display:none`: escondido del todo
 * dejaría de recibir el foco, y el grupo volvería a ser inalcanzable con el
 * teclado — que es justo lo que se estaba evitando.
 *
 * ## El `fieldset` no es decoración
 *
 * Sin `legend`, un lector de pantalla lee dos opciones sueltas y nunca dice a
 * qué pregunta contestan. La pregunta se ve en la pantalla; para quien no la ve,
 * el `legend` ES la pregunta.
 */
export function RadioCards<T extends string>({
  legend,
  value,
  options,
  onChange,
  name,
  disabled = false,
}: {
  /** La pregunta. Se pinta y además la lee un lector de pantalla. */
  legend: string
  value: T
  options: { value: T; label: string; hint?: string; icon?: ReactNode }[]
  onChange: (valor: T) => void
  /** Si se omite, se genera: dos grupos en la misma página no pueden compartirlo. */
  name?: string
  disabled?: boolean
}) {
  const generado = useId()
  const grupo = name ?? generado

  return (
    <fieldset disabled={disabled} className="min-w-0">
      <legend className="uppercase-heading mb-2 text-xs text-steel-700">{legend}</legend>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        {options.map((o) => {
          const elegida = o.value === value

          return (
            <label
              key={o.value}
              className={`flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition ${
                elegida
                  ? 'border-safety-500 bg-safety-50'
                  : 'border-steel-200 bg-white hover:border-steel-300'
              } ${disabled ? 'cursor-not-allowed opacity-60' : ''} focus-within:ring-2 focus-within:ring-navy-200`}
            >
              <input
                type="radio"
                name={grupo}
                value={o.value}
                checked={elegida}
                onChange={() => { onChange(o.value) }}
                className="sr-only"
              />

              {o.icon ?? null}

              <span className="min-w-0">
                <span className="block text-sm font-semibold text-carbon">{o.label}</span>
                {o.hint ? (
                  <span className="mt-0.5 block text-xs leading-snug text-steel-600">{o.hint}</span>
                ) : null}
              </span>
            </label>
          )
        })}
      </div>
    </fieldset>
  )
}
