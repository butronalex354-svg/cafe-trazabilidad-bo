// Regla de contrasena IGUAL a la del backend (Password::min(8)->letters()->numbers()),
// para avisar mientras se escribe y no recien al guardar.
export const MENSAJE_PASSWORD = 'Mínimo 8 caracteres, con letras y números'

export function passwordValida (valor) {
  return !!valor && valor.length >= 8 && /\p{L}/u.test(valor) && /\p{N}/u.test(valor)
}

// Para :rules de q-input
export const reglaPassword = (valor) => passwordValida(valor) || MENSAJE_PASSWORD

// Mensaje claro cuando el backend rechaza los datos (422), en vez del generico "Datos invalidos".
export function mensajeDeError (err, porDefecto) {
  const errores = err.response?.data?.errors || {}
  if (errores.password || errores.password_nueva) return MENSAJE_PASSWORD + '.'
  if (errores.email) return 'El correo no es válido o ya está registrado.'
  return err.response?.data?.message || porDefecto
}
