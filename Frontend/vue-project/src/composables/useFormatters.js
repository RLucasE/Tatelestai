/**
 * Composable para formateo consistente de moneda, horas y fechas en Tatelestai.
 */
export function useFormatters() {
  const formatCurrency = (val) => {
    if (val == null || isNaN(val)) return '0';
    return Number(val).toLocaleString('es-AR', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    });
  };

  const formatTime = (isoString) => {
    if (!isoString) return '--:--';
    try {
      const d = new Date(isoString);
      return d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    } catch {
      return '--:--';
    }
  };

  const formatDate = (isoString) => {
    if (!isoString) return '';
    try {
      const d = new Date(isoString);
      return d.toLocaleDateString('es-AR', {
        day: 'numeric',
        month: 'short',
      });
    } catch {
      return '';
    }
  };

  const getInitials = (name) => {
    if (!name || typeof name !== 'string') return 'CL';
    const clean = name.trim();
    const parts = clean.split(/\s+/);
    if (parts.length >= 2) {
      return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return clean.slice(0, 2).toUpperCase();
  };

  return {
    formatCurrency,
    formatTime,
    formatDate,
    getInitials,
  };
}
