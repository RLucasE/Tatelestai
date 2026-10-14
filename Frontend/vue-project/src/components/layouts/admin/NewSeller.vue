<script setup>
import { onMounted, ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axiosInstance from '@/lib/axios.js';

const route = useRoute();
const router = useRouter();
const establishmentId = route.params.id;

// URL del backend para recursos estáticos
const BACKEND_URL = axiosInstance.defaults.baseURL.replace('/api', '');

const establishment = ref(null);
const loading = ref(true);
const error = ref(null);
const verifying = ref(false);
const rejecting = ref(false);
const rejectReason = ref('');
const showRejectModal = ref(false);

// Estado para revisión de archivos individuales
const reviewingFileId = ref(null);
const showFileRejectModal = ref(false);
const selectedFileForReject = ref(null);
const fileRejectReason = ref('');

// Estado para validación de Google Places y llamada
const googleVerifying = ref(false);
const phoneVerifying = ref(false);
const showPhoneModal = ref(false);
const phoneNotes = ref('');

const isImageFile = (fileType) => ['jpg', 'jpeg', 'png'].includes(fileType);
const isPdfFile = (fileType) => fileType === 'pdf';

const getFileTypeLabel = (fileType) => {
  if (isImageFile(fileType)) return 'Imagen';
  if (isPdfFile(fileType)) return 'PDF';
  return 'Documento';
};

const getDocumentCategoryLabel = (cat) => {
  const map = {
    tax_certificate: 'Constancia CUIT (ARCA / AFIP)',
    municipal_permit: 'Habilitación Municipal u Oblea',
    municipal_tax_receipt: 'Boleta de Tasa Municipal (ARMSa)',
    pest_control_certificate: 'Certificado de Fumigación / Desinfección',
    google_places_profile: 'Ficha en Google Maps',
    phone_call_verification: 'Llamada telefónica confirmada',
    store_counter_photo: 'Foto de Mostrador / Cocina',
    facade_photo: 'Foto de Fachada Exterior',
    other: 'Otro documento adicional',
  };
  return map[cat] || 'Documento adjunto';
};

const isGooglePlaceVerified = computed(() => {
  return establishment.value?.trust_logs?.some(
    log => log.source_type === 'google_places_profile' && log.is_active
  );
});

const isPhoneCallVerified = computed(() => {
  return establishment.value?.trust_logs?.some(
    log => log.source_type === 'phone_call_verification' && log.is_active
  );
});

// Opciones de categorías de documentos para reclasificación
const documentCategoryOptions = [
  { value: 'tax_certificate', label: 'Constancia CUIT (ARCA / AFIP)' },
  { value: 'municipal_permit', label: 'Habilitación Municipal u Oblea (ARMSa)' },
  { value: 'municipal_tax_receipt', label: 'Boleta de Tasa Municipal (TISSH)' },
  { value: 'pest_control_certificate', label: 'Certificado de Fumigación y Desinfección' },
  { value: 'facade_photo', label: 'Foto de Fachada Exterior' },
  { value: 'store_counter_photo', label: 'Foto de Mostrador / Cocina' },
  { value: 'other', label: 'Otro documento adicional' },
];

const savingReviews = ref(false);
const saveSuccessMessage = ref('');

// Inicializar propiedades editables locales en cada archivo
const initializeFilesState = () => {
  if (!establishment.value || !establishment.value.verification_files) return;
  establishment.value.verification_files.forEach((file) => {
    const rawCategory = file.document_category?.value || file.document_category || 'other';
    file.edit_status = file.status || 'pending';
    file.edit_category = rawCategory;
    file.edit_rejection_reason = file.rejection_reason || '';
    file.edit_manual_points = typeof file.assigned_points === 'number' ? file.assigned_points : 10;

    file.original_status = file.status || 'pending';
    file.original_category = rawCategory;
    file.original_rejection_reason = file.rejection_reason || '';
    file.original_manual_points = file.edit_manual_points;
  });
};

// Detección de cambios sin guardar
const hasUnsavedChanges = computed(() => {
  const files = establishment.value?.verification_files || [];
  return files.some((file) =>
    file.edit_status !== file.original_status ||
    file.edit_category !== file.original_category ||
    file.edit_rejection_reason !== file.original_rejection_reason ||
    (file.edit_category === 'other' && file.edit_status === 'accepted' && file.edit_manual_points !== file.original_manual_points)
  );
});

// Resumen de verificación de documentos basado en el estado local de edición
const verifiedFilesCount = computed(() => {
  return (establishment.value?.verification_files || []).filter(f => f.edit_status === 'accepted').length;
});

const totalFilesCount = computed(() => {
  return establishment.value?.verification_files?.length || 0;
});

const hasGooglePlace = computed(() => {
  return Boolean(
    establishment.value?.google_place_id ||
    (establishment.value?.google_place_data && Object.keys(establishment.value.google_place_data).length > 0)
  );
});

const availablePhoneNumber = computed(() => {
  return (
    establishment.value?.google_place_data?.formatted_phone_number ||
    establishment.value?.google_place_data?.international_phone_number ||
    establishment.value?.phone ||
    null
  );
});

// Desglose reactivo de puntuación de confianza por concepto
const trustScoreBreakdown = computed(() => {
  const files = establishment.value?.verification_files || [];

  const isDocAccepted = (cat) => {
    return files.some(f => f.edit_category === cat && f.edit_status === 'accepted');
  };

  const isDocRejected = (cat) => {
    return files.some(f => f.edit_category === cat && f.edit_status === 'rejected');
  };

  const hasDocUploaded = (cat) => {
    return files.some(f => f.edit_category === cat);
  };

  const standardList = [
    {
      id: 'tax_certificate',
      name: 'Constancia CUIT (ARCA / AFIP)',
      type: 'Documento',
      maxPoints: 30,
      isVerified: isDocAccepted('tax_certificate'),
      isRejected: isDocRejected('tax_certificate'),
      isUploaded: hasDocUploaded('tax_certificate'),
      earnedPoints: isDocAccepted('tax_certificate') ? 30 : 0,
    },
    {
      id: 'municipal_permit',
      name: 'Habilitación Municipal u Oblea (ARMSa)',
      type: 'Documento',
      maxPoints: 30,
      isVerified: isDocAccepted('municipal_permit'),
      isRejected: isDocRejected('municipal_permit'),
      isUploaded: hasDocUploaded('municipal_permit'),
      earnedPoints: isDocAccepted('municipal_permit') ? 30 : 0,
    },
    {
      id: 'municipal_tax_receipt',
      name: 'Boleta de Tasa Municipal (TISSH)',
      type: 'Documento',
      maxPoints: 30,
      isVerified: isDocAccepted('municipal_tax_receipt'),
      isRejected: isDocRejected('municipal_tax_receipt'),
      isUploaded: hasDocUploaded('municipal_tax_receipt'),
      earnedPoints: isDocAccepted('municipal_tax_receipt') ? 30 : 0,
    },
    {
      id: 'pest_control_certificate',
      name: 'Certificado de Fumigación y Desinfección',
      type: 'Documento',
      maxPoints: 20,
      isVerified: isDocAccepted('pest_control_certificate'),
      isRejected: isDocRejected('pest_control_certificate'),
      isUploaded: hasDocUploaded('pest_control_certificate'),
      earnedPoints: isDocAccepted('pest_control_certificate') ? 20 : 0,
    },
    {
      id: 'facade_photo',
      name: 'Foto de Fachada Exterior',
      type: 'Documento',
      maxPoints: 10,
      isVerified: isDocAccepted('facade_photo'),
      isRejected: isDocRejected('facade_photo'),
      isUploaded: hasDocUploaded('facade_photo'),
      earnedPoints: isDocAccepted('facade_photo') ? 10 : 0,
    },
    {
      id: 'store_counter_photo',
      name: 'Foto de Mostrador / Cocina',
      type: 'Documento',
      maxPoints: 10,
      isVerified: isDocAccepted('store_counter_photo'),
      isRejected: isDocRejected('store_counter_photo'),
      isUploaded: hasDocUploaded('store_counter_photo'),
      earnedPoints: isDocAccepted('store_counter_photo') ? 10 : 0,
    },
    {
      id: 'google_places_profile',
      name: 'Ficha comercial en Google Maps',
      type: 'Verificación',
      maxPoints: 20,
      isVerified: isGooglePlaceVerified.value,
      isRejected: false,
      isUploaded: hasGooglePlace.value,
      notApplicable: !hasGooglePlace.value,
      earnedPoints: isGooglePlaceVerified.value ? 20 : 0,
    },
    {
      id: 'phone_call_verification',
      name: 'Llamada telefónica confirmada',
      type: 'Verificación',
      maxPoints: 30,
      isVerified: isPhoneCallVerified.value,
      isRejected: false,
      isUploaded: hasGooglePlace.value && Boolean(availablePhoneNumber.value),
      notApplicable: !hasGooglePlace.value,
      earnedPoints: isPhoneCallVerified.value ? 30 : 0,
    },
  ];

  // Documentos adicionales con puntaje manual asignado por el admin
  const otherList = files
    .filter(f => f.edit_category === 'other')
    .map((f, idx) => {
      const isAccepted = f.edit_status === 'accepted';
      const isRejected = f.edit_status === 'rejected';
      const customPts = isAccepted ? (Number(f.edit_manual_points) || 0) : 0;
      return {
        id: `other_${f.id}`,
        name: `Otro documento adicional #${idx + 1} (${getFileTypeLabel(f.file_type)})`,
        type: 'Doc. Manual',
        maxPoints: Number(f.edit_manual_points) || 10,
        isVerified: isAccepted,
        isRejected: isRejected,
        isUploaded: true,
        earnedPoints: customPts,
      };
    });

  return [...standardList, ...otherList];
});

const totalCalculatedPoints = computed(() => {
  return trustScoreBreakdown.value.reduce((acc, item) => acc + item.earnedPoints, 0);
});

const totalPossiblePoints = computed(() => {
  return trustScoreBreakdown.value.reduce((acc, item) => acc + (item.notApplicable ? 0 : item.maxPoints), 0);
});

const calculatedTrustLevel = computed(() => {
  const pts = totalCalculatedPoints.value;
  if (pts >= 70) return 'green';
  if (pts >= 40) return 'yellow';
  return 'red';
});

const trustProgressPercentage = computed(() => {
  if (!totalPossiblePoints.value) return 0;
  return Math.min(100, Math.round((totalCalculatedPoints.value / totalPossiblePoints.value) * 100));
});

const revertLocalChanges = () => {
  if (!establishment.value?.verification_files) return;
  establishment.value.verification_files.forEach((file) => {
    file.edit_status = file.original_status;
    file.edit_category = file.original_category;
    file.edit_rejection_reason = file.original_rejection_reason;
    file.edit_manual_points = file.original_manual_points;
  });
};

const fetchEstablishment = async (isInitial = true) => {
  try {
    if (isInitial) {
      loading.value = true;
    }
    const response = await axiosInstance.get(`/adm/pending-establishments/${establishmentId}`);
    establishment.value = response.data.data || null;
    initializeFilesState();
    error.value = null;
  } catch (err) {
    console.error('Error fetching establishment detail:', err);
    if (isInitial) {
      error.value = 'No se pudo cargar la información del establecimiento';
      establishment.value = null;
    }
  } finally {
    if (isInitial) {
      loading.value = false;
    }
  }
};

// Acciones de edición puramente locales (Cero llamadas HTTP al hacer clic)
const toggleVerifyLocal = (file) => {
  if (file.edit_status === 'accepted') {
    file.edit_status = 'pending';
  } else {
    file.edit_status = 'accepted';
    file.edit_rejection_reason = '';
  }
};

const cancelRejectLocal = (file) => {
  file.edit_status = 'pending';
  file.edit_rejection_reason = '';
};

const openFileRejectModal = (file) => {
  selectedFileForReject.value = file;
  fileRejectReason.value = file.edit_rejection_reason || '';
  showFileRejectModal.value = true;
};

const closeFileRejectModal = () => {
  selectedFileForReject.value = null;
  fileRejectReason.value = '';
  showFileRejectModal.value = false;
};

const confirmRejectFile = () => {
  if (!selectedFileForReject.value) return;
  selectedFileForReject.value.edit_status = 'rejected';
  selectedFileForReject.value.edit_rejection_reason = fileRejectReason.value.trim() || 'Documento no válido o ilegible';
  closeFileRejectModal();
};

const changeManualPoints = (file, delta) => {
  const current = Number(file.edit_manual_points || 0);
  const next = Math.min(50, Math.max(0, current + delta));
  file.edit_manual_points = next;
};

const sanitizeManualPoints = (file) => {
  let val = Number(file.edit_manual_points);
  if (isNaN(val) || val < 0) val = 0;
  if (val > 50) val = 50;
  file.edit_manual_points = val;
};

// Guardado en bloque al presionar "Guardar Cambios"
const saveDocumentReviews = async () => {
  if (!establishment.value || savingReviews.value) return;

  savingReviews.value = true;
  saveSuccessMessage.value = '';

  try {
    const payload = {
      files: establishment.value.verification_files.map((file) => ({
        id: file.id,
        status: file.edit_status,
        document_category: file.edit_category,
        rejection_reason: file.edit_status === 'rejected' ? file.edit_rejection_reason : null,
        points: file.edit_category === 'other' ? (Number(file.edit_manual_points) || 0) : null,
      })),
    };

    const res = await axiosInstance.post(
      `/adm/establishments/${establishment.value.id}/review-documents`,
      payload
    );

    establishment.value.trust_score = res.data.trust_score;
    establishment.value.trust_level = res.data.trust_level;

    // Actualizar estados originales para sincronizar con lo persistido
    establishment.value.verification_files.forEach((file) => {
      file.status = file.edit_status;
      file.document_category = file.edit_category;
      file.rejection_reason = file.edit_status === 'rejected' ? file.edit_rejection_reason : null;
      file.assigned_points = file.edit_category === 'other' ? (Number(file.edit_manual_points) || 0) : null;

      file.original_status = file.edit_status;
      file.original_category = file.edit_category;
      file.original_rejection_reason = file.rejection_reason || '';
      file.original_manual_points = file.edit_manual_points;
    });

    saveSuccessMessage.value = 'Revisión y categorías guardadas correctamente';
    setTimeout(() => {
      saveSuccessMessage.value = '';
    }, 4000);
  } catch (err) {
    console.error('Error al guardar revisión de documentos:', err);
    alert(err.response?.data?.message || 'Error al guardar los cambios en los documentos');
  } finally {
    savingReviews.value = false;
  }
};

// Validación de Ficha de Google Places
const toggleGooglePlacesVerification = async () => {
  googleVerifying.value = true;
  try {
    const newStatus = !isGooglePlaceVerified.value;
    const res = await axiosInstance.post(
      `/adm/establishments/${establishment.value.id}/verify-google-places`,
      { is_verified: newStatus, notes: 'Validación manual por el administrador' }
    );
    establishment.value.trust_score = res.data.trust_score;
    establishment.value.trust_level = res.data.trust_level;
    await fetchEstablishment(false);
  } catch (err) {
    alert(err.response?.data?.message || 'Error al validar ficha de Google Places');
  } finally {
    googleVerifying.value = false;
  }
};

// Registro de Llamada Telefónica
const openPhoneModal = () => {
  phoneNotes.value = '';
  showPhoneModal.value = true;
};

const closePhoneModal = () => {
  showPhoneModal.value = false;
  phoneNotes.value = '';
};

const confirmPhoneCall = async () => {
  phoneVerifying.value = true;
  try {
    const res = await axiosInstance.post(
      `/adm/establishments/${establishment.value.id}/verify-phone-call`,
      { call_successful: true, notes: phoneNotes.value.trim() || 'Llamada de confirmación exitosa' }
    );
    establishment.value.trust_score = res.data.trust_score;
    establishment.value.trust_level = res.data.trust_level;
    closePhoneModal();
    await fetchEstablishment(false);
  } catch (err) {
    alert(err.response?.data?.message || 'Error al registrar llamada telefónica');
  } finally {
    phoneVerifying.value = false;
  }
};

// Aprobación global del local
const verifyEstablishment = async () => {
  if (!establishment.value || verifying.value) return;
  if (!confirm('¿Seguro que deseas activar este establecimiento y habilitar al vendedor?')) return;

  try {
    verifying.value = true;
    await axiosInstance.patch(`/adm/establishments/${establishment.value.id}/verify`);
    alert('Establecimiento activado correctamente');
    router.push({ name: 'new-sellers' });
  } catch (err) {
    console.error('Error verifying establishment:', err);
    alert(err.response?.data?.message || 'Error al verificar el establecimiento');
  } finally {
    verifying.value = false;
  }
};

// Rechazo global del local
const openRejectModal = () => {
  showRejectModal.value = true;
  rejectReason.value = '';
};

const closeRejectModal = () => {
  showRejectModal.value = false;
  rejectReason.value = '';
};

const rejectEstablishment = async () => {
  if (!establishment.value || rejecting.value) return;
  if (!rejectReason.value || rejectReason.value.trim().length < 10) {
    alert('Por favor ingresa una razón de al menos 10 caracteres');
    return;
  }

  try {
    rejecting.value = true;
    await axiosInstance.patch(`/adm/establishments/${establishment.value.id}/reject`, {
      reason: rejectReason.value.trim()
    });
    alert('Establecimiento rechazado correctamente');
    closeRejectModal();
    router.push({ name: 'new-sellers' });
  } catch (err) {
    console.error('Error rejecting establishment:', err);
    alert(err.response?.data?.message || 'Error al rechazar el establecimiento');
  } finally {
    rejecting.value = false;
  }
};

const goBack = () => {
  router.push({ name: 'new-sellers' });
};

const openingFileId = ref(null);

const getFileUrl = (fileId) => {
  return `${BACKEND_URL}/api/admin/verification-files/${fileId}`;
};

const openFile = async (file) => {
  if (openingFileId.value === file.id) return;
  openingFileId.value = file.id;

  const newTab = window.open('about:blank', '_blank');

  try {
    const response = await axiosInstance.get(`/admin/verification-files/${file.id}`, {
      responseType: 'blob',
    });

    const contentType = response.headers['content-type'] ||
      (isPdfFile(file.file_type) ? 'application/pdf' : 'image/jpeg');

    const blob = new Blob([response.data], { type: contentType });
    const blobUrl = URL.createObjectURL(blob);

    if (newTab) {
      newTab.location.href = blobUrl;
    } else {
      window.open(blobUrl, '_blank');
    }
  } catch (err) {
    if (newTab) newTab.close();
    console.error('Error abriendo archivo de verificación:', err);
    alert('No se pudo abrir el documento. Verifica tu conexión o que tu sesión esté activa.');
  } finally {
    openingFileId.value = null;
  }
};

const handleImageError = (event) => {
  event.target.style.display = 'none';
  const parent = event.target.parentElement;
  if (parent && !parent.querySelector('.image-error-text')) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'image-error-text';
    errorDiv.textContent = 'Archivo no disponible';
    parent.appendChild(errorDiv);
  }
};

