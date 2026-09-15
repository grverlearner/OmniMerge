# Entrada y registro — la puerta, en el mismo idioma que la casa

## 1. Qué había

Seis pantallas en el flujo de cuenta, y no casaban entre sí:

| Pantalla | Estado |
|---|---|
| Entrar | Personalizada, pero **formulario blanco** sobre una página oscura |
| Crear cuenta | Igual: blanca, con «Ver»/«Ocultar» en texto y un `✓` suelto |
| Olvidé la contraseña | **Breeze sin tocar, en inglés** |
| Elegir contraseña nueva | **Breeze sin tocar, en inglés** |
| Confirmar el correo | **Breeze sin tocar, en inglés** |
| Confirmar la contraseña | **Breeze sin tocar, en inglés** |

El layout de invitado tenía el panel izquierdo oscuro con el discurso antiguo
—«crea tus tipos, atributos y colecciones»— y los glifos `✦ ☷ ▤ ◎`, mientras el
inicio público ya cuenta el producto entero.

Y los **mensajes** también llegaban en inglés, aunque las pantallas se
tradujeran: no hay carpeta `lang`, la app está en `en`, y varios textos salían de
los valores de fábrica de Laravel.

## 2. Lo que es ahora

**Un layout oscuro de principio a fin**, continuación directa del inicio
público: misma marca, mismas luces, mismo arco —*Crea lo que quieras. Dale un
mundo. Hazlo competir.*— y los cinco pasos del recorrido con sus iconos. El
formulario va en una tarjeta oscura. En móvil el panel se oculta y la marca sube
arriba, sin desbordamiento horizontal.

**Dos campos compartidos** —`auth/partials/campo` y `auth/partials/clave`— en vez
de seis versiones del mismo input. La contraseña tiene un único botón de ver y
ocultar con iconos del juego de la aplicación; antes entrar usaba dos SVG pegados
y registro usaba las palabras «Ver» y «Ocultar».

**Cuatro iconos nuevos** en `<x-omni-icon>`: `correo`, `candado`, `ojo` y
`ojo-tachado`. Se añadieron al componente, que es donde se añaden los iconos, en
lugar de pegar SVG en las vistas.

### Crear cuenta

- El **nombre de usuario** muestra la dirección que va a generar
  —`/perfil/tu_usuario`—, se comprueba mientras se escribe con la misma regla que
  valida el servidor y avisa de que se guarda en minúsculas, porque el
  controlador lo hace.
- La **contraseña** dice su único requisito real y cuántos caracteres faltan; la
  repetición dice si coincide.
- **La nota de privacidad era verdad a medias.** Decía «tu biblioteca comenzará
  siendo privada». En la base de datos, `entities`, `collections` y `attributes`
  nacen `PRIVATE`, pero `users.profile_visibility` nace **`PUBLIC`**. Ahora dice
  las dos cosas: todo lo que crees empieza privado, y tu página de perfil es
  visible desde el principio, aunque vacía.

### Olvidé la contraseña, sin prometer lo que no pasa

La instalación tiene `MAIL_MAILER=log`: el enlace de restablecimiento se escribe
en el registro del servidor y **no llega a ningún buzón**. La pantalla lo decía
todo lo contrario. Ahora, cuando el correo está configurado como `log` o
`array`, lo avisa y dice dónde encontrar el enlace. En una instalación con correo
real el aviso no aparece.

La pantalla de confirmar el correo lleva el mismo aviso. Hoy `User` no implementa
`MustVerifyEmail`, así que nadie está obligado a pasar por ella; se rehizo porque
la ruta existe.

## 3. Los mensajes, en español

| Dónde | Qué salía en inglés | Arreglo |
|---|---|---|
| Olvidé / restablecer | Estados del broker (`__($status)`) | `mensaje()` en cada controlador |
| Entrar | Bloqueo por intentos (`auth.throttle`) | Texto directo en `LoginRequest` |
| Registro | «The password field must be at least 8 characters.» | `password.min` |
| Restablecer | Todos los de validación | Mensajes propios |
| Mi perfil › Contraseña | Contraseña actual incorrecta, longitud, coincidencia | Mensajes propios |

Se escriben en español directamente en el código porque es como el proyecto ya lo
hacía (`RegisteredUserController`, `LoginRequest`), en vez de crear una carpeta
`lang` solo para esto o cambiar el idioma global, que afectaría a otras pantallas
—por ejemplo, a los nombres de los meses—.

El de la longitud mínima **salió al probar los envíos**. Ninguna comprobación de
pantallas lo habría encontrado: solo aparece al mandar una contraseña corta.

## 4. Lo que se comprobó

**Por HTTP, las seis pantallas**: entrar, crear cuenta, olvidé, restablecer (con
un token de prueba), confirmar el correo y confirmar la contraseña devuelven 200,
sin un solo glifo suelto ni texto en inglés.

**Los envíos reales, contra MySQL y dentro de una transacción que se deshizo**,
con sesión y caché en memoria y notificaciones falsas para no escribir nada fuera:

| Envío | Resultado |
|---|---|
| Registro con todo mal | Los cuatro errores en español |
| Registro bien | Entra, va al Centro, se guarda `@sonda_revierte` en minúsculas y con perfil `PUBLIC` |
| Entrar con contraseña mala | «El correo o la contraseña no son correctos.» |
| Seis intentos seguidos | «Demasiados intentos seguidos. Vuelve a probar en 59 segundos.» |
| Olvidé, con cuenta | «Te hemos mandado un enlace…», y 1 notificación preparada |
| Olvidé, sin cuenta | «No hay ninguna cuenta con ese correo.» |
| Restablecer con enlace falso | «El enlace ya no sirve: caducó o ya se usó. Pide otro.» |
| Restablecer con clave corta | Longitud y coincidencia, en español |
| Mi perfil › contraseña mal | Los tres errores en español |

Al revertir, la tabla de usuarios volvió a sus 6 filas.

**En el navegador**: las seis pantallas a 1440 px y entrar y registro a 375 px;
los avisos en vivo del registro (usuario corto, con espacio y válido; clave corta
y válida; repetición distinta e igual), y los estados con error, con el campo
marcado en rojo y el mensaje debajo.

## 5. Una trampa de la sonda, para la próxima vez

La primera sonda dio 302 **sin ningún mensaje** para entrar, olvidé y
restablecer, y 0 notificaciones. Parecía un fallo grave y no lo era: esos flujos
se probaban justo después de un registro que deja la sesión iniciada, y como son
pantallas solo para invitados, redirigían sin validar nada. Al probarlos antes
del registro, todos salieron correctos.

## 6. Las pruebas automáticas no cubren esto

Las 21 pruebas de `tests/Feature/Auth` **fallan todas antes de llegar a las
pantallas**, con el error de SQLite `near "INNER"` que ya existía en las
migraciones. El total sigue en 88 pasadas y 99 fallos, pero ninguna de esas 21
ejercita lo que se ha cambiado aquí. Por eso la verificación se hizo por HTTP
contra MySQL: es la única que de verdad toca estas pantallas hasta que se arregle
esa migración.
