/**
 * Calcula la distancia en kilómetros entre dos puntos geográficos
 * usando la fórmula de Haversine.
 *
 * @param {number} lat1 - Latitud del primer punto (grados)
 * @param {number} lng1 - Longitud del primer punto (grados)
 * @param {number} lat2 - Latitud del segundo punto (grados)
 * @param {number} lng2 - Longitud del segundo punto (grados)
 * @returns {number} Distancia en kilómetros
 */
export function calculateDistance(lat1, lng1, lat2, lng2) {
  const R = 6371 // Radio de la Tierra en km
  const toRad = (deg) => (deg * Math.PI) / 180

  const dLat = toRad(lat2 - lat1)
  const dLng = toRad(lng2 - lng1)

  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
    Math.sin(dLng / 2) * Math.sin(dLng / 2)

  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))

  return R * c
}

/**
 * Formatea una distancia en kilómetros a un string legible en español.
 *
 * @param {number} km - Distancia en kilómetros
 * @returns {string} Distancia formateada (ej: 'a 350 m', 'a 2.3 km', 'a 15 km')
 */
export function formatDistance(km) {
  if (km < 1) {
    return `a ${Math.round(km * 1000)} m`
  }
  if (km < 10) {
    return `a ${km.toFixed(1)} km`
  }
  return `a ${Math.round(km)} km`
}