onMounted(fetchEstablishment);
</script>

<template>
  <div class="new-seller-page">
    <div class="new-seller-container">
      <!-- Barra de Navegación Superior -->
      <div class="top-bar">
        <button class="back-btn" @click="goBack">
          <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Volver a solicitudes</span>
        </button>

        <div v-if="establishment && hasUnsavedChanges" class="unsaved-top-pill">
          <span class="unsaved-pulse-dot"></span>
          <span>Tienes cambios en documentos sin guardar</span>
        </div>
      </div>

      <!-- Estado de Carga -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p class="text-sm font-medium opacity-80">Cargando expediente del comercio...</p>
      </div>

      <!-- Estado de Error -->
      <div v-else-if="error" class="error-state">
        <div class="error-icon-box">
          <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="font-semibold text-white mb-1">{{ error }}</p>
        <p class="text-xs opacity-60 mb-4">Comprueba tu conexión o intenta recargar la vista.</p>
        <button class="retry-btn" @click="fetchEstablishment">Reintentar carga</button>
      </div>

      <!-- Contenido Principal -->
      <div v-else-if="establishment" class="content">
        
        <!-- ============================================================ -->
        <!-- HERO CARD: ENCABEZADO + MEDIDOR DE CONFIANZA                 -->
        <!-- ============================================================ -->
        <div class="hero-card mb-6" :class="'hero-card--' + calculatedTrustLevel">
          <div class="hero-main">
            <div class="hero-info">
              <div class="flex items-center gap-2.5 flex-wrap mb-2">
                <span class="status-chip" :class="'status-chip--' + establishment.verification_status">
                  <span class="status-chip-dot"></span>
                  {{ establishment.verification_status === 'pending' ? 'En Revisión' : establishment.verification_status }}
                </span>
                <span v-if="establishment.establishment_type" class="rubro-chip">
                  {{ establishment.establishment_type.name }}
                </span>
                <span class="text-xs opacity-50 font-mono">ID #{{ establishment.id }}</span>
              </div>

              <h1 class="title">{{ establishment.name }}</h1>
              
              <p class="subtitle mt-1 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Solicitud ingresada el {{ new Date(establishment.created_at).toLocaleDateString('es-AR', { day: '2-digit', month: 'long', year: 'numeric' }) }}</span>
              </p>
            </div>

            <!-- Widget Resumen de Puntos -->
            <div class="hero-score-widget">
              <div class="score-widget-header">
                <span class="score-widget-label">Nivel de Confianza</span>
                <span class="trust-badge-label" :class="'badge-' + calculatedTrustLevel">
                  {{ calculatedTrustLevel === 'green' ? 'Alto' : calculatedTrustLevel === 'yellow' ? 'Medio' : 'Bajo' }}
                </span>
              </div>

              <div class="score-widget-numbers">
                <span class="score-big" :class="'total-pts--' + calculatedTrustLevel">{{ totalCalculatedPoints }}</span>
                <span class="score-max">/ {{ totalPossiblePoints }} pts</span>
              </div>

              <div class="score-progress-track">
                <div
                  class="score-progress-fill"
                  :class="'fill-' + calculatedTrustLevel"
                  :style="{ width: trustProgressPercentage + '%' }"
                ></div>
              </div>
              <p class="score-widget-caption">
                {{ verifiedFilesCount }} de {{ totalFilesCount }} docs verificados
              </p>
            </div>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- DATOS DEL TITULAR Y DEL COMERCIO                             -->
        <!-- ============================================================ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          <!-- Usuario / Titular -->
          <section class="section">
            <div class="section-header-inline">
              <div class="section-icon-box">
                <svg class="w-4 h-4 text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <h2 class="section-title mb-0">Datos del Titular</h2>
            </div>

            <div class="info-grid mt-4">
              <div class="info-card">
                <span class="label">Nombre completo</span>
                <span class="value">{{ establishment.user?.name }} {{ establishment.user?.last_name }}</span>
              </div>
              <div class="info-card">
                <span class="label">ID de Usuario</span>
                <span class="value font-mono">#{{ establishment.user?.id }}</span>
              </div>
              <div class="info-card span-full">
                <span class="label">Correo electrónico</span>
                <span class="value break-all">{{ establishment.user?.email }}</span>
              </div>
            </div>
          </section>

          <!-- Establecimiento -->
          <section class="section">
            <div class="section-header-inline">
              <div class="section-icon-box">
                <svg class="w-4 h-4 text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <h2 class="section-title mb-0">Datos del Comercio</h2>
            </div>

            <div class="info-grid mt-4">
              <div class="info-card">
                <span class="label">Nombre Comercial</span>
                <span class="value">{{ establishment.name }}</span>
              </div>
              <div class="info-card">
                <span class="label">Teléfono declarado</span>
                <span class="value">{{ establishment.phone || 'No registrado' }}</span>
              </div>
              <div class="info-card span-full">
                <span class="label">Dirección declarada</span>
                <span class="value">{{ establishment.address || 'No especificada' }}</span>
              </div>
            </div>
          </section>
        </div>

        <!-- ============================================================ -->
        <!-- DOCUMENTOS DE VERIFICACIÓN                                   -->
        <!-- ============================================================ -->
        <section class="section mb-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-4 border-b border-white/[0.06]">
            <div class="flex items-start gap-3">
              <div class="section-icon-box mt-0.5">
                <svg class="w-4 h-4 text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div>
                <h2 class="section-title mb-0">Documentos de Verificación</h2>
                <p class="text-xs text-[#A5A8C2]/70 mt-0.5">
                  Revisa cada archivo adjunto, reclasifica su categoría si fue subido por error y aprueba o rechaza su validez.
                </p>
              </div>
            </div>
            
            <div class="flex items-center gap-2 self-start sm:self-center">
              <span class="counter-badge">
                <span
                  class="w-2 h-2 rounded-full"
                  :class="verifiedFilesCount === totalFilesCount && totalFilesCount > 0 ? 'bg-[#10B981]' : verifiedFilesCount > 0 ? 'bg-[#F59E0B]' : 'bg-[#EF4444]'"
                ></span>
                <span>{{ verifiedFilesCount }} de {{ totalFilesCount }} aprobados</span>
              </span>
            </div>
          </div>

          <!-- Si no hay documentos subidos -->
          <div v-if="!establishment.verification_files || establishment.verification_files.length === 0" class="empty-docs-box">
            <div class="empty-docs-icon">
              <svg class="w-6 h-6 text-[#A5A8C2]/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <p class="text-sm text-white font-semibold">Sin documentación adjunta</p>
            <p class="text-xs text-[#A5A8C2]/60 mt-1">El comercio no subió archivos de respaldo en su solicitud.</p>
          </div>

          <!-- Lista de documentos -->
          <div v-else class="doc-list-stack">
            <div
              v-for="file in establishment.verification_files"
              :key="file.id"
              class="doc-list-item"
              :class="{
                'doc-list-item--accepted': file.edit_status === 'accepted',
                'doc-list-item--rejected': file.edit_status === 'rejected'
              }"
            >
              <div class="doc-list-main">
                <!-- Zona Izquierda: Icono + Selector + Estado + Puntos Manuales -->
                <div class="doc-list-left">
                  <span
                    class="doc-format-icon"
                    :class="isPdfFile(file.file_type) ? 'doc-format-icon--pdf' : 'doc-format-icon--img'"
                    :title="getFileTypeLabel(file.file_type)"
                  >
                    <svg v-if="isPdfFile(file.file_type)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </span>

                  <div class="doc-type-control">
                    <div class="select-wrapper">
                      <select
                        v-model="file.edit_category"
                        class="doc-category-inline-select"
                        title="Reclasificar tipo de documento"
                      >
                        <option v-for="opt in documentCategoryOptions" :key="opt.value" :value="opt.value">
                          {{ opt.label }}
                        </option>
                      </select>
                      <svg class="select-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </div>

                    <span class="doc-format-badge">
                      {{ getFileTypeLabel(file.file_type) }}
                    </span>
                  </div>

                  <!-- Pill de estado -->
                  <div
                    class="doc-status-pill"
                    :class="file.edit_status === 'accepted' ? 'doc-status-pill--accepted' : file.edit_status === 'rejected' ? 'doc-status-pill--rejected' : 'doc-status-pill--pending'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full current-bg"></span>
                    <span>
                      {{ file.edit_status === 'accepted' ? 'Verificado' : file.edit_status === 'rejected' ? 'Desaprobado' : 'Pendiente' }}
                    </span>
                  </div>

                  <!-- Stepper Horizontal de Puntos Manuales (para 'other' aceptado) -->
                  <div
                    v-if="file.edit_category === 'other' && file.edit_status === 'accepted'"
                    class="doc-manual-points-control"
                    title="Puntos de confianza asignados manualmente (0 a 50)"
                  >
                    <span class="doc-points-label">Puntaje:</span>
                    <div class="doc-points-horizontal-stepper">
                      <button
                        type="button"
                        class="stepper-btn"
                        @click="changeManualPoints(file, -5)"
                        :disabled="Number(file.edit_manual_points || 0) <= 0"
                        title="Restar 5 puntos"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4" />
                        </svg>
                      </button>

                      <input
                        type="number"
                        v-model.number="file.edit_manual_points"
                        min="0"
                        max="50"
                        step="5"
                        class="doc-points-input"
                        @blur="sanitizeManualPoints(file)"
                        @keydown.up.prevent="changeManualPoints(file, 5)"
                        @keydown.down.prevent="changeManualPoints(file, -5)"
                      />

                      <button
                        type="button"
                        class="stepper-btn"
                        @click="changeManualPoints(file, 5)"
                        :disabled="Number(file.edit_manual_points || 0) >= 50"
                        title="Sumar 5 puntos"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                      </button>
                    </div>
                    <span class="doc-points-unit">pts</span>
                  </div>
                </div>

                <!-- Zona Derecha: Ver archivo + Conmutador [ Aceptar | Rechazar ] -->
                <div class="doc-list-right">
                  <button
                    type="button"
                    @click="openFile(file)"
                    :disabled="openingFileId === file.id"
                    class="btn-open-doc"
                    title="Abrir documento en pestaña nueva"
                  >
                    <span v-if="openingFileId === file.id" class="btn-loader-small"></span>
                    <svg v-else class="w-3.5 h-3.5 text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>Ver archivo</span>
                  </button>

                  <div class="doc-action-segmented">
                    <button
                      type="button"
                      @click="toggleVerifyLocal(file)"
                      class="doc-seg-btn"
                      :class="file.edit_status === 'accepted' ? 'doc-seg-btn--accepted' : 'doc-seg-btn--idle'"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                      </svg>
                      <span>{{ file.edit_status === 'accepted' ? 'Verificado' : 'Aceptar' }}</span>
                    </button>

                    <button
                      type="button"
                      @click="file.edit_status === 'rejected' ? cancelRejectLocal(file) : openFileRejectModal(file)"
                      class="doc-seg-btn"
                      :class="file.edit_status === 'rejected' ? 'doc-seg-btn--rejected' : 'doc-seg-btn--idle'"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      <span>{{ file.edit_status === 'rejected' ? 'Rechazado' : 'Rechazar' }}</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Motivo de Desaprobación -->
              <div
                v-if="file.edit_status === 'rejected' && file.edit_rejection_reason"
                class="doc-rejection-badge mt-2.5"
              >
                <div class="flex items-start gap-2">
                  <svg class="w-3.5 h-3.5 text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                  </svg>
                  <span class="leading-relaxed"><strong class="text-red-300 font-semibold">Motivo de rechazo:</strong> {{ file.edit_rejection_reason }}</span>
                </div>
                <button type="button" class="edit-reason-link" @click="openFileRejectModal(file)">
                  Editar motivo
                </button>
              </div>
            </div>
          </div>

          <!-- Barra Inferior de Guardado de Documentos -->
          <div class="docs-footer-bar" :class="{ 'docs-footer-bar--unsaved': hasUnsavedChanges }">
            <div class="docs-footer-info">
              <div v-if="hasUnsavedChanges" class="flex items-center gap-2 text-[#FDE68A] text-xs font-semibold">
                <span class="unsaved-pulse-dot"></span>
                <span>Hay cambios locales pendientes de guardar en el servidor</span>
              </div>
              <div v-else class="text-xs text-[#A5A8C2]/70 flex items-center gap-2">
                <svg class="w-4 h-4 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Revisiones de documentos sincronizadas</span>
              </div>
            </div>

            <div class="flex items-center gap-2.5">
              <button
                v-if="hasUnsavedChanges"
                type="button"
                @click="revertLocalChanges"
                class="btn-revert-changes"
              >
                Descartar cambios
              </button>

              <button
                type="button"
                @click="saveDocumentReviews"
                :disabled="savingReviews || !hasUnsavedChanges"
                class="btn-save-minimal"
                :class="{ 'btn-save-minimal--active': hasUnsavedChanges }"
              >
                <span v-if="savingReviews" class="btn-loader-small"></span>
                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
                <span>{{ savingReviews ? 'Guardando...' : 'Guardar Cambios' }}</span>
              </button>
            </div>
          </div>

          <!-- Notificación de guardado exitoso -->
          <Transition name="fade">
            <div v-if="saveSuccessMessage" class="save-success-banner mt-3">
              <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
              </svg>
              <span>{{ saveSuccessMessage }}</span>
            </div>
          </Transition>
        </section>

        <!-- ============================================================ -->
        <!-- FICHA Y CONTACTO EN GOOGLE MAPS                              -->
        <!-- ============================================================ -->
        <section v-if="hasGooglePlace" class="section mb-6">
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 mb-4 border-b border-white/[0.06]">
            <div class="flex items-start gap-3">
              <div class="section-icon-box mt-0.5">
                <svg class="w-4 h-4 text-[#60A5FA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <h2 class="section-title mb-0">Ficha y Contacto en Google Maps</h2>
                  <span class="badge-google-place">Perfil Vinculado</span>
                </div>
                <p class="text-xs text-[var(--color-text)]/70 mt-0.5">
                  Verifica la existencia del comercio en Google Maps y realiza la llamada de validación al teléfono comercial.
                </p>
              </div>
            </div>

            <!-- Botones de Acción de Google y Teléfono -->
            <div class="flex items-center gap-2.5 flex-wrap">
              <a
                v-if="availablePhoneNumber"
                :href="'tel:' + availablePhoneNumber"
                class="btn-call-direct"
                title="Marcar número en tu dispositivo"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span>Llamar: {{ availablePhoneNumber }}</span>
              </a>

              <button
                type="button"
                @click="openPhoneModal"
                class="call-action-btn"
                :class="{ 'call-action-btn--verified': isPhoneCallVerified }"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ isPhoneCallVerified ? 'Llamada Confirmada (+30 pts)' : 'Registrar Llamada (+30 pts)' }}</span>
              </button>

              <button
                type="button"
                @click="toggleGooglePlacesVerification"
                :disabled="googleVerifying"
                class="btn-check-primary"
                :class="{ 'btn-check-primary--active': isGooglePlaceVerified }"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ isGooglePlaceVerified ? 'Ficha Verificada (+20 pts)' : 'Validar Ficha (+20 pts)' }}</span>
              </button>
            </div>
          </div>

          <div class="info-grid">
            <div class="info-card" v-if="establishment.google_place_data?.rating">
              <span class="label">Calificación en Google</span>
              <span class="value flex items-center gap-1.5">
                <svg class="w-4 h-4 text-amber-400 fill-current" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span>{{ establishment.google_place_data.rating }} / 5.0</span>
              </span>
            </div>
            <div class="info-card" v-if="establishment.google_place_data?.user_ratings_total">
              <span class="label">Volumen de reseñas</span>
              <span class="value">{{ establishment.google_place_data.user_ratings_total }} opiniones</span>
            </div>
            <div class="info-card" v-if="availablePhoneNumber">
              <span class="label">Teléfono comercial</span>
              <span class="value font-mono">{{ availablePhoneNumber }}</span>
            </div>
            <div class="info-card" v-if="establishment.google_place_data?.business_status">
              <span class="label">Estado en Google</span>
              <span class="value">{{ establishment.google_place_data.business_status }}</span>
            </div>
            <div class="info-card span-full" v-if="establishment.google_place_data?.formatted_address">
              <span class="label">Dirección en Google Maps</span>
              <span class="value">{{ establishment.google_place_data.formatted_address }}</span>
            </div>
          </div>
        </section>

        <!-- Mensaje si NO tiene local en Google Maps -->
        <section v-else class="section mb-6 opacity-80">
          <div class="flex items-center gap-3.5">
            <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.06] text-[var(--color-text)]/40 flex-shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-white">Sin ficha vinculada en Google Maps</h3>
              <p class="text-xs text-[var(--color-text)]/60 mt-0.5">
                El comercio opera sin perfil de Google Places vinculado. Los criterios de ficha y llamada telefónica no se contabilizan en el puntaje máximo.
              </p>
            </div>
          </div>
        </section>

        <!-- ============================================================ -->
        <!-- TABLA DE PUNTUACIÓN DE CONFIANZA                             -->
        <!-- ============================================================ -->
        <section class="section mb-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-4 border-b border-white/[0.06]">
            <div class="flex items-start gap-3">
              <div class="section-icon-box mt-0.5">
                <svg class="w-4 h-4 text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <div>
                <h2 class="section-title mb-0">Desglose de Puntuación de Confianza</h2>
                <p class="text-xs text-[var(--color-text)]/70 mt-0.5">
                  Simulación en tiempo real del puntaje según los documentos y verificaciones aprobadas.
                </p>
              </div>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-center">
              <span class="text-xs text-[var(--color-text)]/70 font-medium">Total acumulado:</span>
              <span class="points-total-chip" :class="'points-total-chip--' + calculatedTrustLevel">
                {{ totalCalculatedPoints }} / {{ totalPossiblePoints }} pts
              </span>
            </div>
          </div>

          <div class="overflow-x-auto rounded-xl border border-[var(--color-border)]">
            <table class="trust-points-table">
              <thead>
                <tr>
                  <th>Criterio / Documento</th>
                  <th>Categoría</th>
                  <th>Estado</th>
                  <th class="text-center">Puntaje Base</th>
                  <th class="text-right">Puntos Sumados</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in trustScoreBreakdown"
                  :key="item.id"
                  :class="{
                    'row-verified': item.isVerified,
                    'row-rejected': item.isRejected,
                    'row-na': item.notApplicable
                  }"
                >
                  <td class="font-medium text-white">
                    <div class="flex items-center gap-2.5">
                      <span
                        class="table-status-icon"
                        :class="item.isVerified ? 'icon-verified' : item.isRejected ? 'icon-rejected' : 'icon-pending'"
                      >
                        <svg v-if="item.isVerified" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg v-else-if="item.isRejected" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span v-else class="w-1.5 h-1.5 rounded-full bg-current opacity-50"></span>
                      </span>
                      <span>{{ item.name }}</span>
                    </div>
                  </td>

                  <td>
                    <span class="type-tag">{{ item.type }}</span>
                  </td>

                  <td>
                    <span v-if="item.isVerified" class="status-pill status-pill--verified">Verificado</span>
                    <span v-else-if="item.isRejected" class="status-pill status-pill--rejected">Desaprobado</span>
                    <span v-else-if="item.notApplicable" class="status-pill status-pill--na">No aplica</span>
                    <span v-else-if="item.isUploaded" class="status-pill status-pill--pending">Pendiente</span>
                    <span v-else class="status-pill status-pill--missing">No adjuntado</span>
                  </td>

                  <td class="text-center font-mono text-xs text-[var(--color-text)]/60">
                    +{{ item.maxPoints }} pts
                  </td>

                  <td class="text-right font-mono font-bold" :class="item.isVerified ? 'text-emerald-400' : 'text-[var(--color-text)]/35'">
                    {{ item.isVerified ? `+${item.earnedPoints} pts` : '0 pts' }}
                  </td>
                </tr>
              </tbody>

              <tfoot>
                <tr class="total-row">
                  <td colspan="3" class="font-bold text-white">
                    <div class="flex items-center gap-2 flex-wrap">
                      <span>Puntaje Total de Confianza</span>
                      <span v-if="hasUnsavedChanges" class="unsaved-table-tag">
                        Proyección con cambios sin guardar
                      </span>
                    </div>
                  </td>
                  <td class="text-center text-xs text-[var(--color-text)]/60 font-mono">
                    Máx: {{ totalPossiblePoints }} pts
                  </td>
                  <td class="text-right font-mono font-bold text-base" :class="'total-pts--' + calculatedTrustLevel">
                    {{ totalCalculatedPoints }} pts
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Leyenda de Niveles -->
          <div class="table-legend mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-[var(--color-text)]/70">
            <div class="flex items-center gap-4 flex-wrap">
              <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                <span><strong>Alto:</strong> 70 pts o más</span>
              </span>
              <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                <span><strong>Medio:</strong> 40 a 69 pts</span>
              </span>
              <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                <span><strong>Bajo:</strong> menos de 40 pts</span>
              </span>
            </div>
            <div>
              <span v-if="totalCalculatedPoints >= 70" class="text-emerald-400 font-medium">
                Respaldo óptimo para activar el establecimiento.
              </span>
              <span v-else-if="totalCalculatedPoints >= 40" class="text-amber-400 font-medium">
                Respaldo moderado. Se recomienda verificar contacto antes de aprobar.
              </span>
              <span v-else class="text-red-400 font-medium">
                Respaldo bajo. Requiere mayor validación documental.
              </span>
            </div>
          </div>
        </section>

        <!-- ============================================================ -->
        <!-- MAPA DE UBICACIÓN                                            -->
        <!-- ============================================================ -->
        <section v-if="establishment.latitude && establishment.longitude" class="section mb-6">
          <div class="flex items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
              <div class="section-icon-box">
                <svg class="w-4 h-4 text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
              </div>
              <h2 class="section-title mb-0">Geolocalización Registrada</h2>
            </div>

            <a
              :href="`https://www.google.com/maps?q=${establishment.latitude},${establishment.longitude}`"
              target="_blank"
              rel="noopener noreferrer"
              class="map-link"
            >
              <span>Abrir en Google Maps</span>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
              </svg>
            </a>
          </div>

          <div class="map-container">
            <iframe
              :src="`https://www.google.com/maps?q=${establishment.latitude},${establishment.longitude}&z=15&output=embed`"
              width="100%"
              height="280"
              style="border:0;"
              allowfullscreen=""
              loading="lazy"
            ></iframe>
          </div>
        </section>

        <!-- ============================================================ -->
        <!-- BARRA DE DECISIÓN FINAL                                      -->
        <!-- ============================================================ -->
        <section class="section actions-section" v-if="establishment.verification_status === 'pending'">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
              <h2 class="section-title mb-1">Resolución de la Solicitud</h2>
              <p class="text-xs text-[var(--color-text)]/70">
                Has verificado <strong class="text-white">{{ verifiedFilesCount }} de {{ totalFilesCount }}</strong> documentos adjuntos ({{ totalCalculatedPoints }} pts).
                <span v-if="hasUnsavedChanges" class="text-amber-400 font-semibold block sm:inline mt-1 sm:mt-0">
                  Recuerda guardar los cambios de documentos antes de activar.
                </span>
              </p>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
              <button class="action-btn reject-btn" @click="openRejectModal" :disabled="rejecting || verifying">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>Rechazar Solicitud</span>
              </button>

              <button class="action-btn approve-btn" @click="verifyEstablishment" :disabled="verifying || rejecting">
                <span v-if="verifying" class="btn-loader-small"></span>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span>Activar Establecimiento</span>
              </button>
            </div>
          </div>
        </section>

        <div v-else class="info-message">
          <svg class="w-5 h-5 text-[#A78BFA] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Esta solicitud ya fue procesada y se encuentra en estado: <strong class="text-white uppercase">{{ establishment.verification_status }}</strong>.</span>
        </div>
      </div>

      <!-- ============================================================ -->
      <!-- MODALES CON TRANSICIÓN                                       -->
      <!-- ============================================================ -->
      <Transition name="fade">
        <!-- Modal Rechazo Global de Establecimiento -->
        <div v-if="showRejectModal" class="modal-overlay" @click.self="closeRejectModal">
          <div class="modal-content">
            <div class="modal-header">
              <div class="flex items-center gap-2.5">
                <span class="modal-header-icon modal-header-icon--danger">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                </span>
                <h3 class="modal-title">Rechazar Establecimiento</h3>
              </div>
              <button class="modal-close" @click="closeRejectModal">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="modal-body">
              <p class="text-xs text-[var(--color-text)]/70 mb-3">
                Indica el motivo del rechazo. El titular recibirá esta observación para corregir su solicitud.
              </p>
              <label class="input-label">Razón del rechazo (mínimo 10 caracteres)</label>
              <textarea
                v-model="rejectReason"
                rows="4"
                class="reject-textarea"
                placeholder="Explica detalladamente por qué se rechaza la solicitud..."
              ></textarea>
            </div>
            <div class="modal-footer">
              <button class="modal-btn cancel-btn" @click="closeRejectModal">Cancelar</button>
              <button class="modal-btn confirm-btn" @click="rejectEstablishment" :disabled="rejecting || rejectReason.trim().length < 10">
                {{ rejecting ? 'Rechazando...' : 'Confirmar Rechazo' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <Transition name="fade">
        <!-- Modal Desaprobación de Documento Individual -->
        <div v-if="showFileRejectModal" class="modal-overlay" @click.self="closeFileRejectModal">
          <div class="modal-content">
            <div class="modal-header">
              <div class="flex items-center gap-2.5">
                <span class="modal-header-icon modal-header-icon--danger">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </span>
                <h3 class="modal-title">Desaprobar Documento</h3>
              </div>
              <button class="modal-close" @click="closeFileRejectModal">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="modal-body">
              <label class="input-label">Motivo de desaprobación de este archivo</label>
              <textarea
                v-model="fileRejectReason"
                rows="3"
                class="reject-textarea"
                placeholder="Ej. Imagen borrosa, documento vencido o el titular no coincide..."
              ></textarea>
            </div>
            <div class="modal-footer">
              <button class="modal-btn cancel-btn" @click="closeFileRejectModal">Cancelar</button>
              <button class="modal-btn confirm-btn" @click="confirmRejectFile" :disabled="!fileRejectReason.trim()">
                Marcar como Desaprobado
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <Transition name="fade">
        <!-- Modal Registro de Llamada -->
        <div v-if="showPhoneModal" class="modal-overlay" @click.self="closePhoneModal">
          <div class="modal-content">
            <div class="modal-header">
              <div class="flex items-center gap-2.5">
                <span class="modal-header-icon modal-header-icon--success">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                </span>
                <h3 class="modal-title">Registrar Llamada Telefónica</h3>
              </div>
              <button class="modal-close" @click="closePhoneModal">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="p-3 rounded-lg bg-white/[0.03] border border-white/[0.07] mb-3.5 flex items-center justify-between">
                <span class="text-xs text-[var(--color-text)]/70">Número discado:</span>
                <strong class="text-sm text-white font-mono">{{ establishment.google_place_data?.formatted_phone_number || establishment.phone || 'No registrado' }}</strong>
              </div>
              <label class="input-label">Notas u observaciones de la llamada</label>
              <textarea
                v-model="phoneNotes"
                rows="3"
                class="reject-textarea"
                placeholder="Ej. Atendió el encargado (Mario) y confirmó la solicitud de alta del comercio..."
              ></textarea>
            </div>
            <div class="modal-footer">
              <button class="modal-btn cancel-btn" @click="closePhoneModal">Cancelar</button>
              <button class="modal-btn approve-btn" @click="confirmPhoneCall" :disabled="phoneVerifying">
                {{ phoneVerifying ? 'Guardando...' : 'Confirmar Llamada (+30 pts)' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>

    </div>
  </div>
</template>

<style scoped>
.new-seller-page {
  min-height: 100vh;
  background: var(--color-bg);
  padding: 2rem 1.25rem 4rem;
}

.new-seller-container {
  max-width: 1080px;
  margin: 0 auto;
}

/* ============================================================ */
/* TOP BAR                                                      */
/* ============================================================ */
.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--color-text);
  background: var(--color-primary);
  border: 1px solid var(--color-border);
  padding: 0.5rem 1rem;
  border-radius: 0.625rem;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.back-btn:hover {
  border-color: var(--color-focus);
  color: #ffffff;
  transform: translateX(-2px);
}

.unsaved-top-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 0.85rem;
  border-radius: 9999px;
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.35);
  color: #fde68a;
  font-size: 0.75rem;
  font-weight: 600;
}

/* ============================================================ */
/* HERO CARD                                                    */
/* ============================================================ */
.hero-card {
  background: var(--color-primary);
  border: 1px solid var(--color-border);
  border-radius: 1.125rem;
  padding: 1.5rem;
  position: relative;
  overflow: hidden;
  transition: border-color 0.3s ease;
}

.hero-card--green { border-color: rgba(16, 185, 129, 0.4); }
.hero-card--yellow { border-color: rgba(245, 158, 11, 0.4); }
.hero-card--red { border-color: rgba(239, 68, 68, 0.35); }

.hero-main {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 1.5rem;
}

@media (min-width: 768px) {
  .hero-main {
    flex-direction: row;
    align-items: center;
  }
}

.title {
  font-size: 1.625rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.015em;
  line-height: 1.2;
}

.subtitle {
  font-size: 0.8125rem;
  color: var(--color-text);
  opacity: 0.75;
}

.status-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  background: color-mix(in srgb, var(--color-focus) 18%, transparent);
  color: #c4b5fd;
  border: 1px solid rgba(124, 58, 237, 0.45);
  text-transform: capitalize;
}

