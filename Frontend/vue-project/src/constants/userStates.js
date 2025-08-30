/**
 * Estados de usuario - Fuente de verdad
 * Centraliza todos los estados posibles de un usuario en la aplicación
 */

export const USER_STATES = {
  // Estado cuando el usuario está seleccionando su rol
  SELECTING: 'selecting',

  // Estado cuando el usuario está registrando su establecimiento
  REGISTERING: 'registering',

  // Estado cuando el usuario está esperando confirmación para ser seller
  WAITING_FOR_CONFIRMATION: 'waiting_for_confirmation',

  DENIED_CONFIRMATION: 'denied_confirmation',

  // Estado cuando el usuario está activo
  ACTIVE: 'active',

  // Estado cuando el usuario está inactivo/desactivado
  INACTIVE: 'inactive'
};

/**
 * Validadores de estado
 * Funciones helper para verificar el estado actual del usuario
 */
export const UserStateValidators = {
  isSelecting: (state) => state === USER_STATES.SELECTING,
  isRegistering: (state) => state === USER_STATES.REGISTERING,
  isWaitingConfirmation: (state) => state === USER_STATES.WAITING_FOR_CONFIRMATION,
  isDeniedConfirmation: (state) => state === USER_STATES.DENIED_CONFIRMATION,
  isActive: (state) => state === USER_STATES.ACTIVE,
  isInactive: (state) => state === USER_STATES.INACTIVE
};

/**
 * Transiciones válidas de estado
 * Define qué cambios de estado son permitidos
 */
export const STATE_TRANSITIONS = {
  [USER_STATES.SELECTING]: [USER_STATES.REGISTERING, USER_STATES.ACTIVE],
  [USER_STATES.REGISTERING]: [USER_STATES.WAITING_FOR_CONFIRMATION, USER_STATES.ACTIVE],
  [USER_STATES.WAITING_FOR_CONFIRMATION]: [USER_STATES.ACTIVE, USER_STATES.INACTIVE, USER_STATES.DENIED_CONFIRMATION],
  [USER_STATES.DENIED_CONFIRMATION]: [USER_STATES.REGISTERING, USER_STATES.WAITING_FOR_CONFIRMATION],
  [USER_STATES.ACTIVE]: [USER_STATES.INACTIVE],
  [USER_STATES.INACTIVE]: [USER_STATES.ACTIVE]
};

/**
 * Valida si una transición de estado es permitida
 * @param {string} currentState - Estado actual
 * @param {string} newState - Nuevo estado propuesto
 * @returns {boolean} - true si la transición es válida
 */
export function isValidStateTransition(currentState, newState) {
  const allowedTransitions = STATE_TRANSITIONS[currentState];
  return allowedTransitions && allowedTransitions.includes(newState);
}

/**
 * Obtiene todos los estados disponibles como array
 * @returns {string[]} - Array con todos los estados
 */
export function getAllStates() {
  return Object.values(USER_STATES);
}

/**
 * Verifica si un estado es válido
 * @param {string} state - Estado a verificar
 * @returns {boolean} - true si el estado es válido
 */
export function isValidState(state) {
  return getAllStates().includes(state);
}

export default USER_STATES;