.status-chip-dot {
  width: 0.4rem;
  height: 0.4rem;
  border-radius: 50%;
  background: currentColor;
}

.rubro-chip {
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.05);
  color: var(--color-text);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Widget de puntaje en el Hero */
.hero-score-widget {
  background: rgba(0, 0, 0, 0.22);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 0.875rem;
  padding: 1rem 1.25rem;
  min-width: 240px;
  flex-shrink: 0;
}

.score-widget-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.35rem;
}

.score-widget-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text);
  opacity: 0.75;
}

.score-widget-numbers {
  display: flex;
  align-items: baseline;
  gap: 0.35rem;
  margin-bottom: 0.5rem;
}

.score-big {
  font-size: 1.625rem;
  font-weight: 800;
  font-family: monospace;
  line-height: 1;
}

.score-max {
  font-size: 0.8125rem;
  font-family: monospace;
  color: var(--color-text);
  opacity: 0.55;
}

.score-progress-track {
  width: 100%;
  height: 6px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 9999px;
  overflow: hidden;
  margin-bottom: 0.45rem;
}

.score-progress-fill {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.fill-green { background: #10b981; }
.fill-yellow { background: #f59e0b; }
.fill-red { background: #ef4444; }

.score-widget-caption {
  font-size: 0.6875rem;
  color: var(--color-text);
  opacity: 0.6;
}

.trust-badge-label {
  font-size: 0.6875rem;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: 0.375rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.badge-green { background: rgba(16, 185, 129, 0.18); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); }
.badge-yellow { background: rgba(245, 158, 11, 0.18); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); }
.badge-red { background: rgba(239, 68, 68, 0.18); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); }

/* ============================================================ */
/* SECCIONES Y TARJETAS DE DATOS                                */
/* ============================================================ */
.section {
  background: var(--color-primary);
  border: 1px solid var(--color-border);
  border-radius: 1rem;
  padding: 1.5rem;
}

.section-header-inline {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.section-icon-box {
  width: 2rem;
  height: 2rem;
  border-radius: 0.5rem;
  background: rgba(124, 58, 237, 0.12);
  border: 1px solid rgba(124, 58, 237, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.section-title {
  font-size: 1.0625rem;
  font-weight: 700;
  color: #ffffff;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  gap: 0.75rem;
}

.info-card {
  display: flex;
  flex-direction: column;
  padding: 0.75rem 0.9rem;
  border-radius: 0.625rem;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.info-card.span-full {
  grid-column: 1 / -1;
}

.label {
  font-size: 0.6875rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text);
  opacity: 0.55;
  margin-bottom: 0.25rem;
  font-weight: 600;
}

.value {
  font-size: 0.9375rem;
  color: #ffffff;
  font-weight: 600;
}

.counter-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.35rem 0.75rem;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  font-size: 0.75rem;
  font-weight: 600;
  color: #cbd5e1;
}

/* ============================================================ */
/* LISTA DE DOCUMENTOS                                          */
/* ============================================================ */
.empty-docs-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2.75rem 1rem;
  border: 1px dashed var(--color-border);
  border-radius: 0.875rem;
  text-align: center;
  background: rgba(255, 255, 255, 0.01);
}

.empty-docs-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.03);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.75rem;
}

.doc-list-stack {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.doc-list-item {
  background-color: #261E32;
  border: 1px solid #3D3450;
  border-radius: 0.75rem;
  padding: 0.85rem 1rem;
  transition: all 0.2s ease;
}

.doc-list-item:hover {
  border-color: rgba(124, 58, 237, 0.55);
}

.doc-list-item--accepted {
  border-color: rgba(16, 185, 129, 0.4);
  background: color-mix(in srgb, #10b981 4%, #261E32);
}

.doc-list-item--rejected {
  border-color: rgba(239, 68, 68, 0.4);
  background: color-mix(in srgb, #ef4444 4%, #261E32);
}

.doc-list-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.doc-list-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1 1 auto;
  min-width: 0;
  flex-wrap: wrap;
}

.doc-format-icon {
  width: 2.125rem;
  height: 2.125rem;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.doc-format-icon--pdf {
  background: rgba(239, 68, 68, 0.14);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #f87171;
}

.doc-format-icon--img {
  background: rgba(59, 130, 246, 0.14);
  border: 1px solid rgba(59, 130, 246, 0.3);
  color: #60a5fa;
}

.doc-type-control {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
}

.select-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.doc-category-inline-select {
  appearance: none;
  -webkit-appearance: none;
  background: #1A1424;
  border: 1px solid #3D3450;
  color: #ffffff;
  border-radius: 0.5rem;
  padding: 0.4rem 2rem 0.4rem 0.75rem;
  font-size: 0.8125rem;
  font-weight: 600;
  transition: border-color 0.15s ease;
  outline: none;
  cursor: pointer;
}

.doc-category-inline-select:hover,
.doc-category-inline-select:focus {
  border-color: #7c3aed;
}

.doc-category-inline-select option {
  background: #261E32;
  color: #ffffff;
}

.select-chevron {
  width: 0.875rem;
  height: 0.875rem;
  color: #a5a8c2;
  position: absolute;
  right: 0.65rem;
  pointer-events: none;
}

.doc-format-badge {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #cbd5e1;
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  border-radius: 0.375rem;
  white-space: nowrap;
}

.doc-status-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
}

.current-bg { background-color: currentColor; }

.doc-status-pill--accepted {
  background: rgba(16, 185, 129, 0.12);
  color: #34d399;
}

.doc-status-pill--rejected {
  background: rgba(239, 68, 68, 0.12);
  color: #f87171;
}

.doc-status-pill--pending {
  background: rgba(245, 158, 11, 0.12);
  color: #fbbf24;
}

/* Stepper Horizontal Mejorado para puntos manuales */
.doc-manual-points-control {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.2rem 0.55rem;
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: 0.5rem;
}

.doc-points-label {
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #34d399;
}

.doc-points-horizontal-stepper {
  display: inline-flex;
  align-items: center;
  background: #1A1424;
  border: 1px solid rgba(16, 185, 129, 0.4);
  border-radius: 0.375rem;
  overflow: hidden;
}

.stepper-btn {
  width: 1.5rem;
  height: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  color: #a5a8c2;
  cursor: pointer;
  transition: all 0.15s ease;
}

.stepper-btn:hover:not(:disabled) {
  background: rgba(16, 185, 129, 0.2);
  color: #ffffff;
}

.stepper-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.doc-points-input {
  width: 2rem;
  height: 1.5rem;
  background: transparent;
  border: none;
  border-left: 1px solid rgba(255, 255, 255, 0.08);
  border-right: 1px solid rgba(255, 255, 255, 0.08);
  color: #ffffff;
  font-weight: 700;
  font-size: 0.75rem;
  font-family: monospace;
  text-align: center;
  outline: none;
  -moz-appearance: textfield;
  appearance: textfield;
}

.doc-points-input::-webkit-outer-spin-button,
.doc-points-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.doc-points-unit {
  font-size: 0.6875rem;
  font-weight: 700;
  color: #34d399;
}

.doc-list-right {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-shrink: 0;
}

@media (max-width: 640px) {
  .doc-list-main {
    flex-direction: column;
    align-items: stretch;
  }
  .doc-list-right {
    justify-content: space-between;
    padding-top: 0.65rem;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
  }
}

.btn-open-doc {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem 0.65rem;
  border-radius: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #cbd5e1;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-open-doc:hover:not(:disabled) {
  color: #ffffff;
  background: rgba(124, 58, 237, 0.15);
  border-color: rgba(124, 58, 237, 0.4);
}

.doc-action-segmented {
  display: inline-flex;
  align-items: center;
  background: #181324;
  border: 1px solid #3D3450;
  padding: 2px;
  border-radius: 0.5rem;
}

.doc-seg-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.3rem 0.65rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.15s ease;
}

.doc-seg-btn--idle {
  background: transparent;
  color: #8e8ba7;
}

.doc-seg-btn--idle:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.05);
}

.doc-seg-btn--accepted {
  background: #10b981;
  color: #ffffff;
}

.doc-seg-btn--rejected {
  background: #ef4444;
  color: #ffffff;
}

.doc-rejection-badge {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.25);
  color: #fca5a5;
  font-size: 0.75rem;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.edit-reason-link {
  background: transparent;
  border: none;
  color: #f87171;
  font-size: 0.75rem;
  font-weight: 600;
  text-decoration: underline;
  cursor: pointer;
}

/* Barra inferior de guardado de documentos */
.docs-footer-bar {
  margin-top: 1.25rem;
  padding: 0.9rem 1.15rem;
  background: var(--color-secondary);
  border: 1px solid var(--color-border);
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  transition: all 0.2s ease;
}

.docs-footer-bar--unsaved {
  border-color: rgba(245, 158, 11, 0.45);
  background: color-mix(in srgb, #f59e0b 6%, var(--color-secondary));
}

.btn-save-minimal {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.55rem 1.1rem;
  border-radius: 0.625rem;
  background: rgba(255, 255, 255, 0.05);
  color: var(--color-text);
  border: 1px solid #3d3450;
  cursor: not-allowed;
  opacity: 0.5;
  transition: all 0.2s ease;
}

.btn-save-minimal--active {
  background: #7c3aed;
  color: #ffffff;
  border-color: #7c3aed;
  opacity: 1;
  cursor: pointer;
  box-shadow: 0 2px 10px rgba(124, 58, 237, 0.35);
}

.btn-save-minimal--active:hover:not(:disabled) {
  background: #6d28d9;
}

.btn-revert-changes {
  padding: 0.5rem 0.9rem;
  border-radius: 0.625rem;
  font-size: 0.75rem;
  font-weight: 600;
  background: transparent;
  color: var(--color-text);
  border: 1px solid var(--color-border);
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-revert-changes:hover {
  border-color: #ef4444;
  color: #f87171;
}

.save-success-banner {
  padding: 0.75rem 1rem;
  border-radius: 0.625rem;
  background: rgba(16, 185, 129, 0.14);
  border: 1px solid rgba(16, 185, 129, 0.35);
  color: #34d399;
  font-size: 0.8125rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* ============================================================ */
/* GOOGLE MAPS Y LLAMADA                                        */
/* ============================================================ */
.badge-google-place {
  padding: 0.15rem 0.55rem;
  border-radius: 0.375rem;
  font-size: 0.6875rem;
  font-weight: 700;
  background: rgba(59, 130, 246, 0.15);
  color: #60a5fa;
  border: 1px solid rgba(59, 130, 246, 0.3);
  text-transform: uppercase;
}

.btn-call-direct {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.85rem;
  border-radius: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  background: rgba(16, 185, 129, 0.12);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.3);
  text-decoration: none;
  transition: all 0.15s ease;
}

.btn-call-direct:hover {
  background: #10b981;
  color: #ffffff;
}

.call-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.9rem;
  border-radius: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  background: var(--color-focus);
  color: #ffffff;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.call-action-btn--verified {
  background: rgba(16, 185, 129, 0.18);
  color: #34d399;
  border: 1px solid #10b981;
}

.btn-check-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.9rem;
  border-radius: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  background: var(--color-secondary);
  color: var(--color-text);
  border: 1px solid var(--color-border);
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-check-primary:hover:not(:disabled) {
  border-color: #10b981;
  color: #34d399;
}

.btn-check-primary--active {
  background: rgba(16, 185, 129, 0.18);
  border-color: #10b981;
  color: #34d399;
}

/* ============================================================ */
/* TABLA DE PUNTOS                                              */
/* ============================================================ */
.trust-points-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}

.trust-points-table th {
  padding: 0.75rem 1rem;
  background: var(--color-secondary);
  color: var(--color-text);
  opacity: 0.75;
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid var(--color-border);
  text-align: left;
}

.trust-points-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--color-border);
  vertical-align: middle;
}

.trust-points-table tbody tr:hover {
  background: rgba(255, 255, 255, 0.02);
}

.row-verified { background: rgba(16, 185, 129, 0.04); }
.row-rejected { background: rgba(239, 68, 68, 0.03); }
.row-na { opacity: 0.5; }

.table-status-icon {
  width: 1.35rem;
  height: 1.35rem;
  border-radius: 9999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-verified { background: rgba(16, 185, 129, 0.18); color: #34d399; }
.icon-rejected { background: rgba(239, 68, 68, 0.18); color: #f87171; }
.icon-pending { background: rgba(255, 255, 255, 0.06); color: var(--color-text); }

.type-tag {
  font-size: 0.6875rem;
  padding: 0.15rem 0.5rem;
  border-radius: 0.375rem;
  background: var(--color-secondary);
  border: 1px solid var(--color-border);
  color: var(--color-text);
  opacity: 0.85;
}

.total-row td {
  padding: 1rem;
  background: color-mix(in srgb, var(--color-primary) 85%, black);
  border-top: 2px solid var(--color-border);
  border-bottom: none;
}

.unsaved-table-tag {
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 0.375rem;
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.18rem 0.55rem;
  border-radius: 0.375rem;
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
}

.status-pill--verified { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
.status-pill--rejected { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
.status-pill--pending { background: rgba(245, 158, 11, 0.12); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.25); }
.status-pill--missing { background: rgba(255, 255, 255, 0.03); color: var(--color-text); opacity: 0.55; border: 1px dashed var(--color-border); }
.status-pill--na { background: transparent; color: var(--color-text); opacity: 0.4; border: 1px solid var(--color-border); }

.points-total-chip {
  padding: 0.3rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.8125rem;
  font-weight: 700;
  font-family: monospace;
}

.points-total-chip--green { background: rgba(16, 185, 129, 0.18); color: #34d399; border: 1px solid #10b981; }
.points-total-chip--yellow { background: rgba(245, 158, 11, 0.18); color: #fbbf24; border: 1px solid #f59e0b; }
.points-total-chip--red { background: rgba(239, 68, 68, 0.18); color: #f87171; border: 1px solid #ef4444; }

.total-pts--green { color: #34d399; }
.total-pts--yellow { color: #fbbf24; }
.total-pts--red { color: #f87171; }

.table-legend {
  padding: 0.75rem 1rem;
  border-radius: 0.625rem;
  background: var(--color-secondary);
  border: 1px solid var(--color-border);
}

/* ============================================================ */
/* MAPA Y ACCIONES FINALES                                      */
/* ============================================================ */
.map-container {
  border-radius: 0.75rem;
  overflow: hidden;
  border: 1px solid var(--color-border);
}

.map-link {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: #a78bfa;
  font-size: 0.8125rem;
  font-weight: 600;
  text-decoration: none;
}

.map-link:hover {
  text-decoration: underline;
}

.actions-section {
  border: 1px solid rgba(124, 58, 237, 0.45);
  background: linear-gradient(180deg, var(--color-primary) 0%, color-mix(in srgb, var(--color-focus) 8%, var(--color-primary)) 100%);
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.7rem 1.25rem;
  border-radius: 0.625rem;
  font-size: 0.8125rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.reject-btn {
  background: rgba(239, 68, 68, 0.1);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.35);
}

.reject-btn:hover:not(:disabled) {
  background: #ef4444;
  color: #ffffff;
}

.approve-btn {
  background: #10b981;
  color: #ffffff;
  box-shadow: 0 2px 10px rgba(16, 185, 129, 0.3);
}

.approve-btn:hover:not(:disabled) {
  background: #059669;
  transform: translateY(-1px);
}

.info-message {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  border-radius: 0.75rem;
  background: var(--color-primary);
  border: 1px solid var(--color-border);
  color: var(--color-text);
  font-size: 0.875rem;
}

/* ============================================================ */
/* MODALES Y ESTADOS DE CARGA                                   */
/* ============================================================ */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: var(--color-primary);
  border: 1px solid var(--color-border);
  border-radius: 1rem;
  width: 100%;
  max-width: 480px;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.15rem 1.5rem;
  border-bottom: 1px solid var(--color-border);
}

.modal-header-icon {
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-header-icon--danger { background: rgba(239, 68, 68, 0.15); color: #f87171; }
.modal-header-icon--success { background: rgba(16, 185, 129, 0.15); color: #34d399; }

.modal-title {
  font-size: 1rem;
  font-weight: 700;
  color: #ffffff;
}

.modal-close {
  background: transparent;
  border: none;
  color: var(--color-text);
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 0.375rem;
}

.modal-body { padding: 1.25rem 1.5rem; }

.input-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: 0.5rem;
}

.reject-textarea {
  width: 100%;
  padding: 0.75rem;
  border-radius: 0.625rem;
  background: var(--color-secondary);
  border: 1px solid var(--color-border);
  color: #ffffff;
  font-size: 0.875rem;
  resize: vertical;
}

.reject-textarea:focus {
  outline: none;
  border-color: var(--color-focus);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.65rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border);
  background: color-mix(in srgb, var(--color-primary) 90%, black);
}

.modal-btn {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.cancel-btn {
  background: transparent;
  color: var(--color-text);
  border: 1px solid var(--color-border);
}

.confirm-btn {
  background: #ef4444;
  color: #ffffff;
  border: none;
}

.confirm-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

/* Loaders y Animaciones */
.unsaved-pulse-dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  background: #f59e0b;
  box-shadow: 0 0 6px #f59e0b;
  animation: pulse-dot 1.5s infinite;
  display: inline-block;
}

@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.4; transform: scale(1.2); }
}

.btn-loader-small {
  width: 0.875rem;
  height: 0.875rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  display: inline-block;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-state,
.error-state {
  text-align: center;
  padding: 5rem 1rem;
  color: var(--color-text);
  background: var(--color-primary);
  border: 1px solid var(--color-border);
  border-radius: 1rem;
}

.error-icon-box {
  width: 3rem;
  height: 3rem;
  border-radius: 9999px;
  background: rgba(239, 68, 68, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 0.75rem;
}

.spinner {
  width: 2.25rem;
  height: 2.25rem;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-focus);
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
  margin: 0 auto 1rem;
}

.retry-btn {
  padding: 0.5rem 1.15rem;
  border-radius: 0.5rem;
  background: var(--color-focus);
  color: #ffffff;
  font-size: 0.8125rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>